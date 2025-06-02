<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    body {
        background-color: #f8f9fa;
    }
    .step-container {
        display: flex;
        align-items: flex-start;
        margin-bottom: 30px;
        padding: 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    .step-number {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #007bff;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 18px;
        margin-right: 20px;
        flex-shrink: 0;
        transition: all 0.3s;
    }
    .step-number.completed {
        background: #28a745;
        transform: scale(1.1);
    }
    .step-number.disabled {
        background: #dee2e6;
        color: #6c757d;
    }
    .step-content {
        flex: 1;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-row {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }
    .form-control {
        border: 2px solid #dee2e6;
        border-radius: 8px;
        padding: 12px 15px;
        font-size: 14px;
        transition: all 0.3s;
        min-width: 200px;
    }
    .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
    }
    .btn {
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 500;
        transition: all 0.3s;
    }
    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,123,255,0.3);
    }
    .btn-execute {
        background: linear-gradient(135deg, #28a745, #20c997);
        border: none;
        color: white;
        padding: 15px 30px;
        font-size: 16px;
        font-weight: 600;
        border-radius: 10px;
        margin-top: 20px;
    }
    .btn-execute:hover:not(:disabled) {
        background: linear-gradient(135deg, #218838, #1eb88a);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40,167,69,0.3);
    }
    .display-value {
        display: inline-block;
        padding: 10px 15px;
        background: #e9ecef;
        border-radius: 8px;
        font-weight: 500;
        color: #495057;
        min-width: 200px;
        border: 2px solid transparent;
    }
    .display-value.empty {
        color: #6c757d;
        font-style: italic;
    }
    .product-list {
        border: 2px solid #dee2e6;
        border-radius: 8px;
        max-height: 300px;
        overflow-y: auto;
        background: white;
    }
    .product-item {
        padding: 15px 20px;
        border-bottom: 1px solid #dee2e6;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .product-item:hover {
        background-color: #f8f9fa;
    }
    .product-item.selected {
        background-color: #e3f2fd;
        border-left: 4px solid #007bff;
    }
    .product-item:last-child {
        border-bottom: none;
    }
    .product-info {
        flex: 1;
    }
    .product-name {
        font-weight: 600;
        color: #495057;
        font-size: 16px;
    }
    .product-details {
        font-size: 13px;
        color: #6c757d;
        margin-top: 4px;
    }
    .jan-count {
        background: #007bff;
        color: white;
        padding: 6px 12px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 600;
    }
    .target-products {
        background: #f8f9fa;
        border: 2px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
    }
    .target-products h4 {
        margin: 0 0 15px 0;
        color: #495057;
        font-size: 16px;
        font-weight: 600;
    }
    .jan-list {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .jan-item {
        background: white;
        border: 1px solid #dee2e6;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 13px;
        color: #495057;
        font-weight: 500;
    }
    .success-message {
        color: #28a745;
        font-size: 14px;
        margin-top: 8px;
        font-weight: 500;
    }
    .success-message i {
        margin-right: 5px;
    }
    .loading {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #007bff;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-left: 8px;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .header-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    .header-section h1 {
        margin: 0 0 10px 0;
        font-size: 28px;
        font-weight: 600;
    }
    .header-section p {
        margin: 0;
        opacity: 0.9;
        font-size: 16px;
    }
    .warning-box {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        border-radius: 6px;
        padding: 15px;
        margin-top: 20px;
    }
    .warning-box strong {
        color: #856404;
    }
    .warning-box ul {
        margin: 8px 0 0 20px;
        color: #856404;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid" style="max-width: 1200px;">
    <!-- ヘッダーセクション -->
    <div class="header-section">
        <h1><i class="bi bi-search me-3"></i>商品販売分析（単品）</h1>
        <p>単品売上分析・収益性分析・在庫処分判定</p>
    </div>

    <!-- エラー・成功メッセージ -->
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>入力エラー:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach (session()->getFlashdata('errors') as $error): ?>
                    <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?= form_open(site_url('sales-analysis/single-product/execute'), ['id' => 'singleProductForm']) ?>
    
    <!-- Step 1: メーカーコード入力 -->
    <div class="step-container" id="step1">
        <div class="step-number" id="step1-number">1</div>
        <div class="step-content">
            <div class="form-group">
                <label class="form-label fw-bold">メーカーコード</label>
                <div class="form-row">
                    <input type="text" 
                           class="form-control" 
                           id="manufacturerCode" 
                           name="manufacturer_code"
                           value="<?= old('manufacturer_code') ?>"
                           placeholder="例: 0001" 
                           required>
                    <button type="button" class="btn btn-outline-secondary" onclick="openMakerSearch()">
                        <i class="bi bi-search me-2"></i>参照
                    </button>
                </div>
                <div class="form-row">
                    <span class="form-label">メーカー名:</span>
                    <div class="display-value empty" id="manufacturerName">メーカーコードを入力してください</div>
                </div>
                <div id="manufacturer-message"></div>
            </div>
        </div>
    </div>

    <!-- Step 2: 品番入力 -->
    <div class="step-container" id="step2">
        <div class="step-number disabled" id="step2-number">2</div>
        <div class="step-content">
            <div class="form-group">
                <label class="form-label fw-bold">メーカー品番</label>
                <div class="form-row">
                    <input type="text" 
                           class="form-control" 
                           id="productNumber" 
                           name="product_number"
                           value="<?= old('product_number') ?>"
                           placeholder="例: S-001" 
                           disabled>
                    <button type="button" class="btn btn-outline-secondary" id="productSearchBtn" onclick="openProductSearch()" disabled>
                        <i class="bi bi-search me-2"></i>参照
                    </button>
                </div>
                <div id="product-message"></div>
            </div>
        </div>
    </div>

    <!-- Step 3: 品番名選択 -->
    <div class="step-container" id="step3">
        <div class="step-number disabled" id="step3-number">3</div>
        <div class="step-content">
            <div class="form-group">
                <label class="form-label fw-bold">品番名一覧（グループ選択）</label>
                <div class="product-list" id="productList" style="display: none;">
                    <!-- Ajax で結果を表示 -->
                </div>
                
                <div class="target-products" id="targetProducts" style="display: none;">
                    <h4><i class="bi bi-box-seam me-2"></i>集計対象商品（選択中: <span id="selectedProductName">-</span>）</h4>
                    <div class="jan-list" id="janList">
                        <!-- Ajax で JANコード一覧を表示 -->
                    </div>
                </div>
                <input type="hidden" id="productName" name="product_name" value="<?= old('product_name') ?>">
            </div>
        </div>
    </div>

    <!-- Step 4: 集計実行 -->
    <div class="step-container" id="step4">
        <div class="step-number disabled" id="step4-number">4</div>
        <div class="step-content">
            <div class="form-group">
                <label class="form-label fw-bold">集計実行</label>
                <p style="color: #6c757d; margin-bottom: 15px;">
                    選択された商品の販売状況を集計します。品出し日から現在までの週別販売推移、原価回収率、在庫処分判定などを表示します。
                </p>
                <button type="submit" class="btn btn-execute" id="executeBtn" disabled>
                    <i class="bi bi-graph-up me-2"></i>販売分析を実行
                </button>
                
                <div class="warning-box">
                    <strong><i class="bi bi-exclamation-triangle me-2"></i>注意事項:</strong>
                    <ul>
                        <li>集計には数秒から数十秒かかる場合があります</li>
                        <li>品出し日が設定されていない商品は集計できません</li>
                        <li>仕入データまたは移動データがない場合はエラーになります</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?= form_close() ?>

    <div class="text-center mt-4">
        <a href="<?= site_url('sales-analysis') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>分析メニューに戻る
        </a>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let selectedProduct = null;
    let currentManufacturerCode = '';
    let currentProductNumber = '';

    // Step管理
    function updateStepStatus(stepNumber, status) {
        const stepElement = document.getElementById(`step${stepNumber}-number`);
        stepElement.classList.remove('completed', 'disabled');
        if (status === 'completed') {
            stepElement.classList.add('completed');
        } else if (status === 'disabled') {
            stepElement.classList.add('disabled');
        }
    }

    // メーカーコード入力処理
    document.getElementById('manufacturerCode').addEventListener('input', function() {
        const code = this.value.trim();
        currentManufacturerCode = code;
        
        if (code) {
            // メーカー名を取得（仮実装）
            setTimeout(() => {
                document.getElementById('manufacturerName').textContent = 'サンプル商事株式会社';
                document.getElementById('manufacturerName').classList.remove('empty');
                document.getElementById('manufacturer-message').innerHTML = '<div class="success-message"><i class="bi bi-check-circle"></i>メーカー情報が確認されました</div>';
                
                updateStepStatus(1, 'completed');
                
                // Step 2を有効化
                updateStepStatus(2, '');
                document.getElementById('productNumber').disabled = false;
                document.getElementById('productSearchBtn').disabled = false;
            }, 500);
        } else {
            document.getElementById('manufacturerName').textContent = 'メーカーコードを入力してください';
            document.getElementById('manufacturerName').classList.add('empty');
            document.getElementById('manufacturer-message').innerHTML = '';
            
            updateStepStatus(1, 'disabled');
            updateStepStatus(2, 'disabled');
            updateStepStatus(3, 'disabled');
            updateStepStatus(4, 'disabled');
            
            // 後続のStepをリセット
            resetSteps([2, 3, 4]);
        }
    });

    // 品番入力処理
    document.getElementById('productNumber').addEventListener('input', function() {
        const number = this.value.trim();
        currentProductNumber = number;
        
        if (number && currentManufacturerCode) {
            // 品番名一覧を取得（仮実装）
            setTimeout(() => {
                showProductList();
                document.getElementById('product-message').innerHTML = '<div class="success-message"><i class="bi bi-check-circle"></i>品番が確認されました（3件の商品グループが見つかりました）</div>';
                updateStepStatus(2, 'completed');
                updateStepStatus(3, '');
            }, 500);
        } else {
            document.getElementById('product-message').innerHTML = '';
            document.getElementById('productList').style.display = 'none';
            updateStepStatus(2, 'disabled');
            updateStepStatus(3, 'disabled');
            updateStepStatus(4, 'disabled');
            resetSteps([3, 4]);
        }
    });

    // 品番名一覧表示
    function showProductList() {
        const productList = document.getElementById('productList');
        productList.innerHTML = `
            <div class="product-item" onclick="selectProduct(this, 'tshirt')">
                <div class="product-info">
                    <div class="product-name">半袖Tシャツ</div>
                    <div class="product-details">シーズン: 2025SS | 定価: ¥1,800 | 廃盤予定: 2025-07-31</div>
                </div>
                <div class="jan-count">3 SKU</div>
            </div>
            <div class="product-item" onclick="selectProduct(this, 'cut')">
                <div class="product-info">
                    <div class="product-name">カットソー</div>
                    <div class="product-details">シーズン: 2025SS | 定価: ¥1,800 | 廃盤予定: 2025-07-31</div>
                </div>
                <div class="jan-count">3 SKU</div>
            </div>
            <div class="product-item" onclick="selectProduct(this, 'polo')">
                <div class="product-info">
                    <div class="product-name">ポロシャツ</div>
                    <div class="product-details">シーズン: 2025SS | 定価: ¥2,200 | 廃盤予定: 2025-07-31</div>
                </div>
                <div class="jan-count">3 SKU</div>
            </div>
        `;
        productList.style.display = 'block';
    }

    // 商品選択処理
    window.selectProduct = function(element, productType) {
        // 既存の選択を解除
        document.querySelectorAll('.product-item').forEach(item => {
            item.classList.remove('selected');
        });
        
        // 新しい選択を設定
        element.classList.add('selected');
        selectedProduct = productType;
        
        const productName = element.querySelector('.product-name').textContent;
        document.getElementById('productName').value = productName;
        document.getElementById('selectedProductName').textContent = productName;
        
        // Step 3を完了状態に
        updateStepStatus(3, 'completed');
        
        // Step 4を有効化
        updateStepStatus(4, '');
        document.getElementById('executeBtn').disabled = false;
        
        // 集計対象商品の表示を更新
        updateTargetProducts(productType);
        document.getElementById('targetProducts').style.display = 'block';
    };

    // 集計対象商品更新
    function updateTargetProducts(productType) {
        let janCodes;
        
        switch(productType) {
            case 'tshirt':
                janCodes = ['4912300000011 (S)', '4912300000022 (M)', '4912300000033 (L)'];
                break;
            case 'cut':
                janCodes = ['4912300200055 (S)', '4912300200066 (M)', '4912300200077 (L)'];
                break;
            case 'polo':
                janCodes = ['4912300300088 (S)', '4912300300099 (M)', '4912300300100 (L)'];
                break;
        }
        
        const janList = document.getElementById('janList');
        janList.innerHTML = janCodes.map(jan => `<div class="jan-item">${jan}</div>`).join('');
    }

    // Step リセット
    function resetSteps(steps) {
        steps.forEach(step => {
            if (step === 2) {
                document.getElementById('productNumber').disabled = true;
                document.getElementById('productNumber').value = '';
                document.getElementById('productSearchBtn').disabled = true;
                document.getElementById('product-message').innerHTML = '';
            } else if (step === 3) {
                document.getElementById('productList').style.display = 'none';
                document.getElementById('targetProducts').style.display = 'none';
                document.getElementById('productName').value = '';
            } else if (step === 4) {
                document.getElementById('executeBtn').disabled = true;
            }
        });
    }

    // メーカー検索モーダル（仮実装）
    window.openMakerSearch = function() {
        alert('メーカー検索モーダルを開きます\n（実装予定：既存のsales_analysis/index.phpのモーダルを活用）');
    };

    // 品番検索モーダル（仮実装）
    window.openProductSearch = function() {
        alert('品番検索モーダルを開きます\n（メーカーコード: ' + currentManufacturerCode + ' で商品マスタを検索）');
    };

    // フォーム送信処理
    document.getElementById('singleProductForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('executeBtn');
        const originalText = btn.innerHTML;
        
        // ローディング状態
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>集計中...';
        btn.disabled = true;
        
        // 実際の処理はサーバーサイドで行われるため、ここでは見た目の変更のみ
    });

    // 初期値が設定されている場合の処理
    const initialManufacturerCode = document.getElementById('manufacturerCode').value;
    if (initialManufacturerCode) {
        document.getElementById('manufacturerCode').dispatchEvent(new Event('input'));
    }
});
</script>
<?= $this->endSection() ?>