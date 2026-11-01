<?php

declare(strict_types=1);

namespace Atud\Controllers;

use Atud\Core\Database;
use Atud\Core\View;

class StaticController
{
    public function about(): void
    {
        View::render('about', ['title' => 'Giới thiệu']);
    }

    public function health(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            Database::pdo()->query('SELECT 1');
            echo json_encode([
                'status' => 'ok',
                'database' => 'ok',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable) {
            http_response_code(503);
            echo json_encode([
                'status' => 'error',
                'database' => 'unavailable',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }
}
