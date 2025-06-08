<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container-fluid sales-analysis" style="max-width: 1200px;">
    <!-- ヘッダーセクション -->
    <div class="header-section">
        <h1 class="page-title"><i class="bi bi-search me-3"></i>商品販売分析システム</h1>
        <p class="page-subtitle">コード指定による商品分析・複数商品同時分析</p>
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

    <?= form_open(site_url('sales-analysis/code-analysis/execute'), ['id' => 'codeAnalysisForm']) ?>
    
    <!-- Step 1: コード種類選択 -->
    <div class="step-container" id="step1">
        <div class="step-number" id="step1-number">1</div>
        <div class="step-content">
            <div class="form-group">
                <label class="form-label fw-bold">分析対象コード種類</label>
                <div class="code-type-selection">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" id="codeTypeJan" name="code_type" value="jan_code" checked>
                        <label class="form-check-label" for="codeTypeJan">
                            <i class="bi bi-upc-scan me-2"></i>JANコード（バーコード）
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" id="codeTypeSku" name="code_type" value="sku_code">
                        <label class="form-check-label" for="codeTypeSku">
                            <i class="bi bi-tag me-2"></i>SKUコード（在庫管理コード）
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: 商品コード入力 -->
    <div class="step-container" id="step2">
        <div class="step-number" id="step2-number">2</div>
        <div class="step-content">
            <div class="form-group">
                <div class="product-input-header">
                    <label class="form-label fw-bold">
                        <span id="currentCodeType">JANコード</span>入力
                    </label>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addProductBtn">
                        <i class="bi bi-plus-circle me-2"></i>行追加
                    </button>
                </div>
                
                <div class="product-input-list" id="productInputList">
                    <!-- 初期行（JavaScriptで動的生成される内容と同じ構造） -->
                    <div class="product-input-row" data-index="1">
                        <div class="row-header">
                            <span class="row-number">1</span>
                            <button type="button" class="btn btn-outline-danger btn-sm remove-row-btn" style="display: none;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                        
                        <div class="product-input-content">
                            <div class="code-input-area">
                                <div class="input-group">
                                    <input type="text" 
                                           class="form-control product-code-input" 
                                           id="productCode1" 
                                           name="product_code_1"
                                           placeholder="JANコードを入力してください" 
                                           data-index="1"
                                           autocomplete="off">
                                    <button type="button" 
                                            class="btn btn-outline-secondary product-search-btn" 
                                            data-index="1"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#productSearchModal">
                                        <i class="bi bi-search me-2"></i>参照
                                    </button>
                                </div>
                                <div class="code-validation-message" id="codeMessage1"></div>
                            </div>
                            
                            <div class="product-info-display" id="productInfo1" style="display: none;">
                                <div class="product-info-grid">
                                    <div class="info-item">
                                        <label>商品名:</label>
                                        <span class="info-value" data-field="product_name">-</span>
                                    </div>
                                    <div class="info-item">
                                        <label>メーカー品番:</label>
                                        <span class="info-value" data-field="product_number">-</span>
                                    </div>
                                    <div class="info-item">
                                        <label>カラー:</label>
                                        <span class="info-value" data-field="color_name">-</span>
                                    </div>
                                    <div class="info-item">
                                        <label>サイズ:</label>
                                        <span class="info-value" data-field="size_name">-</span>
                                    </div>
                                    <div class="info-item">
                                        <label>売価:</label>
                                        <span class="info-value" data-field="selling_price">-</span>
                                    </div>
                                    <div class="info-item">
                                        <label>M単価:</label>
                                        <span class="info-value" data-field="m_unit_price">-</span>
                                    </div>
                                    <div class="info-item">
                                        <label>原価:</label>
                                        <span class="info-value" data-field="cost_price">-</span>
                                    </div>
                                    <div class="info-item">
                                        <label>メーカー:</label>
                                        <span class="info-value" data-field="manufacturer">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <input type="hidden" id="productCodesInput" name="product_codes" value="[]">
            </div>
        </div>
    </div>

    <!-- Step 3: 集計実行 -->
    <div class="step-container" id="step3">
        <div class="step-number disabled" id="step3-number">3</div>
        <div class="step-content">
            <div class="form-group">
                <label class="form-label fw-bold">原価計算方式選択</label>
                <div class="cost-method-selection">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" id="costMethodAverage" name="cost_method" value="average" checked>
                        <label class="form-check-label" for="costMethodAverage">
                            平均原価法
                        </label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" id="costMethodLatest" name="cost_method" value="latest">
                        <label class="form-check-label" for="costMethodLatest">
                            最終仕入原価法
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="analysis-summary" id="analysisSummary" style="display: none;">
                <h6><i class="bi bi-check-circle me-2"></i>分析対象サマリー</h6>
                <div class="summary-content" id="summaryText">商品コードを入力してください</div>
            </div>
            
            <div class="form-group">
                <label class="form-label fw-bold">集計実行</label>
                <p style="color: #6c757d; margin-bottom: 15px;">
                    指定された商品コードの販売状況を集計します。品出し日から現在までの週別販売推移、原価回収率、在庫処分判定などを表示します。
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
                        <li>複数の商品グループが混在する場合は、代表的な商品情報で表示されます</li>
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

