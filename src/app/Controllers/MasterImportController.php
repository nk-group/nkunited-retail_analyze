<?php

namespace App\Controllers;

use Config\Services; // Services を正しく use
use CodeIgniter\HTTP\RedirectResponse; // RedirectResponse の型ヒントのため

class MasterImportController extends BaseController // layouts/default.php を使うためBaseControllerを継承
{
    protected $helpers = ['form', 'url', 'filesystem']; // filesystem ヘルパーを追加

    // 各マスタの情報を定義
    private array $masterTypesDefinition;

    public function __construct()
    {
        $this->masterTypesDefinition = [
            'product_master' => [
                'title' => '商品マスタ', // Flashdataメッセージなどで使用
                'file_input_name' => 'master_file', // 共通のファイル入力名
                'flash_success_key' => 'success_product_master',
                'flash_error_key' => 'error_product_master',
            ],
            'department_master' => [
                'title' => '部門マスタ',
                'file_input_name' => 'master_file',
                'flash_success_key' => 'success_department_master',
                'flash_error_key' => 'error_department_master',
            ],
            'manufacturer_master' => [
                'title' => 'メーカーマスタ',
                'file_input_name' => 'master_file',
                'flash_success_key' => 'success_manufacturer_master',
                'flash_error_key' => 'error_manufacturer_master',
            ],
            'supplier_master' => [
                'title' => '仕入先マスタ',
                'file_input_name' => 'master_file',
                'flash_success_key' => 'success_supplier_master',
                'flash_error_key' => 'error_supplier_master',
            ],
        ];
    }


    /**
     * 各種マスタ取込画面の表示
     * この画面で複数のマスタ取込フォームを表示します。
     */
    public function index()
    {
        $data = [
            'pageTitle'   => '各種マスタ取込',
            // 'masterTypes' => $this->masterTypesDefinition, // ビューで直接定義している場合は不要
        ];
        return view('master_import/index_form', $data);
    }

    /**
     * 商品マスタExcelファイルのアップロードを受け付け、タスクとして登録する処理
     */
    public function processProductImport(): RedirectResponse
    {
        return $this->processMasterImport('product_master');
    }

    /**
     * 部門マスタファイルのアップロードを受け付け、タスクとして登録する処理 (プレースホルダー)
     */
    public function processDepartmentImport(): RedirectResponse
    {
        $masterInfo = $this->masterTypesDefinition['department_master'] ?? ['title' => '部門マスタ', 'flash_error_key' => 'error_department_master'];
        session()->setFlashdata($masterInfo['flash_error_key'], esc($masterInfo['title']) . 'の取込機能は現在準備中です。');
        return redirect()->to(route_to('master_import_form'))->withCookies();
        // return $this->processMasterImport('department_master'); // 実装時に有効化
    }

    /**
     * メーカーマスタファイルのアップロードを受け付け、タスクとして登録する処理 (プレースホルダー)
     */
    public function processManufacturerImport(): RedirectResponse
    {
        return $this->processMasterImport('manufacturer_master');
    }

    /**
     * 仕入先マスタファイルのアップロードを受け付け、タスクとして登録する処理 (プレースホルダー)
     */
    public function processSupplierImport(): RedirectResponse
    {
        $masterInfo = $this->masterTypesDefinition['supplier_master'] ?? ['title' => '仕入先マスタ', 'flash_error_key' => 'error_supplier_master'];
        session()->setFlashdata($masterInfo['flash_error_key'], esc($masterInfo['title']) . 'の取込機能は現在準備中です。');
        return redirect()->to(route_to('master_import_form'))->withCookies();
        // return $this->processMasterImport('supplier_master'); // 実装時に有効化
    }


