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

class EditorController
{
    public function index(): void
    {
        $user = Auth::requireLogin();

        $posts = Database::all(
            'SELECT * FROM posts WHERE author_id = ? ORDER BY updated_at DESC, id DESC',
            [(int) $user['id']]
        );

        View::render('editor', [
            'title' => 'Bài viết của tôi',
            'posts' => $posts,
            'post' => null,
            'formMode' => false,
        ]);
    }

    public function create(): void
    {
        $user = Auth::requireLogin();

        View::render('editor', [
            'title' => 'Tạo bài viết',
            'posts' => Database::all('SELECT * FROM posts WHERE author_id = ? ORDER BY updated_at DESC', [(int) $user['id']]),
            'post' => null,
            'formMode' => true,
        ]);
    }

    public function edit(): void
    {
        $user = Auth::requireLogin();
        $post = $this->findPost(Validator::integerId($_GET['id'] ?? null));

        if ($post === null) {
            View::notFound('Bài viết không tồn tại.');
            return;
        }

        if ((int) $post['author_id'] !== (int) $user['id'] && $user['role'] !== 'admin') {
            http_response_code(403);
            View::render('error', [
                'title' => 'Không đủ quyền',
                'code' => 403,
                'message' => 'Bạn không phải tác giả của bài viết này.',
            ]);
            return;
        }

        View::render('editor', [
            'title' => 'Sửa bài viết',
            'posts' => Database::all('SELECT * FROM posts WHERE author_id = ? ORDER BY updated_at DESC', [(int) $user['id']]),
            'post' => $post,
            'formMode' => true,
        ]);
    }

    public function save(): void
    {
        $user = Auth::requireLogin();

        if (!method_is('POST')) {
            redirect('/editor');
        }

        if (!Lab::isVulnerable()) {
            Csrf::requireValid($_POST['_csrf'] ?? null);
        }

        $id = Validator::integerId($_POST['id'] ?? null);
        $title = trim((string) ($_POST['title'] ?? ''));
        $summary = trim((string) ($_POST['summary'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        $status = (string) ($_POST['status'] ?? 'draft');

        $errors = [];
        if (!Validator::length($title, 3, 200)) {
            $errors[] = 'Tiêu đề phải từ 3 đến 200 ký tự.';
        }
        if (strlen($summary) > 500) {
            $errors[] = 'Tóm tắt không vượt quá 500 ký tự.';
        }
        if (!Validator::length($body, 10, 20000)) {
            $errors[] = 'Nội dung phải từ 10 đến 20000 ký tự.';
        }

        $existing = null;
        if ($id !== null) {
            $existing = $this->findPost($id);
            if ($existing === null) {
                View::notFound('Bài viết không tồn tại.');
                return;
            }
            if ((int) $existing['author_id'] !== (int) $user['id'] && $user['role'] !== 'admin') {
                http_response_code(403);
                View::render('error', ['title' => 'Không đủ quyền', 'code' => 403, 'message' => 'Bạn không phải tác giả của bài viết này.']);
                return;
            }
        }

        if (!Lab::isVulnerable()) {
            $allowed = ['draft', 'pending'];
            if (in_array($user['role'], ['author', 'admin'], true)) {
                $allowed[] = 'published';
            }
            if (!in_array($status, $allowed, true)) {
                http_response_code(403);
                View::render('error', [
                    'title' => 'Trạng thái không được phép',
                    'code' => 403,
                    'message' => 'Tài khoản của bạn không được phép đặt bài viết ở trạng thái này.',
                ]);
                return;
            }

            if ($existing !== null && !in_array($user['role'], ['author', 'admin'], true) && $existing['status'] === 'published') {
                http_response_code(403);
                View::render('error', [
                    'title' => 'Không đủ quyền',
                    'code' => 403,
                    'message' => 'Bài viết đã xuất bản chỉ có thể được sửa bởi quản trị viên.',
                ]);
                return;
            }
        }

        if ($errors !== []) {
            View::render('editor', [
                'title' => $id === null ? 'Tạo bài viết' : 'Sửa bài viết',
                'errors' => $errors,
                'posts' => Database::all('SELECT * FROM posts WHERE author_id = ? ORDER BY updated_at DESC', [(int) $user['id']]),
                'post' => [
                    'id' => $id,
                    'title' => $title,
                    'summary' => $summary,
                    'body' => $body,
                    'status' => $status,
                ],
                'formMode' => true,
            ]);
            return;
        }

        if ($existing === null) {
            Database::run(
                'INSERT INTO posts (author_id, title, summary, body, status) VALUES (?, ?, ?, ?, ?)',
                [(int) $user['id'], $title, $summary, $body, $status]
            );
            $id = Database::lastId();
            Audit::record((int) $user['id'], 'post.create', 'Bài viết #' . $id . ' trạng thái ' . $status);
        } else {
            Database::run(
                'UPDATE posts SET title = ?, summary = ?, body = ?, status = ? WHERE id = ?',
                [$title, $summary, $body, $status, (int) $id]
            );
            Audit::record((int) $user['id'], 'post.update', 'Bài viết #' . $id . ' trạng thái ' . $status);
        }

        flash('success', 'Đã lưu bài viết ở trạng thái ' . $this->statusLabel($status) . '.');
        redirect('/editor');
    }

    public function delete(): void
    {
        $user = Auth::requireLogin();

        if (!method_is('POST')) {
            redirect('/editor');
        }

        if (!Lab::isVulnerable()) {
            Csrf::requireValid($_POST['_csrf'] ?? null);
        }

        $post = $this->findPost(Validator::integerId($_POST['id'] ?? null));
        if ($post === null) {
            flash('error', 'Bài viết không tồn tại.');
            redirect('/editor');
        }

        if ((int) $post['author_id'] !== (int) $user['id'] && $user['role'] !== 'admin') {
            http_response_code(403);
            View::render('error', ['title' => 'Không đủ quyền', 'code' => 403, 'message' => 'Bạn không phải tác giả của bài viết này.']);
            return;
        }

        if (!Lab::isVulnerable() && $user['role'] === 'user' && $post['status'] === 'published') {
            http_response_code(403);
            View::render('error', ['title' => 'Không đủ quyền', 'code' => 403, 'message' => 'Không thể xóa bài viết đã xuất bản.']);
            return;
        }

        Database::run('DELETE FROM posts WHERE id = ?', [(int) $post['id']]);
        Audit::record((int) $user['id'], 'post.delete', 'Bài viết #' . (int) $post['id']);
        flash('success', 'Đã xóa bài viết.');
        redirect('/editor');
    }

    private function findPost(?int $id): ?array
    {
        if ($id === null) {
            return null;
        }

        return Database::one('SELECT * FROM posts WHERE id = ? LIMIT 1', [$id]);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'nháp',
            'pending' => 'chờ duyệt',
            'published' => 'xuất bản',
            default => $status,
        };
    }
}
