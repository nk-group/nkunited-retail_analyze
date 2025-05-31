<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
<style>
.form-section {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.form-section h5 {
    color: #495057;
    border-bottom: 2px solid #007bff;
    padding-bottom: 8px;
    margin-bottom: 15px;
}

.btn-execute {
    background: linear-gradient(45deg, #007bff, #0056b3);
    border: none;
    padding: 12px 30px;
    font-size: 16px;
    font-weight: 600;
}

.btn-execute:hover {
    background: linear-gradient(45deg, #0056b3, #004085);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,123,255,0.3);
}

/* メーカー参照ボタン */
.btn-reference {
    border-radius: 0 4px 4px 0;
    border-left: 0;
}

.input-group .form-control {
    border-radius: 4px 0 0 4px;
}

/* モーダル内のテーブル - 高さ制限とスクロール対応 */
.maker-reference-table-container {
    max-height: 400px; /* 高さ制限 */
    overflow-y: auto;   /* 縦スクロール有効 */
    border: 1px solid #dee2e6;
    border-radius: 4px;
}

.maker-reference-table {
    font-size: 0.9rem;
    margin-bottom: 0; /* テーブル下のマージンを削除 */
}

.maker-reference-table thead th {
    position: sticky; /* ヘッダー固定 */
    top: 0;
    background-color: #f8f9fa;
    z-index: 10;
    border-bottom: 2px solid #dee2e6;
}

.maker-reference-table tbody tr {
    cursor: pointer;
    transition: all 0.2s ease; /* スムーズなトランジション */
}

.maker-reference-table tbody tr:hover {
    background-color: #f8f9fa !important;
    transform: scale(1.01); /* わずかに拡大 */
}

.maker-reference-table tbody tr.selected {
    background-color: #0d6efd !important; /* Bootstrap primary色 */
    color: white !important;
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(13, 110, 253, 0.3); /* 影を追加 */
}

.maker-reference-table tbody tr.selected:hover {
    background-color: #0b5ed7 !important; /* より濃い青 */
}

/* 検索フォーム */
.modal-search-form {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

/* 選択情報表示エリア */
.selected-maker-info {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border: 2px solid #2196f3;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    display: none; /* 初期は非表示 */
}

.selected-maker-info.show {
    display: block;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.selected-maker-info .info-header {
    font-weight: bold;
    color: #1976d2;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
}

.selected-maker-info .info-content {
    color: #0d47a1;
    font-size: 0.95rem;
}

/* 検索結果情報 */
.search-results-info {
    background: #e9ecef;
    padding: 8px 15px;
    border-radius: 4px;
    margin-bottom: 15px;
    font-size: 0.9rem;
    color: #495057;
}

/* ページング */
.modal-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 15px;
    padding: 10px 0;
    border-top: 1px solid #dee2e6;
}

.modal-pagination button {
    min-width: 80px;
}

.modal-pagination .page-info {
    color: #6c757d;
    font-size: 0.9rem;
    margin: 0 10px;
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- ページヘッダー -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1"><?= esc($pageTitle ?? '販売分析 - 集計指示') ?></h2>
                </div>
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

            <!-- 集計条件入力フォーム -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">
                        <i class="bi bi-bar-chart-line me-2"></i>販売分析 集計条件
                    </h4>
                </div>
                <div class="card-body">
                    <?= form_open('/sales-analysis/execute', ['id' => 'analysisForm']) ?>
                    
                    <!-- 集計基準日 -->
                    <div class="form-section">
                        <h5><i class="bi bi-calendar-date me-2"></i>集計基準日</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="date_from" class="form-label">開始日 <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control flatpickr" 
                                       id="date_from" 
                                       name="date_from" 
                                       value="<?= old('date_from') ?>" 
                                       placeholder="YYYY-MM-DD"
                                       required>
                            </div>
                            <div class="col-md-6">
                                <label for="date_to" class="form-label">終了日 <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control flatpickr" 
                                       id="date_to" 
                                       name="date_to" 
                                       value="<?= old('date_to') ?>" 
                                       placeholder="YYYY-MM-DD"
                                       required>
                            </div>
                        </div>
                    </div>

                    <!-- メーカーコード範囲 -->
                    <div class="form-section">
                        <h5><i class="bi bi-building me-2"></i>メーカーコード範囲</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="maker_code_from" class="form-label">開始コード</label>
                                <div class="input-group">
                                    <input type="text" 
                                           class="form-control" 
                                           id="maker_code_from" 
                                           name="maker_code_from" 
                                           value="<?= old('maker_code_from') ?>" 
                                           placeholder="メーカーコードを選択"
                                           readonly>
                                    <button type="button" 
                                            class="btn btn-outline-secondary btn-reference" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#makerReferenceModal"
                                            data-target="maker_code_from">
                                        <i class="bi bi-search"></i> 参照
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="maker_code_to" class="form-label">終了コード</label>
                                <div class="input-group">
                                    <input type="text" 
                                           class="form-control" 
                                           id="maker_code_to" 
                                           name="maker_code_to" 
                                           value="<?= old('maker_code_to') ?>" 
                                           placeholder="メーカーコードを選択"
                                           readonly>
                                    <button type="button" 
                                            class="btn btn-outline-secondary btn-reference" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#makerReferenceModal"
                                            data-target="maker_code_to">
                                        <i class="bi bi-search"></i> 参照
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                空欄の場合は全メーカーが対象となります。参照ボタンからメーカーを選択してください。
                            </small>
                        </div>
                    </div>

                    <!-- メーカー品番範囲 -->
                    <div class="form-section">
                        <h5><i class="bi bi-upc-scan me-2"></i>メーカー品番範囲</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="maker_item_code_from" class="form-label">開始品番</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="maker_item_code_from" 
                                       name="maker_item_code_from" 
                                       value="<?= old('maker_item_code_from') ?>" 
                                       placeholder="開始品番を入力">
                            </div>
                            <div class="col-md-6">
                                <label for="maker_item_code_to" class="form-label">終了品番</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="maker_item_code_to" 
                                       name="maker_item_code_to" 
                                       value="<?= old('maker_item_code_to') ?>" 
                                       placeholder="終了品番を入力">
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                空欄の場合は全品番が対象となります。品番の範囲を指定する場合は開始・終了両方を入力してください。
                            </small>
                        </div>
                    </div>

                    <!-- 実行ボタン -->
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary btn-execute">
                            <i class="bi bi-play me-2"></i>分析実行
                        </button>
                        <button type="reset" class="btn btn-outline-secondary ms-3">
                            <i class="bi bi-arrow-clockwise me-2"></i>リセット
                        </button>
                    </div>

                    <?= form_close() ?>
                </div>
            </div>
        </div>
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
                <div id="selected_maker_info" class="selected-maker-info">
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
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ja.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 日付ピッカー初期化
    flatpickr('.flatpickr', {
        locale: 'ja',
        dateFormat: 'Y-m-d',
        allowInput: true,
        clickOpens: true
    });

    // メーカー参照モーダル関連の変数
    let currentTargetField = null;
    let selectedMaker = null;
    let currentPage = 1;
    let currentKeyword = '';
    let totalPages = 1;

    // DOM要素の取得
    const makerModal = document.getElementById('makerReferenceModal');
    const resultsContainer = document.getElementById('maker_search_results');
    const loadingDiv = document.getElementById('modal_loading');
    const noResultsDiv = document.getElementById('modal_no_results');
    const selectButton = document.getElementById('btn_select_maker');
    const resultsInfoDiv = document.getElementById('search_results_info');
    const resultsCountText = document.getElementById('results_count_text');
    const paginationDiv = document.getElementById('modal_pagination');
    const pageInfo = document.getElementById('page_info');
    const btnPrevPage = document.getElementById('btn_prev_page');
    const btnNextPage = document.getElementById('btn_next_page');
    
    // 選択情報表示用の要素
    const selectedMakerInfoDiv = document.getElementById('selected_maker_info');
    const selectedCodeSpan = document.getElementById('selected_code');
    const selectedNameSpan = document.getElementById('selected_name');

    // モーダル表示時の処理
    makerModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        if (button) {
            currentTargetField = button.getAttribute('data-target');
        }
        
        // 検索フィールドをクリア
        document.getElementById('modal_search_keyword').value = '';
        
        // UI要素の非表示
        resultsInfoDiv.style.display = 'none';
        paginationDiv.style.display = 'none';
        noResultsDiv.style.display = 'none';
        loadingDiv.style.display = 'none';
        
        // 選択情報をクリア
        clearSelectedMakerInfo();
        
        // テーブルクリア
        resultsContainer.innerHTML = '';
        
        // 初期検索実行（全件表示）
        searchMakersInModal('', 1);
    });

    // モーダル検索処理
    document.getElementById('btn_modal_search').addEventListener('click', function() {
        const keyword = document.getElementById('modal_search_keyword').value.trim();
        searchMakersInModal(keyword, 1);
    });

    // Enterキーでの検索
    document.getElementById('modal_search_keyword').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const keyword = this.value.trim();
            searchMakersInModal(keyword, 1);
        }
    });

    // ページングボタンのイベント
    btnPrevPage.addEventListener('click', function() {
        if (currentPage > 1) {
            searchMakersInModal(currentKeyword, currentPage - 1);
        }
    });

    btnNextPage.addEventListener('click', function() {
        if (currentPage < totalPages) {
            searchMakersInModal(currentKeyword, currentPage + 1);
        }
    });

    // メーカー選択処理
    selectButton.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (selectedMaker && currentTargetField) {
            // 対象フィールドに値を設定
            const targetField = document.getElementById(currentTargetField);
            if (targetField) {
                targetField.value = selectedMaker.manufacturer_code;
            }
            
            // モーダルを閉じる
            try {
                const modal = bootstrap.Modal.getInstance(makerModal);
                if (modal) {
                    modal.hide();
                } else {
                    makerModal.querySelector('.btn-close').click();
                }
            } catch (error) {
                // 強制的にモーダルを閉じる
                makerModal.classList.remove('show');
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) backdrop.remove();
            }
            
            // 選択状態をリセット
            selectedMaker = null;
        }
    });

    // Ajax検索関数
    function searchMakersInModal(keyword, page = 1) {
        // 表示状態をリセット
        resultsContainer.innerHTML = '';
        loadingDiv.style.display = 'block';
        noResultsDiv.style.display = 'none';
        resultsInfoDiv.style.display = 'none';
        paginationDiv.style.display = 'none';
        selectButton.disabled = true;
        selectedMaker = null;

        // 現在の検索条件を保存
        currentKeyword = keyword;
        currentPage = page;

        const searchUrl = `<?= site_url('sales-analysis/search-makers') ?>?keyword=${encodeURIComponent(keyword)}&page=${page}`;
        
        fetch(searchUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            loadingDiv.style.display = 'none';
            
            if (data.success && data.data.length > 0) {
                displayMakersInModal(data.data);
                updateResultsInfo(data.pagination, data.keyword);
                updatePagination(data.pagination);
            } else {
                noResultsDiv.style.display = 'block';
                if (data.pagination && data.pagination.total_count === 0) {
                    updateResultsInfo(data.pagination, data.keyword);
                }
            }
        })
        .catch(error => {
            loadingDiv.style.display = 'none';
            noResultsDiv.style.display = 'block';
        });
    }

    // 検索結果情報更新関数
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
        
        resultsCountText.textContent = infoText;
        resultsInfoDiv.style.display = 'block';
    }

    // ページング更新関数
    function updatePagination(pagination) {
        if (!pagination || pagination.total_pages <= 1) {
            paginationDiv.style.display = 'none';
            return;
        }
        
        currentPage = pagination.current_page;
        totalPages = pagination.total_pages;
        
        // ページ情報表示
        pageInfo.textContent = `${currentPage} / ${totalPages}`;
        
        // ボタンの有効/無効制御
        btnPrevPage.disabled = !pagination.has_prev_page;
        btnNextPage.disabled = !pagination.has_next_page;
        
        paginationDiv.style.display = 'flex';
    }

    // 検索結果表示関数
    function displayMakersInModal(makers) {
        resultsContainer.innerHTML = '';
        
        makers.forEach(maker => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${escapeHtml(maker.manufacturer_code)}</td>
                <td>${escapeHtml(maker.manufacturer_name)}</td>
            `;
            
            // 行クリック時の選択処理
            row.addEventListener('click', function() {
                // 既存の選択を解除
                document.querySelectorAll('#maker_search_results tr').forEach(tr => {
                    tr.classList.remove('selected');
                });
                
                // 新しい選択を設定
                this.classList.add('selected');
                selectedMaker = maker;
                selectButton.disabled = false;
                
                // 選択されたメーカー情報を表示
                updateSelectedMakerInfo(maker);
            });
            
            resultsContainer.appendChild(row);
        });
    }

    // 選択されたメーカー情報を更新する関数
    function updateSelectedMakerInfo(maker) {
        if (maker && selectedCodeSpan && selectedNameSpan && selectedMakerInfoDiv) {
            selectedCodeSpan.textContent = maker.manufacturer_code;
            selectedNameSpan.textContent = maker.manufacturer_name;
            selectedMakerInfoDiv.classList.add('show');
        }
    }

    // 選択情報をクリアする関数
    function clearSelectedMakerInfo() {
        if (selectedMakerInfoDiv) {
            selectedMakerInfoDiv.classList.remove('show');
        }
        if (selectedCodeSpan) selectedCodeSpan.textContent = '-';
        if (selectedNameSpan) selectedNameSpan.textContent = '-';
    }

    // HTMLエスケープ関数
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // フォームリセット時の処理
    document.querySelector('button[type="reset"]').addEventListener('click', function() {
        setTimeout(() => {
            // メーカーコードフィールドもクリア
            document.getElementById('maker_code_from').value = '';
            document.getElementById('maker_code_to').value = '';
        }, 10);
    });
});
</script>
<?= $this->endSection() ?>