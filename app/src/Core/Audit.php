<?php

declare(strict_types=1);

namespace Atud\Core;

class Audit
{
    public static function record(?int $userId, string $action, string $detail = ''): void
    {
        try {
            Database::run(
                'INSERT INTO audit_log (user_id, action, detail, ip) VALUES (?, ?, ?, ?)',
                [$userId, substr($action, 0, 80), substr($detail, 0, 255), client_ip()]
            );
        } catch (\Throwable) {
            error_log('Không ghi được nhật ký kiểm toán cho hành động: ' . $action);
        }
    }
}
