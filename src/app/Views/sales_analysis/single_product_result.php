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
        <button type="button" class="btn btn-outline-info" id="shareUrlBtn">
            <i class="bi bi-share me-2"></i>URL共有
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
        <h2 class="page-title">
            商品販売分析 - 単品集計表
        </h2>
        
        <!-- メーカー情報 -->
        <div class="manufacturer-info">
            <?= esc($formatted_result['header_info']['manufacturer_name']) ?> (<?= esc($formatted_result['header_info']['manufacturer_code']) ?>)
            <?php if ($formatted_result['header_info']['total_manufacturers'] > 1): ?>
                <span class="badge bg-info ms-2">複数メーカー含む（<?= $formatted_result['header_info']['total_manufacturers'] ?>社）</span>
            <?php endif; ?>
        </div>
        
        <!-- 商品名 -->
        <div class="product-name">
            <?= esc($formatted_result['header_info']['product_name']) ?>
            <?php if ($formatted_result['header_info']['is_multi_group']): ?>
                <span class="badge bg-warning ms-2">複数商品グループ含む（<?= $formatted_result['header_info']['total_product_groups'] ?>グループ）</span>
            <?php endif; ?>
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
                <span class="metric-label">M単価:</span>
                <span class="metric-value price">¥<?= number_format($formatted_result['header_info']['m_unit_price']) ?></span>
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
        <!-- 1. 仕入原価合計 -->
        <div class="summary-card">
            <h4>仕入原価合計</h4>
            <div class="value">¥<?= number_format($formatted_result['summary_info']['total_purchase_cost']) ?></div>
        </div>
        
        <!-- 2. 売上合計 -->
        <div class="summary-card">
            <h4>売上合計</h4>
            <div class="value">¥<?= number_format($formatted_result['summary_info']['total_sales_amount']) ?></div>
        </div>
        
        <!-- 3. 粗利合計 -->
        <div class="summary-card">
            <h4>粗利合計</h4>
            <div class="value <?= $formatted_result['summary_info']['total_gross_profit'] >= 0 ? 'recovery-rate' : 'recovery-rate danger' ?>">
                ¥<?= number_format($formatted_result['summary_info']['total_gross_profit']) ?>
            </div>
        </div>
        
        <!-- 4. 原価回収率 -->
        <div class="summary-card">
            <h4>原価回収率</h4>
            <div class="value <?= $formatted_result['summary_info']['recovery_rate'] >= 100 ? 'recovery-rate' : ($formatted_result['summary_info']['recovery_rate'] >= 70 ? 'recovery-rate warning' : 'recovery-rate danger') ?>">
                <?= number_format($formatted_result['summary_info']['recovery_rate'], 1) ?>%
            </div>
        </div>
        
        <!-- 5. 総仕入数 -->
        <div class="summary-card">
            <h4>総仕入数</h4>
            <div class="value"><?= number_format($formatted_result['summary_info']['total_purchase_qty']) ?>個</div>
        </div>
        
        <!-- 6. 総販売数 -->
        <div class="summary-card">
            <h4>総販売数</h4>
            <div class="value"><?= number_format($formatted_result['summary_info']['total_sales_qty']) ?>個</div>
        </div>
        
        <!-- 7. 残在庫数 -->
        <div class="summary-card">
            <h4>残在庫数</h4>
            <div class="value"><?= number_format($formatted_result['summary_info']['current_stock_qty']) ?>個</div>
        </div>
        
        <!-- 8. 残在庫原価 -->
        <div class="summary-card">
            <h4>残在庫原価</h4>
            <div class="value">¥<?= number_format($formatted_result['summary_info']['current_stock_value']) ?></div>
        </div>
        
        <!-- 9. M単価 -->
        <div class="summary-card">
            <h4>M単価</h4>
            <div class="value">¥<?= number_format($formatted_result['summary_info']['m_unit_price']) ?></div>
        </div>
        
        <!-- 10. 集計対象商品 -->
        <div class="summary-card clickable" onclick="showProductModal()">
            <h4>集計対象商品</h4>
            <div class="value"><?= $formatted_result['summary_info']['target_products_count'] ?>個のJAN</div>
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
                    <p>対象JANコード数: <?= $formatted_result['summary_info']['target_products_count'] ?>個</p>
                </div>
                
                <?php if (!empty($formatted_result['manufacturer_groups']) && count($formatted_result['manufacturer_groups']) > 1): ?>
                    <div class="mb-3">
                        <h5>メーカー別内訳</h5>
                        <?php foreach ($formatted_result['manufacturer_groups'] as $mfg): ?>
                            <div class="badge bg-info me-2 mb-1">
                                <?= esc($mfg['manufacturer_name']) ?>: <?= $mfg['jan_count'] ?>個
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($formatted_result['product_groups']) && count($formatted_result['product_groups']) > 1): ?>
                    <div class="mb-3">
                        <h5>商品グループ別内訳</h5>
                        <?php foreach ($formatted_result['product_groups'] as $group): ?>
                            <div class="card mb-2">
                                <div class="card-body py-2">
                                    <small>
                                        <strong><?= esc($group['product_number']) ?></strong> - <?= esc($group['product_name']) ?>
                                        <span class="badge bg-secondary ms-2"><?= $group['jan_count'] ?>個</span>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>JANコード</th>
                            <th>メーカー</th>
                            <th>品番</th>
                            <th>サイズ</th>
                            <th>カラー</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($analysis_result['basic_info']['products'])): ?>
                            <?php foreach ($analysis_result['basic_info']['products'] as $product): ?>
                                <tr>
                                    <td style="font-family: monospace;"><?= esc($product['jan_code']) ?></td>
                                    <td><?= esc($product['manufacturer_name']) ?></td>
                                    <td><?= esc($product['product_number']) ?></td>
                                    <td><?= esc($product['size_name'] ?? 'F') ?></td>
                                    <td><?= esc($product['color_name'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">商品データがありません</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <p class="info-text">
                    <i class="bi bi-info-circle me-1"></i>
                    これらのJANコードの販売実績を合算して分析しています
                </p>
            </div>
        </div>
    </div>

    <!-- 週別販売推移 -->
    <div class="analysis-table">
        <h3 class="table-header"><i class="bi bi-calendar-week me-2"></i>週別販売推移</h3>
        <div style="overflow-x: auto;">
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
                        <th>残在庫数</th>
                        <th style="min-width: 200px;">備考</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($formatted_result['weekly_data'])): ?>
                        <?php foreach ($formatted_result['weekly_data'] as $week): ?>
                            <tr class="<?= $week['has_returns'] ? 'negative-sales' : ($week['week_number'] <= 2 ? 'best-seller' : ($week['avg_price'] < $formatted_result['summary_info']['m_unit_price'] * 0.95 ? 'price-change' : '')) ?>">
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
                                <td class="<?= $week['remaining_stock'] <= 5 && $week['remaining_stock'] > 0 ? 'text-warning' : ($week['remaining_stock'] <= 0 ? 'text-muted' : '') ?>">
                                    <?= number_format($week['remaining_stock']) ?>
                                </td>
                                <td class="text-left remarks-cell">
                                    <?= esc($week['remarks']) ?>
                                    <!-- イベントバッジ表示 -->
                                    <?php if (!empty($week['purchase_events'])): ?>
                                        <?php
                                        $totalPurchase = array_sum(array_column($week['purchase_events'], 'quantity'));
                                        if ($totalPurchase > 0):
                                        ?>
                                            <span class="event-badge badge-purchase">📦 仕入+<?= $totalPurchase ?></span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($week['adjustment_events'])): ?>
                                        <?php
                                        $totalAdjustment = array_sum(array_column($week['adjustment_events'], 'quantity'));
                                        if ($totalAdjustment != 0):
                                            $sign = $totalAdjustment > 0 ? '+' : '';
                                        ?>
                                            <span class="event-badge badge-adjustment">⚖️ 調整<?= $sign . $totalAdjustment ?></span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($week['transfer_events'])): ?>
                                        <span class="event-badge badge-transfer">🚚 移動</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="text-center text-muted">販売データがありません</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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

    <!-- 伝票詳細情報（折りたたみ式） -->
    <div class="analysis-table">
        <h3 class="table-header">
            <span><i class="bi bi-receipt me-2"></i>伝票情報詳細</span>
            <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#slipDetails" aria-expanded="false">
                <small class="me-3">
                    📦 仕入<?= $formatted_result['slip_details']['summary']['purchase_count'] ?>件 
                    ⚖️ 調整<?= $formatted_result['slip_details']['summary']['adjustment_count'] ?>件 
                    🚚 移動<?= $formatted_result['slip_details']['summary']['transfer_count'] ?>件
                </small>
                <i class="bi bi-chevron-down"></i>
            </button>
        </h3>
        <div class="collapse" id="slipDetails">
            <div class="p-3">
                <!-- タブ式表示 -->
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#purchaseSlips">
                            📦 仕入伝票 (<?= $formatted_result['slip_details']['summary']['purchase_count'] ?>件)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#adjustmentSlips">
                            ⚖️ 調整伝票 (<?= $formatted_result['slip_details']['summary']['adjustment_count'] ?>件)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#transferSlips">
                            🚚 移動伝票 (<?= $formatted_result['slip_details']['summary']['transfer_count'] ?>件)
                        </a>
                    </li>
                </ul>
                
                <div class="tab-content">
                    <!-- 仕入伝票 -->
                    <div class="tab-pane fade show active" id="purchaseSlips">
                        <div style="overflow-x: auto;">
                            <table class="table table-sm slip-table">
                                <thead>
                                    <tr>
                                        <th>日付</th>
                                        <th>伝票番号</th>
                                        <th>店舗</th>
                                        <th>仕入先</th>
                                        <th>数量</th>
                                        <th>単価</th>
                                        <th>金額</th>
                                        <th>備考</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($formatted_result['slip_details']['purchase_slips'])): ?>
                                        <?php foreach ($formatted_result['slip_details']['purchase_slips'] as $slip): ?>
                                            <tr>
                                                <td><?= esc($slip['date']) ?></td>
                                                <td><?= esc($slip['slip_number']) ?></td>
                                                <td><?= esc($slip['store']) ?></td>
                                                <td><?= esc($slip['supplier']) ?></td>
                                                <td class="<?= $slip['quantity'] > 0 ? 'text-success' : 'text-danger' ?>">
                                                    <?= $slip['quantity'] > 0 ? '+' : '' ?><?= number_format($slip['quantity']) ?>
                                                </td>
                                                <td>¥<?= number_format($slip['unit_price']) ?></td>
                                                <td>¥<?= number_format($slip['amount']) ?></td>
                                                <td><?= esc($slip['remarks']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">仕入データがありません</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- 調整伝票 -->
                    <div class="tab-pane fade" id="adjustmentSlips">
                        <div style="overflow-x: auto;">
                            <table class="table table-sm slip-table">
                                <thead>
                                    <tr>
                                        <th>日付</th>
                                        <th>伝票番号</th>
                                        <th>店舗</th>
                                        <th>調整種別</th>
                                        <th>数量</th>
                                        <th>理由</th>
                                        <th>担当者</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($formatted_result['slip_details']['adjustment_slips'])): ?>
                                        <?php foreach ($formatted_result['slip_details']['adjustment_slips'] as $slip): ?>
                                            <tr>
                                                <td><?= esc($slip['date']) ?></td>
                                                <td><?= esc($slip['slip_number']) ?></td>
                                                <td><?= esc($slip['store']) ?></td>
                                                <td><?= esc($slip['type']) ?></td>
                                                <td class="<?= $slip['quantity'] > 0 ? 'text-success' : 'text-danger' ?>">
                                                    <?= $slip['quantity'] > 0 ? '+' : '' ?><?= number_format($slip['quantity']) ?>
                                                </td>
                                                <td><?= esc($slip['reason']) ?></td>
                                                <td><?= esc($slip['staff']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">調整データがありません</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- 移動伝票 -->
                    <div class="tab-pane fade" id="transferSlips">
                        <div style="overflow-x: auto;">
                            <table class="table table-sm slip-table">
                                <thead>
                                    <tr>
                                        <th>日付</th>
                                        <th>伝票番号</th>
                                        <th>移動種別</th>
                                        <th>振出店</th>
                                        <th>受入店</th>
                                        <th>数量</th>
                                        <th>備考</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($formatted_result['slip_details']['transfer_slips'])): ?>
                                        <?php foreach ($formatted_result['slip_details']['transfer_slips'] as $slip): ?>
                                            <tr class="<?= $slip['is_initial_delivery'] ? 'table-success' : '' ?>">
                                                <td><?= esc($slip['date']) ?></td>
                                                <td><?= esc($slip['slip_number']) ?></td>
                                                <td><?= esc($slip['type']) ?></td>
                                                <td><?= esc($slip['source_store']) ?></td>
                                                <td><?= esc($slip['destination_store']) ?></td>
                                                <td><?= number_format($slip['quantity']) ?></td>
                                                <td><?= esc($slip['remarks']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">移動データがありません</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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

    <!-- URL共有機能 -->
    <div class="url-share-section" id="urlShareSection" style="display: none;">
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-share me-2"></i>分析結果URL共有</h5>
            </div>
            <div class="card-body">
                <p class="card-text">
                    この分析結果に直接アクセスできるURLです。同僚との共有やブックマークにご利用ください。
                </p>
                <div class="input-group">
                    <input type="text" class="form-control" id="shareUrl" readonly>
                    <button type="button" class="btn btn-outline-primary" id="copyShareUrlBtn">
                        <i class="bi bi-clipboard me-2"></i>URLをコピー
                    </button>
                </div>
                <small class="text-muted mt-2 d-block">
                    このURLは分析対象のJANコードが含まれており、同じ分析結果を表示します。
                </small>
            </div>
        </div>
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
                <i class="bi bi-calendar me-1"></i>集計日時: <?= esc($analysis_result['analysis_date'] ?? date('Y-m-d H:i:s')) ?> |
                <i class="bi bi-code me-1"></i>入力JANコード数: <?= count($input_jan_codes ?? []) ?>個
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
    console.log('クイック分析結果画面が読み込まれました');
    console.log('実行時間: <?= $execution_time ?? 0 ?>秒');
    
    // 現在のURLを取得してURL共有機能を初期化
    const currentUrl = window.location.href;
    const shareUrlInput = document.getElementById('shareUrl');
    const shareUrlSection = document.getElementById('urlShareSection');
    const shareUrlBtn = document.getElementById('shareUrlBtn');
    const copyShareUrlBtn = document.getElementById('copyShareUrlBtn');
    
    if (shareUrlInput && shareUrlSection && shareUrlBtn && copyShareUrlBtn) {
        shareUrlInput.value = currentUrl;
        
        // URL共有ボタンのクリックイベント
        shareUrlBtn.addEventListener('click', function() {
            if (shareUrlSection.style.display === 'none') {
                shareUrlSection.style.display = 'block';
                shareUrlBtn.innerHTML = '<i class="bi bi-eye-slash me-2"></i>URL非表示';
            } else {
                shareUrlSection.style.display = 'none';
                shareUrlBtn.innerHTML = '<i class="bi bi-share me-2"></i>URL共有';
            }
        });
        
        // URLコピーボタンのクリックイベント
        copyShareUrlBtn.addEventListener('click', function() {
            shareUrlInput.select();
            shareUrlInput.setSelectionRange(0, 99999); // モバイル対応
            
            try {
                navigator.clipboard.writeText(shareUrlInput.value).then(function() {
                    // コピー成功の表示
                    const originalText = copyShareUrlBtn.innerHTML;
                    copyShareUrlBtn.innerHTML = '<i class="bi bi-check me-2"></i>コピー完了';
                    copyShareUrlBtn.classList.remove('btn-outline-primary');
                    copyShareUrlBtn.classList.add('btn-success');
                    
                    setTimeout(function() {
                        copyShareUrlBtn.innerHTML = originalText;
                        copyShareUrlBtn.classList.remove('btn-success');
                        copyShareUrlBtn.classList.add('btn-outline-primary');
                    }, 2000);
                });
            } catch (err) {
                // フォールバック: execCommand使用
                document.execCommand('copy');
                console.log('URL copied using execCommand');
            }
        });
    }
    
    // 折りたたみボタンのアイコン切り替え
    const collapseElement = document.getElementById('slipDetails');
    const chevronIcon = document.querySelector('[data-bs-target="#slipDetails"] .bi-chevron-down');
    
    if (collapseElement && chevronIcon) {
        collapseElement.addEventListener('show.bs.collapse', function() {
            chevronIcon.style.transform = 'rotate(180deg)';
        });
        
        collapseElement.addEventListener('hide.bs.collapse', function() {
            chevronIcon.style.transform = 'rotate(0deg)';
        });
    }
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