    /**
     * 汎用的なマスタファイルアップロード処理メソッド
     * @param string $targetDataName処理対象のマスタ識別子 (例: 'product_master')
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    private function processMasterImport(string $targetDataName): RedirectResponse
    {
        $masterInfo = $this->masterTypesDefinition[$targetDataName] ?? null;

        if (!$masterInfo) {
            // 予期しないtargetDataNameの場合、汎用的なエラーキーを使用するか、
            // master_import_form に 'error_general_master' のような表示箇所を設ける。
            // ここでは最初のマスタ（商品マスタ）のエラーキーを仮で使用。
            $firstMasterErrorKey = $this->masterTypesDefinition[array_key_first($this->masterTypesDefinition)]['flash_error_key'] ?? 'error_master_import';
            session()->setFlashdata($firstMasterErrorKey, '不明なマスタ種別が指定されました。');
            log_message('error', "[MasterImportController] Unknown master type specified for processing: " . $targetDataName);
            return redirect()->to(route_to('master_import_form'))->withCookies();
        }

        // フォームから送信されたtarget_data_nameも確認 (Hiddenフィールドの値)
        $formTarget = $this->request->getPost('target_data_name');
        if ($formTarget !== $targetDataName) {
            log_message('error', "[MasterImportController] Mismatch between route target '{$targetDataName}' and form target '{$formTarget}'.");
            session()->setFlashdata($masterInfo['flash_error_key'], '処理の整合性が取れませんでした。ページの再読み込み後、再度お試しください。');
            return redirect()->to(route_to('master_import_form'))->withCookies();
        }
        
        $fileInputName = $masterInfo['file_input_name']; // ビューで定義された共通の 'master_file'

        // バリデーションルールの設定
        $validationRule = [
            $fileInputName => [
                'label' => esc($masterInfo['title']) . 'ファイル',
                'rules' => [
                    "uploaded[{$fileInputName}]",
                    "mime_in[{$fileInputName},application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel]", // Excelのみ
                    "max_size[{$fileInputName},20480]", // 20MB (元のMasterImportControllerの値)
                ],
                'errors' => [ 
                    'uploaded' => '{field}を選択してください。',
                    'mime_in'  => '{field}はExcelファイル (.xlsx または .xls) である必要があります。',
                    'max_size' => '{field}のサイズが大きすぎます。20MB以下のファイルを選択してください。',
                ]
            ],
        ];

        if (!$this->validate($validationRule)) {
            $errors = $this->validator->getErrors();
            // バリデーションエラーは、$fileInputName に紐づくもののみ取得 (他は現状ないはず)
            $errorMessage = !empty($errors[$fileInputName]) ? $errors[$fileInputName] : esc($masterInfo['title']) . 'のファイルアップロードで予期せぬエラーが発生しました。';
            session()->setFlashdata($masterInfo['flash_error_key'], $errorMessage);
            return redirect()->to(route_to('master_import_form'))->withInput()->withCookies();
        }

        $excelFile = $this->request->getFile($fileInputName);

        // isValid()チェックの前に $excelFile が null でないことを確認
        if (!$excelFile || !$excelFile->isValid()) {
            $errorFromFile = $excelFile ? $excelFile->getErrorString() . '(' . $excelFile->getError() . ')' : 'ファイルが受信できませんでした。';
             log_message('error', '[MasterImportController] Invalid or no file uploaded for ' . $targetDataName . ': ' . $errorFromFile);
            session()->setFlashdata($masterInfo['flash_error_key'], esc($masterInfo['title']) . 'のファイルアップロードに失敗しました。無効なファイルです。(' . $errorFromFile . ')');
            return redirect()->to(route_to('master_import_form'))->withCookies();
        }
        
        if ($excelFile->hasMoved()) {
            log_message('warning', '[MasterImportController] File already moved for ' . $targetDataName . ': ' . $excelFile->getName());
            session()->setFlashdata($masterInfo['flash_error_key'], esc($masterInfo['title']) . 'のファイルは既に処理されたか、二重送信の可能性があります。');
            return redirect()->to(route_to('master_import_form'))->withCookies();
        }

        $uploadBaseDir = 'master_files'; 
        $uploadPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . $uploadBaseDir . DIRECTORY_SEPARATOR;
        
        $newName = $excelFile->getRandomName(); // ランダム名で保存

        if (!is_dir($uploadPath)) {
            if (!mkdir($uploadPath, 0775, true) && !is_dir($uploadPath)) {
                log_message('error', '[MasterImportController] Failed to create upload directory: ' . $uploadPath);
                session()->setFlashdata($masterInfo['flash_error_key'], 'アップロードディレクトリの作成に失敗しました。サーバー管理者に連絡してください。');
                return redirect()->to(route_to('master_import_form'))->withCookies();
            }
        }

        $fullStoredPath = $uploadPath . $newName;

        if ($excelFile->move($uploadPath, $newName)) {
            $db = \Config\Database::connect();
            $taskData = [
                'status'               => 'pending',
                'target_data_name'     => $targetDataName,
                'original_file_name'   => $excelFile->getClientName(),
                'stored_file_path'     => $fullStoredPath, // 絶対パス
                'uploaded_at'          => date('Y-m-d H:i:s'),
                'uploaded_by'          => session()->get('displayName') ?? (session()->get('username') ?? 'system'),
                // 'result_message' はバックグラウンド処理で設定
            ];

            try {
                $db->table('import_tasks')->insert($taskData);
                log_message('info', "[MasterImportController] Task created for {$targetDataName}: {$taskData['original_file_name']} stored as {$taskData['stored_file_path']}");
                session()->setFlashdata($masterInfo['flash_success_key'], esc($masterInfo['title']) . 'のファイルは正常にアップロードされ、取込キューに追加されました。処理には時間がかかる場合があります。');
            } catch (\Exception $e) {
                log_message('error', '[MasterImportController] Failed to insert task into import_tasks table for ' . $targetDataName . ': ' . $e->getMessage());
                if (file_exists($fullStoredPath)) {
                    unlink($fullStoredPath); // DB登録失敗時はアップロードファイルを削除
                }
                session()->setFlashdata($masterInfo['flash_error_key'], '取込タスクの登録中にデータベースエラーが発生しました。システム管理者にお問い合わせください。');
            }
        } else {
            $error = $excelFile->getErrorString() . '(' . $excelFile->getError() . ')';
            log_message('error', '[MasterImportController] Failed to move uploaded file for ' . $targetDataName . ': ' . $error);
            session()->setFlashdata($masterInfo['flash_error_key'], 'ファイルの保存処理中にエラーが発生しました。サーバーの状態を確認してください。');
        }
        return redirect()->to(route_to('master_import_form'))->withCookies();
    }
}