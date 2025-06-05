<?php

namespace App\Controllers;

use Config\Services;
use CodeIgniter\HTTP\RedirectResponse; // RedirectResponse の型ヒントのため

class SlipImportController extends BaseController
{
    protected $helpers = ['form', 'url', 'filesystem'];

    // 各伝票の情報を定義 (ビューと共通)
    private array $slipTypesDefinition;
    private string $pageTitle = '各種伝票取込';

    public function __construct()
    {
        
        $this->slipTypesDefinition = [
            'purchase_slip' => [
                'title' => '仕入伝票', 
                'file_input_name' => 'slip_file', 
                'flash_success_key' => 'success_purchase_slip',
                'flash_error_key' => 'error_purchase_slip',
            ],
            'sales_slip' => [
                'title' => '売上伝票',
                'file_input_name' => 'slip_file',
                'flash_success_key' => 'success_sales_slip',
                'flash_error_key' => 'error_sales_slip',
            ],
            'transfer_slip' => [
                'title' => '移動伝票',
                'file_input_name' => 'slip_file',
                'flash_success_key' => 'success_transfer_slip',
                'flash_error_key' => 'error_transfer_slip',
            ],
            'adjustment_slip' => [
                'title' => '調整伝票',
                'file_input_name' => 'slip_file',
                'flash_success_key' => 'success_adjustment_slip',
                'flash_error_key' => 'error_adjustment_slip',
            ],
        ];

    }

    /**
     * 各種伝票取込画面の表示
     */
    public function uploadForm(): string
    {
        $data = [
            'pageTitle'             => $this->pageTitle,
            'slipTypesDefinition'   => $this->slipTypesDefinition, // ビューでループ表示するため
            // Flashdataはビュー側で直接 session()->getFlashdata() で取得
        ];
        return view('slip_import/index_form', $data);
    }

    /**
     * 仕入伝票ファイルのアップロード処理
     */
    public function processPurchaseSlipImport(): RedirectResponse
    {
        return $this->processSlipImport('purchase_slip');
    }

    /**
     * 売上伝票ファイルのアップロード処理
     */
    public function processSalesSlipImport(): RedirectResponse
    {
        return $this->processSlipImport('sales_slip'); 
    }

    /**
     * 移動伝票ファイルのアップロード処理
     */
    public function processTransferSlipImport(): RedirectResponse
    {
        return $this->processSlipImport('transfer_slip');
    }

    /**
     * 調整伝票ファイルのアップロード処理
     */
    public function processAdjustmentSlipImport(): RedirectResponse
    {
        return $this->processSlipImport('adjustment_slip');
    }

