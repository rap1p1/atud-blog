<?php

use Atud\Core\Auth;

try {
    $currentUser = Auth::user();
} catch (Throwable) {
    $currentUser = null;
}
$flashes = take_flashes();
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Góc nhỏ') ?> | Góc nhỏ</title>
    <link rel="stylesheet" href="<?= asset('style.css') ?>">
</head>
<body>
<header class="site-header">
    <div class="wrap header-inner">
        <a class="brand" href="/">
            <span class="brand-mark">GN</span>
            <span class="brand-text">Góc nhỏ</span>
        </a>
        <nav class="main-nav" aria-label="Điều hướng chính">
            <a href="/">Trang chủ</a>
            <a href="/search">Tìm kiếm</a>
            <?php if ($currentUser !== null): ?>
                <a href="/files">Tệp</a>
                <a href="/editor">Bài viết</a>
                <?php if ($currentUser['role'] === 'admin'): ?>
                    <a href="/admin">Quản trị</a>
                <?php endif; ?>
                <a href="/account">Tài khoản</a>
                <a class="nav-logout" href="/logout">Đăng xuất</a>
            <?php else: ?>
                <a href="/login">Đăng nhập</a>
                <a class="nav-cta" href="/register">Đăng ký</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="wrap site-main">
    <?php foreach ($flashes as $item): ?>
        <div class="alert alert-<?= e($item['type']) ?>" role="alert"><?= e($item['message']) ?></div>
    <?php endforeach; ?>

    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="wrap footer-inner">
        <p>Góc nhỏ - nơi lưu lại những câu chuyện, cuốn sách và thói quen trong ngày.</p>
        <p><a href="/about">Giới thiệu</a></p>
    </div>
</footer>

<script src="<?= asset('app.js') ?>"></script>
</body>
</html>
