<?php

use Atud\Core\Csrf;

$statusLabels = ['draft' => 'Bản nháp', 'pending' => 'Chờ duyệt', 'published' => 'Xuất bản'];
?>
<section class="section-head">
    <div>
        <p class="eyebrow">Quản trị</p>
        <h1>Điều hành nội dung</h1>
        <p class="muted">Kiểm tra bài chờ duyệt, quản lý bài viết và bình luận.</p>
    </div>
</section>

<div class="stat-grid">
    <div class="stat"><span><?= (int) $stats['users'] ?></span><p>Tài khoản</p></div>
    <div class="stat"><span><?= (int) $stats['posts'] ?></span><p>Bài viết</p></div>
    <div class="stat"><span><?= (int) $stats['pending'] ?></span><p>Chờ duyệt</p></div>
    <div class="stat"><span><?= (int) $stats['comments'] ?></span><p>Bình luận</p></div>
</div>

<section class="panel">
    <h2>Bài viết</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>Tiêu đề</th>
                <th>Tác giả</th>
                <th>Trạng thái</th>
                <th>Cập nhật</th>
                <th>Thao tác</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($posts as $post): ?>
                <tr>
                    <td><a href="/post?id=<?= (int) $post['id'] ?>"><?= e($post['title']) ?></a></td>
                    <td><?= e($post['author_name']) ?></td>
                    <td><span class="badge badge-<?= e($post['status']) ?>"><?= e($statusLabels[$post['status']] ?? $post['status']) ?></span></td>
                    <td><?= e(format_datetime($post['updated_at'])) ?></td>
                    <td class="actions">
                        <?php if ($post['status'] === 'pending'): ?>
                            <form method="post" action="/admin/post/publish">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                                <button class="link-button" type="submit">Xuất bản</button>
                            </form>
                        <?php endif; ?>
                        <a href="/editor/edit?id=<?= (int) $post['id'] ?>">Sửa</a>
                        <form method="post" action="/admin/post/delete" data-confirm="Xóa bài viết và các bình luận liên quan?">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                            <button class="link-button" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <h2>Bình luận gần đây</h2>
    <?php if ($comments === []): ?>
        <p class="empty">Chưa có bình luận.</p>
    <?php endif; ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>Bài viết</th>
                <th>Người gửi</th>
                <th>Nội dung</th>
                <th>Thời gian</th>
                <th>Thao tác</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($comments as $comment): ?>
                <tr>
                    <td><?= e($comment['post_title']) ?></td>
                    <td><?= e($comment['author_name']) ?></td>
                    <td class="cell-clip"><?= e(excerpt($comment['body'], 120)) ?></td>
                    <td><?= e(format_datetime($comment['created_at'])) ?></td>
                    <td class="actions">
                        <form method="post" action="/admin/comment/delete" data-confirm="Xóa bình luận này?">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int) $comment['id'] ?>">
                            <button class="link-button" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <h2>Tài khoản</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>Tên đăng nhập</th>
                <th>Email</th>
                <th>Vai trò</th>
                <th>Trạng thái</th>
                <th>Bài viết</th>
                <th>Ngày tạo</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $item): ?>
                <tr>
                    <td><?= e($item['username']) ?></td>
                    <td><?= e($item['email']) ?></td>
                    <td><?= $item['role'] === 'admin' ? 'Quản trị viên' : ($item['role'] === 'author' ? 'Tác giả' : 'Độc giả') ?></td>
                    <td><?= (int) $item['is_active'] === 1 ? 'Đang hoạt động' : 'Tạm khóa' ?></td>
                    <td><?= (int) $item['post_count'] ?></td>
                    <td><?= e(format_datetime($item['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
