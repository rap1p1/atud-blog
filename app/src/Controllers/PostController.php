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

class PostController
{
    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = Lab::isVulnerable() ? 50 : 10;
        $offset = ($page - 1) * $perPage;

        $total = Database::count("SELECT COUNT(*) FROM posts WHERE status = 'published'");
        $posts = Database::all(
            "SELECT p.*, u.username AS author_name
             FROM posts p
             JOIN users u ON u.id = p.author_id
             WHERE p.status = 'published'
             ORDER BY p.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );

        View::render('home', [
            'title' => 'Trang chủ',
            'posts' => $posts,
            'page' => $page,
            'total' => $total,
            'perPage' => $perPage,
        ]);
    }

    public function search(): void
    {
        $q = (string) ($_GET['q'] ?? '');
        $q = trim($q);

        if (!Lab::isVulnerable() && strlen($q) > 100) {
            http_response_code(400);
            View::render('error', [
                'title' => 'Từ khóa không hợp lệ',
                'code' => 400,
                'message' => 'Từ khóa tìm kiếm vượt quá giới hạn 100 ký tự.',
            ]);
            return;
        }

        $posts = [];
        if ($q !== '') {
            $posts = Database::all(
                "SELECT p.*, u.username AS author_name
                 FROM posts p
                 JOIN users u ON u.id = p.author_id
                 WHERE p.status = 'published' AND (p.title LIKE ? OR p.summary LIKE ? OR p.body LIKE ?)
                 ORDER BY p.created_at DESC
                 LIMIT 20",
                ['%' . $q . '%', '%' . $q . '%', '%' . $q . '%']
            );
        }

        View::render('search', [
            'title' => 'Tìm kiếm bài viết',
            'q' => $q,
            'posts' => $posts,
        ]);
    }

    public function show(): void
    {
        $id = Validator::integerId($_GET['id'] ?? null);
        if ($id === null) {
            View::notFound('Mã bài viết không hợp lệ.');
            return;
        }

        $post = Database::one(
            'SELECT p.*, u.username AS author_name
             FROM posts p JOIN users u ON u.id = p.author_id
             WHERE p.id = ? LIMIT 1',
            [$id]
        );

        if ($post === null) {
            View::notFound('Bài viết không tồn tại.');
            return;
        }

        $user = Auth::user();
        $canPreview = $user !== null && ((int) $user['id'] === (int) $post['author_id'] || $user['role'] === 'admin');
        if ($post['status'] !== 'published' && !$canPreview) {
            View::notFound('Bài viết không tồn tại hoặc chưa được xuất bản.');
            return;
        }

        $comments = Database::all(
            'SELECT c.*, u.username AS author_name
             FROM comments c JOIN users u ON u.id = c.user_id
             WHERE c.post_id = ?
             ORDER BY c.created_at ASC, c.id ASC',
            [$id]
        );

        View::render('post', [
            'title' => $post['title'],
            'post' => $post,
            'comments' => $comments,
        ]);
    }

    public function comment(): void
    {
        $user = Auth::requireLogin();

        if (!method_is('POST')) {
            redirect('/');
        }

        if (!Lab::isVulnerable()) {
            Csrf::requireValid($_POST['_csrf'] ?? null);
        }

        $postId = Validator::integerId($_POST['post_id'] ?? null);
        $body = trim((string) ($_POST['body'] ?? ''));

        if ($postId === null) {
            flash('error', 'Bài viết không hợp lệ.');
            redirect('/');
        }

        $post = Database::one('SELECT id, author_id, status FROM posts WHERE id = ? LIMIT 1', [$postId]);
        if ($post === null || ($post['status'] !== 'published' && !Auth::isAdmin() && (int) $user['id'] !== (int) ($post['author_id'] ?? 0))) {
            flash('error', 'Không thể bình luận bài viết này.');
            redirect('/post?id=' . $postId);
        }

        $max = Lab::isVulnerable() ? 10000 : 2000;
        if ($body === '' || strlen($body) > $max) {
            flash('error', 'Nội dung bình luận phải từ 1 đến ' . $max . ' ký tự.');
            redirect('/post?id=' . $postId);
        }

        Database::run(
            'INSERT INTO comments (post_id, user_id, body) VALUES (?, ?, ?)',
            [$postId, (int) $user['id'], $body]
        );

        Audit::record((int) $user['id'], 'comment.create', 'Bài viết #' . $postId);
        flash('success', 'Đã gửi bình luận.');
        redirect('/post?id=' . $postId);
    }

    public function author(): void
    {
        $id = Validator::integerId($_GET['id'] ?? null);
        if ($id === null) {
            View::notFound('Tác giả không tồn tại.');
            return;
        }

        $author = Database::one(
            'SELECT id, username, role, created_at FROM users WHERE id = ? AND is_active = 1 LIMIT 1',
            [$id]
        );

        if ($author === null) {
            View::notFound('Tác giả không tồn tại.');
            return;
        }

        $posts = Database::all(
            "SELECT p.*, u.username AS author_name
             FROM posts p JOIN users u ON u.id = p.author_id
             WHERE p.author_id = ? AND p.status = 'published'
             ORDER BY p.created_at DESC",
            [$id]
        );

        View::render('author', [
            'title' => 'Bài viết của ' . $author['username'],
            'author' => $author,
            'posts' => $posts,
        ]);
    }

    public function dom(): void
    {
        View::render('dom', ['title' => 'Minh họa DOM']);
    }
}
