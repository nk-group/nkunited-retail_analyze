<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container">
    <h2 class="mb-4">メインメニュー</h2>
    
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <div class="col">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <div class="card-icon text-primary"><i class="bi bi-graph-up-arrow"></i></div>
                    <h5 class="card-title mt-3">商品販売分析</h5>
                    <p class="card-text">商品の販売傾向や実績を詳細に分析し、売上向上に繋がるインサイトを得ます。</p>
                    <a href="<?= site_url('sales-analysis') ?>" class="btn btn-outline-primary stretched-link">分析を開始</a>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <div class="card-icon text-success"><i class="bi bi-file-earmark-arrow-up-fill"></i></div>
                    <h5 class="card-title mt-3">各種マスタ取込</h5>
                    <p class="card-text">商品、店舗、顧客などのマスタデータをシステムに一括で取り込み、管理します。</p>
                    <a href="<?= site_url('masters/import') ?>" class="btn btn-outline-success stretched-link">マスタ取込へ</a>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <div class="card-icon text-warning"><i class="bi bi-receipt-cutoff"></i></div>
                    <h5 class="card-title mt-3">各種伝票取込</h5>
                    <p class="card-text">日々の売上伝票や仕入伝票などの取引データを効率的に取り込み、記録します。</p>
                    <a href="<?= site_url('slips/import') ?>" class="btn btn-outline-warning stretched-link">伝票取込へ</a>
                </div>
            </div>
        </div>
        
        <div class="col">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <div class="card-icon text-info"><i class="bi bi-card-checklist"></i></div>
                    <h5 class="card-title mt-3">取込タスク一覧</h5>
                    <p class="card-text">アップロードされたファイルの処理状況や結果を確認し、管理します。</p>
                    <a href="<?= site_url('tasks') ?>" class="btn btn-outline-info stretched-link">タスク一覧へ</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?php /* メニューページ固有のスタイルやスクリプト */ ?>
<?= $this->section('styles') ?>
<?php /* <link rel="stylesheet" href="<?= base_url('assets/css/menu_specific.css') ?>"> */ ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php /* <script src="<?= base_url('assets/js/menu_specific.js') ?>"></script> */ ?>
<?= $this->endSection() ?>