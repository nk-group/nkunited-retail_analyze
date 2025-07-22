<?= $this->extend('layouts/auth_layout') ?>

<?= $this->section('content') ?>
<div class="container">
    <div class="login-card mx-auto"> 
        <div class="card shadow">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <?php
                        $logoPath = 'assets/images/logo.png'; // ロゴ画像のパス
                        if (file_exists(FCPATH . $logoPath)):
                    ?>
                    <img src="<?= base_url($logoPath) ?>" alt="<?= esc(getenv('app.name') ?: '') ?> Logo" style="max-height: 60px; margin-bottom: 1rem;">
                    <?php endif; ?>
                    <h4><?= esc($pageTitle ?? 'ログイン') ?></h4>
                </div>
                
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= session()->getFlashdata('error') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?= form_open(site_url('login/attempt')) ?>
                    <div class="mb-3">
                        <label for="username" class="form-label">ユーザー名</label>
                        <input type="text" name="username" id="username" class="form-control" value="<?= old('username') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">パスワード</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">ログイン</button>
                    </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<!-- ログインページ固有のスタイル -->
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- ログインページ固有のスクリプト -->
<?= $this->endSection() ?>