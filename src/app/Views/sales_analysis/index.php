<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container sales-analysis">
    <!-- ヘッダーセクション -->
    <div class="header-section">
        <h1 class="page-title">📊 商品販売分析システム</h1>
        <p class="page-subtitle">データドリブンな意思決定を支援する総合分析プラットフォーム</p>
    </div>
    
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <div class="col">
            <div class="card-modern h-100">
                <div class="card-body text-center">
                    <div class="card-icon">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <h5 class="card-title">単品分析</h5>
                    <p class="card-text">
                        指定した商品の週別販売推移、原価回収率、在庫処分判定を分析します。
                        品出し後の販売状況を詳細に確認できます。
                    </p>
                    <a href="<?= site_url('sales-analysis/single-product') ?>" class="btn btn-primary">
                        <i class="bi bi-search me-2"></i>単品分析を開始
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col">
            <div class="card-modern h-100">
                <div class="card-body text-center">
                    <div class="card-icon">
                        <i class="bi bi-upc-scan"></i>
                    </div>
                    <h5 class="card-title">コード分析</h5>
                    <p class="card-text">
                        JANコードやSKUコードを直接入力して分析します。
                        複数商品の同時分析や一括入力に対応しています。
                    </p>
                    <a href="<?= site_url('sales-analysis/code-analysis') ?>" class="btn btn-success">
                        <i class="bi bi-qr-code me-2"></i>コード分析を開始
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col">
            <div class="card-modern h-100 disabled">
                <div class="card-body text-center">
                    <div class="card-icon">
                        <i class="bi bi-bar-chart-line"></i>
                    </div>
                    <h5 class="card-title">カテゴリ分析</h5>
                    <p class="card-text">
                        部門やカテゴリ別の販売動向を分析します。
                        複数商品の比較分析が可能です。
                    </p>
                    <button class="btn btn-outline-secondary" disabled>
                        <i class="bi bi-wrench me-2"></i>準備中
                    </button>
                </div>
            </div>
        </div>
        
        <div class="col">
            <div class="card-modern h-100 disabled">
                <div class="card-body text-center">
                    <div class="card-icon">
                        <i class="bi bi-calendar-week"></i>
                    </div>
                    <h5 class="card-title">期間比較分析</h5>
                    <p class="card-text">
                        前年同期や前シーズンとの比較分析を行います。
                        トレンド分析に活用できます。
                    </p>
                    <button class="btn btn-outline-secondary" disabled>
                        <i class="bi bi-wrench me-2"></i>準備中
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info mt-4" role="alert">
        <h6 class="alert-heading">
            <i class="bi bi-info-circle me-2"></i>販売分析について
        </h6>
        <p class="mb-0">
            販売分析機能では、基幹システムから取り込まれた売上データ、仕入データ、移動データを活用して、
            商品の販売状況を多角的に分析します。原価回収率の算出、在庫処分判定、値引きタイミングの提案など、
            データに基づいた意思決定をサポートします。
        </p>
        <hr>
        <div class="row mt-3">
            <div class="col-md-6">
                <h6><i class="bi bi-search me-2"></i>単品分析の特徴</h6>
                <ul class="mb-0">
                    <li>メーカーと品番から商品を選択</li>
                    <li>サイズ・カラー統合集計</li>
                    <li>週別販売推移分析</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6><i class="bi bi-qr-code me-2"></i>コード分析の特徴</h6>
                <ul class="mb-0">
                    <li>JANコード・SKUコード直接入力</li>
                    <li>複数商品同時分析対応</li>
                    <li>バーコードスキャナー対応</li>
                </ul>
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