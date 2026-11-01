<?php

use Atud\Core\Csrf;
?>
<section class="auth-layout">
    <div class="auth-panel">
        <p class="eyebrow">Xác thực</p>
        <h1>Đăng nhập</h1>
        <p class="muted">Đăng nhập để bình luận và quản lý bài viết của bạn.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>



        <form class="stack-form" method="post" action="/login">
            <?= Csrf::field() ?>
            <label for="username">Tên đăng nhập</label>
            <input id="username" name="username" type="text" value="<?= e($username ?? '') ?>" autocomplete="username" required>

            <label for="password">Mật khẩu</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>

            <button class="button" type="submit">Đăng nhập</button>
        </form>

        <p class="auth-links"><a href="/forgot">Quên mật khẩu</a> · <a href="/register">Tạo tài khoản</a></p>
    </div>

    <aside class="auth-note">
        <h2>Tài khoản</h2>
        <p>Đăng nhập để bình luận, lưu bài viết và quản lý nội dung của bạn.</p>
        <p>Nếu quên mật khẩu, dùng liên kết <a href="/forgot">Quên mật khẩu</a> để tạo liên kết đặt lại.</p>
    </aside>
</section>
