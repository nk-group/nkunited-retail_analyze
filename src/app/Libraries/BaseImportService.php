<?php
namespace App\Libraries;

use Config\Database;
use Config\Services;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Reader\Xls as XlsReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet; 

/**
 * データ取り込みサービスの基底クラス。
 * Excelファイルの基本的な読み込み機能や、結果生成の共通メソッドなどを提供します。
 * このクラスを継承して、各マスタ・伝票固有の取り込みサービスを作成します。
 */
abstract class BaseImportService implements ImportServiceInterface
{
    protected $db;
    protected $logger;
    protected int $importBatchSize = 200;
    protected int $headerRowNumber = 1;
    protected string $serviceNameForLogging = 'BaseImportService';

    public function __construct()
    {
        $this->db     = Database::connect();
        $this->logger = Services::logger();
    }

    abstract public function processFile(string $filePath): array;

    protected function loadAndGetWorksheet(string $filePath, ?IReadFilter $readFilter, string &$errorMessage): ?Worksheet
    {
        $this->logger->info("{$this->serviceNameForLogging}: Attempting to load spreadsheet: " . basename($filePath));
        $spreadsheet = null; 
        $reader = null;
        $errorMessage = '';      

        try {
            if (!file_exists($filePath) || !is_readable($filePath)) {
                $errorMessage = "ファイルが存在しないか、読み取り権限がありません: " . basename($filePath);
                $this->logger->error("{$this->serviceNameForLogging}: {$errorMessage}");
                return null;
            }

            $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            
            if ($fileExtension === 'xlsx') {
                $reader = new XlsxReader();
            } elseif ($fileExtension === 'xls') {
                $reader = new XlsReader();
            } else {
                $errorMessage = "未対応のファイル形式です: {$fileExtension}。xlsx または xls 形式のファイルが必要です。";
                $this->logger->error("{$this->serviceNameForLogging}: {$errorMessage} for file " . basename($filePath));
                return null;
            }
            
            $reader->setReadDataOnly(true); 
            if ($readFilter !== null) {
                $reader->setReadFilter($readFilter);
                $this->logger->info("{$this->serviceNameForLogging}: Read filter applied to " . basename($filePath));
            }
            
            $spreadsheet = $reader->load($filePath);
            $worksheet   = $spreadsheet->getActiveSheet(); 
            
            $this->logger->info("{$this->serviceNameForLogging}: Spreadsheet loaded successfully. Peak memory: " . round(memory_get_peak_usage(true)/1024/1024,2) . "MB");
            
            // Worksheet を返す前に Spreadsheet オブジェクトをプロパティに保持するか、
            // または呼び出し元が Worksheet->getParent() で取得できるようにする。
            // ここでは Worksheet のみを返し、解放は呼び出し元の finally で行う。
            return $worksheet;

        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            $errorMessage = "Excel処理ライブラリエラー (ロード時): " . $e->getMessage();
            $this->logger->error("[{$this->serviceNameForLogging}] PHPSpreadsheet error while loading " . basename($filePath) . ": " . $e->getMessage());
        } catch (\Throwable $e) {
            $errorMessage = "予期せぬエラー (ファイルロード時): " . $e->getMessage();
            $this->logger->critical("[{$this->serviceNameForLogging}] General error while loading " . basename($filePath) . ": " . $e->getMessage(), ['exception' => $e]);
        } finally {
            if (isset($reader)) {
                unset($reader); // Reader はここで解放
            }
        }
        return null; 
    }