    /**
     * 汎用的な伝票ファイルアップロード処理メソッド
     * @param string $targetDataName 処理対象の伝票識別子 (例: 'purchase_slip')
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    private function processSlipImport(string $targetDataName): RedirectResponse
    {
        $slipInfo = $this->slipTypesDefinition[$targetDataName] ?? null;

        if (!$slipInfo) {
            // 通常、このルートに来る場合は $targetDataName は定義済みのはずだが念のため
            $firstSlipErrorKey = $this->slipTypesDefinition[array_key_first($this->slipTypesDefinition)]['flash_error_key'] ?? 'error_slip_import'; // フォールバック用のキー
            session()->setFlashdata($firstSlipErrorKey, '不明な伝票種別が指定されました。');
            log_message('error', "[SlipImportController] Unknown slip type specified for processing: " . $targetDataName);
            return redirect()->to(route_to('slip_import_form'))->withCookies();
        }

        // フォームから送信されたtarget_data_nameも確認 (Hiddenフィールドの値)
        $formTarget = $this->request->getPost('target_data_name');
        if ($formTarget !== $targetDataName) {
            log_message('error', "[SlipImportController] Mismatch between route target '{$targetDataName}' and form target '{$formTarget}'.");
            session()->setFlashdata($slipInfo['flash_error_key'], '処理の整合性が取れませんでした。ページの再読み込み後、再度お試しください。');
            return redirect()->to(route_to('slip_import_form'))->withCookies();
        }
        
        $fileInputName = $slipInfo['file_input_name']; // 'slip_file'

        // バリデーションルールの設定
        $validationRule = [
            $fileInputName => [
                'label' => esc($slipInfo['title']) . 'ファイル',
                'rules' => [
                    "uploaded[{$fileInputName}]",
                    "mime_in[{$fileInputName},application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv]", // Excel, CSV
                    "max_size[{$fileInputName},30720]",
                ],
                'errors' => [ 
                    'uploaded' => '{field}を選択してください。',
                    'mime_in'  => '{field}はExcelファイル (.xlsx, .xls) またはCSVファイル (.csv) である必要があります。',
                    'max_size' => '{field}のサイズが大きすぎます。30MB以下のファイルを選択してください。',
                ]
            ],
            // 'target_data_name' (hidden field) のバリデーションは必須ではないが、
            // もし追加するなら 'required|in_list[...]' のようにする
        ];

        if (!$this->validate($validationRule)) {
            $errors = $this->validator->getErrors();
            // MasterImportControllerに倣い、ファイル入力に関するエラーを優先的に表示
            $errorMessage = !empty($errors[$fileInputName]) ? $errors[$fileInputName] : (esc($slipInfo['title']) . 'のファイルアップロードで予期せぬエラーが発生しました。');
            if (empty($errors[$fileInputName]) && !empty($errors)) { // ファイルエラー以外が先頭の場合
                $errorMessage = reset($errors);
            }
            session()->setFlashdata($slipInfo['flash_error_key'], $errorMessage);
            return redirect()->to(route_to('slip_import_form'))->withInput()->withCookies();
        }

        $uploadedFile = $this->request->getFile($fileInputName);

        if (!$uploadedFile || !$uploadedFile->isValid()) {
            $errorFromFile = $uploadedFile ? $uploadedFile->getErrorString() . '(' . $uploadedFile->getError() . ')' : 'ファイルが受信できませんでした。';
            log_message('error', '[SlipImportController] Invalid or no file uploaded for ' . $targetDataName . ': ' . $errorFromFile);
            session()->setFlashdata($slipInfo['flash_error_key'], esc($slipInfo['title']) . 'のファイルアップロードに失敗しました。無効なファイルです。(' . $errorFromFile . ')');
            return redirect()->to(route_to('slip_import_form'))->withCookies();
        }
        
        if ($uploadedFile->hasMoved()) {
            log_message('warning', '[SlipImportController] File already moved for ' . $targetDataName . ': ' . $uploadedFile->getName());
            session()->setFlashdata($slipInfo['flash_error_key'], esc($slipInfo['title']) . 'のファイルは既に処理されたか、二重送信の可能性があります。');
            return redirect()->to(route_to('slip_import_form'))->withCookies();
        }

        $uploadBaseDir = 'slips_files'; // マスタは 'master_files'
        $uploadPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . $uploadBaseDir . DIRECTORY_SEPARATOR;
        
        $newName = $uploadedFile->getRandomName();

        if (!is_dir($uploadPath)) {
            if (!mkdir($uploadPath, 0775, true) && !is_dir($uploadPath)) {
                log_message('error', '[SlipImportController] Failed to create upload directory: ' . $uploadPath);
                session()->setFlashdata($slipInfo['flash_error_key'], 'アップロードディレクトリの作成に失敗しました。サーバー管理者に連絡してください。');
                return redirect()->to(route_to('slip_import_form'))->withCookies();
            }
        }

        $fullStoredPath = $uploadPath . $newName;

        if ($uploadedFile->move($uploadPath, $newName)) {
            $db = \Config\Database::connect();
            $taskData = [
                'status'               => 'pending',
                'target_data_name'     => $targetDataName,
                'original_file_name'   => $uploadedFile->getClientName(),
                'stored_file_path'     => $fullStoredPath, // 絶対パス
                'uploaded_at'          => date('Y-m-d H:i:s'),
                'uploaded_by'          => session()->get('displayName') ?? (session()->get('username') ?? 'system'),
                // 'result_message' はバックグラウンド処理で設定
            ];

            try {
                $db->table('import_tasks')->insert($taskData);
                log_message('info', "[SlipImportController] Task created for {$targetDataName}: {$taskData['original_file_name']} stored as {$taskData['stored_file_path']}");
                session()->setFlashdata($slipInfo['flash_success_key'], esc($slipInfo['title']) . 'のファイル「' . esc($taskData['original_file_name']) . '」は正常にアップロードされ、取込キューに追加されました。処理には時間がかかる場合があります。');
            } catch (\Exception $e) {
                log_message('error', '[SlipImportController] Failed to insert task into import_tasks table for ' . $targetDataName . ': ' . $e->getMessage());
                if (file_exists($fullStoredPath)) {
                    unlink($fullStoredPath); 
                }
                session()->setFlashdata($slipInfo['flash_error_key'], '取込タスクの登録中にデータベースエラーが発生しました。システム管理者にお問い合わせください。');
            }
        } else {
            $error = $uploadedFile->getErrorString() . '(' . $uploadedFile->getError() . ')';
            log_message('error', '[SlipImportController] Failed to move uploaded file for ' . $targetDataName . ': ' . $error);
            session()->setFlashdata($slipInfo['flash_error_key'], 'ファイルの保存処理中にエラーが発生しました。サーバーの状態を確認してください。');
        }
        return redirect()->to(route_to('slip_import_form'))->withCookies();
    }
}