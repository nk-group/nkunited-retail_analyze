<?php
namespace App\Commands;

use App\Libraries\FileArchiveManager;

use App\Libraries\ProductImportService; 
use App\Libraries\ManufacturerImportService;

use App\Libraries\PurchaseSlipImportService;
use App\Libraries\SalesSlipImportService;
use App\Libraries\TransferSlipImportService;
use App\Libraries\AdjustmentSlipImportService;
use App\Libraries\OrderSlipImportService;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;


/**
 * 保留中のデータ取り込みタスク（マスタや伝票など）を処理するCLIコマンド。
 * このコマンドは、サーバーのスケジューラ（cronなど）によって定期的に実行されることを想定しています。
 */
class ProcessImportTasks extends BaseCommand
{
    /**
     * コマンドグループ名。
     * `php spark` で表示される際のグループ化に使用されます。
     * @var string
     */
    protected $group = 'Tasks';

    /**
     * コマンド名。
     * `php spark tasks:process_imports` のように呼び出します。
     * @var string
     */
    protected $name = 'tasks:process_imports';

    /**
     * コマンドの説明。
     * `php spark list` で表示されます。
     * @var string
     */
    protected $description = 'キューイングされたデータ取り込みタスク（商品マスタなど）を処理します。';

    /**
     * コマンドの使用方法の説明。
     * `php spark help tasks:process_imports` で表示されます。
     * @var string
     */
    protected $usage = 'tasks:process_imports';

    /**
     * コマンドが受け付けるオプション。
     * @var array
     */
    protected $options = [];

    /**
     * ファイルロックに使用するファイルのパス。
     * @var string
     */
    private $lockFile;

    /**
     * ファイルロックを取得した際のファイルハンドル。
     * @var resource|null
     */
    private $lockHandle = null;

    /**
     * 現在処理中のタスクID。
     * シャットダウンハンドラや例外ハンドラで、どのタスクが処理中だったかを特定するために使用します。
     * @var int|null
     */
    private $currentProcessingTaskId = null;

    /**
     * ロガーインスタンス。
     */
    // protected $logger; // BaseCommandに既に定義されているため、再宣言は不要


    /**
     * コマンド実行前の初期化処理。
     */
    protected function initialize(): void
    {
        if (!isset($this->logger) || $this->logger === null) {
            $this->logger = Services::logger();
        }

        $this->logger->info('ProcessImportTasks: コマンドを初期化中...');
        CLI::write('ProcessImportTasks: コマンドを初期化中...', 'light_gray');

        $this->lockFile = WRITEPATH . 'tasks/importer.lock';
        if (!is_dir(WRITEPATH . 'tasks')) {
            if (!@mkdir(WRITEPATH . 'tasks', 0777, true) && !is_dir(WRITEPATH . 'tasks')) {
                $errorMessage = 'タスクディレクトリの作成に失敗しました: ' . WRITEPATH . 'tasks';
                CLI::error($errorMessage);
                $this->logger->error($errorMessage);
                throw new \RuntimeException($errorMessage);
            }
        }
        
        register_shutdown_function([$this, 'handleShutdown']);
        set_exception_handler([$this, 'handleException']);
        $this->logger->info('ProcessImportTasks: シャットダウンおよび例外ハンドラを登録しました。');
    }

