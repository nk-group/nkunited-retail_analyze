<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container-fluid sales-analysis" style="max-width: 1200px;">
    <!-- ヘッダーセクション -->
    <div class="header-section">
        <h1 class="page-title"><i class="bi bi-search me-3"></i>商品販売分析システム</h1>
        <p class="page-subtitle">単品売上分析・収益性分析・在庫処分判定</p>
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
                    <button type="button" 
                            class="btn btn-outline-secondary" 
                            id="productSearchBtn" 
                            data-bs-toggle="modal" 
                            data-bs-target="#productReferenceModal"
                            disabled>
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

    <!-- URLコピー機能 -->
    <div class="url-copy-section" id="urlCopySection" style="display: none;">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-link me-2"></i>分析結果URLの生成</h5>
            </div>
            <div class="card-body">
                <p class="card-text">
                    この分析条件で直接アクセスできるURLを生成できます。ブックマークや共有にご利用ください。
                </p>
                <div class="input-group">
                    <input type="text" class="form-control" id="quickAnalysisUrl" readonly>
                    <button type="button" class="btn btn-outline-primary" id="copyUrlBtn">
                        <i class="bi bi-clipboard me-2"></i>URLをコピー
                    </button>
                </div>
                <small class="text-muted mt-2 d-block">
                    このURLを使用すると、フォーム入力を省略して直接分析結果を表示できます。
                </small>
            </div>
        </div>
    </div>

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
                <div class="modal-search-form">
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
                <div id="selected_maker_info" class="selected-maker-info" style="display: none;">
                    <div class="info-header">
                        <i class="bi bi-check-circle-fill me-2"></i>選択中のメーカー
                    </div>
                    <div class="info-content">
                        <strong>コード:</strong> <span id="selected_code">-</span><br>
                        <strong>名称:</strong> <span id="selected_name">-</span>
                    </div>
                </div>

                <!-- 検索結果情報 -->
                <div id="search_results_info" class="search-results-info" style="display: none;">
                    <i class="bi bi-info-circle me-2"></i>
                    <span id="results_count_text">-</span>
                </div>

                <!-- 検索結果テーブル -->
                <div class="maker-reference-table-container">
                    <table class="table table-hover maker-reference-table">
                        <thead class="table-light">
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
                <div id="modal_pagination" class="modal-pagination" style="display: none;">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn_prev_page" disabled>
                        <i class="bi bi-chevron-left"></i> 前へ
                    </button>
                    <span class="page-info" id="page_info">1 / 1</span>
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

<!-- 品番参照モーダル -->
<div class="modal fade" id="productReferenceModal" tabindex="-1" aria-labelledby="productReferenceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productReferenceModalLabel">
                    <i class="bi bi-box-seam me-2"></i>品番選択
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- 現在選択中のメーカー情報 -->
                <div class="current-maker-info">
                    <div class="info-header">
                        <i class="bi bi-building-fill me-2"></i>対象メーカー
                    </div>
                    <div class="info-content">
                        <strong>コード:</strong> <span id="current_maker_code">-</span><br>
                        <strong>名称:</strong> <span id="current_maker_name">-</span>
                    </div>
                </div>

                <!-- 検索フォーム -->
                <div class="product-search-form">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="product_search_keyword" class="form-label">品番・品名検索</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="product_search_keyword" 
                                   placeholder="品番または品番名を入力（例: S-001, カットソー）">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" class="btn btn-primary w-100" id="btn_product_search">
                                <i class="bi bi-search me-2"></i>検索
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 選択された品番情報表示 -->
                <div id="selected_product_info" class="selected-product-info" style="display: none;">
                    <div class="info-header">
                        <i class="bi bi-check-circle-fill me-2"></i>選択中の品番
                    </div>
                    <div class="info-content">
                        <strong>品番:</strong> <span id="selected_product_number">-</span><br>
                        <strong>品名:</strong> <span id="selected_product_name">-</span><br>
                        <strong>シーズン:</strong> <span id="selected_season_code">-</span><br>
                        <strong>価格:</strong> <span id="selected_selling_price">-</span><br>
                        <strong>SKU数:</strong> <span id="selected_jan_count">-</span>
                    </div>
                </div>

                <!-- 検索結果情報 -->
                <div id="product_search_results_info" class="search-results-info" style="display: none;">
                    <i class="bi bi-info-circle me-2"></i>
                    <span id="product_results_count_text">-</span>
                </div>

                <!-- 検索結果テーブル -->
                <div class="product-reference-table-container">
                    <table class="table table-hover product-reference-table">
                        <thead class="table-light">
                            <tr>
                                <th width="15%">品番</th>
                                <th width="25%">品名</th>
                                <th width="12%">シーズン</th>
                                <th width="15%">価格</th>
                                <th width="10%">SKU数</th>
                                <th width="23%">廃盤予定日</th>
                            </tr>
                        </thead>
                        <tbody id="product_search_results">
                            <!-- Ajax で結果を表示 -->
                        </tbody>
                    </table>
                </div>

                <!-- ページング -->
                <div id="product_modal_pagination" class="modal-pagination" style="display: none;">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn_product_prev_page" disabled>
                        <i class="bi bi-chevron-left"></i> 前へ
                    </button>
                    <span class="page-info" id="product_page_info">1 / 1</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn_product_next_page" disabled>
                        次へ <i class="bi bi-chevron-right"></i>
                    </button>
                </div>

                <!-- ローディング表示 -->
                <div id="product_modal_loading" class="text-center py-4" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">検索中...</span>
                    </div>
                    <div class="mt-2">検索中...</div>
                </div>

                <!-- 結果なし表示 -->
                <div id="product_modal_no_results" class="text-center py-4 text-muted" style="display: none;">
                    <i class="bi bi-search me-2"></i>
                    該当する品番が見つかりませんでした。
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>キャンセル
                </button>
                <button type="button" class="btn btn-primary" id="btn_select_product" disabled>
                    <i class="bi bi-check-circle me-2"></i>選択
                </button>
            </div>
        </div>
    </div>
