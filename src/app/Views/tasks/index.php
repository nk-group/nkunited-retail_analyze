<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
    <?php /* もしローカルに配置した場合:
    <link rel="stylesheet" href="<?= base_url('assets/flatpickr/css/flatpickr.min.css') ?>">
    */ ?>
    <style>
        .table th.sortable {
            cursor: pointer;
        }
        .table th.sortable:hover {
            background-color: #f8f9fa;
        }
        .table th .bi-arrow-up {
            color: #007bff;
        }
        .table th .bi-arrow-down {
            color: #007bff;
        }
        .status-pending { background-color: #fff3cd; color: #664d03; }
        .status-processing { background-color: #cfe2ff; color: #0a3678; }
        .status-success { background-color: #d1e7dd; color: #0f5132; }
        .status-failed { background-color: #f8d7da; color: #58151c; }
        .status-completed_with_issues { background-color: #fff3cd; }
        .status-perfect_success { background-color: #d1e7dd; }
        .status-import_failed { background-color: #f8d7da; }
        .status-service_return_error { background-color: #f8d7da; }
        .status-task_processing_exception { background-color: #f8d7da; }
        .status-unknown_processing_error { background-color: #f8d7da; }


        .table-responsive td, .table-responsive th {
            white-space: nowrap;
        }
        .result-message-short {
            max-width: 300px; 
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
            cursor: pointer;
        }
        .result-message-full {
            white-space: pre-wrap; 
            word-break: break-all;
        }
    </style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><?= esc($pageTitle ?? '取込タスク一覧') ?></h2>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title">絞り込み条件</h5>
            <?= form_open(route_to('task_list'), ['method' => 'get', 'id' => 'filterForm']) ?>
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="date_range" class="form-label">アップロード日時範囲 (uploaded_at)</label>
                        <input type="text" class="form-control" id="date_range" name="date_range" value="<?= esc($dateRange ?? '', 'attr') ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">絞り込み</button>
                    </div>
                    <div class="col-md-2">
                        <a href="<?= site_url('tasks') ?>" class="btn btn-secondary w-100">リセット</a>
                    </div>
                </div>
                <input type="hidden" name="sort" value="<?= esc($sortColumn ?? 'uploaded_at', 'attr') ?>">
                <input type="hidden" name="order" value="<?= esc($sortOrder ?? 'DESC', 'attr') ?>">
            <?= form_close() ?>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            タスク一覧
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <?php
                            $columns = [
                                'id' => 'ID',
                                'status' => 'ステータス',
                                'target_data_name' => '対象データ',
                                'original_file_name' => '元ファイル名',
                                'uploaded_at' => 'アップロード日時',
                                'uploaded_by' => 'アップロード者',
                                'processing_started_at' => '処理開始日時',
                                'processing_finished_at' => '処理完了日時',
                                'result_message' => '処理結果メッセージ'
                            ];
                            $currentSortColumn = $sortColumn ?? 'uploaded_at';
                            $currentSortOrder = $sortOrder ?? 'DESC';
                            $nextSortOrder = ($currentSortOrder === 'ASC') ? 'DESC' : 'ASC';
                            ?>
                            <?php foreach ($columns as $colKey => $colName): ?>
                                <th class="sortable" data-sort="<?= esc($colKey, 'attr') ?>" data-order="<?= ($currentSortColumn === $colKey) ? $nextSortOrder : 'ASC' ?>">
                                    <?= esc($colName) ?>
                                    <?php if ($currentSortColumn === $colKey): ?>
                                        <i class="bi <?= ($currentSortOrder === 'ASC') ? 'bi-arrow-up' : 'bi-arrow-down' ?>"></i>
                                    <?php endif; ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($tasks) && !empty($tasks)): ?>
                            <?php foreach ($tasks as $task): ?>
                                <?php
                                $statusDisplay = esc($task->status);
                                $statusClass = 'status-' . strtolower(esc($task->status, 'attr'));
                                switch ($task->status) {
                                    case 'pending': $statusDisplay = '処理待ち'; break;
                                    case 'processing': $statusDisplay = '処理中'; break;
                                    case 'success': $statusDisplay = '成功'; break;
                                    case 'failed': $statusDisplay = '失敗'; break;
                                    case 'completed_with_issues': $statusDisplay = '完了（一部問題あり）'; break;
                                    case 'perfect_success': $statusDisplay = '完全成功'; break;
                                    case 'import_failed': $statusDisplay = 'インポート失敗'; break;
                                    case 'service_return_error': $statusDisplay = 'サービスエラー'; break;
                                    case 'task_processing_exception': $statusDisplay = '処理例外'; break;
                                    case 'unknown_processing_error': $statusDisplay = '不明なエラー'; break;
                                }

                                $targetDataDisplay = esc($task->target_data_name);
                                switch ($task->target_data_name) {
                                    case 'product_master': $targetDataDisplay = '商品マスタ'; break;
                                    case 'manufacturer_master': $targetDataDisplay = 'メーカーマスタ'; break;
                                    case 'purchase_slip': $targetDataDisplay = '仕入伝票'; break;
                                    case 'sales_slip': $targetDataDisplay = '売上伝票'; break;
                                    case 'transfer_slip': $targetDataDisplay = '移動伝票'; break;
                                    case 'adjustment_slip': $targetDataDisplay = '調整伝票'; break;
                                }
                                ?>
                                <tr>
                                    <td><?= esc($task->id) ?></td>
                                    <td><span class="badge <?= $statusClass ?>"><?= $statusDisplay ?></span></td>
                                    <td><?= $targetDataDisplay ?></td>
                                    <td><?= esc($task->original_file_name) ?></td>
                                    <td><?= esc($task->uploaded_at) ?></td>
                                    <td><?= esc($task->uploaded_by) ?></td>
                                    <td><?= esc($task->processing_started_at) ?></td>
                                    <td><?= esc($task->processing_finished_at) ?></td>
                                    <td>
                                        <?php if (!empty($task->result_message)): ?>
                                            <span class="result-message-short" title="クリックして詳細表示">
                                                <?= esc(ellipsize($task->result_message, 50, .5, '...')) ?>
                                            </span>
                                            <div class="result-message-full d-none">
                                                <?= nl2br(esc($task->result_message)) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= count($columns) ?>" class="text-center">該当するタスクはありません。</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                <?= $pager->links() ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/ja.js"></script> 
    <?php /* もしローカルに配置した場合:
    <script src="<?= base_url('assets/flatpickr/js/flatpickr.min.js') ?>"></script>
    <script src="<?= base_url('assets/flatpickr/js/l10n/ja.js') ?>"></script>
    */ ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr("#date_range", {
                mode: "range",
                dateFormat: "Y-m-d",
                locale: "ja",
            });

            document.querySelectorAll('.table th.sortable').forEach(function(th) {
                th.addEventListener('click', function() {
                    const column = this.dataset.sort;
                    const order = this.dataset.order;
                    const form = document.getElementById('filterForm');
                    
                    form.querySelector('input[name="sort"]').value = column;
                    form.querySelector('input[name="order"]').value = order;
                    
                    form.submit();
                });
            });

            document.querySelectorAll('.result-message-short').forEach(function(shortMsg) {
                shortMsg.addEventListener('click', function() {
                    const fullMsg = this.nextElementSibling;
                    if (fullMsg && fullMsg.classList.contains('result-message-full')) {
                        fullMsg.classList.toggle('d-none');
                        this.classList.toggle('d-none'); 
                    }
                });
            });
             document.querySelectorAll('.result-message-full').forEach(function(fullMsg) {
                fullMsg.addEventListener('click', function() {
                    const shortMsg = this.previousElementSibling;
                    if (shortMsg && shortMsg.classList.contains('result-message-short')) {
                        shortMsg.classList.toggle('d-none');
                        this.classList.toggle('d-none');
                    }
                });
            });
        });
    </script>
<?= $this->endSection() ?>