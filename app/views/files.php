<?php

use Atud\Core\Csrf;
?>
<section class="section-head">
    <div>
        <p class="eyebrow">Tệp và tài liệu</p>
        <h1>Tệp và tài liệu</h1>
        <p class="muted">Tệp công khai được mọi tài khoản đã đăng nhập xem; tệp riêng chỉ chủ sở hữu và quản trị viên.</p>
    </div>
</section>

<section class="panel">
    <h2>Tải tệp lên</h2>
    <form class="stack-form" method="post" action="/files/upload" enctype="multipart/form-data">
        <?= Csrf::field() ?>
        <label for="file">Chọn tệp</label>
        <input id="file" name="file" type="file" required>
        <label class="checkbox-label"><input type="checkbox" name="is_public" value="1"> Cho phép mọi tài khoản xem</label>
        <button class="button" type="submit">Tải lên</button>
    </form>
</section>

<section class="panel">
    <h2>Danh sách tệp</h2>
    <?php if ($uploads === []): ?>
        <p class="empty">Chưa có tệp nào.</p>
    <?php endif; ?>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>Tên tệp</th>
                <th>Người tải</th>
                <th>Kích thước</th>
                <th>Phạm vi</th>
                <th>Thao tác</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($uploads as $upload): ?>
                <tr>
                    <td><?= e($upload['original_name']) ?></td>
                    <td><?= e($upload['username']) ?></td>
                    <td><?= number_format(((int) $upload['size_bytes']) / 1024, 1) ?> KB</td>
                    <td><?= (int) $upload['is_public'] === 1 ? 'Công khai' : 'Riêng tư' ?></td>
                    <td class="actions">
                        <a href="/download?id=<?= (int) $upload['id'] ?>">Tải xuống</a>
                        <?php if (str_starts_with((string) $upload['mime_type'], 'image/')): ?>
                            <a href="/download?id=<?= (int) $upload['id'] ?>&amp;inline=1">Xem</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