</div>

<?php
// CSS読み込みフラグとbodyクラスを設定
$this->setData([
    'useSalesAnalysisCSS' => true,
    'bodyClass' => 'sales-analysis'
]);
?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== 商品販売分析 単品分析フォーム初期化 ===');
    
    // URL設定の取得
    const body = document.body;
    const baseUrl = body.dataset.baseUrl || '';
    const siteUrl = body.dataset.siteUrl || '';
    const apiBase = body.dataset.apiBase || '';
    
    if (!baseUrl || !siteUrl || !apiBase) {
        console.error('URL設定が不正です:', { baseUrl, siteUrl, apiBase });
        return;
    }
    
    console.log('URL設定完了:', { baseUrl, siteUrl, apiBase });
    
    // 重要な要素の存在確認
    const elements = {
        manufacturerCode: document.getElementById('manufacturerCode'),
        manufacturerName: document.getElementById('manufacturerName'),
        productNumber: document.getElementById('productNumber'),
        productList: document.getElementById('productList'),
        targetProducts: document.getElementById('targetProducts'),
        executeBtn: document.getElementById('executeBtn'),
        makerModal: document.getElementById('makerReferenceModal'),
        urlCopySection: document.getElementById('urlCopySection'),
        quickAnalysisUrl: document.getElementById('quickAnalysisUrl'),
        copyUrlBtn: document.getElementById('copyUrlBtn')
    };
    
    // 必要な要素が存在するかチェック
    for (const [key, element] of Object.entries(elements)) {
        if (!element) {
            console.error(`必須要素が見つかりません: ${key}`);
            return;
        }
    }

    // グローバル変数
    let selectedMaker = null;
    let selectedProduct = null;
    let currentPage = 1;
    let currentKeyword = '';
    let totalPages = 1;
    let currentManufacturerCode = '';
    let currentProductNumber = '';
    let currentJanCodes = []; // JANコード配列

    // === Step 1: メーカーコード入力処理 ===
    elements.manufacturerCode.addEventListener('input', function() {
        const code = this.value.trim();
        currentManufacturerCode = code;
        console.log('メーカーコード入力:', code);
        
        if (code) {
            validateManufacturerCode(code);
        } else {
            resetManufacturerDisplay();
            resetSteps([2, 3, 4]);
            hideUrlCopySection();
        }
    });

    // メーカーコード検証
    function validateManufacturerCode(code) {
        console.log('メーカーコード検証開始:', code);
        
        const searchUrl = `${apiBase}/search-makers?keyword=${encodeURIComponent(code)}&exact=1`;
        
        fetch(searchUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('メーカー検証結果:', data);
            
            if (data.success && data.data.length > 0) {
                const maker = data.data[0];
                showManufacturerFound(maker);
                enableStep2();
            } else {
                showManufacturerNotFound();
                resetSteps([2, 3, 4]);
                hideUrlCopySection();
            }
        })
        .catch(error => {
            console.error('メーカー検証エラー:', error);
            showManufacturerError(error.message);
            resetSteps([2, 3, 4]);
            hideUrlCopySection();
        });
    }

    // === Step 2: 品番入力処理 ===
    elements.productNumber.addEventListener('input', function() {
        const number = this.value.trim();
        currentProductNumber = number;
        console.log('品番入力:', number);
        
        if (number && currentManufacturerCode) {
            validateProductNumber(currentManufacturerCode, number);
        } else {
            resetProductDisplay();
            resetSteps([3, 4]);
            hideUrlCopySection();
        }
    });

    // 品番存在確認
    function validateProductNumber(manufacturerCode, productNumber) {
        const validateUrl = `${apiBase}/validate-product-number?manufacturer_code=${encodeURIComponent(manufacturerCode)}&product_number=${encodeURIComponent(productNumber)}`;
        
        fetch(validateUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.exists) {
                // 品番が存在する場合、商品リストを取得
                fetchProductList(manufacturerCode, productNumber);
            } else {
                showProductMessage('該当する品番が見つかりません', 'error');
                resetSteps([3, 4]);
                hideUrlCopySection();
            }
        })
        .catch(error => {
            console.error('品番検証エラー:', error);
            showProductMessage('品番検証でエラーが発生しました', 'error');
            resetSteps([3, 4]);
            hideUrlCopySection();
        });
    }

    // 品番リスト取得
    function fetchProductList(manufacturerCode, productNumber) {
        console.log('品番リスト取得開始:', manufacturerCode, productNumber);
        
        const searchUrl = `${apiBase}/search-products?manufacturer_code=${encodeURIComponent(manufacturerCode)}&keyword=${encodeURIComponent(productNumber)}`;
        
        fetch(searchUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('品番リスト取得結果:', data);
            
            if (data.success && data.data.length > 0) {
                showProductList(data.data);
                showProductMessage(`品番が確認されました（${data.data.length}件の商品グループが見つかりました）`, 'success');
                updateStepStatus(2, 'completed');
                updateStepStatus(3, '');
            } else {
                showProductMessage('該当する品番が見つかりません', 'error');
                resetSteps([3, 4]);
                hideUrlCopySection();
            }
        })
        .catch(error => {
            console.error('品番リスト取得エラー:', error);
            showProductMessage(`品番検索でエラーが発生しました: ${error.message}`, 'error');
            resetSteps([3, 4]);
            hideUrlCopySection();
        });
    }

    // === メーカー表示関数群 ===
    function showManufacturerFound(maker) {
        elements.manufacturerName.textContent = maker.manufacturer_name;
        elements.manufacturerName.classList.remove('empty');
        
        const messageElement = document.getElementById('manufacturer-message');
        messageElement.innerHTML = '<div class="success-message"><i class="bi bi-check-circle"></i>メーカー情報が確認されました</div>';
        
        updateStepStatus(1, 'completed');
    }

    function showManufacturerNotFound() {
        elements.manufacturerName.textContent = 'メーカーが見つかりません';
        elements.manufacturerName.classList.add('empty');
        
        const messageElement = document.getElementById('manufacturer-message');
        messageElement.innerHTML = '<div class="text-danger"><i class="bi bi-exclamation-triangle"></i>該当するメーカーが見つかりません</div>';
        
        updateStepStatus(1, 'disabled');
    }

    function showManufacturerError(errorMessage) {
        elements.manufacturerName.textContent = 'エラーが発生しました';
        elements.manufacturerName.classList.add('empty');
        
        const messageElement = document.getElementById('manufacturer-message');
        messageElement.innerHTML = `<div class="text-danger"><i class="bi bi-exclamation-triangle"></i>エラー: ${errorMessage}</div>`;
        
        updateStepStatus(1, 'disabled');
    }

    function resetManufacturerDisplay() {
        elements.manufacturerName.textContent = 'メーカーコードを入力してください';
        elements.manufacturerName.classList.add('empty');
        
        const messageElement = document.getElementById('manufacturer-message');
        messageElement.innerHTML = '';
        
        updateStepStatus(1, 'disabled');
    }

    // === 商品表示関数群 ===
    function showProductList(products) {
        elements.productList.innerHTML = '';
        
        products.forEach(product => {
            const item = document.createElement('div');
            item.className = 'product-item';
            
            // 価格表示の改善
            let priceDisplay = '';
            if (product.min_price === product.max_price) {
                priceDisplay = `¥${product.selling_price.toLocaleString()}`;
            } else {
                priceDisplay = `¥${product.min_price.toLocaleString()} - ¥${product.max_price.toLocaleString()}`;
            }
            
            item.innerHTML = `
                <div class="product-info">
                    <div class="product-name">${escapeHtml(product.product_name)}</div>
                    <div class="product-details">
                        シーズン: ${escapeHtml(product.season_code || '-')} | 
                        価格: ${priceDisplay} | 
                        SKU数: ${product.jan_count}個
                    </div>
                </div>
                <div class="jan-count">${product.jan_count} SKU</div>
            `;
            
            item.addEventListener('click', function() {
                selectProduct(this, product);
            });
            
            elements.productList.appendChild(item);
        });
        
        elements.productList.style.display = 'block';
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
        selectedProduct = product;
        
        document.getElementById('productName').value = product.product_name;
        document.getElementById('selectedProductName').textContent = product.product_name;
        
        // Step 3を完了状態に
        updateStepStatus(3, 'completed');
        updateStepStatus(4, '');
        elements.executeBtn.disabled = false;
        
        // JANコード一覧を取得
        fetchTargetProducts(product);
        elements.targetProducts.style.display = 'block';
    }

    // 集計対象商品（JANコード）取得
    function fetchTargetProducts(product) {
        console.log('JANコード取得開始:', product);
        
        const searchUrl = `${apiBase}/get-target-products?manufacturer_code=${encodeURIComponent(currentManufacturerCode)}&product_number=${encodeURIComponent(currentProductNumber)}&product_name=${encodeURIComponent(product.product_name)}`;
        
        fetch(searchUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('JANコード取得結果:', data);
            
            if (data.success && data.data.length > 0) {
                currentJanCodes = data.data.map(item => item.jan_code);
                
                const janList = document.getElementById('janList');
                janList.innerHTML = data.data.map(item => {
                    let displayText = item.jan_code;
                    if (item.size_name && item.size_name !== 'F') {
                        displayText += ` (${item.size_name})`;
                    }
                    if (item.color_name && item.color_name !== '-') {
                        displayText += ` [${item.color_name}]`;
                    }
                    return `<div class="jan-item" data-jan-code="${escapeHtml(item.jan_code)}">${escapeHtml(displayText)}</div>`;
                }).join('');
                
                // URL生成機能を表示
                showUrlCopySection();
                
                // サマリー情報も表示
                if (data.summary) {
                    console.log('商品サマリー:', data.summary);
                }
            } else {
                const janList = document.getElementById('janList');
                janList.innerHTML = '<div class="text-muted">JANコードが見つかりません</div>';
                hideUrlCopySection();
            }
        })
        .catch(error => {
            console.error('JANコード取得エラー:', error);
            const janList = document.getElementById('janList');
            janList.innerHTML = '<div class="text-danger">JANコード取得エラー</div>';
            hideUrlCopySection();
        });
    }

    function showProductMessage(message, type) {
        const messageElement = document.getElementById('product-message');
        const className = type === 'success' ? 'success-message' : 'text-danger';
        const icon = type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle';
        
        messageElement.innerHTML = `<div class="${className}"><i class="bi ${icon}"></i> ${message}</div>`;
    }

    function resetProductDisplay() {
        document.getElementById('product-message').innerHTML = '';
        elements.productList.style.display = 'none';
        elements.targetProducts.style.display = 'none';
        updateStepStatus(2, 'disabled');
        hideUrlCopySection();
    }

    // === URLコピー機能 ===
    function showUrlCopySection() {
        if (currentJanCodes.length > 0) {
            const resultUrl = `${siteUrl}/sales-analysis/single-product/result`;
            const janCodesParam = currentJanCodes.join(',');
            const quickUrl = `${resultUrl}?jan_codes=${encodeURIComponent(janCodesParam)}`;
            
            elements.quickAnalysisUrl.value = quickUrl;
            elements.urlCopySection.style.display = 'block';
            elements.urlCopySection.classList.add('fade-in');
        }
    }

    function hideUrlCopySection() {
        elements.urlCopySection.style.display = 'none';
        elements.quickAnalysisUrl.value = '';
    }

    // URLコピーボタンイベント
    elements.copyUrlBtn.addEventListener('click', function() {
        elements.quickAnalysisUrl.select();
        elements.quickAnalysisUrl.setSelectionRange(0, 99999); // モバイル対応
        
        try {
            navigator.clipboard.writeText(elements.quickAnalysisUrl.value).then(function() {
                // コピー成功の表示
                const originalText = elements.copyUrlBtn.innerHTML;
                elements.copyUrlBtn.innerHTML = '<i class="bi bi-check me-2"></i>コピー完了';
                elements.copyUrlBtn.classList.remove('btn-outline-primary');
                elements.copyUrlBtn.classList.add('btn-success');
                
                setTimeout(function() {
                    elements.copyUrlBtn.innerHTML = originalText;
                    elements.copyUrlBtn.classList.remove('btn-success');
                    elements.copyUrlBtn.classList.add('btn-outline-primary');
                }, 2000);
            });
        } catch (err) {
            // フォールバック: execCommand使用
            document.execCommand('copy');
            console.log('URL copied using execCommand');
        }
    });

    // === Step管理関数 ===
    function enableStep2() {
        updateStepStatus(2, '');
        elements.productNumber.disabled = false;
        document.getElementById('productSearchBtn').disabled = false;
    }

    function updateStepStatus(stepNumber, status) {
        const stepElement = document.getElementById(`step${stepNumber}-number`);
        stepElement.classList.remove('completed', 'disabled');
        
        if (status === 'completed') {
            stepElement.classList.add('completed');
        } else if (status === 'disabled') {
            stepElement.classList.add('disabled');
        }
    }

    function resetSteps(steps) {
        steps.forEach(step => {
            if (step === 2) {
                updateStepStatus(2, 'disabled');
                elements.productNumber.disabled = true;
                elements.productNumber.value = '';
                document.getElementById('productSearchBtn').disabled = true;
                document.getElementById('product-message').innerHTML = '';
            } else if (step === 3) {
                updateStepStatus(3, 'disabled');
                elements.productList.style.display = 'none';
                elements.targetProducts.style.display = 'none';
                document.getElementById('productName').value = '';
                selectedProduct = null;
                currentJanCodes = [];
            } else if (step === 4) {
                updateStepStatus(4, 'disabled');
                elements.executeBtn.disabled = true;
            }
        });
    }

    // === メーカー参照モーダル処理 ===
    if (elements.makerModal) {
        elements.makerModal.addEventListener('show.bs.modal', function() {
            console.log('メーカーモーダル表示');
            initializeMakerModal();
        });
    }

    function initializeMakerModal() {
        document.getElementById('modal_search_keyword').value = '';
        document.getElementById('btn_select_maker').disabled = true;
        clearSelectedMakerInfo();
        hideAllModalElements();
        searchMakersInModal('', 1);
    }

    function hideAllModalElements() {
        const hideElements = [
            'search_results_info', 'modal_pagination', 
            'modal_no_results', 'modal_loading'
        ];
        hideElements.forEach(id => {
            const element = document.getElementById(id);
            if (element) element.style.display = 'none';
        });
        
        const resultsContainer = document.getElementById('maker_search_results');
        if (resultsContainer) resultsContainer.innerHTML = '';
    }

    // モーダル検索イベント
    const modalSearchBtn = document.getElementById('btn_modal_search');
    const modalSearchInput = document.getElementById('modal_search_keyword');
    
    if (modalSearchBtn) {
        modalSearchBtn.addEventListener('click', function() {
            const keyword = modalSearchInput.value.trim();
            searchMakersInModal(keyword, 1);
        });
    }
    
    if (modalSearchInput) {
        modalSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const keyword = this.value.trim();
                searchMakersInModal(keyword, 1);
            }
        });
    }

    // メーカー検索実行（モーダル用）
    function searchMakersInModal(keyword, page = 1) {
        console.log('モーダルメーカー検索:', keyword, 'ページ:', page);
        
        showModalLoading();
        currentKeyword = keyword;
        currentPage = page;

        const searchUrl = `${apiBase}/search-makers?keyword=${encodeURIComponent(keyword)}&page=${page}`;

        fetch(searchUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            hideModalLoading();
            
            if (data.success && data.data.length > 0) {
                displayMakersInModal(data.data);
                updateResultsInfo(data.pagination, data.keyword);
                updatePagination(data.pagination);
            } else {
                showNoResults();
                if (data.pagination && data.pagination.total_count === 0) {
                    updateResultsInfo(data.pagination, data.keyword);
                }
            }
        })
        .catch(error => {
            console.error('モーダルメーカー検索エラー:', error);
            hideModalLoading();
            showModalError(error.message);
        });
    }

    function showModalLoading() {
        document.getElementById('modal_loading').style.display = 'block';
        document.getElementById('modal_no_results').style.display = 'none';
        document.getElementById('search_results_info').style.display = 'none';
        document.getElementById('modal_pagination').style.display = 'none';
    }

    function hideModalLoading() {
        document.getElementById('modal_loading').style.display = 'none';
    }

    function showNoResults() {
        document.getElementById('modal_no_results').style.display = 'block';
    }

    function showModalError(message) {
        const noResultsElement = document.getElementById('modal_no_results');
        noResultsElement.style.display = 'block';
        noResultsElement.innerHTML = `
            <i class="bi bi-exclamation-triangle text-danger me-2"></i>
            検索中にエラーが発生しました: ${message}
        `;
    }

    // メーカー一覧表示（モーダル用）
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

    function updateSelectedMakerInfo(maker) {
        document.getElementById('selected_code').textContent = maker.manufacturer_code;
        document.getElementById('selected_name').textContent = maker.manufacturer_name;
        document.getElementById('selected_maker_info').style.display = 'block';
    }

    function clearSelectedMakerInfo() {
        document.getElementById('selected_maker_info').style.display = 'none';
        document.getElementById('selected_code').textContent = '-';
        document.getElementById('selected_name').textContent = '-';
    }

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

    // ページングボタンイベント
    const prevBtn = document.getElementById('btn_prev_page');
    const nextBtn = document.getElementById('btn_next_page');
    
    if (prevBtn) {
        prevBtn.addEventListener('click', function() {
            if (currentPage > 1) {
                searchMakersInModal(currentKeyword, currentPage - 1);
            }
        });
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', function() {
            if (currentPage < totalPages) {
                searchMakersInModal(currentKeyword, currentPage + 1);
            }
        });
    }

    // メーカー選択ボタン
    const selectMakerBtn = document.getElementById('btn_select_maker');
    if (selectMakerBtn) {
        selectMakerBtn.addEventListener('click', function() {
            if (selectedMaker) {
                console.log('選択されたメーカー:', selectedMaker);
                
                // メーカーコードを入力フィールドに設定
                elements.manufacturerCode.value = selectedMaker.manufacturer_code;
                currentManufacturerCode = selectedMaker.manufacturer_code;
                
                // メーカー情報を直接設定
                showManufacturerFound(selectedMaker);
                enableStep2();
                
                // モーダルを閉じる
                const modal = bootstrap.Modal.getInstance(elements.makerModal);
                if (modal) {
                    modal.hide();
                }
                
                selectedMaker = null;
            }
        });
    }

    // HTMLエスケープ関数
    function escapeHtml(text) {
        if (text == null) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // === 品番参照モーダル処理 ===
    const productModal = document.getElementById('productReferenceModal');
    let selectedProductFromModal = null;
    let currentProductPage = 1;
    let currentProductKeyword = '';
    let totalProductPages = 1;

    if (productModal) {
        productModal.addEventListener('show.bs.modal', function() {
            console.log('品番参照モーダル表示');
            initializeProductModal();
        });
    }

    function initializeProductModal() {
        // 現在のメーカー情報を表示
        document.getElementById('current_maker_code').textContent = currentManufacturerCode || '-';
        const makerName = elements.manufacturerName.textContent;
        document.getElementById('current_maker_name').textContent = makerName === 'メーカーコードを入力してください' ? '-' : makerName;
        
        // 検索フォームリセット
        document.getElementById('product_search_keyword').value = '';
        document.getElementById('btn_select_product').disabled = true;
        clearSelectedProductInfo();
        hideAllProductModalElements();
        
        // 現在のメーカーの全品番を検索（初期表示）
        if (currentManufacturerCode) {
            searchProductsInModal('', 1);
        }
    }

    function hideAllProductModalElements() {
        const hideElements = [
            'product_search_results_info', 'product_modal_pagination', 
            'product_modal_no_results', 'product_modal_loading'
        ];
        hideElements.forEach(id => {
            const element = document.getElementById(id);
            if (element) element.style.display = 'none';
        });
        
        const resultsContainer = document.getElementById('product_search_results');
        if (resultsContainer) resultsContainer.innerHTML = '';
    }

    // 品番検索イベント
    const productSearchBtn = document.getElementById('btn_product_search');
    const productSearchInput = document.getElementById('product_search_keyword');
    
    if (productSearchBtn) {
        productSearchBtn.addEventListener('click', function() {
            const keyword = productSearchInput.value.trim();
            searchProductsInModal(keyword, 1);
        });
    }
    
    if (productSearchInput) {
        productSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const keyword = this.value.trim();
                searchProductsInModal(keyword, 1);
            }
        });
    }

    // 品番検索実行（モーダル用）
    function searchProductsInModal(keyword, page = 1) {
        console.log('モーダル品番検索:', keyword, 'ページ:', page);
        
        if (!currentManufacturerCode) {
            showProductModalError('メーカーが選択されていません');
            return;
        }
        
        showProductModalLoading();
        currentProductKeyword = keyword;
        currentProductPage = page;

        const searchUrl = `${apiBase}/search-products?manufacturer_code=${encodeURIComponent(currentManufacturerCode)}&keyword=${encodeURIComponent(keyword)}&page=${page}`;

        fetch(searchUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            hideProductModalLoading();
            
            if (data.success && data.data.length > 0) {
                displayProductsInModal(data.data);
                updateProductResultsInfo(data.pagination, data.keyword);
                updateProductPagination(data.pagination);
            } else {
                showProductNoResults();
                if (data.pagination && data.pagination.total_count === 0) {
                    updateProductResultsInfo(data.pagination, data.keyword);
                }
            }
        })
        .catch(error => {
            console.error('モーダル品番検索エラー:', error);
            hideProductModalLoading();
            showProductModalError(error.message);
        });
    }

    function showProductModalLoading() {
        document.getElementById('product_modal_loading').style.display = 'block';
        document.getElementById('product_modal_no_results').style.display = 'none';
        document.getElementById('product_search_results_info').style.display = 'none';
        document.getElementById('product_modal_pagination').style.display = 'none';
    }

    function hideProductModalLoading() {
        document.getElementById('product_modal_loading').style.display = 'none';
    }

    function showProductNoResults() {
        document.getElementById('product_modal_no_results').style.display = 'block';
    }

    function showProductModalError(message) {
        const noResultsElement = document.getElementById('product_modal_no_results');
        noResultsElement.style.display = 'block';
        noResultsElement.innerHTML = `
            <i class="bi bi-exclamation-triangle text-danger me-2"></i>
            検索中にエラーが発生しました: ${message}
        `;
    }

    // 品番一覧表示（モーダル用）
    function displayProductsInModal(products) {
        const resultsContainer = document.getElementById('product_search_results');
        resultsContainer.innerHTML = '';
        
        products.forEach(product => {
            const row = document.createElement('tr');
            row.style.cursor = 'pointer';
            
            // 価格表示の改善
            let priceDisplay = '';
            if (product.min_price === product.max_price) {
                priceDisplay = `¥${product.selling_price.toLocaleString()}`;
            } else {
                priceDisplay = `¥${product.min_price.toLocaleString()} - ¥${product.max_price.toLocaleString()}`;
            }
            
            // 廃盤予定日の表示
            let deletionDate = '-';
            if (product.earliest_deletion_date) {
                const date = new Date(product.earliest_deletion_date);
                deletionDate = date.toLocaleDateString('ja-JP');
            }
            
            row.innerHTML = `
                <td>${escapeHtml(product.product_number)}</td>
                <td>${escapeHtml(product.product_name)}</td>
                <td>${escapeHtml(product.season_code || '-')}</td>
                <td>${priceDisplay}</td>
                <td>${product.jan_count}個</td>
                <td>${deletionDate}</td>
            `;
            
            row.addEventListener('click', function() {
                // 既存の選択を解除
                document.querySelectorAll('#product_search_results tr').forEach(tr => {
                    tr.classList.remove('table-primary');
                });
                
                // 新しい選択を設定
                this.classList.add('table-primary');
                selectedProductFromModal = product;
                document.getElementById('btn_select_product').disabled = false;
                updateSelectedProductInfo(product);
            });
            
            resultsContainer.appendChild(row);
        });
    }

    function updateSelectedProductInfo(product) {
        document.getElementById('selected_product_number').textContent = product.product_number;
        document.getElementById('selected_product_name').textContent = product.product_name;
        document.getElementById('selected_season_code').textContent = product.season_code || '-';
        
        // 価格表示
        let priceDisplay = '';
        if (product.min_price === product.max_price) {
            priceDisplay = `¥${product.selling_price.toLocaleString()}`;
        } else {
            priceDisplay = `¥${product.min_price.toLocaleString()} - ¥${product.max_price.toLocaleString()}`;
        }
        document.getElementById('selected_selling_price').textContent = priceDisplay;
        document.getElementById('selected_jan_count').textContent = `${product.jan_count}個`;
        
        document.getElementById('selected_product_info').style.display = 'block';
    }

    function clearSelectedProductInfo() {
        document.getElementById('selected_product_info').style.display = 'none';
        const fields = ['selected_product_number', 'selected_product_name', 'selected_season_code', 'selected_selling_price', 'selected_jan_count'];
        fields.forEach(field => {
            document.getElementById(field).textContent = '-';
        });
    }

    function updateProductResultsInfo(pagination, keyword) {
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
        
        document.getElementById('product_results_count_text').textContent = infoText;
        document.getElementById('product_search_results_info').style.display = 'block';
    }

    function updateProductPagination(pagination) {
        if (!pagination || pagination.total_pages <= 1) {
            document.getElementById('product_modal_pagination').style.display = 'none';
            return;
        }
        
        currentProductPage = pagination.current_page;
        totalProductPages = pagination.total_pages;
        
        document.getElementById('product_page_info').textContent = `${currentProductPage} / ${totalProductPages}`;
        document.getElementById('btn_product_prev_page').disabled = !pagination.has_prev_page;
        document.getElementById('btn_product_next_page').disabled = !pagination.has_next_page;
        
        document.getElementById('product_modal_pagination').style.display = 'flex';
    }

    // 品番ページングボタンイベント
    const productPrevBtn = document.getElementById('btn_product_prev_page');
    const productNextBtn = document.getElementById('btn_product_next_page');
    
    if (productPrevBtn) {
        productPrevBtn.addEventListener('click', function() {
            if (currentProductPage > 1) {
                searchProductsInModal(currentProductKeyword, currentProductPage - 1);
            }
        });
    }
    
    if (productNextBtn) {
        productNextBtn.addEventListener('click', function() {
            if (currentProductPage < totalProductPages) {
                searchProductsInModal(currentProductKeyword, currentProductPage + 1);
            }
        });
    }

    // 品番選択ボタン
    const selectProductBtn = document.getElementById('btn_select_product');
    if (selectProductBtn) {
        selectProductBtn.addEventListener('click', function() {
            if (selectedProductFromModal) {
                console.log('選択された品番:', selectedProductFromModal);
                
                // 品番を入力フィールドに設定
                elements.productNumber.value = selectedProductFromModal.product_number;
                currentProductNumber = selectedProductFromModal.product_number;
                
                // 品番検証をスキップして直接商品リストを表示
                showProductList([selectedProductFromModal]);
                showProductMessage(`品番が確認されました（選択: ${selectedProductFromModal.product_name}）`, 'success');
                updateStepStatus(2, 'completed');
                updateStepStatus(3, '');
                
                // 選択した商品を自動選択
                setTimeout(() => {
                    const productItems = document.querySelectorAll('.product-item');
                    if (productItems.length > 0) {
                        // 最初の商品を自動選択
                        productItems[0].click();
                    }
                }, 100);
                
                // モーダルを閉じる
                const modal = bootstrap.Modal.getInstance(productModal);
                if (modal) {
                    modal.hide();
                }
                
                selectedProductFromModal = null;
            }
        });
    }

    console.log('=== 商品販売分析フォーム初期化完了 ===');
});
</script>
<?= $this->endSection() ?>