<?php

declare(strict_types=1);

namespace Atud\Controllers;

use Atud\Core\Audit;
use Atud\Core\Auth;
use Atud\Core\Csrf;
use Atud\Core\Database;
use Atud\Core\Lab;
use Atud\Core\Session;
use Atud\Core\Validator;
use Atud\Core\View;

class AccountController
{
    public function show(): void
    {
        $user = Auth::requireLogin();

        View::render('account', [
            'title' => 'Tài khoản cá nhân',
            'user' => $user,
        ]);
    }

    public function updateEmail(): void
    {
        $user = Auth::requireLogin();

        if (!method_is('POST')) {
            redirect('/account');
        }

        if (!Lab::isVulnerable()) {
            Csrf::requireValid($_POST['_csrf'] ?? null);
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        if (!Validator::email($email)) {
            flash('error', 'Địa chỉ email không hợp lệ.');
            redirect('/account');
        }

        $exists = Database::count('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?', [$email, (int) $user['id']]);
        if ($exists > 0) {
            flash('error', 'Địa chỉ email đã được tài khoản khác sử dụng.');
            redirect('/account');
        }

        Database::run('UPDATE users SET email = ? WHERE id = ?', [$email, (int) $user['id']]);
        Audit::record((int) $user['id'], 'account.email', 'Đổi địa chỉ email');
        flash('success', 'Đã cập nhật địa chỉ email.');
        redirect('/account');
    }

    public function updatePassword(): void
    {
        $user = Auth::requireLogin();

        if (!method_is('POST')) {
            redirect('/account');
        }

        if (!Lab::isVulnerable()) {
            Csrf::requireValid($_POST['_csrf'] ?? null);
        }

        $current = (string) ($_POST['current_password'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm'] ?? '');

        $record = Database::one('SELECT password_hash FROM users WHERE id = ? LIMIT 1', [(int) $user['id']]);
        if ($record === null || !password_verify($current, (string) $record['password_hash'])) {
            flash('error', 'Mật khẩu hiện tại không đúng.');
            redirect('/account');
        }

        if (!Validator::password($password) || $password !== $confirm) {
            flash('error', 'Mật khẩu mới phải từ 10 đến 128 ký tự và nhập lại phải khớp.');
            redirect('/account');
        }

        Database::run(
            'UPDATE users SET password_hash = ?, password_legacy = ? WHERE id = ?',
            [password_hash($password, PASSWORD_DEFAULT), md5($password), (int) $user['id']]
        );

        if (!Lab::isVulnerable()) {
            Session::regenerate();
        }

        Audit::record((int) $user['id'], 'account.password', 'Đổi mật khẩu');
        flash('success', 'Đã đổi mật khẩu.');
        redirect('/account');
    }
}