    protected function getValidatedRowIterator(Worksheet $worksheet, array $expectedHeaders, string &$errorMessage, int $headerRowToValidate = 1): ?\PhpOffice\PhpSpreadsheet\Worksheet\RowIterator
    {
        $this->headerRowNumber = $headerRowToValidate; 
        $errorMessage = ''; 

        if (empty($expectedHeaders)) {
            $this->logger->info("{$this->serviceNameForLogging} (HeaderVal): No expected headers provided, skipping validation. Returning iterator from row " . ($this->headerRowNumber + 1));
            $rowIterator = $worksheet->getRowIterator();
            for ($i = 1; $i <= $this->headerRowNumber; $i++) { 
                if ($rowIterator->valid()) {
                    $rowIterator->next();
                } else {
                    $errorMessage = "データ開始行 (期待ヘッダー行 " . $this->headerRowNumber . " の次) が存在しません。";
                    $this->logger->warning("{$this->serviceNameForLogging} (HeaderVal): " . $errorMessage);
                    return null;
                }
            }
            return $rowIterator;
        }

        $rowIterator = $worksheet->getRowIterator($this->headerRowNumber); 

        if (!$rowIterator->valid()) { 
            $errorMessage = "ヘッダー行 ({$this->headerRowNumber}行目) を読み取れませんでした。ファイルが空か行数が不足しています。";
            $this->logger->warning("{$this->serviceNameForLogging} (HeaderVal): " . $errorMessage);
            return null;
        }
        
        $headerExcelRowObject = $rowIterator->current();
        $headerCellIterator   = $headerExcelRowObject->getCellIterator();
        $headerCellIterator->setIterateOnlyExistingCells(false); 
        $actualHeaderRow = [];
        foreach ($headerCellIterator as $cell) { 
            $actualHeaderRow[] = trim((string) $cell->getValue());
        }
        unset($headerExcelRowObject, $headerCellIterator, $cell); 

        if (empty($actualHeaderRow)) {
            $errorMessage = "ヘッダー行 ({$this->headerRowNumber}行目) の読み取りに失敗しました（内容が空です）。";
            $this->logger->error("{$this->serviceNameForLogging} (HeaderVal): " . $errorMessage);
            return null;
        }

        $mismatchFound = false;
        foreach ($expectedHeaders as $idx => $expectedValue) {
            $actualValue = $actualHeaderRow[$idx] ?? null;

            if ($expectedValue === '') { 
                continue; 
            }

            if ($actualValue === null || mb_strtoupper($actualValue) !== mb_strtoupper($expectedValue)) {
                $itemNumber           = $idx + 1;
                $foundHeaderValue     = $actualValue ?? '存在なし';
                $expectedHeaderValue  = $expectedValue;
                $contextExpectedHeadersSample = implode(", ", array_slice($expectedHeaders, 0, 5));
                if (count($expectedHeaders) > 5) {
                    $contextExpectedHeadersSample .= ", ...他";
                }
                
                $errorMessage = sprintf(
                    "ヘッダーの形式が不正です。%d 番目の期待ヘッダーは「%s」ですが、実際には「%s」でした。(期待ヘッダー例: %s)",
                    $itemNumber,
                    $expectedHeaderValue,
                    $foundHeaderValue,
                    $contextExpectedHeadersSample
                );
                $this->logger->error("{$this->serviceNameForLogging} (HeaderVal): Header mismatch - {$errorMessage}");
                $mismatchFound = true;
                break; 
            }
        }

        if ($mismatchFound) {
            return null;
        }

        $this->logger->info("{$this->serviceNameForLogging} (HeaderVal): Header validation successful for row " . $this->headerRowNumber . ".");

        $rowIterator->next(); 
        return $rowIterator; 
    }

    protected function generateResult(bool $success, string $message, int $importedCount, int $updatedCount, int $skippedCount, int $processedDataRows, array $errorMessages): array
    {
        return [
            'success'           => $success,
            'message'           => $message,
            'imported_count'    => $importedCount,
            'updated_count'     => $updatedCount,
            'skipped_count'     => $skippedCount,
            'processed_rows'    => $processedDataRows,
            'error_messages'    => $errorMessages,
        ];
    }

    /**
     * PHPSpreadsheet関連のオブジェクトを解放し、ガベージコレクションを試みます。
     * 引数の参照渡しを解除し、null許容型に修正。
     *
     * @param Spreadsheet|null                                  $spreadsheet
     * @param Worksheet|null                                    $worksheet
     * @param \PhpOffice\PhpSpreadsheet\Reader\IReader|null     $reader
     */
    protected function cleanupSpreadsheetObjects(?Spreadsheet $spreadsheet, ?Worksheet $worksheet, ?\PhpOffice\PhpSpreadsheet\Reader\IReader $reader = null): void
    {
        // $worksheet, $spreadsheet, $reader が null でない場合のみ操作
        if ($spreadsheet !== null) {
            $spreadsheet->disconnectWorksheets(); 
        }
        
        unset($worksheet); 
        unset($spreadsheet); 
        unset($reader); 
        
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        $this->logger->info("{$this->serviceNameForLogging}: Spreadsheet objects attempt to be cleaned up. Peak memory after cleanup: " . round(memory_get_peak_usage(true)/1024/1024,2) . "MB");
    }