<!-- 商品検索モーダル -->
<div class="modal fade" id="productSearchModal" tabindex="-1" aria-labelledby="productSearchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productSearchModalLabel">
                    <i class="bi bi-search me-2"></i>商品検索
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- 検索フォーム -->
                <div class="product-search-form">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="product_search_modal_keyword" class="form-label">検索キーワード</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="product_search_modal_keyword" 
                                   placeholder="商品名、メーカー名、品番、JANコード、SKUコードを入力">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" class="btn btn-primary w-100" id="btn_product_search_modal">
                                <i class="bi bi-search me-2"></i>検索
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 選択された商品情報表示 -->
                <div id="selected_product_modal_info" class="selected-product-info" style="display: none;">
                    <div class="info-header">
                        <i class="bi bi-check-circle-fill me-2"></i>選択中の商品
                    </div>
                    <div class="info-content">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>コード:</strong> <span id="selected_product_modal_code">-</span><br>
                                <strong>品番:</strong> <span id="selected_product_modal_number">-</span><br>
                                <strong>商品名:</strong> <span id="selected_product_modal_name">-</span><br>
                                <strong>メーカー:</strong> <span id="selected_product_modal_manufacturer">-</span>
                            </div>
                            <div class="col-md-6">
                                <strong>カラー:</strong> <span id="selected_product_modal_color">-</span><br>
                                <strong>サイズ:</strong> <span id="selected_product_modal_size">-</span><br>
                                <strong>価格:</strong> <span id="selected_product_modal_price">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 検索結果情報 -->
                <div id="product_search_modal_results_info" class="search-results-info" style="display: none;">
                    <i class="bi bi-info-circle me-2"></i>
                    <span id="product_search_modal_results_count_text">-</span>
                </div>

                <!-- 検索結果テーブル -->
                <div class="product-search-table-container">
                    <table class="table table-hover product-search-table">
                        <thead class="table-light">
                            <tr>
                                <th width="15%">コード</th>
                                <th width="12%">品番</th>
                                <th width="25%">商品名</th>
                                <th width="15%">メーカー</th>
                                <th width="8%">カラー</th>
                                <th width="8%">サイズ</th>
                                <th width="10%">売価</th>
                                <th width="10%">M単価</th>
                            </tr>
                        </thead>
                        <tbody id="product_search_modal_results">
                            <!-- Ajax で結果を表示 -->
                        </tbody>
                    </table>
                </div>

                <!-- ページング -->
                <div id="product_search_modal_pagination" class="modal-pagination" style="display: none;">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn_product_search_prev_page" disabled>
                        <i class="bi bi-chevron-left"></i> 前へ
                    </button>
                    <span class="page-info" id="product_search_modal_page_info">1 / 1</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn_product_search_next_page" disabled>
                        次へ <i class="bi bi-chevron-right"></i>
                    </button>
                </div>

                <!-- ローディング表示 -->
                <div id="product_search_modal_loading" class="text-center py-4" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">検索中...</span>
                    </div>
                    <div class="mt-2">検索中...</div>
                </div>

                <!-- 結果なし表示 -->
                <div id="product_search_modal_no_results" class="text-center py-4 text-muted" style="display: none;">
                    <i class="bi bi-search me-2"></i>
                    該当する商品が見つかりませんでした。
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>キャンセル
                </button>
                <button type="button" class="btn btn-primary" id="btn_select_product_modal" disabled>
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
<script src="<?= base_url('assets/js/code_analysis_form.js') ?>"></script>
<?= $this->endSection() ?>