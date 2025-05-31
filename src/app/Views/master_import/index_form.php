<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    /* 既存のスタイルはそのまま利用 */
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
    .form-control.is-invalid, .form-select.is-invalid {
        border-color: #dc3545;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?= esc($pageTitle ?? '各種マスタ取込') ?></h2>
        <?php /* <a href="#" class="btn btn-outline-secondary"><i class="bi bi-info-circle"></i> 取込ヘルプ(仮)</a> */ ?>
    </div>

    <?php
    // 各マスタの情報を配列で定義
    $masterTypes = [
        'product_master' => [
            'title' => '商品マスタ取込',
            'icon' => 'bi-box-seam',
            'form_id' => 'productImportForm',
            'file_input_id' => 'product_master_file',
            'file_input_name' => 'master_file', // 全マスタで共通のファイル入力名
            'status_div_id' => 'productMasterStatus',
            'action_route' => 'product_master_import_process', // ルート名 (masters/import/product)
            'flash_success_key' => 'success_product_master',
            'flash_error_key' => 'error_product_master',
            'notes' => 'ファイル形式例: 1行目ヘッダー (JANコード, 品番, 品名, 価格等)、2行目以降データ。',
            'enabled' => true 
        ],
        'department_master' => [
            'title' => '部門マスタ取込',
            'icon' => 'bi-diagram-3',
            'form_id' => 'departmentImportForm',
            'file_input_id' => 'department_master_file',
            'file_input_name' => 'master_file',
            'status_div_id' => 'departmentMasterStatus',
            'action_route' => 'department_master_import_process', 
            'flash_success_key' => 'success_department_master',
            'flash_error_key' => 'error_department_master',
            'notes' => 'ファイル形式: (準備中)',
            'enabled' => false 
        ],
        'manufacturer_master' => [
            'title' => 'メーカーマスタ取込',
            'icon' => 'bi-building',
            'form_id' => 'manufacturerImportForm',
            'file_input_id' => 'manufacturer_master_file',
            'file_input_name' => 'master_file',
            'status_div_id' => 'manufacturerMasterStatus',
            'action_route' => 'manufacturer_master_import_process', 
            'flash_success_key' => 'success_manufacturer_master',
            'flash_error_key' => 'error_manufacturer_master',
            'notes' => 'ファイル形式: (準備中)',
            'enabled' => true
        ],
        'supplier_master' => [
            'title' => '仕入先マスタ取込',
            'icon' => 'bi-truck',
            'form_id' => 'supplierImportForm',
            'file_input_id' => 'supplier_master_file',
            'file_input_name' => 'master_file',
            'status_div_id' => 'supplierMasterStatus',
            'action_route' => 'supplier_master_import_process', 
            'flash_success_key' => 'success_supplier_master',
            'flash_error_key' => 'error_supplier_master',
            'notes' => 'ファイル形式: (準備中)',
            'enabled' => false
        ],
    ];
    ?>

    <?php foreach ($masterTypes as $targetName => $master): ?>
    <section id="<?= esc($targetName) ?>-section" class="import-section shadow-sm <?= !$master['enabled'] ? 'bg-light' : '' ?>">
        <h4><i class="bi <?= esc($master['icon']) ?>"></i> <?= esc($master['title']) ?></h4>
        
        <?php $successMessage = session()->getFlashdata($master['flash_success_key']); ?>
        <?php if (isset($successMessage) && !empty($successMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $successMessage; // メッセージにHTMLが含まれる可能性を考慮 (コントローラでエスケープ制御) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php $errorMessage = session()->getFlashdata($master['flash_error_key']); ?>
        <?php if (isset($errorMessage) && !empty($errorMessage)): ?>
            <div class="alert alert-danger" role="alert">
                <?= esc($errorMessage); // エラーメッセージは念のためesc ?>
            </div>
        <?php endif; ?>

        <?= form_open_multipart(route_to($master['action_route']), ['id' => $master['form_id']]) ?>
            <?= csrf_field() ?>
            <input type="hidden" name="target_data_name" value="<?= esc($targetName) ?>">
            
            <div class="mb-3">
                <label for="<?= esc($master['file_input_id']) ?>" class="form-label fw-bold">取込ファイル選択 (Excel: .xlsx, .xls)</label>
                <input class="form-control <?= (isset($errorMessage) && $master['enabled'] && (strpos($errorMessage, '選択してください') !== false || strpos($errorMessage, 'Excelファイル') !== false || strpos($errorMessage, 'サイズが大きすぎます') !== false || strpos($errorMessage, 'ファイルの保存処理中にエラー') !== false )) ? 'is-invalid' : '' ?>" 
                       type="file" 
                       id="<?= esc($master['file_input_id']) ?>" 
                       name="<?= esc($master['file_input_name']) ?>" 
                       <?= !$master['enabled'] ? 'disabled' : 'required' ?>
                       accept=".xlsx,.xls">
                <?php if (isset($errorMessage) && $master['enabled']): ?>
                    <div class="invalid-feedback"> {/* is-invalid クラスだけでは表示されないことがあるため、表示条件と内容をここに集約 */}
                         <?= esc($errorMessage) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4">
                <button type="submit" class="btn <?= $master['enabled'] ? 'btn-primary' : 'btn-secondary' ?> btn-lg" <?= !$master['enabled'] ? 'disabled' : '' ?>>
                    <i class="bi <?= esc($master['icon']) ?>"></i> <?= esc(str_replace('取込', ' 取込実行', $master['title'])) ?>
                </button>
                <div id="<?= esc($master['status_div_id']) ?>" class="status-message-placeholder">
                    <?php if ($master['enabled']): ?>
                        <small class="text-muted">アップロード後、バックグラウンドで処理されます。</small>
                    <?php else: ?>
                        <small class="text-muted">準備中</small>
                    <?php endif; ?>
                </div>
            </div>
        <?= form_close() ?>
        <?php if ($master['enabled']): ?>
        <div class="mt-3">
            <small class="form-text text-muted">
                <strong><?= esc($master['notes']) ?></strong>
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
    <?php foreach ($masterTypes as $targetName => $master): ?>
    <?php if ($master['enabled']): ?>
    const <?= esc(str_replace('-', '_', $targetName)) // formId用にハイフンをアンダースコアに変換 ?>Form = document.getElementById('<?= esc($master['form_id']) ?>');
    if (<?= esc(str_replace('-', '_', $targetName)) ?>Form) {
        <?= esc(str_replace('-', '_', $targetName)) ?>Form.addEventListener('submit', function(event) {
            const statusDiv = document.getElementById('<?= esc($master['status_div_id']) ?>');
            const submitButton = <?= esc(str_replace('-', '_', $targetName)) ?>Form.querySelector('button[type="submit"]');
            const fileInput = document.getElementById('<?= esc($master['file_input_id']) ?>');

            // クライアントサイドの簡易バリデーション
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