<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle ?? getenv('app.name') ?: 'アプリケーション') ?></title>
    
    <?php 
    // ========================================
    // アセットバージョン管理
    // ファイル更新時は該当バージョンを変更してください
    // ========================================
    $cssVersion = '1.0.1'; // CSS更新時にここを変更
    $jsVersion = '1.0.1';  // JavaScript更新時にここを変更
    ?>
    
    <link href="<?= base_url('assets/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- 販売分析CSS読み込み -->
    <?php if (isset($useSalesAnalysisCSS) && $useSalesAnalysisCSS): ?>
        <!-- 共通CSS（必須） -->
        <link rel="stylesheet" href="<?= base_url('assets/css/sales_analysis_common.css?v=' . $cssVersion) ?>">
        
        <!-- 画面別CSS -->
        <?php if (isset($salesAnalysisPage)): ?>
            <link rel="stylesheet" href="<?= base_url('assets/css/sales_analysis_' . esc($salesAnalysisPage) . '.css?v=' . $cssVersion) ?>">
        <?php endif; ?>
    <?php endif; ?>

    <?= $this->renderSection('styles') ?>
</head>
<body class="d-flex flex-column min-vh-100 <?= isset($bodyClass) ? esc($bodyClass) : '' ?>" 
      data-base-url="<?= base_url() ?>" 
      data-site-url="<?= site_url() ?>"
      data-api-base="<?= site_url('sales-analysis') ?>"
      data-js-version="<?= $jsVersion ?>">

    <?= $this->include('templates/navbar') ?>

    <main class="flex-grow-1 main-with-navbar">
        <?= $this->renderSection('content') ?>
    </main>

    <footer class="container mt-auto py-3 text-center">
        <hr>
        <p class="text-muted">
            &copy; <?= date('Y') ?> <?= esc(getenv('app.company') ?: '会社名') ?> |
            <?= esc(getenv('app.name') ?: 'アプリケーション') ?> Ver. <?= esc(getenv('app.version') ?: '1.0.0') ?>
        </p>
    </footer>

    <script src="<?= base_url('assets/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>