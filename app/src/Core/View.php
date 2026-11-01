<?php

declare(strict_types=1);

namespace Atud\Core;

use RuntimeException;

class View
{
    public static function render(string $template, array $data = []): void
    {
        $file = APP_ROOT . '/views/' . $template . '.php';
        if (!is_file($file)) {
            throw new RuntimeException('Không tìm thấy giao diện: ' . $template);
        }

        extract($data, EXTR_SKIP);
        $title = $data['title'] ?? 'Blog an toàn thông tin';

        ob_start();
        require $file;
        $content = (string) ob_get_clean();

        require APP_ROOT . '/views/layout.php';
    }

    public static function partial(string $template, array $data = []): void
    {
        $file = APP_ROOT . '/views/partials/' . $template . '.php';
        if (!is_file($file)) {
            throw new RuntimeException('Không tìm thấy thành phần giao diện: ' . $template);
        }

        extract($data, EXTR_SKIP);
        require $file;
    }

    public static function notFound(string $message = 'Nội dung không tồn tại hoặc đã bị xóa.'): void
    {
        http_response_code(404);
        self::render('error', [
            'title' => 'Không tìm thấy nội dung',
            'code' => 404,
            'message' => $message,
        ]);
    }
}
