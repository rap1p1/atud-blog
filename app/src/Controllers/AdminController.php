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

class AdminController
{
    public function index(): void
    {
        Auth::requireAdmin();

        $posts = Database::all(
            'SELECT p.*, u.username AS author_name
             FROM posts p JOIN users u ON u.id = p.author_id
             ORDER BY p.updated_at DESC, p.id DESC'
        );
        $comments = Database::all(
            'SELECT c.*, u.username AS author_name, p.title AS post_title
             FROM comments c
             JOIN users u ON u.id = c.user_id
             JOIN posts p ON p.id = c.post_id
             ORDER BY c.created_at DESC, c.id DESC
             LIMIT 50'
        );

        $users = Database::all(
            'SELECT u.id, u.username, u.email, u.role, u.is_active, u.created_at,
                    COUNT(p.id) AS post_count
             FROM users u
             LEFT JOIN posts p ON p.author_id = u.id
             GROUP BY u.id, u.username, u.email, u.role, u.is_active, u.created_at
             ORDER BY u.id'
        );

        View::render('admin', [
            'title' => 'Trang quản trị',
            'posts' => $posts,
            'comments' => $comments,
            'users' => $users,
            'stats' => [
                'users' => Database::count('SELECT COUNT(*) FROM users'),
                'posts' => Database::count('SELECT COUNT(*) FROM posts'),
                'pending' => Database::count("SELECT COUNT(*) FROM posts WHERE status = 'pending'"),
                'comments' => Database::count('SELECT COUNT(*) FROM comments'),
            ],
        ]);
    }

    public function publish(): void
    {
        Auth::requireAdmin();

        if (!method_is('POST')) {
            redirect('/admin');
        }

        if (!Lab::isVulnerable()) {
            Csrf::requireValid($_POST['_csrf'] ?? null);
        }

        $id = Validator::integerId($_POST['id'] ?? null);
        if ($id === null) {
            flash('error', 'Bài viết không hợp lệ.');
            redirect('/admin');
        }

        $post = Database::one('SELECT id, status FROM posts WHERE id = ? LIMIT 1', [$id]);
        if ($post === null || $post['status'] !== 'pending') {
            flash('error', 'Chỉ bài viết đang chờ duyệt mới được xuất bản.');
            redirect('/admin');
        }

        Database::run("UPDATE posts SET status = 'published' WHERE id = ? AND status = 'pending'", [$id]);
        Audit::record((int) Auth::user()['id'], 'post.publish', 'Bài viết #' . $id);
        flash('success', 'Đã xuất bản bài viết.');
        redirect('/admin');
    }

    public function deletePost(): void
    {
        Auth::requireAdmin();

        if (!method_is('POST')) {
            redirect('/admin');
        }

        if (!Lab::isVulnerable()) {
            Csrf::requireValid($_POST['_csrf'] ?? null);
        }

        $id = Validator::integerId($_POST['id'] ?? null);
        if ($id !== null) {
            Database::run('DELETE FROM posts WHERE id = ?', [$id]);
            Audit::record((int) Auth::user()['id'], 'post.delete', 'Bài viết #' . $id);
            flash('success', 'Đã xóa bài viết.');
        }

        redirect('/admin');
    }

    public function deleteComment(): void
    {
        Auth::requireAdmin();

        if (!method_is('POST')) {
            redirect('/admin');
        }

        if (!Lab::isVulnerable()) {
            Csrf::requireValid($_POST['_csrf'] ?? null);
        }

        $id = Validator::integerId($_POST['id'] ?? null);
        if ($id !== null) {
            Database::run('DELETE FROM comments WHERE id = ?', [$id]);
            Audit::record((int) Auth::user()['id'], 'comment.delete', 'Bình luận #' . $id);
            flash('success', 'Đã xóa bình luận.');
        }

        redirect('/admin');
    }
}
