<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container-fluid sales-analysis" style="max-width: 1200px;">
    <!-- ヘッダーセクション -->
    <div class="header-section">
        <h1 class="page-title"><i class="bi bi-exclamation-triangle me-3"></i><?= esc($error_title) ?></h1>
        <p class="page-subtitle">クイック分析でエラーが発生しました</p>
    </div>

    <!-- エラー詳細 -->
    <div class="card border-danger">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="bi bi-x-circle me-2"></i>エラー詳細</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-danger">
                <p class="mb-0"><strong><?= esc($error_message) ?></strong></p>
            </div>
            
            <?php if (!empty($additional_data)): ?>
                <div class="mt-3">
                    <h6><i class="bi bi-info-circle me-2"></i>詳細情報</h6>
                    
                    <?php if (isset($additional_data['invalid_jan_codes']) && !empty($additional_data['invalid_jan_codes'])): ?>
                        <div class="alert alert-warning">
                            <strong>無効なJANコード:</strong><br>
                            <code><?= implode(', ', array_map('esc', $additional_data['invalid_jan_codes'])) ?></code>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($additional_data['invalid_sku_codes']) && !empty($additional_data['invalid_sku_codes'])): ?>
                        <div class="alert alert-warning">
                            <strong>無効なSKUコード:</strong><br>
                            <code><?= implode(', ', array_map('esc', $additional_data['invalid_sku_codes'])) ?></code>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($additional_data['target_jan_codes']) && !empty($additional_data['target_jan_codes'])): ?>
                        <div class="alert alert-info">
                            <strong>分析対象として指定されたJANコード:</strong><br>
                            <code><?= implode(', ', array_map('esc', $additional_data['target_jan_codes'])) ?></code>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($additional_data['example_url'])): ?>
                        <div class="alert alert-info">
                            <strong>正しいURL例:</strong><br>
                            <code><?= esc($additional_data['example_url']) ?></code>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($additional_data['error_detail'])): ?>
                        <div class="alert alert-secondary">
                            <strong>技術的詳細:</strong><br>
                            <small><code><?= esc($additional_data['error_detail']) ?></code></small>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 解決方法の提案 -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-lightbulb me-2"></i>解決方法</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="bi bi-1-circle me-2"></i>従来の分析フォームを使用</h6>
                    <p>メーカーコードと品番から商品を選択して分析を実行できます。</p>
                    <a href="<?= site_url('sales-analysis/single-product') ?>" class="btn btn-primary">
                        <i class="bi bi-search me-2"></i>分析フォームを開く
                    </a>
                </div>
                
                <div class="col-md-6">
                    <h6><i class="bi bi-2-circle me-2"></i>正しいURLで再実行</h6>
                    <p>JANコードまたはSKUコードを正しい形式で指定してください。</p>
                    <div class="mb-2">
                        <small class="text-muted"><strong>JANコード例:</strong></small><br>
                        <code><?= site_url('sales-analysis/quick-analysis?jan_codes=1234567890123,9876543210987') ?></code>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted"><strong>SKUコード例:</strong></small><br>
                        <code><?= site_url('sales-analysis/quick-analysis?sku_codes=SKU001,SKU002,SKU003') ?></code>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- パラメータ説明 -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-question-circle me-2"></i>クイック分析URL仕様</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>パラメータ</th>
                            <th>説明</th>
                            <th>形式</th>
                            <th>例</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>jan_codes</code></td>
                            <td>JANコード（商品バーコード）を指定</td>
                            <td>カンマ区切りの文字列</td>
                            <td><code>1234567890123,9876543210987</code></td>
                        </tr>
                        <tr>
                            <td><code>sku_codes</code></td>
                            <td>SKUコード（在庫管理コード）を指定</td>
                            <td>カンマ区切りの文字列</td>
                            <td><code>SKU001,SKU002,SKU003</code></td>
                        </tr>
                        <tr>
                            <td><code>cost_method</code></td>
                            <td>原価計算方式（オプション）</td>
                            <td>average または latest</td>
                            <td><code>average</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="alert alert-info mt-3">
                <h6><i class="bi bi-info-circle me-2"></i>重要な注意事項</h6>
                <ul class="mb-0">
                    <li><code>jan_codes</code> と <code>sku_codes</code> のどちらか一方を指定してください</li>
                    <li>複数のコードを指定する場合は、カンマで区切ってください</li>
                    <li>空白や特殊文字は含めないでください</li>
                    <li>存在しないコードが含まれている場合、そのコードは無視されます</li>
                    <li>すべてのコードが無効な場合はエラーになります</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- ナビゲーション -->
    <div class="text-center mt-4">
        <a href="<?= site_url('sales-analysis') ?>" class="btn btn-outline-secondary btn-lg">
            <i class="bi bi-arrow-left me-2"></i>分析メニューに戻る
        </a>
        <a href="<?= site_url('sales-analysis/single-product') ?>" class="btn btn-primary btn-lg ms-3">
            <i class="bi bi-search me-2"></i>分析フォームで再実行
        </a>
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