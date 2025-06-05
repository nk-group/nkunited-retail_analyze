<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container-fluid sales-analysis" style="max-width: 1400px;">
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
        <h2 class="page-title">商品販売分析 - 単品集計表</h2>
        
        <!-- メーカー情報 -->
        <div class="manufacturer-info">
            <?= esc($formatted_result['header_info']['manufacturer_name']) ?> (<?= esc($formatted_result['header_info']['manufacturer_code']) ?>)
        </div>
        
        <!-- 商品名 -->
        <div class="product-name">
            <?= esc($formatted_result['header_info']['product_name']) ?>
        </div>
        
        <!-- 品番・シーズン情報 -->
        <div class="product-details">
            品番: <?= esc($formatted_result['header_info']['product_number']) ?> | 
            シーズン: <?= esc($formatted_result['header_info']['season_code']) ?>
        </div>
        
        <!-- 重要指標 -->
        <div class="key-metrics">
            <div class="metric-item">
                <span class="metric-icon">📅</span>
                <span class="metric-label">品出し日:</span>
                <span class="metric-value date">
                    <?= esc($formatted_result['header_info']['first_transfer_date']) ?>
                    <?php if ($formatted_result['header_info']['is_fallback_date']): ?>
                        <small style="opacity: 0.8;">※商品登録日を使用</small>
                    <?php endif; ?>
                </span>
            </div>
            
            <div class="metric-item">
                <span class="metric-icon">⏰</span>
                <span class="metric-label">経過日数:</span>
                <span class="metric-value days"><?= esc($formatted_result['header_info']['days_since_transfer']) ?>日</span>
            </div>
            
            <div class="metric-item">
                <span class="metric-icon">💰</span>
                <span class="metric-label">仕入単価:</span>
                <span class="metric-value price">¥<?= number_format($formatted_result['header_info']['avg_cost_price']) ?></span>
            </div>
            
            <div class="metric-item">
                <span class="metric-icon">🏪</span>
                <span class="metric-label">定価:</span>
                <span class="metric-value price">¥<?= number_format($formatted_result['header_info']['selling_price']) ?></span>
            </div>
            
            <?php if ($formatted_result['header_info']['deletion_scheduled_date']): ?>
            <div class="metric-item">
                <span class="metric-icon">📋</span>
                <span class="metric-label">廃盤予定日:</span>
                <span class="metric-value date"><?= esc($formatted_result['header_info']['deletion_scheduled_date']) ?></span>
            </div>
            <?php endif; ?>
        </div>
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
        <div class="summary-card clickable" onclick="showProductModal()">
            <h4>集計対象商品</h4>
            <div class="value"><?= count($analysis_result['basic_info']['jan_details'] ?? []) ?>個のSKU</div>
        </div>
    </div>

    <!-- 対象商品モーダル -->
    <div class="modal-overlay" id="productModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="bi bi-box-seam me-2"></i>集計対象商品一覧</h3>
                <button class="modal-close" onclick="hideProductModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="product-group-info">
                    <h4><?= esc($formatted_result['header_info']['product_name']) ?></h4>
                    <p>品番: <?= esc($formatted_result['header_info']['product_number']) ?> | 
                       対象SKU数: <?= count($analysis_result['basic_info']['jan_details'] ?? []) ?>個</p>
                </div>
                
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>JANコード</th>
                            <th>サイズ</th>
                            <th>カラー</th>
                            <th>売価</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($analysis_result['basic_info']['jan_details'])): ?>
                            <?php foreach ($analysis_result['basic_info']['jan_details'] as $product): ?>
                                <tr>
                                    <td style="font-family: monospace;"><?= esc($product['jan_code']) ?></td>
                                    <td><?= esc($product['size_name'] ?? 'F') ?></td>
                                    <td><?= esc($product['color_name'] ?? '-') ?></td>
                                    <td>¥<?= number_format($product['selling_price'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">商品データがありません</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <p class="info-text">
                    <i class="bi bi-info-circle me-1"></i>
                    これらのSKUの販売実績を合算して分析しています
                </p>
            </div>
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
    console.log('単品分析結果画面が読み込まれました');
    console.log('実行時間: <?= $execution_time ?? 0 ?>秒');
    
    // 将来的な拡張: データの動的読み込みやチャート表示などを実装予定
});

// 対象商品モーダル表示
function showProductModal() {
    const modal = document.getElementById('productModal');
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

// 対象商品モーダル非表示
function hideProductModal() {
    const modal = document.getElementById('productModal');
    modal.classList.remove('show');
    document.body.style.overflow = '';
}

// モーダル外クリックで閉じる
document.getElementById('productModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideProductModal();
    }
});

// ESCキーでモーダルを閉じる
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideProductModal();
    }
});
</script>
<?= $this->endSection() ?>