<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    body {
        background-color: #f5f5f5;
    }
    .header-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px 30px;
        border-radius: 8px;
        margin-bottom: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .header-section h2 {
        margin: 0 0 10px 0;
        font-size: 24px;
        font-weight: 600;
    }
    .header-section p {
        margin: 0;
        opacity: 0.9;
        font-size: 14px;
        line-height: 1.4;
    }
    .summary-section {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }
    .summary-card {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        border-left: 4px solid #007bff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        text-align: center;
    }
    .summary-card h4 {
        margin: 0 0 12px 0;
        color: #495057;
        font-size: 14px;
        font-weight: 600;
    }
    .summary-card .value {
        font-size: 24px;
        font-weight: bold;
        color: #007bff;
    }
    .recovery-rate {
        color: #28a745;
    }
    .recovery-rate.warning {
        color: #ffc107;
    }
    .recovery-rate.danger {
        color: #dc3545;
    }
    .analysis-table {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 25px;
    }
    .table-header {
        background: #343a40;
        color: white;
        padding: 15px 20px;
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }
    th {
        background: #495057;
        color: white;
        padding: 12px 8px;
        text-align: center;
        font-size: 12px;
        font-weight: 600;
        border: none;
    }
    td {
        padding: 12px 8px;
        text-align: center;
        border-bottom: 1px solid #dee2e6;
        font-size: 13px;
        border: none;
    }
    .text-left {
        text-align: left !important;
    }
    .price-change {
        background: #fff3cd;
        border-left: 3px solid #ffc107;
    }
    .sold-out {
        background: #f8d7da;
        color: #721c24;
    }
    .best-seller {
        background: #d4edda;
        color: #155724;
    }
    .negative-sales {
        background: #ffebee;
        color: #721c24;
    }
    .recommendation-section {
        background: #d4edda;
        padding: 20px;
        border-radius: 8px;
        margin-top: 20px;
        border-left: 4px solid #28a745;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .recommendation-section.warning {
        background: #fff3cd;
        border-left-color: #ffc107;
    }
    .recommendation-section.danger {
        background: #f8d7da;
        border-left-color: #dc3545;
    }
    .recommendation-section p {
        margin: 8px 0;
        font-size: 14px;
        line-height: 1.5;
    }
    .recommendation-section strong {
        font-weight: 600;
    }
    .action-buttons {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    .btn {
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.3s;
    }
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid" style="max-width: 1400px;">
    <!-- ページヘッダー -->
    <div class="action-buttons">
        <a href="<?= site_url('sales-analysis/single-product') ?>" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left me-2"></i>条件変更
        </a>
        <a href="<?= site_url('sales-analysis') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-house me-2"></i>分析メニュー
        </a>
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-2"></i>印刷
        </button>
    </div>

    <!-- 成功メッセージ -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- 警告メッセージ -->
    <?php if (!empty($warnings)): ?>
        <div class="analysis-warnings">
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>データ品質の注意事項:</strong>
                <ul>
                    <?php foreach ($warnings as $warning): ?>
                        <li><i class="<?= esc($warning['icon']) ?> me-1"></i><?= esc($warning['message']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <!-- ヘッダー情報 -->
    <div class="header-section">
        <h2>商品販売分析 - 単品集計表</h2>
        <p>メーカー: <?= esc($formatted_result['header_info']['manufacturer_name']) ?> (<?= esc($formatted_result['header_info']['manufacturer_code']) ?>) | 
           品番: <?= esc($formatted_result['header_info']['product_number']) ?> | 
           品名: <?= esc($formatted_result['header_info']['product_name']) ?> | 
           シーズン: <?= esc($formatted_result['header_info']['season_code']) ?></p>
        <p>品出し日: <?= esc($formatted_result['header_info']['first_transfer_date']) ?> 
           <?php if ($formatted_result['header_info']['is_fallback_date']): ?>
               <span class="badge bg-warning">※商品登録日を使用</span>
           <?php endif; ?> | 
           経過日数: <?= esc($formatted_result['header_info']['days_since_transfer']) ?>日 | 
           仕入単価: ¥<?= number_format($formatted_result['header_info']['avg_cost_price']) ?></p>
        <p>定価: ¥<?= number_format($formatted_result['header_info']['selling_price']) ?>
           <?php if ($formatted_result['header_info']['deletion_scheduled_date']): ?>
               | 廃盤予定日: <?= esc($formatted_result['header_info']['deletion_scheduled_date']) ?>
           <?php endif; ?>
        </p>
    </div>

    <!-- サマリー情報 -->
    <div class="summary-section">
        <div class="summary-card">
            <h4>仕入原価合計</h4>
            <div class="value">¥<?= number_format($formatted_result['summary_info']['total_purchase_cost']) ?></div>
        </div>
        <div class="summary-card">
            <h4>売上合計</h4>
            <div class="value">¥<?= number_format($formatted_result['summary_info']['total_sales_amount']) ?></div>
        </div>
        <div class="summary-card">
            <h4>粗利合計</h4>
            <div class="value <?= $formatted_result['summary_info']['total_gross_profit'] >= 0 ? 'recovery-rate' : 'recovery-rate danger' ?>">
                ¥<?= number_format($formatted_result['summary_info']['total_gross_profit']) ?>
            </div>
        </div>
        <div class="summary-card">
            <h4>原価回収率</h4>
            <div class="value <?= $formatted_result['summary_info']['recovery_rate'] >= 100 ? 'recovery-rate' : ($formatted_result['summary_info']['recovery_rate'] >= 70 ? 'recovery-rate warning' : 'recovery-rate danger') ?>">
                <?= number_format($formatted_result['summary_info']['recovery_rate'], 1) ?>%
            </div>
        </div>
        <div class="summary-card">
            <h4>残在庫数</h4>
            <div class="value"><?= number_format($formatted_result['summary_info']['current_stock_qty']) ?>個</div>
        </div>
        <div class="summary-card">
            <h4>残在庫原価</h4>
            <div class="value">¥<?= number_format($formatted_result['summary_info']['current_stock_value']) ?></div>
        </div>
        <div class="summary-card">
            <h4>総販売数</h4>
            <div class="value"><?= number_format($formatted_result['summary_info']['total_sales_qty']) ?>個</div>
        </div>
        <div class="summary-card">
            <h4>定価</h4>
            <div class="value">¥<?= number_format($formatted_result['summary_info']['selling_price']) ?></div>
        </div>
    </div>

    <!-- 週別販売推移 -->
    <div class="analysis-table">
        <h3 class="table-header"><i class="bi bi-calendar-week me-2"></i>週別販売推移</h3>
        <table>
            <thead>
                <tr>
                    <th>週</th>
                    <th>期間</th>
                    <th>販売数</th>
                    <th>平均売価</th>
                    <th>売上金額</th>
                    <th>粗利</th>
                    <th>累計販売数</th>
                    <th>累計粗利</th>
                    <th>累計回収率</th>
                    <th>備考</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($formatted_result['weekly_data'])): ?>
                    <?php foreach ($formatted_result['weekly_data'] as $week): ?>
                        <tr class="<?= $week['has_returns'] ? 'negative-sales' : ($week['week_number'] <= 2 ? 'best-seller' : ($week['avg_price'] < $formatted_result['summary_info']['selling_price'] * 0.95 ? 'price-change' : '')) ?>">
                            <td><?= $week['week_number'] ?>週目</td>
                            <td class="text-left"><?= esc($week['period']) ?></td>
                            <td><?= number_format($week['sales_qty']) ?></td>
                            <td><?= $week['avg_price'] > 0 ? '¥' . number_format($week['avg_price']) : '-' ?></td>
                            <td>¥<?= number_format($week['sales_amount']) ?></td>
                            <td class="<?= $week['gross_profit'] >= 0 ? '' : 'text-danger' ?>">¥<?= number_format($week['gross_profit']) ?></td>
                            <td><?= number_format($week['cumulative_sales']) ?></td>
                            <td class="<?= $week['cumulative_profit'] >= 0 ? '' : 'text-danger' ?>">¥<?= number_format($week['cumulative_profit']) ?></td>
                            <td class="<?= $week['recovery_rate'] >= 100 ? 'recovery-rate' : ($week['recovery_rate'] >= 70 ? 'recovery-rate warning' : 'recovery-rate danger') ?>">
                                <?= number_format($week['recovery_rate'], 1) ?>%
                            </td>
                            <td class="text-left"><?= esc($week['remarks']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted">販売データがありません</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- 売価別販売状況 -->
    <div class="analysis-table">
        <h3 class="table-header"><i class="bi bi-tag me-2"></i>売価別販売状況</h3>
        <table>
            <thead>
                <tr>
                    <th>売価</th>
                    <th>販売数</th>
                    <th>売上金額</th>
                    <th>構成比</th>
                    <th>値引率</th>
                    <th>期間</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($formatted_result['price_breakdown'])): ?>
                    <?php foreach ($formatted_result['price_breakdown'] as $price): ?>
                        <tr class="<?= $price['discount_rate'] > 0 ? 'price-change' : '' ?>">
                            <td>¥<?= number_format($price['price']) ?></td>
                            <td><?= number_format($price['quantity']) ?></td>
                            <td>¥<?= number_format($price['amount']) ?></td>
                            <td><?= number_format($price['ratio'], 1) ?>%</td>
                            <td><?= number_format($price['discount_rate'], 0) ?>%</td>
                            <td class="text-left"><?= esc($price['period']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">販売データがありません</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- 推奨アクション -->
    <div class="recommendation-section <?= $formatted_result['recommendation']['status_class'] ?>">
        <p><strong><i class="bi bi-<?= $formatted_result['recommendation']['disposal_possible'] ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>判定: <?= esc($formatted_result['recommendation']['status_text']) ?></strong></p>
        <p><strong>詳細:</strong> <?= esc($formatted_result['recommendation']['message']) ?></p>
        <p><strong>推奨アクション:</strong> <?= esc($formatted_result['recommendation']['action']) ?></p>
        <?php if ($formatted_result['recommendation']['days_to_disposal']): ?>
            <p><strong>廃盤まで:</strong> あと<?= number_format($formatted_result['recommendation']['days_to_disposal']) ?>日</p>
        <?php endif; ?>
    </div>

    <!-- 操作ボタン -->
    <div class="text-center mt-4">
        <a href="<?= site_url('sales-analysis/single-product') ?>" class="btn btn-primary btn-lg">
            <i class="bi bi-search me-2"></i>新しい分析を実行
        </a>
        <a href="<?= site_url('sales-analysis') ?>" class="btn btn-outline-secondary btn-lg ms-3">
            <i class="bi bi-arrow-left me-2"></i>分析メニューに戻る
        </a>
        <?php if (!empty($formatted_result['weekly_data'])): ?>
            <button type="button" class="btn btn-outline-info btn-lg ms-3" onclick="window.print()">
                <i class="bi bi-printer me-2"></i>印刷
            </button>
        <?php endif; ?>
    </div>

    <!-- デバッグ情報（開発時のみ） -->
    <?php if (ENVIRONMENT === 'development' && !empty($execution_time)): ?>
        <div class="mt-4 p-3 bg-light border rounded">
            <small class="text-muted">
                <i class="bi bi-clock me-1"></i>実行時間: <?= number_format($execution_time, 3) ?>秒 | 
                <i class="bi bi-calendar me-1"></i>集計日時: <?= esc($analysis_result['analysis_date'] ?? date('Y-m-d H:i:s')) ?>
            </small>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('単品分析結果画面が読み込まれました');
    console.log('実行時間: <?= $execution_time ?? 0 ?>秒');
    
    // 将来的な拡張: データの動的読み込みやチャート表示などを実装予定
});
</script>
<?= $this->endSection() ?>