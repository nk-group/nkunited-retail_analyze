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
$this->setData([
    'useSalesAnalysisCSS' => true,
    'salesAnalysisPage' => 'form',
    'bodyClass' => 'sales-analysis'
]);

?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/single_product_form.js') ?>"></script>
<?= $this->endSection() ?>