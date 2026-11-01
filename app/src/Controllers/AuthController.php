<?php

declare(strict_types=1);

namespace Atud\Controllers;

use Atud\Core\Audit;
use Atud\Core\Auth;
use Atud\Core\Config;
use Atud\Core\Csrf;
use Atud\Core\Database;
use Atud\Core\Lab;
use Atud\Core\LoginThrottle;
use Atud\Core\Security;
use Atud\Core\Session;
use Atud\Core\Validator;
use Atud\Core\View;
use PDOException;

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/');
        }

        View::render('login', ['title' => 'Đăng nhập']);
    }

    public function login(): void
    {
        if (!method_is('POST')) {
            redirect('/login');
        }

        if (!Lab::isVulnerable()) {
            Csrf::requireValid($_POST['_csrf'] ?? null);
        }

        $username = (string) ($_POST['username'] ?? '');
        if (!Lab::isVulnerable()) {
            $username = trim($username);
        }
        $password = (string) ($_POST['password'] ?? '');
        $ip = client_ip();
        $user = null;

        if (!Lab::isVulnerable() && LoginThrottle::isLocked($username, $ip)) {
            http_response_code(429);
            Audit::record(null, 'login.locked', 'Tài khoản: ' . substr($username, 0, 50));
            View::render('login', [
                'title' => 'Đăng nhập',
                'error' => 'Tài khoản đang tạm khóa do nhiều lần đăng nhập không thành công. Vui lòng thử lại sau 15 phút.',
                'username' => $username,
            ]);
            return;
        }

        if (Lab::isVulnerable()) {
            $query = "SELECT id, username, email, role, is_active FROM users
                      WHERE username = '{$username}' AND password_legacy = MD5('{$password}') AND is_active = 1 LIMIT 1";

            try {
                $user = Database::pdo()->query($query)->fetch();
            } catch (PDOException) {
                $user = null;
            }

            if ($user !== false && $user !== null) {
                $user = (array) $user;
            } else {
                $user = null;
            }
        } elseif (Validator::length($username, 1, 50) && $password !== '') {
            $candidate = Database::one(
                'SELECT id, username, email, role, is_active, password_hash FROM users WHERE username = ? LIMIT 1',
                [$username]
            );

            if ($candidate !== null
                && (int) $candidate['is_active'] === 1
                && password_verify($password, (string) $candidate['password_hash'])) {
                $user = $candidate;
            }
        }

        if ($user === null) {
            LoginThrottle::record($username, $ip, false);
            Audit::record(null, 'login.fail', 'Tài khoản: ' . substr($username, 0, 50));

            View::render('login', [
                'title' => 'Đăng nhập',
                'error' => 'Tên đăng nhập hoặc mật khẩu không đúng.',
                'username' => $username,
            ]);
            return;
        }

        LoginThrottle::record($username, $ip, true);
        LoginThrottle::clearFailures($username, $ip);
        Auth::login($user);
        Audit::record((int) $user['id'], 'login.success', 'Vai trò: ' . $user['role']);

        flash('success', 'Đăng nhập thành công với tài khoản ' . $user['username'] . '.');
        redirect($user['role'] === 'admin' ? '/admin' : '/');
    }

    public function logout(): void
    {
        $user = Auth::user();
        if ($user !== null) {
            Audit::record((int) $user['id'], 'logout', '');
        }

        Auth::logout();
        Session::start();
        flash('success', 'Đã đăng xuất.');
        redirect('/login');
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            redirect('/');
        }

        View::render('register', ['title' => 'Đăng ký tài khoản']);
    }

    public function register(): void
    {
        if (!method_is('POST')) {
            redirect('/register');
        }

        if (!Lab::isVulnerable()) {
            Csrf::requireValid($_POST['_csrf'] ?? null);
        }

        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm'] ?? '');
        $errors = [];

        if (!Validator::username($username)) {
            $errors[] = 'Tên đăng nhập gồm 3 đến 50 ký tự chữ, số, dấu chấm, gạch ngang hoặc gạch dưới.';
        }
        if (!Validator::email($email)) {
            $errors[] = 'Địa chỉ email không hợp lệ.';
        }
        if (!Validator::password($password)) {
            $errors[] = 'Mật khẩu phải có từ 10 đến 128 ký tự.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Mật khẩu nhập lại không khớp.';
        }

        if ($errors === [] && Database::count('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?', [$username, $email]) > 0) {
            $errors[] = 'Tên đăng nhập hoặc email đã được sử dụng.';
        }

        if ($errors !== []) {
            View::render('register', [
                'title' => 'Đăng ký tài khoản',
                'errors' => $errors,
                'username' => $username,
                'email' => $email,
            ]);
            return;
        }

        Database::run(
            'INSERT INTO users (username, email, password_hash, password_legacy, role) VALUES (?, ?, ?, ?, ?)',
            [$username, $email, password_hash($password, PASSWORD_DEFAULT), md5($password), 'author']
        );

        Audit::record(null, 'register', 'Tài khoản: ' . $username);
        flash('success', 'Đã tạo tài khoản. Hãy đăng nhập.');
        redirect('/login');
    }

    public function showForgot(): void
    {
        View::render('forgot', ['title' => 'Quên mật khẩu']);
    }

    public function forgot(): void
    {
        if (!method_is('POST')) {
            redirect('/forgot');
        }

        if (!Lab::isVulnerable()) {
            Csrf::requireValid($_POST['_csrf'] ?? null);
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $user = Validator::email($email)
            ? Database::one('SELECT id, username, email FROM users WHERE email = ? AND is_active = 1 LIMIT 1', [$email])
            : null;

        if (Lab::isVulnerable()) {
            if ($user === null) {
                View::render('forgot', [
                    'title' => 'Quên mật khẩu',
                    'error' => 'Không tìm thấy tài khoản với email ' . $email . '.',
                    'email' => $email,
                ]);
                return;
            }

            $token = substr(md5($user['username'] . '|' . date('Y-m-d')), 0, 20);
            Database::run(
                'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 DAY))',
                [(int) $user['id'], Security::hashToken($token)]
            );

            Audit::record((int) $user['id'], 'password_reset.request', 'Liên kết dùng lại được trong 24 giờ');
            View::render('forgot', [
                'title' => 'Quên mật khẩu',
                'success' => 'Đã tạo liên kết đặt lại cho ' . $user['email'] . '.',
                'resetLink' => url('/reset?token=' . urlencode($token)),
                'email' => $email,
            ]);
            return;
        }

        if ($user !== null) {
            $token = Security::randomToken(32);
            Database::run('UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL', [(int) $user['id']]);
            Database::run(
                'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))',
                [(int) $user['id'], Security::hashToken($token)]
            );

            $link = rtrim((string) Config::get('APP_URL', 'http://localhost:8080'), '/') . '/reset?token=' . urlencode($token);
            file_put_contents(
                LOG_PATH . '/mail.log',
                sprintf("[%s] to=%s link=%s\n", date('c'), $user['email'], $link),
                FILE_APPEND | LOCK_EX
            );
            Audit::record((int) $user['id'], 'password_reset.request', 'Liên kết một lần, hết hạn sau 30 phút');
        }

        View::render('forgot', [
            'title' => 'Quên mật khẩu',
            'success' => 'Nếu email tồn tại trong hệ thống, liên kết đặt lại đã được gửi. Vui lòng kiểm tra hộp thư.',
            'email' => $email,
        ]);
    }

    public function showReset(): void
    {
        $token = (string) ($_GET['token'] ?? '');
        $record = $this->findResetToken($token);

        if ($record === null) {
            View::render('reset', [
                'title' => 'Đặt lại mật khẩu',
                'error' => 'Liên kết đặt lại không hợp lệ, đã hết hạn hoặc đã được sử dụng.',
            ]);
            return;
        }

        View::render('reset', [
            'title' => 'Đặt lại mật khẩu',
            'token' => $token,
            'username' => $record['username'],
        ]);
    }

    public function reset(): void
    {
        if (!method_is('POST')) {
            redirect('/forgot');
        }

        if (!Lab::isVulnerable()) {
            Csrf::requireValid($_POST['_csrf'] ?? null);
        }

        $token = (string) ($_POST['token'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm'] ?? '');
        $record = $this->findResetToken($token);

        if ($record === null) {
            View::render('reset', [
                'title' => 'Đặt lại mật khẩu',
                'error' => 'Liên kết đặt lại không hợp lệ, đã hết hạn hoặc đã được sử dụng.',
            ]);
            return;
        }

        $errors = [];
        if (!Validator::password($password)) {
            $errors[] = 'Mật khẩu phải có từ 10 đến 128 ký tự.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Mật khẩu nhập lại không khớp.';
        }

        if ($errors !== []) {
            View::render('reset', [
                'title' => 'Đặt lại mật khẩu',
                'errors' => $errors,
                'token' => $token,
                'username' => $record['username'],
            ]);
            return;
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            Database::run(
                'UPDATE users SET password_hash = ?, password_legacy = ? WHERE id = ?',
                [password_hash($password, PASSWORD_DEFAULT), md5($password), (int) $record['user_id']]
            );

            if (!Lab::isVulnerable()) {
                $stmt = $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ? AND used_at IS NULL');
                $stmt->execute([(int) $record['id']]);
                if ($stmt->rowCount() !== 1) {
                    throw new \RuntimeException('Liên kết đã được sử dụng.');
                }
            }

            $pdo->commit();
            Audit::record((int) $record['user_id'], 'password_reset.complete', Lab::isVulnerable() ? 'Mã không bị thu hồi' : 'Mã đã bị thu hồi');
            flash('success', 'Đã đặt lại mật khẩu. Hãy đăng nhập bằng mật khẩu mới.');
            redirect('/login');
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            View::render('reset', [
                'title' => 'Đặt lại mật khẩu',
                'error' => Lab::isVulnerable() ? $exception->getMessage() : 'Không thể đặt lại mật khẩu. Liên kết có thể đã được sử dụng.',
                'token' => $token,
                'username' => $record['username'],
            ]);
        }
    }

    private function findResetToken(string $token): ?array
    {
        if ($token === '' || strlen($token) > 128) {
            return null;
        }

        $sql = 'SELECT t.id, t.user_id, t.expires_at, t.used_at, u.username
                FROM password_reset_tokens t JOIN users u ON u.id = t.user_id
                WHERE t.token_hash = ? AND t.expires_at > NOW()';

        if (!Lab::isVulnerable()) {
            $sql .= ' AND t.used_at IS NULL';
        }

        return Database::one($sql . ' ORDER BY t.id DESC LIMIT 1', [Security::hashToken($token)]);
    }
}
