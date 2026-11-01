<?php

declare(strict_types=1);

namespace Atud\Controllers;

use Atud\Core\Audit;
use Atud\Core\Auth;
use Atud\Core\Csrf;
use Atud\Core\Database;
use Atud\Core\Lab;
use Atud\Core\Validator;
use Atud\Core\View;
use RuntimeException;

class FileController
{
    private const ALLOWED = [
        'text/plain' => 'txt',
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'application/pdf' => 'pdf',
    ];

    public function index(): void
    {
        $user = Auth::requireLogin();

        $sql = 'SELECT f.*, u.username
                FROM uploads f JOIN users u ON u.id = f.user_id
                WHERE f.is_public = 1 OR f.user_id = ?
                ORDER BY f.created_at DESC';
        if (!Lab::isVulnerable()) {
            $sql .= ' LIMIT 50';
        }

        View::render('files', [
            'title' => 'Tệp và tài liệu',
            'uploads' => Database::all($sql, [(int) $user['id']]),
        ]);
    }

    public function upload(): void
    {
        $user = Auth::requireLogin();

        if (!method_is('POST')) {
            redirect('/files');
        }

        if (!Lab::isVulnerable()) {
            Csrf::requireValid($_POST['_csrf'] ?? null);
        }

        $file = $_FILES['file'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash('error', 'Không nhận được tệp tải lên hợp lệ.');
            redirect('/files');
        }

        $size = (int) ($file['size'] ?? 0);
        $maxSize = 2 * 1024 * 1024;
        if ($size <= 0 || $size > $maxSize) {
            flash('error', 'Tệp phải có kích thước từ 1 byte đến 2 MB.');
            redirect('/files');
        }

        $original = basename((string) ($file['name'] ?? 'tep-tai-len'));
        $tmp = (string) ($file['tmp_name'] ?? '');
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($tmp);

        if (!Lab::isVulnerable()) {
            if (!isset(self::ALLOWED[$mime])) {
                flash('error', 'Định dạng tệp không được phép. Chỉ nhận văn bản, PNG, JPEG hoặc PDF.');
                redirect('/files');
            }

            $extension = self::ALLOWED[$mime];
            $stored = bin2hex(random_bytes(16)) . '.' . $extension;
            $original = preg_replace('/[^A-Za-z0-9._ -]/u', '_', $original) ?: ('tep.' . $extension);
        } else {
            $stored = preg_replace('/[^A-Za-z0-9._-]/', '_', $original) ?: 'tep-tai-len';
            if (is_file(UPLOAD_PATH . '/' . $stored)) {
                $stored = time() . '-' . $stored;
            }
        }

        $destination = UPLOAD_PATH . '/' . basename($stored);
        if (!move_uploaded_file($tmp, $destination)) {
            flash('error', 'Không thể lưu tệp trên máy chủ.');
            redirect('/files');
        }

        $isPublic = isset($_POST['is_public']) ? 1 : 0;
        Database::run(
            'INSERT INTO uploads (user_id, original_name, stored_name, mime_type, size_bytes, is_public) VALUES (?, ?, ?, ?, ?, ?)',
            [(int) $user['id'], substr($original, 0, 255), basename($stored), substr($mime, 0, 100), $size, $isPublic]
        );

        Audit::record((int) $user['id'], 'file.upload', 'Tệp: ' . substr($original, 0, 100));
        flash('success', 'Đã tải tệp lên.');
        redirect('/files');
    }

    public function download(): void
    {
        $user = Auth::user();

        if (Lab::isVulnerable() && isset($_GET['file'])) {
            $relative = (string) $_GET['file'];
            $path = UPLOAD_PATH . '/' . $relative;

            if (is_file($path)) {
                $this->stream($path, basename($relative), 'application/octet-stream');
                return;
            }

            View::notFound('Tệp không tồn tại.');
            return;
        }

        if (!Lab::isVulnerable() && isset($_GET['file'])) {
            http_response_code(400);
            View::render('error', [
                'title' => 'Tham số không hợp lệ',
                'code' => 400,
                'message' => 'Tệp không tồn tại hoặc không hợp lệ.',
            ]);
            return;
        }

        $id = Validator::integerId($_GET['id'] ?? null);
        if ($id === null) {
            View::notFound('Mã tệp không hợp lệ.');
            return;
        }

        $upload = Database::one('SELECT * FROM uploads WHERE id = ? LIMIT 1', [$id]);
        if ($upload === null) {
            View::notFound('Tệp không tồn tại.');
            return;
        }

        $isOwner = $user !== null && (int) $user['id'] === (int) $upload['user_id'];
        $isAdmin = $user !== null && $user['role'] === 'admin';
        if ((int) $upload['is_public'] !== 1 && !$isOwner && !$isAdmin) {
            http_response_code(403);
            View::render('error', [
                'title' => 'Không đủ quyền',
                'code' => 403,
                'message' => 'Bạn không có quyền tải tệp này.',
            ]);
            return;
        }

        $base = realpath(UPLOAD_PATH);
        $path = realpath(UPLOAD_PATH . '/' . basename((string) $upload['stored_name']));

        if ($base === false || $path === false || !str_starts_with($path, $base . DIRECTORY_SEPARATOR)) {
            View::notFound('Tệp không tồn tại trên máy chủ.');
            return;
        }

        $inline = isset($_GET['inline']) && $_GET['inline'] === '1' && str_starts_with((string) $upload['mime_type'], 'image/');
        $this->stream($path, (string) $upload['original_name'], (string) $upload['mime_type'], $inline);
    }

    private function stream(string $path, string $name, string $mime, bool $inline = false): void
    {
        $size = filesize($path);
        if ($size === false) {
            throw new RuntimeException('Không đọc được kích thước tệp.');
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . $size);
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . rawurlencode(basename($name)) . '"');
        header('X-Content-Type-Options: nosniff');

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Không mở được tệp.');
        }

        fpassthru($handle);
        fclose($handle);
        exit;
    }
}
