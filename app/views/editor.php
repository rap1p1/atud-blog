<?php

use Atud\Core\Auth;
use Atud\Core\Csrf;
use Atud\Core\Lab;

$currentUser = Auth::user();
$formMode = $formMode ?? false;
$selectedStatus = $post['status'] ?? 'draft';
$statusLabels = ['draft' => 'Bản nháp', 'pending' => 'Chờ duyệt', 'published' => 'Xuất bản'];
?>
<section class="section-head">
    <div>
        <p class="eyebrow">Khu vực tác giả</p>
        <h1><?= $formMode ? ($post !== null && !empty($post['id']) ? 'Sửa bài viết' : 'Tạo bài viết') : 'Bài viết của tôi' ?></h1>
        <p class="muted">Tác giả có thể lưu nháp, gửi duyệt hoặc xuất bản bài của mình.</p>
    </div>
    <?php if (!$formMode): ?>
        <a class="button" href="/editor/new">Tạo bài viết</a>
    <?php endif; ?>
</section>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error" role="alert">
        <?php foreach ($errors as $error): ?>
            <p><?= e($error) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($formMode): ?>
    <section class="panel">
        <form class="stack-form" method="post" action="/editor/save">
            <?= Csrf::field() ?>
            <?php if ($post !== null && !empty($post['id'])): ?>
                <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
            <?php endif; ?>

            <label for="title">Tiêu đề</label>
            <input id="title" name="title" type="text" value="<?= e($post['title'] ?? '') ?>" maxlength="200" required>

            <label for="summary">Tóm tắt</label>
            <textarea id="summary" name="summary" rows="2" maxlength="500"><?= e($post['summary'] ?? '') ?></textarea>

            <label for="body">Nội dung</label>
            <textarea id="body" name="body" rows="12" required><?= e($post['body'] ?? '') ?></textarea>

            <label for="status">Trạng thái</label>
            <select id="status" name="status">
                <option value="draft" <?= $selectedStatus === 'draft' ? 'selected' : '' ?>>Bản nháp</option>
                <option value="pending" <?= $selectedStatus === 'pending' ? 'selected' : '' ?>>Chờ duyệt</option>
                <?php if (Lab::isVulnerable() || in_array($currentUser['role'], ['author', 'admin'], true)): ?>
                    <option value="published" <?= $selectedStatus === 'published' ? 'selected' : '' ?>>Xuất bản</option>
                <?php endif; ?>
            </select>

            <div class="form-actions">
                <button class="button" type="submit">Lưu bài viết</button>
                <a class="text-link" href="/editor">Quay lại danh sách</a>
            </div>
        </form>
    </section>
<?php endif; ?>

<section class="panel">
    <h2>Danh sách bài viết</h2>
    <?php if ($posts === []): ?>
        <p class="empty">Bạn chưa có bài viết nào.</p>
    <?php endif; ?>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>Tiêu đề</th>
                <th>Trạng thái</th>
                <th>Cập nhật</th>
                <th>Thao tác</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($posts as $item): ?>
                <tr>
                    <td><?= e($item['title']) ?></td>
                    <td><span class="badge badge-<?= e($item['status']) ?>"><?= e($statusLabels[$item['status']] ?? $item['status']) ?></span></td>
                    <td><?= e(format_datetime($item['updated_at'])) ?></td>
                    <td class="actions">
                        <a href="/editor/edit?id=<?= (int) $item['id'] ?>">Sửa</a>
                        <a href="/post?id=<?= (int) $item['id'] ?>">Xem</a>
                        <form method="post" action="/editor/delete" data-confirm="Xóa bài viết này?">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                            <button class="link-button" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
