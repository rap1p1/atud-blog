<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use Atud\Controllers\AccountController;
use Atud\Controllers\AdminController;
use Atud\Controllers\AuthController;
use Atud\Controllers\EditorController;
use Atud\Controllers\FileController;
use Atud\Controllers\PostController;
use Atud\Controllers\StaticController;
use Atud\Core\Router;

$router = new Router();

$router->get('/', static fn () => (new PostController())->index());
$router->get('/search', static fn () => (new PostController())->search());
$router->get('/post', static fn () => (new PostController())->show());
$router->get('/author', static fn () => (new PostController())->author());
$router->post('/comment', static fn () => (new PostController())->comment());
$router->get('/demo/dom', static fn () => (new PostController())->dom());

$router->get('/login', static fn () => (new AuthController())->showLogin());
$router->post('/login', static fn () => (new AuthController())->login());
$router->get('/logout', static fn () => (new AuthController())->logout());
$router->get('/register', static fn () => (new AuthController())->showRegister());
$router->post('/register', static fn () => (new AuthController())->register());
$router->get('/forgot', static fn () => (new AuthController())->showForgot());
$router->post('/forgot', static fn () => (new AuthController())->forgot());
$router->get('/reset', static fn () => (new AuthController())->showReset());
$router->post('/reset', static fn () => (new AuthController())->reset());

$router->get('/account', static fn () => (new AccountController())->show());
$router->post('/account/email', static fn () => (new AccountController())->updateEmail());
$router->post('/account/password', static fn () => (new AccountController())->updatePassword());

$router->get('/files', static fn () => (new FileController())->index());
$router->post('/files/upload', static fn () => (new FileController())->upload());
$router->get('/download', static fn () => (new FileController())->download());

$router->get('/editor', static fn () => (new EditorController())->index());
$router->get('/editor/new', static fn () => (new EditorController())->create());
$router->get('/editor/edit', static fn () => (new EditorController())->edit());
$router->post('/editor/save', static fn () => (new EditorController())->save());
$router->post('/editor/delete', static fn () => (new EditorController())->delete());

$router->get('/admin', static fn () => (new AdminController())->index());
$router->post('/admin/post/publish', static fn () => (new AdminController())->publish());
$router->post('/admin/post/delete', static fn () => (new AdminController())->deletePost());
$router->post('/admin/comment/delete', static fn () => (new AdminController())->deleteComment());

$router->get('/about', static fn () => (new StaticController())->about());
$router->get('/health', static fn () => (new StaticController())->health());

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', is_string($path) ? $path : '/');