    /**
     * コマンドのメイン実行ロジック。
     */
    public function run(array $params): int
    {
        try {
            $this->initialize();
        } catch (\Throwable $e) {
            error_log("ProcessImportTasks: Initialization failed: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            CLI::error("コマンド初期化中に致命的エラー: " . $e->getMessage());
            $logger = $this->logger ?? Services::logger();
            $logger->critical("コマンド初期化中に致命的エラー: " . $e->getMessage(), ['exception' => $e]);
            return EXIT_ERROR;
        }

        $this->logger->info('ProcessImportTasks: runメソッドが開始されました。');
        CLI::write('--- ProcessImportTasks: 実行開始 ---', 'green');
        
        $this->lockHandle = fopen($this->lockFile, 'w');
        if ($this->lockHandle === false || !flock($this->lockHandle, LOCK_EX | LOCK_NB)) {
            $message = '[' . date('Y-m-d H:i:s') . '] 他の取込処理が実行中か、ロックファイルを取得できませんでした。今回の処理はスキップします。';
            CLI::write($message, 'yellow');
            $this->logger->info($message);
            if ($this->lockHandle && is_resource($this->lockHandle)) {
                fclose($this->lockHandle);
            }
            $this->lockHandle = null;
            return EXIT_SUCCESS;
        }
        CLI::write('ファイルロックを取得しました。 (' . $this->lockFile . ')', 'light_gray');
        $this->logger->info('ファイルロック取得完了。');

        $db = \Config\Database::connect();
        $task = null; 

        try { 
            
            $processingTask = $db->table('import_tasks')
                                ->where('status', 'processing')
                                ->limit(1)
                                ->get()
                                ->getRow();

            if ($processingTask) {
                $message = '[' . date('Y-m-d H:i:s') . "] 現在処理中 (status='processing') のタスク (ID: {$processingTask->id}) が存在するため、今回の新規タスク処理はスキップします。";
                CLI::write($message, 'yellow');
                $this->logger->info($message);
                // この場合もファイルロックは解放して正常終了とする
                if ($this->lockHandle && is_resource($this->lockHandle)) {
                    flock($this->lockHandle, LOCK_UN);
                    fclose($this->lockHandle);
                    $this->lockHandle = null;
                    $this->logger->info('ファイルロックを解放しました（処理中タスクありのためスキップ）。');
                }
                return EXIT_SUCCESS; 
            }
            

            $task = $db->table('import_tasks')
                        ->where('status', 'pending')
                        ->orderBy('uploaded_at', 'ASC')
                        ->limit(1)
                        ->get()
                        ->getRow();

            if ($task) {
                $this->currentProcessingTaskId = (int)$task->id; 
                $this->logger->info("処理開始準備 - タスクID: {$this->currentProcessingTaskId} (ファイル: {$task->original_file_name}, 対象: {$task->target_data_name})");
                CLI::write("処理開始 - タスクID: {$this->currentProcessingTaskId} (ファイル: {$task->original_file_name}, 対象: {$task->target_data_name})", 'cyan');

                $db->table('import_tasks')->where('id', $this->currentProcessingTaskId)->update([
                    'status' => 'processing',
                    'processing_started_at' => date('Y-m-d H:i:s')
                ]);

                $determinedArchiveStatus = 'unknown_processing_error'; 
                $resultFromService = null;                            
                $fullResultMessageForDB = "タスク処理中に予期せぬ状態が発生しました。詳細はログを確認してください。"; 

                try {
                    if ($task->target_data_name === 'product_master') {
                        $importer = new ProductImportService();
                        CLI::write("ProductImportServiceを呼び出します (タスクID: {$this->currentProcessingTaskId})", 'blue');
                        $this->logger->info("ProductImportService呼び出し - タスクID: {$this->currentProcessingTaskId}, ファイルパス: {$task->stored_file_path}");
                        $resultFromService = $importer->processFile($task->stored_file_path);
                        unset($importer);
                    } 

                    elseif ($task->target_data_name === 'manufacturer_master') {
                        $importer = new ManufacturerImportService();
                        CLI::write("ManufacturerImportServiceを呼び出します (タスクID: {$this->currentProcessingTaskId})", 'blue');
                        $this->logger->info("ManufacturerImportService呼び出し - タスクID: {$this->currentProcessingTaskId}, ファイルパス: {$task->stored_file_path}");
                        $resultFromService = $importer->processFile($task->stored_file_path);
                        unset($importer);
                    }


                    elseif ($task->target_data_name === 'purchase_slip') { 
                        $importer = new PurchaseSlipImportService();
                        CLI::write("PurchaseSlipImportServiceを呼び出します (タスクID: {$this->currentProcessingTaskId})", 'blue');
                        $this->logger->info("PurchaseSlipImportService呼び出し - タスクID: {$this->currentProcessingTaskId}, ファイルパス: {$task->stored_file_path}");
                        $resultFromService = $importer->processFile($task->stored_file_path);
                        unset($importer);
                    }

                    elseif ($task->target_data_name === 'sales_slip') { 
                        $importer = new SalesSlipImportService();
                        CLI::write("SalesSlipImportServiceを呼び出します (タスクID: {$this->currentProcessingTaskId})", 'blue');
                        $this->logger->info("SalesSlipImportService呼び出し - タスクID: {$this->currentProcessingTaskId}, ファイルパス: {$task->stored_file_path}");
                        $resultFromService = $importer->processFile($task->stored_file_path);
                        unset($importer);
                    }

                    elseif ($task->target_data_name === 'transfer_slip') {
                        $importer = new TransferSlipImportService();
                        CLI::write("TransferSlipImportServiceを呼び出します (タスクID: {$this->currentProcessingTaskId})", 'blue');
                        $this->logger->info("TransferSlipImportService呼び出し - タスクID: {$this->currentProcessingTaskId}, ファイルパス: {$task->stored_file_path}");
                        $resultFromService = $importer->processFile($task->stored_file_path);
                        unset($importer);
                    }

                    elseif ($task->target_data_name === 'adjustment_slip') { 
                        $importer = new AdjustmentSlipImportService();
                        CLI::write("AdjustmentSlipImportServiceを呼び出します (タスクID: {$this->currentProcessingTaskId})", 'blue');
                        $this->logger->info("AdjustmentSlipImportService呼び出し - タスクID: {$this->currentProcessingTaskId}, ファイルパス: {$task->stored_file_path}");
                        $resultFromService = $importer->processFile($task->stored_file_path);
                        unset($importer);
                    }

                    elseif ($task->target_data_name === 'order_slip') { 
                        $importer = new OrderSlipImportService();
                        CLI::write("OrderSlipImportServiceを呼び出します (タスクID: {$this->currentProcessingTaskId})", 'blue');
                        $this->logger->info("OrderSlipImportService呼び出し - タスクID: {$this->currentProcessingTaskId}, ファイルパス: {$task->stored_file_path}");
                        $resultFromService = $importer->processFile($task->stored_file_path);
                        unset($importer);
                    }                    

                    else { 
                        $this->logger->error("Task ID {$this->currentProcessingTaskId}: Unknown target_data_name '{$task->target_data_name}'. No service available.");
                        $resultFromService = [
                            'success' => false,
                            'message' => "未対応の取り込み対象データです: " . esc($task->target_data_name ?? 'N/A'),
                            'imported_count' => 0,
                            'updated_count' => 0,
                            'skipped_count' => 0,
                            'processed_rows' => 0,
                            'error_messages' => ["指定された取り込み対象 ('" . esc($task->target_data_name ?? 'N/A') . "') の処理ロジックがありません。"]
                        ];
                    }

                    if (isset($resultFromService) && is_array($resultFromService)) {
                        $fullResultMessageForDB = $resultFromService['message'] ?? '処理結果メッセージなし。';
                        if (isset($resultFromService['error_messages']) && !empty($resultFromService['error_messages'])) {
                            $details = implode("\n  - ", array_slice($resultFromService['error_messages'], 0, 15));
                            if (count($resultFromService['error_messages']) > 15) {
                                $details .= "\n  ...他 " . (count($resultFromService['error_messages']) - 15) . "件のエラーがあります。(詳細はログファイルを確認してください)";
                            }
                            $fullResultMessageForDB .= "\n--- 詳細エラー ---\n  - " . $details;
                        }

                        if (isset($resultFromService['success']) && $resultFromService['success']) {
                            $skippedCountFromService = $resultFromService['skipped_count'] ?? 0;
                            $dataErrorMessagesCount = (isset($resultFromService['error_messages']) && is_array($resultFromService['error_messages'])) ? count(array_filter($resultFromService['error_messages'])) : 0;

                            if ($skippedCountFromService > 0 || $dataErrorMessagesCount > 0) {
                                $determinedArchiveStatus = 'completed_with_issues';
                            } else {
                                $determinedArchiveStatus = 'perfect_success';
                            }
                            $this->succeedTask($this->currentProcessingTaskId, $fullResultMessageForDB, $db);
                        } else { 
                            $determinedArchiveStatus = 'import_failed'; 
                            $this->failTask($this->currentProcessingTaskId, $fullResultMessageForDB, $db);
                        }
                    } else { 
                        $determinedArchiveStatus = 'service_return_error';
                        $fullResultMessageForDB = "Import service did not return a valid result array. Result was: " . print_r($resultFromService, true);
                        $this->logger->error("Task ID {$this->currentProcessingTaskId}: " . $fullResultMessageForDB);
                        $this->failTask($this->currentProcessingTaskId, $fullResultMessageForDB, $db);
                    }
                } catch (\Throwable $e) { 
                    $this->logger->critical("[ProcessImportTasks:run:task_try] タスク処理中に例外発生 - タスクID {$this->currentProcessingTaskId}: " . $e->getMessage(), ['exception' => $e]);
                    $determinedArchiveStatus = 'task_processing_exception'; 
                    $fullResultMessageForDB = "タスク処理中の例外: " . $e->getMessage();
                    $this->failTask($this->currentProcessingTaskId, $fullResultMessageForDB, $db); 
                } finally {
                    if ($task) { 
                        $this->logger->info("Task ID {$task->id}: Entering finally block for file archiving. Determined archive status: '{$determinedArchiveStatus}'.");
                        
                        if (empty($task->stored_file_path)) {
                            $this->logger->warning("Task ID {$task->id}: 'stored_file_path' is empty. Cannot archive file.");
                        } elseif (empty($task->target_data_name)) {
                            $this->logger->warning("Task ID {$task->id}: 'target_data_name' is empty. Cannot determine archive target identifier.");
                        } elseif (empty($task->original_file_name)) {
                            $this->logger->warning("Task ID {$task->id}: 'original_file_name' is empty. Cannot determine original filename for archiving.");
                        } elseif (!file_exists($task->stored_file_path)) {
                            $this->logger->warning("Task ID {$task->id}: Source file '{$task->stored_file_path}' not found for archiving. Archive status was '{$determinedArchiveStatus}'.");
                        } else {
                            $archiveManager = new FileArchiveManager(); 
                            if ($archiveManager->archive($task->stored_file_path, $task->target_data_name, $task->original_file_name, $determinedArchiveStatus)) {
                                $this->logger->info("Task ID {$task->id}: Successfully archived file '{$task->original_file_name}' for target '{$task->target_data_name}' with status '{$determinedArchiveStatus}'.");
                            } else {
                                $this->logger->error("Task ID {$task->id}: Failed to archive file '{$task->original_file_name}' for target '{$task->target_data_name}'. It remains at '{$task->stored_file_path}'. Archive status was '{$determinedArchiveStatus}'.");
                            }
                        }
                    } else {
                        $this->logger->warning("ProcessImportTasks: Task object was not available in finally block for archiving.");
                    }
                }

            } else { 
                CLI::write('処理対象のペンディング状態のタスクは見つかりませんでした。', 'blue');
                $this->logger->info('処理対象のペンディング状態のタスクは見つかりませんでした。');
            }
        } catch (\Throwable $e) { 
            $this->logger->critical("[ProcessImportTasks:run:main_try] 予期せぬエラー発生 - タスクID特定前かループ処理中: " . ($this->currentProcessingTaskId ?? ($task->id ?? 'N/A')) . ": " . $e->getMessage(), ['exception' => $e]);
            $dbToUseForFail = (isset($db) && $db->connID) ? $db : \Config\Database::connect(null, false);
            $taskIdForCatch = $this->currentProcessingTaskId ?? ($task->id ?? null);
            if ($taskIdForCatch) { 
                 $this->failTask($taskIdForCatch, "コマンド実行中の予期せぬ上位エラー: " . $e->getMessage(), $dbToUseForFail);
            }
            CLI::error("致命的なエラーが発生しました: " . $e->getMessage());
        } finally { 
            if ($this->lockHandle && is_resource($this->lockHandle)) {
                flock($this->lockHandle, LOCK_UN);
                fclose($this->lockHandle);
                $this->lockHandle = null;
                CLI::write('ファイルロックを解放しました。', 'light_gray');
                $this->logger->info('ファイルロックを解放しました。');
            }
        }
        
        CLI::write('[' . date('Y-m-d H:i:s') . '] Import task processor finished.', 'green');
        $this->logger->info('Import task processor finished.');
        return EXIT_SUCCESS;
    }

    /**
     * タスク成功時のデータベース更新処理
     * @param int    $taskId 処理したタスクのID
     * @param string $message タスクの結果メッセージ
     * @param \CodeIgniter\Database\BaseConnection $db データベース接続インスタンス
     */
    private function succeedTask(int $taskId, string $message, \CodeIgniter\Database\BaseConnection $db): void
    {
        try {
            $updateData = [
                'status' => 'success',
                'processing_finished_at' => date('Y-m-d H:i:s'),
                'result_message' => $message
            ];
            $db->table('import_tasks')->where('id', $taskId)->update($updateData);
            $affectedRows = $db->affectedRows();

            $this->logger->info("[ProcessImportTasks] succeedTask: DB update for TaskID {$taskId}. Affected rows: {$affectedRows}. Target status: success. Message: {$message}");
            if ($affectedRows <= 0 && $message !== 'ファイルに処理対象データがありませんでした。' && $message !== '処理対象の有効なデータ行が見つかりませんでした。') { 
                $this->logger->error("[ProcessImportTasks] succeedTask: DB update for TaskID {$taskId} reported 0 or error in affected rows, but no exception.");
            }

            CLI::write("タスクID {$taskId} は正常に処理されました。", 'green');
        } catch (\Throwable $e) {
            CLI::error("タスクID {$taskId} は正常に処理されましたが、ステータス更新に失敗: " . $e->getMessage());
            $this->logger->critical("タスクID {$taskId} 正常終了、しかしステータス更新失敗: " . $e->getMessage(), ['original_message' => $message, 'exception' => $e]);
        }
        $this->currentProcessingTaskId = null;
    }

    /**
     * タスク失敗時のデータベース更新処理
     * @param int    $taskId 処理したタスクのID
     * @param string $errorMessage タスクのエラーメッセージ
     * @param \CodeIgniter\Database\BaseConnection $db データベース接続インスタンス
     */
    private function failTask(int $taskId, string $errorMessage, \CodeIgniter\Database\BaseConnection $db): void
    {
        try {
            $canPing = method_exists($db, 'ping') ? $db->ping() : true; 
            if (!$db->connID || !$canPing) { 
                CLI::write("DB接続が失われたためタスクID {$taskId} の失敗ステータス更新を再試行...", "red");
                $this->logger->warning("DB接続が失われたためタスクID {$taskId} の失敗ステータス更新を再試行...");
                try { $db->reconnect(); } catch (\Throwable $reconnectEx) { 
                    CLI::error("DB再接続失敗(failTask): " . $reconnectEx->getMessage()); 
                    $this->logger->error("DB再接続失敗(failTask) - TaskID {$taskId}: " . $reconnectEx->getMessage()); 
                }
            }
            $updateData = [
                'status' => 'failed',
                'processing_finished_at' => date('Y-m-d H:i:s'),
                'result_message' => mb_substr($errorMessage, 0, 4000)
            ];
            $db->table('import_tasks')->where('id', $taskId)->update($updateData);
            $affectedRows = $db->affectedRows();
            
            $this->logger->info("[ProcessImportTasks] failTask: DB update for TaskID {$taskId}. Affected rows: {$affectedRows}. Target status: failed. Error: {$errorMessage}");
            if ($affectedRows <= 0) {
                $this->logger->error("[ProcessImportTasks] failTask: DB update for TaskID {$taskId} reported 0 or error in affected rows, but no exception.");
            }
            
            CLI::error("タスクID {$taskId} は失敗しました。");
        } catch (\Throwable $dbException) {
            CLI::error("タスクID {$taskId} は失敗しましたが、さらに失敗ステータスのDB更新も失敗: " . $dbException->getMessage());
            $this->logger->critical("タスクID {$taskId} 失敗、さらにDB更新も失敗: " . $dbException->getMessage(), ['original_error' => $errorMessage, 'exception' => $dbException]);
        }
        $this->currentProcessingTaskId = null;
    }
    
    /**
     * スクリプトシャットダウン時の処理。
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();
        $logger = $this->logger ?? Services::logger();

        if ($this->currentProcessingTaskId !== null && $error !== null && 
            in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR])) {
            
            $errorMessageForLog = "致命的なエラーによりシャットダウン (タスクID: {$this->currentProcessingTaskId}): {$error['message']} in {$error['file']} on line {$error['line']}";
            error_log("ProcessImportTasks: SHUTDOWN (FATAL ERROR): " . $errorMessageForLog); 
            $logger->critical($errorMessageForLog, ['error_details' => $error]);
            
            try {
                $db = \Config\Database::connect(null, false); 
                $canPing = method_exists($db, 'ping') ? $db->ping() : true;
                if ($db->connID && $canPing) {
                   $dbErrorMessage = mb_substr("シャットダウン時の致命的エラー: " . $error['message'], 0, 4000);
                   $updateData = [
                        'status' => 'failed', 
                        'processing_finished_at' => date('Y-m-d H:i:s'),
                        'result_message' => $dbErrorMessage . " (Shutdown Handler)"
                    ];
                   $db->table('import_tasks')->where('id', $this->currentProcessingTaskId)->update($updateData);
                   $affectedRows = $db->affectedRows();
                   $logger->info("タスクID {$this->currentProcessingTaskId} のステータスをシャットダウンハンドラで 'failed' に更新しました。Affected rows: {$affectedRows}");
                } else {
                    $logger->error("シャットダウン時、タスクID {$this->currentProcessingTaskId} のステータス更新失敗 (DB接続不可)。");
                }
            } catch (\Throwable $e) {
                $logger->error("シャットダウン時、タスクID {$this->currentProcessingTaskId} のステータス更新中に例外: " . $e->getMessage());
            }
        }

        if ($this->lockHandle && is_resource($this->lockHandle)) {
            flock($this->lockHandle, LOCK_UN);
            fclose($this->lockHandle);
            $this->lockHandle = null;
            $logger->info('ファイルロックをシャットダウンハンドラで解放しました (runメソッドが正常に完了しなかった可能性)。');
        } else if (isset($this->lockFile) && file_exists($this->lockFile) && $this->currentProcessingTaskId !== null && isset($error)) {
            $logger->warning("シャットダウンハンドラ: 処理中にエラーが発生した可能性があります。ロックファイル ({$this->lockFile}) の状態を確認してください。");
        }
        $this->currentProcessingTaskId = null; 
    }

    /**
     * 未キャッチ例外のハンドラ。
     */
    public function handleException(\Throwable $exception): void
    {
        $errorMessage = "未処理の例外: {$exception->getMessage()} (ファイル: {$exception->getFile()}, 行: {$exception->getLine()})";
        error_log("ProcessImportTasks: EXCEPTION: " . $errorMessage . "\n" . $exception->getTraceAsString()); 
        $logger = $this->logger ?? Services::logger();
        $logger->critical($errorMessage, ['exception' => $exception]);

        if ($this->currentProcessingTaskId !== null) {
            try {
                $db = \Config\Database::connect(null, false);
                $canPing = method_exists($db, 'ping') ? $db->ping() : true;
                if ($db->connID && $canPing) {
                    $updateData = [
                        'status' => 'failed',
                        'processing_finished_at' => date('Y-m-d H:i:s'),
                        'result_message' => mb_substr("未処理の例外: " . $exception->getMessage(),0 , 3500) . " (Exception Handler)"
                    ];
                    $db->table('import_tasks')->where('id', $this->currentProcessingTaskId)->update($updateData);
                    $affectedRows = $db->affectedRows();
                    $logger->info("タスクID {$this->currentProcessingTaskId} のステータスを例外ハンドラで 'failed' に更新しました。Affected rows: {$affectedRows}");
                } else {
                    $logger->error("例外発生時、タスクID {$this->currentProcessingTaskId} のステータス更新失敗 (DB接続不可)。");
                }
            } catch (\Throwable $e) {
                $logger->error("例外発生時、タスクID {$this->currentProcessingTaskId} のステータス更新中にさらに例外: " . $e->getMessage());
            }
        }
        
        if ($this->lockHandle && is_resource($this->lockHandle)) {
            flock($this->lockHandle, LOCK_UN);
            fclose($this->lockHandle);
            $this->lockHandle = null;
            $logger->info('ファイルロックを例外ハンドラで解放しました。');
        }
        $this->currentProcessingTaskId = null;
        exit(EXIT_ERROR); 
    }
}