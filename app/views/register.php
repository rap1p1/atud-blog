<?php

use Atud\Core\Csrf;
?>
<section class="auth-layout">
    <div class="auth-panel">
        <p class="eyebrow">Tài khoản</p>
        <h1>Đăng ký</h1>
        <p class="muted">Tài khoản mới có vai trò người dùng thông thường.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error" role="alert">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="stack-form" method="post" action="/register">
            <?= Csrf::field() ?>
            <label for="username">Tên đăng nhập</label>
            <input id="username" name="username" type="text" value="<?= e($username ?? '') ?>" autocomplete="username" required>

            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="<?= e($email ?? '') ?>" autocomplete="email" required>

            <label for="password">Mật khẩu</label>
            <input id="password" name="password" type="password" autocomplete="new-password" minlength="10" required>

            <label for="confirm">Nhập lại mật khẩu</label>
            <input id="confirm" name="confirm" type="password" autocomplete="new-password" minlength="10" required>

            <button class="button" type="submit">Tạo tài khoản</button>
        </form>

        <p class="auth-links"><a href="/login">Đã có tài khoản</a></p>
    </div>
</section>
