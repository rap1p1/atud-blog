<?php

use Atud\Core\Csrf;
?>
<section class="auth-layout">
    <div class="auth-panel">
        <p class="eyebrow">Khôi phục truy cập</p>
        <h1>Đặt lại mật khẩu</h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error" role="alert">
                <?php foreach ($errors as $item): ?>
                    <p><?= e($item) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($token)): ?>
            <p class="muted">Tài khoản: <strong><?= e($username ?? '') ?></strong></p>
            <form class="stack-form" method="post" action="/reset">
                <?= Csrf::field() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">

                <label for="password">Mật khẩu mới</label>
                <input id="password" name="password" type="password" autocomplete="new-password" minlength="10" required>

                <label for="confirm">Nhập lại mật khẩu</label>
                <input id="confirm" name="confirm" type="password" autocomplete="new-password" minlength="10" required>

                <button class="button" type="submit">Đặt lại mật khẩu</button>
            </form>
        <?php else: ?>
            <p class="notice"><a href="/forgot">Tạo liên kết mới</a></p>
        <?php endif; ?>
    </div>
</section>
