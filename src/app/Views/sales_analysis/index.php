<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container sales-analysis">
    <!-- ヘッダーセクション -->
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">📊 商品販売分析</h2>
        </div>
    </div>
    
    <!-- 分析機能一覧（既存メニューと同じレイアウト） -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <!-- 単品販売分析 -->
        <div class="col">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <div class="card-icon text-primary">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <h5 class="card-title mt-3">単品販売分析</h5>
                    <p class="card-text">
                        指定した商品の週別販売推移、原価回収率、在庫処分判定を分析します。
                        品出し後の販売状況を詳細に確認できます。
                    </p>
                    <button class="btn btn-outline-primary stretched-link" data-bs-toggle="modal" data-bs-target="#singleProductModal">
                        分析を開始
                    </button>
                </div>
            </div>
        </div>
        
        <!-- カテゴリ分析 -->
        <div class="col">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <div class="card-icon text-success">
                        <i class="bi bi-bar-chart-line"></i>
                    </div>
                    <h5 class="card-title mt-3">カテゴリ分析</h5>
                    <p class="card-text">
                        部門やカテゴリ別の販売動向を分析します。
                        複数商品の比較分析が可能です。
                    </p>
                    <button class="btn btn-outline-secondary stretched-link" disabled>
                        <i class="bi bi-wrench me-2"></i>準備中
                    </button>
                </div>
            </div>
        </div>
        
        <!-- 期間比較分析 -->
        <div class="col">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <div class="card-icon text-warning">
                        <i class="bi bi-calendar-week"></i>
                    </div>
                    <h5 class="card-title mt-3">期間比較分析</h5>
                    <p class="card-text">
                        前年同期や前シーズンとの比較分析を行います。
                        季節性やトレンド分析に活用できます。
                    </p>
                    <button class="btn btn-outline-secondary stretched-link" disabled>
                        <i class="bi bi-wrench me-2"></i>準備中
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 利用ガイド -->
    <div class="alert alert-info mt-4" role="alert">
        <h6 class="alert-heading">
            <i class="bi bi-info-circle me-2"></i>販売分析について
        </h6>
        <p class="mb-0">
            販売分析機能では、基幹システムから取り込まれた売上データ、仕入データ、移動データを活用して、
            商品の販売状況を多角的に分析します。原価回収率の算出、在庫処分判定、値引きタイミングの提案など、
            データに基づいた意思決定をサポートします。
        </p>
    </div>
</div>

<!-- 単品分析の入口選択モーダル -->
<div class="modal fade" id="singleProductModal" tabindex="-1" aria-labelledby="singleProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="singleProductModalLabel">
                    <i class="bi bi-graph-up-arrow me-2"></i>単品販売分析 - 商品選択方法
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-4">分析する商品の選択方法を選んでください。</p>
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <a href="<?= site_url('sales-analysis/single-product') ?>" class="sa-menu-selection-card">
                            <div class="text-center">
                                <div class="text-primary mb-3">
                                    <i class="bi bi-search" style="font-size: 2.5rem;"></i>
                                </div>
                                <h6 class="fw-bold">メーカー・品番検索</h6>
                                <p class="text-muted small mb-3">
                                    メーカー名と品番から商品を検索・選択<br>
                                    サイズ・カラー展開商品の統合分析に対応
                                </p>
                                <span class="badge bg-primary">推奨：商品情報が分かる場合</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?= site_url('sales-analysis/code-analysis') ?>" class="sa-menu-selection-card">
                            <div class="text-center">
                                <div class="text-success mb-3">
                                    <i class="bi bi-upc-scan" style="font-size: 2.5rem;"></i>
                                </div>
                                <h6 class="fw-bold">コード直接入力</h6>
                                <p class="text-muted small mb-3">
                                    JANコード・SKUを直接入力して分析<br>
                                    バーコードスキャナーでの一括読取りに対応
                                </p>
                                <span class="badge bg-success">推奨：バーコード読取り・一括入力</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i>
                    どちらの方法でも同じ分析結果が得られます。お手元の情報に応じて選択してください。
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// CSS読み込みフラグとbodyクラスを設定
$this->setData([
    'useSalesAnalysisCSS' => true,
    'salesAnalysisPage' => 'menu',
    'bodyClass' => 'sales-analysis-menu'
]);
?>

<?= $this->endSection() ?>