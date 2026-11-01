<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('STORAGE_PATH', APP_ROOT . '/storage');
define('UPLOAD_PATH', STORAGE_PATH . '/uploads');
define('PRIVATE_PATH', STORAGE_PATH . '/private');
define('LOG_PATH', STORAGE_PATH . '/logs');

spl_autoload_register(static function (string $class): void {
    $prefix = 'Atud\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = APP_ROOT . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require APP_ROOT . '/src/helpers.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');

use Atud\Core\Config;
use Atud\Core\Lab;
use Atud\Core\Security;
use Atud\Core\Session;
use Atud\Core\View;

Config::load(APP_ROOT . '/.env');

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', LOG_PATH . '/php-error.log');

set_exception_handler(static function (Throwable $exception): void {
    $message = $exception->getMessage();
    $secrets = [
        (string) Config::get('DB_PASSWORD', ''),
        (string) Config::get('MYSQL_ROOT_PASSWORD', ''),
    ];
    $message = str_replace(array_filter($secrets), '[đã che]', $message);

    error_log(sprintf(
        '[%s] %s tại %s:%d | %s',
        date('c'),
        $exception::class,
        $exception->getFile(),
        $exception->getLine(),
        $message
    ));

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }

    View::render('error', [
        'title' => 'Lỗi hệ thống',
        'code' => 500,
        'message' => Lab::isVulnerable()
            ? 'Ứng dụng gặp lỗi khi xử lý yêu cầu. Chế độ có lỗi ghi chi tiết vào nhật ký để phục vụ trình diễn.'
            : 'Yêu cầu không thể xử lý. Vui lòng thử lại hoặc liên hệ quản trị viên.',
    ]);
});

Security::sendHeaders();
Session::start();
