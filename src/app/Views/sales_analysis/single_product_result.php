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

    <!-- ヘッダー情報 -->
    <div class="header-section">
        <h2>商品販売分析 - 単品集計表</h2>
        <p>メーカー: サンプル商事 (0001) | 品番: S-001 | 品名: カットソー | シーズン: 2025SS</p>
        <p>品出し日: 2025-04-01 | 経過日数: 62日 | 仕入日: 2025-03-25</p>
        <p>廃盤予定日: 2025-07-31 | 仕入単価: ¥1,500 | 定価: ¥1,800</p>
    </div>

    <!-- サマリー情報 -->
    <div class="summary-section">
        <div class="summary-card">
            <h4>仕入原価合計</h4>
            <div class="value">¥180,000</div>
        </div>
        <div class="summary-card">
            <h4>売上合計</h4>
            <div class="value">¥195,600</div>
        </div>
        <div class="summary-card">
            <h4>粗利合計</h4>
            <div class="value recovery-rate">¥15,600</div>
        </div>
        <div class="summary-card">
            <h4>原価回収率</h4>
            <div class="value recovery-rate">108.7%</div>
        </div>
        <div class="summary-card">
            <h4>残在庫数</h4>
            <div class="value">12個</div>
        </div>
        <div class="summary-card">
            <h4>残在庫原価</h4>
            <div class="value">¥18,000</div>
        </div>
        <div class="summary-card">
            <h4>総販売数</h4>
            <div class="value">108個</div>
        </div>
        <div class="summary-card">
            <h4>定価</h4>
            <div class="value">¥1,800</div>
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
                <tr class="best-seller">
                    <td>1週目</td>
                    <td class="text-left">04/01-04/07</td>
                    <td>32</td>
                    <td>¥1,800</td>
                    <td>¥57,600</td>
                    <td>¥9,600</td>
                    <td>32</td>
                    <td>¥9,600</td>
                    <td class="recovery-rate">53.3%</td>
                    <td class="text-left">定価販売</td>
                </tr>
                <tr>
                    <td>2週目</td>
                    <td class="text-left">04/08-04/14</td>
                    <td>28</td>
                    <td>¥1,800</td>
                    <td>¥50,400</td>
                    <td>¥8,400</td>
                    <td>60</td>
                    <td>¥18,000</td>
                    <td class="recovery-rate">100.0%</td>
                    <td class="text-left">原価回収達成</td>
                </tr>
                <tr>
                    <td>3週目</td>
                    <td class="text-left">04/15-04/21</td>
                    <td>18</td>
                    <td>¥1,800</td>
                    <td>¥32,400</td>
                    <td>¥5,400</td>
                    <td>78</td>
                    <td>¥23,400</td>
                    <td class="recovery-rate">113.0%</td>
                    <td class="text-left">売れ行き鈍化</td>
                </tr>
                <tr class="price-change">
                    <td>4週目</td>
                    <td class="text-left">04/22-04/28</td>
                    <td>15</td>
                    <td>¥1,440</td>
                    <td>¥21,600</td>
                    <td>¥-900</td>
                    <td>93</td>
                    <td>¥22,500</td>
                    <td class="recovery-rate">112.5%</td>
                    <td class="text-left">20%値引開始</td>
                </tr>
                <tr class="price-change">
                    <td>5週目</td>
                    <td class="text-left">04/29-05/05</td>
                    <td>12</td>
                    <td>¥1,200</td>
                    <td>¥14,400</td>
                    <td>¥-3,600</td>
                    <td>105</td>
                    <td>¥18,900</td>
                    <td class="recovery-rate">110.5%</td>
                    <td class="text-left">33%値引</td>
                </tr>
                <tr>
                    <td>6週目</td>
                    <td class="text-left">05/06-05/12</td>
                    <td>3</td>
                    <td>¥1,200</td>
                    <td>¥3,600</td>
                    <td>¥-900</td>
                    <td>108</td>
                    <td>¥18,000</td>
                    <td class="recovery-rate">110.0%</td>
                    <td class="text-left">売れ行き低迷</td>
                </tr>
                <tr>
                    <td>7週目</td>
                    <td class="text-left">05/13-05/19</td>
                    <td>0</td>
                    <td>-</td>
                    <td>¥0</td>
                    <td>¥0</td>
                    <td>108</td>
                    <td>¥18,000</td>
                    <td class="recovery-rate">110.0%</td>
                    <td class="text-left">販売停滞</td>
                </tr>
                <tr>
                    <td>8週目</td>
                    <td class="text-left">05/20-05/26</td>
                    <td>0</td>
                    <td>-</td>
                    <td>¥0</td>
                    <td>¥0</td>
                    <td>108</td>
                    <td>¥18,000</td>
                    <td class="recovery-rate">110.0%</td>
                    <td class="text-left">在庫処分検討</td>
                </tr>
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
                <tr>
                    <td>¥1,800</td>
                    <td>78</td>
                    <td>¥140,400</td>
                    <td>72.2%</td>
                    <td>0%</td>
                    <td class="text-left">04/01-04/21</td>
                </tr>
                <tr class="price-change">
                    <td>¥1,440</td>
                    <td>15</td>
                    <td>¥21,600</td>
                    <td>13.9%</td>
                    <td>20%</td>
                    <td class="text-left">04/22-04/28</td>
                </tr>
                <tr class="price-change">
                    <td>¥1,200</td>
                    <td>15</td>
                    <td>¥18,000</td>
                    <td>13.9%</td>
                    <td>33%</td>
                    <td class="text-left">04/29-05/12</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- 推奨アクション -->
    <div class="recommendation-section">
        <p><strong><i class="bi bi-check-circle me-2"></i>判定: 在庫処分実行可能</strong></p>
        <p><strong>理由:</strong> 粗利が仕入金額を超過（¥15,600 > ¥0）、2週目で原価回収率100%達成</p>
        <p><strong>推奨アクション:</strong> 残在庫12個の早期処分を実行。50%値引きでも利益確保可能</p>
        <p><strong>廃盤まで:</strong> あと59日（7月31日まで）</p>
    </div>

    <!-- 操作ボタン -->
    <div class="text-center mt-4">
        <a href="<?= site_url('sales-analysis/single-product') ?>" class="btn btn-primary btn-lg">
            <i class="bi bi-search me-2"></i>新しい分析を実行
        </a>
        <a href="<?= site_url('sales-analysis') ?>" class="btn btn-outline-secondary btn-lg ms-3">
            <i class="bi bi-arrow-left me-2"></i>分析メニューに戻る
        </a>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('単品分析結果画面が読み込まれました');
    
    // 今後、データの動的読み込みやチャート表示などを実装予定
});
</script>
<?= $this->endSection() ?>