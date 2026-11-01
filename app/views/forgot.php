<?php

use Atud\Core\Csrf;
?>
<section class="auth-layout">
    <div class="auth-panel">
        <p class="eyebrow">Khôi phục truy cập</p>
        <h1>Quên mật khẩu</h1>
        <p class="muted">Nhập email đã đăng ký để tạo liên kết đặt lại mật khẩu.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="status"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if (!empty($resetLink)): ?>
            <div class="debug-box">
                <p>Liên kết đặt lại mật khẩu</p>
                <p><a href="<?= e($resetLink) ?>"><?= e($resetLink) ?></a></p>
            </div>
        <?php endif; ?>

        <form class="stack-form" method="post" action="/forgot">
            <?= Csrf::field() ?>
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="<?= e($email ?? '') ?>" autocomplete="email" required>
            <button class="button" type="submit">Tạo liên kết</button>
        </form>
    </div>
</section>
