<?php

declare(strict_types=1);

namespace Atud\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, callable $handler): void
    {
        $normalized = $this->normalize($path);
        $this->routes[$method][$normalized] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        $path = $this->normalize($path);

        if (isset($this->routes[$method][$path])) {
            ($this->routes[$method][$path])();
            return;
        }

        if (isset($this->routes['GET'][$path]) || isset($this->routes['POST'][$path])) {
            http_response_code(405);
            header('Allow: ' . implode(', ', array_keys(array_filter([
                'GET' => isset($this->routes['GET'][$path]),
                'POST' => isset($this->routes['POST'][$path]),
            ]))));
            View::render('error', [
                'title' => 'Phương thức không được hỗ trợ',
                'code' => 405,
                'message' => 'Yêu cầu sử dụng phương thức không hợp lệ cho địa chỉ này.',
            ]);
            return;
        }

        View::notFound();
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
