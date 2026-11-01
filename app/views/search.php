<?php

use Atud\Core\Lab;
?>
<section class="section-head">
    <div>
        <p class="eyebrow">Tìm kiếm</p>
        <h1>Tra cứu bài viết</h1>
    </div>
</section>

<form class="search-form" method="get" action="/search">
    <label for="q">Từ khóa</label>
    <div class="search-row">
        <input id="q" name="q" type="text" value="<?= e($q) ?>" maxlength="200" placeholder="Nhập từ khóa cần tìm">
        <button class="button" type="submit">Tìm kiếm</button>
    </div>
</form>

<?php if ($q !== ''): ?>
    <section class="results">
        <h2 class="search-summary">Kết quả cho: <?= Lab::isVulnerable() ? $q : e($q) ?></h2>
        <p class="muted"><?= count($posts) ?> bài viết phù hợp.</p>

        <?php if ($posts === []): ?>
            <p class="empty">Không tìm thấy bài viết phù hợp với từ khóa.</p>
        <?php endif; ?>

        <?php foreach ($posts as $post): ?>
            <article class="result-item">
                <p class="post-meta"><a href="/author?id=<?= (int) $post['author_id'] ?>"><?= e($post['author_name']) ?></a> · <?= e(format_datetime($post['created_at'])) ?></p>
                <h3><a href="/post?id=<?= (int) $post['id'] ?>"><?= e($post['title']) ?></a></h3>
                <p><?= e($post['summary'] !== '' ? $post['summary'] : excerpt($post['body'], 220)) ?></p>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
