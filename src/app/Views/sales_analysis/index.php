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

/* モーダル内のテーブル */
.maker-reference-table {
    font-size: 0.9rem;
}

.maker-reference-table tbody tr {
    cursor: pointer;
}

.maker-reference-table tbody tr:hover {
    background-color: #f8f9fa;
}

.maker-reference-table tbody tr.selected {
    background-color: #e3f2fd;
}

/* 検索フォーム */
.modal-search-form {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
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

                <!-- 検索結果テーブル -->
                <div class="table-responsive">
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

    // モーダル表示時の処理
    const makerModal = document.getElementById('makerReferenceModal');
    makerModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        currentTargetField = button.getAttribute('data-target');
        
        // 検索フィールドをクリア
        document.getElementById('modal_search_keyword').value = '';
        
        // 初期検索実行（全件表示）
        searchMakersInModal('');
    });

    // モーダル検索処理
    document.getElementById('btn_modal_search').addEventListener('click', function() {
        const keyword = document.getElementById('modal_search_keyword').value.trim();
        searchMakersInModal(keyword);
    });

    // Enterキーでの検索
    document.getElementById('modal_search_keyword').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const keyword = this.value.trim();
            searchMakersInModal(keyword);
        }
    });

    // メーカー選択処理
    document.getElementById('btn_select_maker').addEventListener('click', function() {
        if (selectedMaker && currentTargetField) {
            document.getElementById(currentTargetField).value = selectedMaker.manufacturer_code;
            
            // モーダルを閉じる
            const modal = bootstrap.Modal.getInstance(makerModal);
            modal.hide();
            
            // 選択状態をリセット
            selectedMaker = null;
            currentTargetField = null;
        }
    });

    // Ajax検索関数
    function searchMakersInModal(keyword) {
        const resultsContainer = document.getElementById('maker_search_results');
        const loadingDiv = document.getElementById('modal_loading');
        const noResultsDiv = document.getElementById('modal_no_results');
        const selectButton = document.getElementById('btn_select_maker');

        // 表示状態をリセット
        resultsContainer.innerHTML = '';
        loadingDiv.style.display = 'block';
        noResultsDiv.style.display = 'none';
        selectButton.disabled = true;
        selectedMaker = null;

        // Ajax検索実行
        const searchUrl = `/sales-analysis/search-makers?keyword=${encodeURIComponent(keyword)}`;
        
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
            } else {
                noResultsDiv.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('検索エラー:', error);
            loadingDiv.style.display = 'none';
            noResultsDiv.style.display = 'block';
        });
    }

    // 検索結果表示関数
    function displayMakersInModal(makers) {
        const resultsContainer = document.getElementById('maker_search_results');
        const selectButton = document.getElementById('btn_select_maker');
        
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
            });
            
            resultsContainer.appendChild(row);
        });
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