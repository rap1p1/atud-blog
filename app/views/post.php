<?php

use Atud\Core\Auth;
use Atud\Core\Csrf;
use Atud\Core\Lab;

$currentUser = Auth::user();
$statusLabel = match ($post['status']) {
    'draft' => 'Bản nháp',
    'pending' => 'Chờ duyệt',
    default => 'Đã xuất bản',
};
?>
<article class="article">
    <p class="eyebrow">Bài viết</p>
    <h1><?= e($post['title']) ?></h1>
    <p class="post-meta">
        <a href="/author?id=<?= (int) $post['author_id'] ?>"><?= e($post['author_name']) ?></a> · <?= e(format_datetime($post['created_at'])) ?>
        <?php if ($post['status'] !== 'published'): ?>
            <span class="badge badge-<?= e($post['status']) ?>"><?= e($statusLabel) ?></span>
        <?php endif; ?>
    </p>

    <?php if ($post['summary'] !== ''): ?>
        <p class="article-summary"><?= e($post['summary']) ?></p>
    <?php endif; ?>

    <div class="article-body">
        <?php foreach (preg_split('/\R{2,}/u', trim($post['body'])) ?: [] as $paragraph): ?>
            <p><?= nl2br(e($paragraph)) ?></p>
        <?php endforeach; ?>
    </div>
</article>

<section class="comments">
    <div class="section-head">
        <h2>Bình luận</h2>
        <p><?= count($comments) ?> bình luận</p>
    </div>

    <?php if ($comments === []): ?>
        <p class="empty">Chưa có bình luận nào.</p>
    <?php endif; ?>

    <?php foreach ($comments as $comment): ?>
        <article class="comment">
            <p class="post-meta"><?= e($comment['author_name']) ?> · <?= e(format_datetime($comment['created_at'])) ?></p>
            <div class="comment-body">
                <?php if (Lab::isVulnerable()): ?>
                    <?= $comment['body'] ?>
                <?php else: ?>
                    <?= nl2br(e($comment['body'])) ?>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>

    <?php if ($currentUser !== null): ?>
        <form class="stack-form" method="post" action="/comment">
            <?= Csrf::field() ?>
            <input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">
            <label for="body">Viết bình luận</label>
            <textarea id="body" name="body" rows="4" maxlength="2000" required></textarea>
            <button class="button" type="submit">Gửi bình luận</button>
        </form>
    <?php else: ?>
        <p class="notice">Cần <a href="/login">đăng nhập</a> để gửi bình luận.</p>
    <?php endif; ?>
</section>
