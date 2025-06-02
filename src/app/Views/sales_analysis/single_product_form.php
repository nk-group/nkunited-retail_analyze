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
        <h1><i class="bi bi-search me-3"></i>商品販売分析システム</h1>
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
                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#makerReferenceModal">
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
                    <button type="button" class="btn btn-outline-secondary" id="productSearchBtn" disabled>
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

<!-- メーカー参照モーダル -->
<div class="modal fade" id="makerReferenceModal" tabindex="-1" aria-labelledby="makerReferenceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="makerReferenceModalLabel">
                    <i class="bi bi-building me-2"></i>メーカー選択
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- 検索フォーム -->
                <div class="modal-search-form" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="modal_search_keyword" class="form-label">検索キーワード</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="modal_search_keyword" 
                                   placeholder="メーカーコードまたは名称を入力">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" class="btn btn-primary w-100" id="btn_modal_search">
                                <i class="bi bi-search me-2"></i>検索
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 選択されたメーカー情報表示 -->
                <div id="selected_maker_info" class="selected-maker-info" style="display: none; background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border: 2px solid #2196f3; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                    <div class="info-header" style="font-weight: bold; color: #1976d2; margin-bottom: 8px;">
                        <i class="bi bi-check-circle-fill me-2"></i>選択中のメーカー
                    </div>
                    <div class="info-content" style="color: #0d47a1; font-size: 0.95rem;">
                        <strong>コード:</strong> <span id="selected_code">-</span><br>
                        <strong>名称:</strong> <span id="selected_name">-</span>
                    </div>
                </div>

                <!-- 検索結果情報 -->
                <div id="search_results_info" class="search-results-info" style="display: none; background: #e9ecef; padding: 8px 15px; border-radius: 4px; margin-bottom: 15px; font-size: 0.9rem; color: #495057;">
                    <i class="bi bi-info-circle me-2"></i>
                    <span id="results_count_text">-</span>
                </div>

                <!-- 検索結果テーブル -->
                <div class="maker-reference-table-container" style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px;">
                    <table class="table table-hover maker-reference-table" style="font-size: 0.9rem; margin-bottom: 0;">
                        <thead class="table-light" style="position: sticky; top: 0; background-color: #f8f9fa; z-index: 10;">
                            <tr>
                                <th width="30%">メーカーコード</th>
                                <th width="70%">メーカー名</th>
                            </tr>
                        </thead>
                        <tbody id="maker_search_results">
                            <!-- Ajax で結果を表示 -->
                        </tbody>
                    </table>
                </div>

                <!-- ページング -->
                <div id="modal_pagination" class="modal-pagination" style="display: none; justify-content: center; align-items: center; gap: 10px; margin-top: 15px; padding: 10px 0; border-top: 1px solid #dee2e6;">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn_prev_page" disabled>
                        <i class="bi bi-chevron-left"></i> 前へ
                    </button>
                    <span class="page-info" id="page_info" style="color: #6c757d; font-size: 0.9rem; margin: 0 10px;">1 / 1</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn_next_page" disabled>
                        次へ <i class="bi bi-chevron-right"></i>
                    </button>
                </div>

                <!-- ローディング表示 -->
                <div id="modal_loading" class="text-center py-4" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">検索中...</span>
                    </div>
                    <div class="mt-2">検索中...</div>
                </div>

                <!-- 結果なし表示 -->
                <div id="modal_no_results" class="text-center py-4 text-muted" style="display: none;">
                    <i class="bi bi-search me-2"></i>
                    該当するメーカーが見つかりませんでした。
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>キャンセル
                </button>
                <button type="button" class="btn btn-primary" id="btn_select_maker" disabled>
                    <i class="bi bi-check-circle me-2"></i>選択
                </button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// === JavaScript テスト開始 ===
console.log('=== JavaScript読み込みテスト ===');
console.log('現在の時刻:', new Date());
console.log('jQuery利用可能:', typeof $ !== 'undefined');
console.log('Bootstrap利用可能:', typeof bootstrap !== 'undefined');

