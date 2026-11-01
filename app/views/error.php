<?php

?>
<section class="error-page">
    <p class="error-code"><?= (int) ($code ?? 500) ?></p>
    <h1><?= e($title ?? 'Lỗi') ?></h1>
    <p><?= e($message ?? 'Đã xảy ra lỗi khi xử lý yêu cầu.') ?></p>
    <p><a class="button" href="/">Về trang chủ</a></p>
</section>
