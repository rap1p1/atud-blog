<?php

$pages = (int) ceil($total / max(1, $perPage));
?>
<section class="hero">
    <div>
        <p class="eyebrow">Ghi chép và chia sẻ</p>
        <h1>Những điều nhỏ trong ngày</h1>
        <p class="lede">Một góc nhỏ để lưu lại những chuyến đi, cuốn sách đang đọc, thói quen buổi sáng và vài điều đáng nhớ.</p>
    </div>
    <div class="mode-card">
        <p class="mode-card-label">Về blog này</p>
        <p class="mode-card-value">Góc nhỏ</p>
        <p class="mode-card-note">Bài viết được cập nhật không theo lịch cố định. Mỗi bài là một ghi chép ngắn, đủ để đọc trong vài phút.</p>
        <a class="text-link" href="/about">Đọc giới thiệu</a>
    </div>
</section>

<section class="section-head">
    <h2>Bài viết mới</h2>
    <p><?= (int) $total ?> bài viết đã xuất bản</p>
</section>

<div class="post-grid">
    <?php if ($posts === []): ?>
        <p class="empty">Chưa có bài viết nào được xuất bản.</p>
    <?php endif; ?>
    <?php foreach ($posts as $post): ?>
        <article class="post-card">
            <p class="post-meta"><a href="/author?id=<?= (int) $post['author_id'] ?>"><?= e($post['author_name']) ?></a> · <?= e(format_datetime($post['created_at'])) ?></p>
            <h3><a href="/post?id=<?= (int) $post['id'] ?>"><?= e($post['title']) ?></a></h3>
            <p><?= e($post['summary'] !== '' ? $post['summary'] : excerpt($post['body'], 200)) ?></p>
            <a class="text-link" href="/post?id=<?= (int) $post['id'] ?>">Đọc bài viết</a>
        </article>
    <?php endforeach; ?>
</div>

<?php if ($pages > 1): ?>
    <nav class="pagination" aria-label="Phân trang">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a class="<?= $i === $page ? 'is-current' : '' ?>" href="/?page=<?= $i ?>"><?= $i ?></a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
