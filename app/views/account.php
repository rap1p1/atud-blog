<?php

use Atud\Core\Csrf;
?>
<section class="section-head">
    <div>
        <p class="eyebrow">Tài khoản</p>
        <h1><?= e($user['username']) ?></h1>
        <p class="muted">Vai trò: <?= $user['role'] === 'admin' ? 'Quản trị viên' : ($user['role'] === 'author' ? 'Tác giả' : 'Độc giả') ?> · Tham gia <?= e(format_datetime($user['created_at'])) ?></p>
    </div>
</section>

<div class="two-columns">
    <section class="panel">
        <h2>Địa chỉ email</h2>
        <p class="muted">Email dùng cho khôi phục mật khẩu và liên hệ.</p>
        <form class="stack-form" method="post" action="/account/email">
            <?= Csrf::field() ?>
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="<?= e($user['email']) ?>" required>
            <button class="button" type="submit">Cập nhật email</button>
        </form>
    </section>

    <section class="panel">
        <h2>Đổi mật khẩu</h2>
        <p class="muted">Mật khẩu mới cần tối thiểu 10 ký tự.</p>
        <form class="stack-form" method="post" action="/account/password">
            <?= Csrf::field() ?>
            <label for="current_password">Mật khẩu hiện tại</label>
            <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>

            <label for="password">Mật khẩu mới</label>
            <input id="password" name="password" type="password" autocomplete="new-password" minlength="10" required>

            <label for="confirm">Nhập lại mật khẩu mới</label>
            <input id="confirm" name="confirm" type="password" autocomplete="new-password" minlength="10" required>

            <button class="button" type="submit">Đổi mật khẩu</button>
        </form>
    </section>
</div>