    protected function executeUpdate(string $tableName, array $data, array $where): bool
    {
        if (empty($where)) {
            $this->logger->error("[{$this->serviceNameForLogging}] executeUpdate: WHERE clause is empty. Update aborted for table '{$tableName}'.");
            return false;
        }
        if (empty($data)) {
            $this->logger->warning("[{$this->serviceNameForLogging}] executeUpdate: Data to update is empty for table '{$tableName}'. WHERE: " . json_encode($where));
            return true; 
        }

        try {
            $builder = $this->db->table($tableName);
            $builder->where($where);
            $result = $builder->update($data); 
            
            if ($result === false) {
                $error = $this->db->error();
                $this->logger->error("[{$this->serviceNameForLogging}] executeUpdate failed for table '{$tableName}'. DB Error: [Code: {$error['code']}] {$error['message']}", ['data_sample' => array_slice($data, 0, 3), 'where' => $where]);
                return false;
            }
            return true; 
        } catch (\Throwable $e) {
            $this->logger->error("[{$this->serviceNameForLogging}] executeUpdate exception for table '{$tableName}': " . $e->getMessage(), ['data_sample' => array_slice($data, 0, 3), 'where' => $where, 'exception' => $e]);
            return false;
        }
    }

    protected function executeBatchInsert(string $tableName, array &$batchData, int &$importedCount, int &$skippedCount, array &$errorMessages, string $batchContextMessage = ''): void
    {
        if (empty($batchData)) {
            return;
        }
        $batchContextMessage = trim($batchContextMessage . " ");
        $batchRowCount = count($batchData);
        $this->logger->info("[{$this->serviceNameForLogging}] Executing {$batchContextMessage}batch insert for table '{$tableName}', {$batchRowCount} rows.");

        try {
            $insertedRows = $this->db->table($tableName)->insertBatch($batchData);

            if ($insertedRows !== false && $insertedRows > 0) {
                $importedCount += $insertedRows;
                if ($insertedRows < $batchRowCount) {
                    $partiallySkipped = $batchRowCount - $insertedRows;
                    $skippedCount += $partiallySkipped;
                    $errorMessageText = "{$batchContextMessage}バッチ挿入で {$partiallySkipped} / {$batchRowCount} 件がスキップまたは失敗しました（DBエラーの可能性）。";
                    $errorMessages[] = $errorMessageText;
                    $this->logger->warning("[{$this->serviceNameForLogging}] {$batchContextMessage}batch insert for '{$tableName}': {$insertedRows} rows inserted, {$partiallySkipped} rows potentially skipped/failed.");
                }
            } elseif ($insertedRows === false) { 
                $error = $this->db->error();
                $dbErrorMessage = "[Code: {$error['code']}] {$error['message']}";
                $errorMessages[] = "{$batchContextMessage}バッチ挿入エラー (影響 {$batchRowCount}件): DBエラー {$dbErrorMessage}";
                $skippedCount += $batchRowCount;
                $this->logger->error("[{$this->serviceNameForLogging}] {$batchContextMessage}batch insert failed for '{$tableName}'. DB Error: {$dbErrorMessage}", ['batch_data_count' => $batchRowCount]);
            } else { 
                 $this->logger->info("[{$this->serviceNameForLogging}] {$batchContextMessage}batch insert for '{$tableName}': 0 rows affected by insertBatch, though query was successful.");
            }
        } catch (\Throwable $e) {
            $errorMessages[] = "{$batchContextMessage}バッチ挿入中に例外発生 (影響 {$batchRowCount}件): " . $e->getMessage();
            $skippedCount += $batchRowCount;
            $this->logger->error("[{$this->serviceNameForLogging}] Exception during {$batchContextMessage}batch insert for '{$tableName}': " . $e->getMessage(), ['exception' => $e, 'batch_data_count' => $batchRowCount]);
        }
        $batchData = []; 
    }

    protected function logMemoryUsage(int $processedRows, int $totalRowsToProcess, string $serviceContextName): void
    {
        $context = !empty($serviceContextName) ? $serviceContextName : $this->serviceNameForLogging;
        $this->logger->info("{$context}: Processed rows: {$processedRows} / {$totalRowsToProcess}. Current memory: " . round(memory_get_usage(true)/1024/1024,2) . "MB. Peak memory: " . round(memory_get_peak_usage(true)/1024/1024,2) . "MB");
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }
    
    protected function summarizeErrorMessages(array $errorMessages, int $limit = 5): array
    {
        if (empty($errorMessages)) {
            return [];
        }
        $summary = array_slice($errorMessages, 0, $limit);
        if (count($errorMessages) > $limit) {
            $summary[] = "...他 " . (count($errorMessages) - $limit) . "件のエラー/警告があります。(詳細はサーバーログを確認してください)";
        }
        return $summary;
    }

    protected function isRowEmpty(array $rowData): bool
    {
        if (empty($rowData)) {
            return true; 
        }
        foreach ($rowData as $cellValue) {
            if ($cellValue !== null && trim((string)$cellValue) !== '') {
                return false; 
            }
        }
        return true; 
    }
}