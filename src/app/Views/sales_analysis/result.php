<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
.condition-summary {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.condition-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #e9ecef;
}

.condition-item:last-child {
    border-bottom: none;
}

.condition-label {
    font-weight: 600;
    color: #495057;
    min-width: 150px;
}

.condition-value {
    color: #007bff;
    font-weight: 500;
}

.result-placeholder {
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 60px 20px;
    text-align: center;
    color: #6c757d;
}

.action-buttons {
    gap: 10px;
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
                    <h2 class="mb-1"><?= esc($pageTitle ?? '販売分析 - 結果') ?></h2>
                </div>
                <div class="action-buttons d-flex">
                    <a href="/sales-analysis" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left me-2"></i>条件変更
                    </a>
                </div>
            </div>

            <!-- 成功メッセージ -->
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= session()->getFlashdata('success') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- 集計条件サマリー -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>集計条件
                    </h5>
                </div>
                <div class="card-body">
                    <div class="condition-summary">
                        <?php if ($conditions): ?>
                            <div class="condition-item">
                                <span class="condition-label">
                                    <i class="bi bi-calendar-date me-2"></i>集計期間
                                </span>
                                <span class="condition-value">
                                    <?= $conditions['date_from'] ?> ～ <?= $conditions['date_to'] ?>
                                </span>
                            </div>
                            
                            <div class="condition-item">
                                <span class="condition-label">
                                    <i class="bi bi-building me-2"></i>メーカーコード
                                </span>
                                <span class="condition-value">
                                    <?php if (!empty($conditions['maker_code_from']) || !empty($conditions['maker_code_to'])): ?>
                                        <?= $conditions['maker_code_from'] ?: '(開始指定なし)' ?> ～ <?= $conditions['maker_code_to'] ?: '(終了指定なし)' ?>
                                    <?php else: ?>
                                        全メーカー
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <div class="condition-item">
                                <span class="condition-label">
                                    <i class="bi bi-upc-scan me-2"></i>メーカー品番
                                </span>
                                <span class="condition-value">
                                    <?php if (!empty($conditions['maker_item_code_from']) || !empty($conditions['maker_item_code_to'])): ?>
                                        <?= $conditions['maker_item_code_from'] ?: '(開始指定なし)' ?> ～ <?= $conditions['maker_item_code_to'] ?: '(終了指定なし)' ?>
                                    <?php else: ?>
                                        全品番
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 結果表示エリア（仮実装） -->
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">
                        <i class="bi bi-graph-up me-2"></i>分析結果
                    </h4>
                </div>
                <div class="card-body">
                    <div class="result-placeholder">
                        <div class="mb-3">
                            <i class="bi bi-gear-fill fa-spin fa-3x text-primary mb-3"></i>
                        </div>
                        <h5 class="mb-2">集計処理実行中...</h5>
                        <p class="mb-4">
                            販売分析の集計処理を実行しています。<br>
                            処理が完了次第、結果を表示いたします。
                        </p>
                        <div class="alert alert-info d-inline-block">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>今後の実装予定:</strong><br>
                            • 販売実績サマリー表示<br>
                            • 商品別売上ランキング<br>
                            • メーカー別売上分析<br>
                            • 期間比較分析<br>
                            • グラフィカルな可視化機能<br>
                            • Excel出力機能
                        </div>
                    </div>
                </div>
            </div>

            <!-- 操作ボタン -->
            <div class="text-center mt-4">
                <a href="/sales-analysis" class="btn btn-primary btn-lg">
                    <i class="bi bi-search me-2"></i>新しい分析を実行
                </a>
                <button type="button" class="btn btn-outline-secondary btn-lg ms-3" onclick="window.print()">
                    <i class="bi bi-printer me-2"></i>印刷
                </button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 今後、リアルタイム進捗表示やAjaxでの結果取得などを実装予定
    console.log('販売分析結果画面が読み込まれました');
    
    // 仮の進捗表示（実際にはバックグラウンド処理の進捗を表示）
    setTimeout(() => {
        console.log('集計処理完了（仮）');
    }, 3000);
});
</script>
<?= $this->endSection() ?>