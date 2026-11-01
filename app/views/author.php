<?php

$roleLabel = match ($author['role']) {
    'admin' => 'Quản trị viên',
    'author' => 'Tác giả',
    default => 'Độc giả',
};
?>
<section class="section-head">
    <div>
        <p class="eyebrow">Tác giả</p>
        <h1><?= e($author['username']) ?></h1>
        <p class="muted"><?= e($roleLabel) ?> · Tham gia <?= e(format_datetime($author['created_at'])) ?></p>
    </div>
</section>

<div class="post-grid">
    <?php if ($posts === []): ?>
        <p class="empty">Tác giả chưa có bài viết nào được xuất bản.</p>
    <?php endif; ?>

    <?php foreach ($posts as $post): ?>
        <article class="post-card">
            <p class="post-meta"><?= e(format_datetime($post['created_at'])) ?></p>
            <h3><a href="/post?id=<?= (int) $post['id'] ?>"><?= e($post['title']) ?></a></h3>
            <p><?= e($post['summary'] !== '' ? $post['summary'] : excerpt($post['body'], 200)) ?></p>
            <a class="text-link" href="/post?id=<?= (int) $post['id'] ?>">Đọc bài viết</a>
        </article>
    <?php endforeach; ?>
</div>
