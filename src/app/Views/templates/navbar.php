<?php
$session = session(); // セッション情報を取得 ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= site_url('menu') ?>"><?= esc(getenv('app.name') ?: 'アプリ名') ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= (uri_string() === 'menu') ? 'active' : '' ?>" aria-current="page" href="<?= site_url('menu') ?>"><i class="bi bi-house-door-fill"></i> ホーム</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= (str_starts_with(uri_string(), 'sales-analysis')) ? 'active' : '' ?>" href="#" id="navbarDropdownAnalysis" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bar-chart-line-fill"></i> 販売分析
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownAnalysis">
                        <li><a class="dropdown-item" href="<?= site_url('sales-analysis') ?>">分析メニュー</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= site_url('sales-analysis/single-product') ?>">単品販売分析（品番指定）</a></li>
                        <li><a class="dropdown-item" href="<?= site_url('sales-analysis/code-analysis') ?>">単品販売分析（コード個別指定）</a></li>
                        <li><a class="dropdown-item disabled" href="#">カテゴリ分析 <small class="text-muted">(準備中)</small></a></li>
                        <li><a class="dropdown-item disabled" href="#">期間比較分析 <small class="text-muted">(準備中)</small></a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= (str_starts_with(uri_string(), 'masters/import') || str_starts_with(uri_string(), 'slips/import') || str_starts_with(uri_string(), 'tasks')) ? 'active' : '' ?>" href="#" id="navbarDropdownImport" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-cloud-upload-fill"></i> データ管理・取込
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownImport">
                        <li><a class="dropdown-item <?= (str_starts_with(uri_string(), 'masters/import')) ? 'active' : '' ?>" href="<?= site_url('masters/import') ?>">各種マスタ取込</a></li>
                        <li><a class="dropdown-item <?= (str_starts_with(uri_string(), 'slips/import')) ? 'active' : '' ?>" href="<?= site_url('slips/import') ?>">各種伝票取込</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item <?= (str_starts_with(uri_string(), 'tasks')) ? 'active' : '' ?>" href="<?= site_url('tasks') ?>"><i class="bi bi-card-checklist"></i> 取込タスク一覧</a></li>
                    </ul>
                </li>
            </ul>
            <span class="navbar-text me-3 text-light">
                ようこそ、<?= esc($session->get('displayName') ?? 'ゲスト') ?> さん！
            </span>
            <a href="<?= site_url('logout') ?>" class="btn btn-danger"><i class="bi bi-box-arrow-right"></i> ログアウト</a>
        </div>
    </div>
</nav>