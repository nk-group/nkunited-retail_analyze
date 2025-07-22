<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    /* master_import/index_form.php と同様のスタイル */
    .import-section {
        margin-bottom: 2.5rem;
        padding: 1.5rem;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        background-color: #fff;
    }
    .import-section h4 {
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #eee;
    }
    .status-message-placeholder {
        min-height: 24px; 
        margin-top: 1rem;
        font-style: italic;
        color: #6c757d; 
    }
    .processing-message {
        color: #007bff; 
        font-weight: bold;
    }
    .is-invalid + .invalid-feedback, .is-invalid ~ .invalid-feedback {
        display: block !important;
    }
    .form-control.is-invalid, .form-select.is-invalid { /* form-select も対象に */
        border-color: #dc3545;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?= esc($pageTitle ?? '各種伝票取込') ?></h2>
        <?php /* <a href="#" class="btn btn-outline-secondary"><i class="bi bi-info-circle"></i> 取込ヘルプ(仮)</a> */ ?>
    </div>

    <?php
    // 各伝票の情報を配列で定義
    $slipTypesDefinition = [
        'purchase_slip' => [
            'title' => '仕入伝票取込',
            'icon' => 'bi-receipt-cutoff', // アイコン例
            'form_id' => 'purchaseSlipImportForm',
            'file_input_id' => 'purchase_slip_file',
            'file_input_name' => 'slip_file', // 全伝票で共通のファイル入力名
            'status_div_id' => 'purchaseSlipStatus',
            'action_route' => 'purchase_slip_import_process', // 回答81のルート名
            'flash_success_key' => 'success_purchase_slip',
            'flash_error_key' => 'error_purchase_slip',
            'notes' => 'ファイル形式: 指定のExcelまたはCSV形式。1行目ヘッダー、2行目以降データ。',
            'enabled' => true
        ],
        'sales_slip' => [
            'title' => '売上伝票取込',
            'icon' => 'bi-cart-check', // アイコン例
            'form_id' => 'salesSlipImportForm',
            'file_input_id' => 'sales_slip_file',
            'file_input_name' => 'slip_file',
            'status_div_id' => 'salesSlipStatus',
            'action_route' => 'sales_slip_import_process',
            'flash_success_key' => 'success_sales_slip',
            'flash_error_key' => 'error_sales_slip',
            'notes' => 'ファイル形式: 指定のExcelまたはCSV形式。1行目ヘッダー、2行目以降データ。',
            'enabled' => true
        ],
        'transfer_slip' => [
            'title' => '移動伝票取込',
            'icon' => 'bi-arrows-exchange', // アイコン例
            'form_id' => 'transferSlipImportForm',
            'file_input_id' => 'transfer_slip_file',
            'file_input_name' => 'slip_file',
            'status_div_id' => 'transferSlipStatus',
            'action_route' => 'transfer_slip_import_process',
            'flash_success_key' => 'success_transfer_slip',
            'flash_error_key' => 'error_transfer_slip',
            'notes' => 'ファイル形式: 指定のExcelまたはCSV形式。1行目ヘッダー、2行目以降データ。',
            'enabled' => true
        ],
        'adjustment_slip' => [
            'title' => '調整伝票取込',
            'icon' => 'bi-sliders', // アイコン例
            'form_id' => 'adjustmentSlipImportForm',
            'file_input_id' => 'adjustment_slip_file',
            'file_input_name' => 'slip_file',
            'status_div_id' => 'adjustmentSlipStatus',
            'action_route' => 'adjustment_slip_import_process',
            'flash_success_key' => 'success_adjustment_slip',
            'flash_error_key' => 'error_adjustment_slip',
            'notes' => 'ファイル形式: 指定のExcelまたはCSV形式。1行目ヘッダー、2行目以降データ。',
            'enabled' => true
        ],
        'order_slip' => [
            'title' => '発注伝票取込',
            'icon' => 'bi-clipboard-check', // アイコン例
            'form_id' => 'orderSlipImportForm',
            'file_input_id' => 'order_slip_file',
            'file_input_name' => 'slip_file',
            'status_div_id' => 'orderSlipStatus',
            'action_route' => 'order_slip_import_process',
            'flash_success_key' => 'success_order_slip',
            'flash_error_key' => 'error_order_slip',
            'notes' => 'ファイル形式: 指定のExcelまたはCSV形式。1行目ヘッダー、2行目以降データ。',
            'enabled' => true
        ],
    ];
    ?>

    <?php foreach ($slipTypesDefinition as $targetName => $slip): ?>
    <section id="<?= esc($targetName) ?>-section" class="import-section shadow-sm <?= !$slip['enabled'] ? 'bg-light' : '' ?>">
        <h4><i class="bi <?= esc($slip['icon']) ?>"></i> <?= esc($slip['title']) ?></h4>
        
        <?php $successMessage = session()->getFlashdata($slip['flash_success_key']); ?>
        <?php if (isset($successMessage) && !empty($successMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $successMessage; // メッセージにHTMLが含まれる可能性を考慮 (コントローラでエスケープ制御) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php $errorMessage = session()->getFlashdata($slip['flash_error_key']); ?>
        <?php if (isset($errorMessage) && !empty($errorMessage)): ?>
            <div class="alert alert-danger" role="alert">
                <?= esc($errorMessage); // エラーメッセージは念のためesc ?>
            </div>
        <?php endif; ?>

        <?= form_open_multipart(route_to($slip['action_route']), ['id' => $slip['form_id']]) ?>
            <?= csrf_field() ?>
            <input type="hidden" name="target_data_name" value="<?= esc($targetName) ?>">
            
            <div class="mb-3">
                <label for="<?= esc($slip['file_input_id']) ?>" class="form-label fw-bold">取込ファイル選択 (Excel: .xlsx, .xls または CSV: .csv)</label>
                <input class="form-control <?= (isset($errorMessage) && $slip['enabled'] && (strpos($errorMessage, '選択してください') !== false || strpos($errorMessage, 'ファイル形式') !== false || strpos($errorMessage, 'サイズが大きすぎます') !== false || strpos($errorMessage, 'ファイルの保存処理中にエラー') !== false )) ? 'is-invalid' : '' ?>" 
                       type="file" 
                       id="<?= esc($slip['file_input_id']) ?>" 
                       name="<?= esc($slip['file_input_name']) ?>" 
                       <?= !$slip['enabled'] ? 'disabled' : 'required' ?>
                       accept=".xlsx,.xls,.csv">
                <?php if (isset($errorMessage) && $slip['enabled']): ?>
                    <div class="invalid-feedback"> 
                         <?= esc($errorMessage) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4">
                <button type="submit" class="btn <?= $slip['enabled'] ? 'btn-primary' : 'btn-secondary' ?> btn-lg" <?= !$slip['enabled'] ? 'disabled' : '' ?>>
                    <i class="bi <?= esc($slip['icon']) ?>"></i> <?= esc(str_replace('取込', ' 取込実行', $slip['title'])) ?>
                </button>
                <div id="<?= esc($slip['status_div_id']) ?>" class="status-message-placeholder">
                    <?php if ($slip['enabled']): ?>
                        <small class="text-muted">アップロード後、バックグラウンドで処理されます。</small>
                    <?php else: ?>
                        <small class="text-muted">準備中</small>
                    <?php endif; ?>
                </div>
            </div>
        <?= form_close() ?>
        <?php if ($slip['enabled']): ?>
        <div class="mt-3">
            <small class="form-text text-muted">
                <strong><?= esc($slip['notes']) ?></strong>
            </small>
        </div>
        <?php endif; ?>
    </section>
    <?php endforeach; ?>

</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    <?php foreach ($slipTypesDefinition as $targetName => $slip): ?>
    <?php if ($slip['enabled']): ?>
    const <?= esc(str_replace('-', '_', $targetName)) // JavaScript変数名用にハイフンをアンダースコアに変換 ?>Form = document.getElementById('<?= esc($slip['form_id']) ?>');
    if (<?= esc(str_replace('-', '_', $targetName)) ?>Form) {
        <?= esc(str_replace('-', '_', $targetName)) ?>Form.addEventListener('submit', function(event) {
            const statusDiv = document.getElementById('<?= esc($slip['status_div_id']) ?>');
            const submitButton = <?= esc(str_replace('-', '_', $targetName)) ?>Form.querySelector('button[type="submit"]');
            const fileInput = document.getElementById('<?= esc($slip['file_input_id']) ?>');

            // クライアントサイドの簡易バリデーション (ファイルが選択されているか)
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                if (statusDiv) {
                    statusDiv.innerHTML = '<span class="text-danger">ファイルを選択してください。</span>';
                }
                event.preventDefault(); 
                return;
            }

            if (statusDiv) {
                statusDiv.innerHTML = '<span class="processing-message"><i class="bi bi-hourglass-split"></i> アップロード中...キューに追加しています...</span>';
            }
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 処理中...';
            }
        });
    }
    <?php endif; ?>
    <?php endforeach; ?>
});
</script>
<?= $this->endSection() ?>