document.addEventListener('DOMContentLoaded', function() {
    console.log('=== DOM読み込み完了 ===');
    
    // 重要な要素の存在確認
    const testElements = {
        'manufacturerCode': document.getElementById('manufacturerCode'),
        'makerReferenceModal': document.getElementById('makerReferenceModal'),
        'productNumber': document.getElementById('productNumber'),
        'executeBtn': document.getElementById('executeBtn')
    };
    
    console.log('=== DOM要素確認 ===');
    Object.keys(testElements).forEach(key => {
        console.log(`${key}:`, testElements[key] ? '✓ 存在' : '✗ 見つからない');
    });
    
    // PHPから生成されるURL確認
    const apiUrl = '<?= site_url('sales-analysis/search-makers') ?>';
    console.log('API URL:', apiUrl);
    
    // 基本的なクリックテスト
    const manufacturerCodeInput = document.getElementById('manufacturerCode');
    if (manufacturerCodeInput) {
        console.log('メーカーコード入力フィールドが見つかりました');
        
        manufacturerCodeInput.addEventListener('input', function() {
            console.log('メーカーコード入力:', this.value);
            handleManufacturerCodeInput(this.value.trim());
        });
    }

    // メーカーコード入力処理
    function handleManufacturerCodeInput(code) {
        if (code) {
            // メーカー名を取得
            fetchMakerName(code);
        } else {
            // 空の場合はリセット
            resetManufacturerDisplay();
            resetSteps([2, 3, 4]);
        }
    }

    // メーカー名取得
    function fetchMakerName(code) {
        console.log('メーカー名取得開始:', code);
        
        const searchUrl = `<?= site_url('sales-analysis/search-makers') ?>?keyword=${encodeURIComponent(code)}&exact=1`;
        
        fetch(searchUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log('メーカー名取得レスポンス:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('メーカー名取得データ:', data);
            if (data.success && data.data.length > 0) {
                const maker = data.data[0];
                showManufacturerFound(maker);
                enableStep2();
            } else {
                showManufacturerNotFound();
                resetSteps([2, 3, 4]);
            }
        })
        .catch(error => {
            console.error('メーカー検索エラー:', error);
            showManufacturerError(error.message);
            resetSteps([2, 3, 4]);
        });
    }

    // メーカー情報表示（見つかった場合）
    function showManufacturerFound(maker) {
        const nameElement = document.getElementById('manufacturerName');
        const messageElement = document.getElementById('manufacturer-message');
        
        nameElement.textContent = maker.manufacturer_name;
        nameElement.classList.remove('empty');
        messageElement.innerHTML = '<div class="success-message"><i class="bi bi-check-circle"></i>メーカー情報が確認されました</div>';
        
        updateStepStatus(1, 'completed');
    }

    // メーカー情報表示（見つからない場合）
    function showManufacturerNotFound() {
        const nameElement = document.getElementById('manufacturerName');
        const messageElement = document.getElementById('manufacturer-message');
        
        nameElement.textContent = 'メーカーが見つかりません';
        nameElement.classList.add('empty');
        messageElement.innerHTML = '<div class="text-danger"><i class="bi bi-exclamation-triangle"></i>該当するメーカーが見つかりません</div>';
        
        updateStepStatus(1, 'disabled');
    }

    // メーカー情報表示（エラーの場合）
    function showManufacturerError(errorMessage) {
        const nameElement = document.getElementById('manufacturerName');
        const messageElement = document.getElementById('manufacturer-message');
        
        nameElement.textContent = 'エラーが発生しました';
        nameElement.classList.add('empty');
        messageElement.innerHTML = `<div class="text-danger"><i class="bi bi-exclamation-triangle"></i>エラー: ${errorMessage}</div>`;
        
        updateStepStatus(1, 'disabled');
    }

    // メーカー表示リセット
    function resetManufacturerDisplay() {
        const nameElement = document.getElementById('manufacturerName');
        const messageElement = document.getElementById('manufacturer-message');
        
        nameElement.textContent = 'メーカーコードを入力してください';
        nameElement.classList.add('empty');
        messageElement.innerHTML = '';
        
        updateStepStatus(1, 'disabled');
    }

    // Step2を有効化
    function enableStep2() {
        updateStepStatus(2, '');
        document.getElementById('productNumber').disabled = false;
        document.getElementById('productSearchBtn').disabled = false;
    }

    // Step管理関数
    function updateStepStatus(stepNumber, status) {
        const stepElement = document.getElementById(`step${stepNumber}-number`);
        stepElement.classList.remove('completed', 'disabled');
        if (status === 'completed') {
            stepElement.classList.add('completed');
        } else if (status === 'disabled') {
            stepElement.classList.add('disabled');
        }
    }

    // Step リセット
    function resetSteps(steps) {
        steps.forEach(step => {
            if (step === 2) {
                updateStepStatus(2, 'disabled');
                document.getElementById('productNumber').disabled = true;
                document.getElementById('productNumber').value = '';
                document.getElementById('productSearchBtn').disabled = true;
                document.getElementById('product-message').innerHTML = '';
            } else if (step === 3) {
                updateStepStatus(3, 'disabled');
                document.getElementById('productList').style.display = 'none';
                document.getElementById('targetProducts').style.display = 'none';
                document.getElementById('productName').value = '';
            } else if (step === 4) {
                updateStepStatus(4, 'disabled');
                document.getElementById('executeBtn').disabled = true;
            }
        });
    }
    
    // モーダル要素のテスト
    const makerModal = document.getElementById('makerReferenceModal');
    if (makerModal) {
        console.log('メーカーモーダル要素が見つかりました');
        
        // 参照ボタンのテスト
        const referenceButtons = document.querySelectorAll('[data-bs-target="#makerReferenceModal"]');
        console.log('参照ボタンの数:', referenceButtons.length);
        
        referenceButtons.forEach((btn, index) => {
            console.log(`参照ボタン ${index + 1}:`, btn);
            btn.addEventListener('click', function() {
                console.log(`参照ボタン ${index + 1} がクリックされました`);
            });
        });
        
        // Bootstrap モーダルイベントのテスト
        try {
            makerModal.addEventListener('show.bs.modal', function() {
                console.log('✓ モーダル表示イベント発火');
            });
            
            makerModal.addEventListener('shown.bs.modal', function() {
                console.log('✓ モーダル表示完了イベント発火');
            });
        } catch (error) {
            console.error('モーダルイベント設定エラー:', error);
        }
    } else {
        console.error('✗ メーカーモーダル要素が見つかりません');
    }
    
    // 簡単なAPIテスト関数を定義
    window.testAPI = function() {
        console.log('=== API テスト開始 ===');
        
        fetch(apiUrl + '?test=1', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log('API レスポンス:', response.status, response.statusText);
            return response.text();
        })
        .then(data => {
            console.log('API データ:', data);
        })
        .catch(error => {
            console.error('API エラー:', error);
        });
    };
    
    // === メーカー参照機能の実装 ===
    let selectedMaker = null;
    let currentPage = 1;
    let currentKeyword = '';
    let totalPages = 1;
    let currentManufacturerCode = '';
    let currentProductNumber = '';

    // モーダル表示時の処理
    if (makerModal) {
        makerModal.addEventListener('show.bs.modal', function() {
            console.log('✓ メーカーモーダルが開かれました');
            initializeMakerModal();
        });
    }

    // モーダル初期化
    function initializeMakerModal() {
        document.getElementById('modal_search_keyword').value = '';
        document.getElementById('btn_select_maker').disabled = true;
        clearSelectedMakerInfo();
        hideAllModalElements();
        
        // 初期検索実行（全件表示）
        searchMakersInModal('', 1);
    }

    // UI要素の非表示
    function hideAllModalElements() {
        document.getElementById('search_results_info').style.display = 'none';
        document.getElementById('modal_pagination').style.display = 'none';
        document.getElementById('modal_no_results').style.display = 'none';
        document.getElementById('modal_loading').style.display = 'none';
        document.getElementById('maker_search_results').innerHTML = '';
    }

    // 検索ボタンのイベント
    document.getElementById('btn_modal_search').addEventListener('click', function() {
        const keyword = document.getElementById('modal_search_keyword').value.trim();
        console.log('検索ボタンクリック。キーワード:', keyword);
        searchMakersInModal(keyword, 1);
    });

    // Enterキーでの検索
    document.getElementById('modal_search_keyword').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const keyword = this.value.trim();
            console.log('Enterキー検索。キーワード:', keyword);
            searchMakersInModal(keyword, 1);
        }
    });

    // メーカー検索実行
    function searchMakersInModal(keyword, page = 1) {
        console.log('メーカー検索開始:', keyword, 'ページ:', page);
        
        // ローディング表示
        document.getElementById('maker_search_results').innerHTML = '';
        document.getElementById('modal_loading').style.display = 'block';
        document.getElementById('modal_no_results').style.display = 'none';
        document.getElementById('search_results_info').style.display = 'none';
        document.getElementById('modal_pagination').style.display = 'none';
        document.getElementById('btn_select_maker').disabled = true;
        selectedMaker = null;

        currentKeyword = keyword;
        currentPage = page;

        const searchUrl = `<?= site_url('sales-analysis/search-makers') ?>?keyword=${encodeURIComponent(keyword)}&page=${page}`;
        console.log('リクエストURL:', searchUrl);

        fetch(searchUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log('レスポンス受信:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('レスポンスデータ:', data);
            document.getElementById('modal_loading').style.display = 'none';
            
            if (data.success && data.data.length > 0) {
                displayMakersInModal(data.data);
                updateResultsInfo(data.pagination, data.keyword);
                updatePagination(data.pagination);
            } else {
                document.getElementById('modal_no_results').style.display = 'block';
                if (data.pagination && data.pagination.total_count === 0) {
                    updateResultsInfo(data.pagination, data.keyword);
                }
            }
        })
        .catch(error => {
            console.error('メーカー検索エラー:', error);
            document.getElementById('modal_loading').style.display = 'none';
            document.getElementById('modal_no_results').style.display = 'block';
            document.getElementById('modal_no_results').innerHTML = `
                <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                検索中にエラーが発生しました: ${error.message}
            `;
        });
    }

    // 検索結果表示
    function displayMakersInModal(makers) {
        const resultsContainer = document.getElementById('maker_search_results');
        resultsContainer.innerHTML = '';
        
        makers.forEach(maker => {
            const row = document.createElement('tr');
            row.style.cursor = 'pointer';
            row.innerHTML = `
                <td>${escapeHtml(maker.manufacturer_code)}</td>
                <td>${escapeHtml(maker.manufacturer_name)}</td>
            `;
            
            row.addEventListener('click', function() {
                // 既存の選択を解除
                document.querySelectorAll('#maker_search_results tr').forEach(tr => {
                    tr.classList.remove('table-primary');
                });
                
                // 新しい選択を設定
                this.classList.add('table-primary');
                selectedMaker = maker;
                document.getElementById('btn_select_maker').disabled = false;
                
                updateSelectedMakerInfo(maker);
            });
            
            resultsContainer.appendChild(row);
        });
    }

    // 選択されたメーカー情報を更新
    function updateSelectedMakerInfo(maker) {
        document.getElementById('selected_code').textContent = maker.manufacturer_code;
        document.getElementById('selected_name').textContent = maker.manufacturer_name;
        document.getElementById('selected_maker_info').style.display = 'block';
    }

    // 選択情報をクリア
    function clearSelectedMakerInfo() {
        document.getElementById('selected_maker_info').style.display = 'none';
        document.getElementById('selected_code').textContent = '-';
        document.getElementById('selected_name').textContent = '-';
    }

    // 検索結果情報更新
    function updateResultsInfo(pagination, keyword) {
        if (!pagination) return;
        
        let infoText = '';
        if (keyword) {
            infoText = `「${keyword}」の検索結果: `;
        } else {
            infoText = '検索結果: ';
        }
        
        if (pagination.total_count === 0) {
            infoText += '該当なし';
        } else {
            infoText += `${pagination.total_count}件中 ${pagination.from}-${pagination.to}件目を表示`;
        }
        
        document.getElementById('results_count_text').textContent = infoText;
        document.getElementById('search_results_info').style.display = 'block';
    }

    // ページング更新
    function updatePagination(pagination) {
        if (!pagination || pagination.total_pages <= 1) {
            document.getElementById('modal_pagination').style.display = 'none';
            return;
        }
        
        currentPage = pagination.current_page;
        totalPages = pagination.total_pages;
        
        document.getElementById('page_info').textContent = `${currentPage} / ${totalPages}`;
        document.getElementById('btn_prev_page').disabled = !pagination.has_prev_page;
        document.getElementById('btn_next_page').disabled = !pagination.has_next_page;
        
        document.getElementById('modal_pagination').style.display = 'flex';
    }

    // ページングボタンのイベント
    document.getElementById('btn_prev_page').addEventListener('click', function() {
        if (currentPage > 1) {
            searchMakersInModal(currentKeyword, currentPage - 1);
        }
    });

    document.getElementById('btn_next_page').addEventListener('click', function() {
        if (currentPage < totalPages) {
            searchMakersInModal(currentKeyword, currentPage + 1);
        }
    });

    // メーカー選択ボタン
    document.getElementById('btn_select_maker').addEventListener('click', function() {
        if (selectedMaker) {
            console.log('選択されたメーカー:', selectedMaker);
            
            // メーカーコードを入力フィールドに設定
            document.getElementById('manufacturerCode').value = selectedMaker.manufacturer_code;
            currentManufacturerCode = selectedMaker.manufacturer_code;
            
            // メーカー情報を直接設定（API呼び出しを省略）
            showManufacturerFound(selectedMaker);
            enableStep2();
            
            // モーダルを閉じる
            const modal = bootstrap.Modal.getInstance(makerModal);
            if (modal) {
                modal.hide();
            }
            
            selectedMaker = null;
        }
    });

    // === 品番入力処理の実装 ===
    const productNumberInput = document.getElementById('productNumber');
    if (productNumberInput) {
        productNumberInput.addEventListener('input', function() {
            const number = this.value.trim();
            currentProductNumber = number;
            console.log('品番入力:', number);
            
            if (number && currentManufacturerCode) {
                // 品番名一覧を取得
                fetchProductList(currentManufacturerCode, number);
            } else {
                resetProductDisplay();
                resetSteps([3, 4]);
            }
        });
    }

    // 品番リスト取得
    function fetchProductList(manufacturerCode, productNumber) {
        console.log('品番リスト取得開始:', manufacturerCode, productNumber);
        
        const searchUrl = `<?= site_url('sales-analysis/search-products') ?>?manufacturer_code=${encodeURIComponent(manufacturerCode)}&keyword=${encodeURIComponent(productNumber)}`;
        
        fetch(searchUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('品番リスト取得データ:', data);
            if (data.success && data.data.length > 0) {
                showProductList(data.data);
                showProductMessage(`品番が確認されました（${data.data.length}件の商品グループが見つかりました）`, 'success');
                updateStepStatus(2, 'completed');
                updateStepStatus(3, '');
            } else {
                showProductMessage('該当する品番が見つかりません', 'error');
                resetSteps([3, 4]);
            }
        })
        .catch(error => {
            console.error('品番検索エラー:', error);
            showProductMessage('品番検索でエラーが発生しました', 'error');
            resetSteps([3, 4]);
        });
    }

    // 品番名一覧表示
    function showProductList(products) {
        const productList = document.getElementById('productList');
        productList.innerHTML = '';
        
        products.forEach(product => {
            const item = document.createElement('div');
            item.className = 'product-item';
            item.innerHTML = `
                <div class="product-info">
                    <div class="product-name">${escapeHtml(product.product_name)}</div>
                    <div class="product-details">シーズン: ${escapeHtml(product.season_code || '-')} | 定価: ¥${product.selling_price ? product.selling_price.toLocaleString() : '-'}</div>
                </div>
                <div class="jan-count">${product.jan_count || 0} SKU</div>
            `;
            
            item.addEventListener('click', function() {
                selectProduct(this, product);
            });
            
            productList.appendChild(item);
        });
        
        productList.style.display = 'block';
    }

    // 商品選択処理
    function selectProduct(element, product) {
        console.log('商品選択:', product);
        
        // 既存の選択を解除
        document.querySelectorAll('.product-item').forEach(item => {
            item.classList.remove('selected');
        });
        
        // 新しい選択を設定
        element.classList.add('selected');
        
        document.getElementById('productName').value = product.product_name;
        document.getElementById('selectedProductName').textContent = product.product_name;
        
        // Step 3を完了状態に
        updateStepStatus(3, 'completed');
        updateStepStatus(4, '');
        document.getElementById('executeBtn').disabled = false;
        
        // JANコード一覧を取得
        fetchTargetProducts(product);
        document.getElementById('targetProducts').style.display = 'block';
    }

    // 集計対象商品（JANコード）取得
    function fetchTargetProducts(product) {
        console.log('JANコード取得開始:', product);
        
        const searchUrl = `<?= site_url('sales-analysis/get-target-products') ?>?manufacturer_code=${encodeURIComponent(currentManufacturerCode)}&product_number=${encodeURIComponent(currentProductNumber)}&product_name=${encodeURIComponent(product.product_name)}`;
        
        fetch(searchUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('JANコード取得データ:', data);
            if (data.success && data.data.length > 0) {
                const janList = document.getElementById('janList');
                janList.innerHTML = data.data.map(item => 
                    `<div class="jan-item">${escapeHtml(item.jan_code)} (${escapeHtml(item.size_name || '-')})</div>`
                ).join('');
            }
        })
        .catch(error => {
            console.error('JANコード取得エラー:', error);
        });
    }

    // 品番メッセージ表示
    function showProductMessage(message, type) {
        const messageElement = document.getElementById('product-message');
        const className = type === 'success' ? 'success-message' : 'text-danger';
        const icon = type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle';
        
        messageElement.innerHTML = `<div class="${className}"><i class="bi ${icon}"></i>${message}</div>`;
    }

    // 品番表示リセット
    function resetProductDisplay() {
        document.getElementById('product-message').innerHTML = '';
        document.getElementById('productList').style.display = 'none';
        updateStepStatus(2, 'disabled');
    }

    // HTMLエスケープ関数
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
<?= $this->endSection() ?>