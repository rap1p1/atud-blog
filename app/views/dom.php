<?php

use Atud\Core\Lab;

$q = (string) ($_GET['q'] ?? '');
?>
<section class="section-head">
    <div>
        <p class="eyebrow">Tìm kiếm nhanh</p>
        <h1>Tìm nhanh trên trang</h1>
        <p class="muted">Nhập từ khóa để xem gợi ý trên trang.</p>
    </div>
</section>

<section class="panel">
    <form class="search-form" method="get" action="/demo/dom">
        <label for="q">Từ khóa</label>
        <div class="search-row">
            <input id="q" name="q" type="text" value="<?= e($q) ?>" maxlength="300" placeholder="Nhập từ khóa cần xem">
            <button class="button" type="submit">Hiển thị</button>
        </div>
    </form>

    <div id="dom-output" class="dom-output" aria-live="polite"></div>
</section>

<script src="<?= asset(Lab::isVulnerable() ? 'dom-vulnerable.js' : 'dom-fixed.js') ?>"></script>
