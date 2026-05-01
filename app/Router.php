<?php

declare(strict_types=1);

namespace App;

use App\Utils\Response;
use App\Utils\Logger;

final class Router
{
    private array $routes = [];

    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, array $handler, array $middleware): void
    {
        // Convert {param} to regex capture group
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_-]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        
        $this->routes[] = [
            'method'     => $method,
            'pattern'    => $pattern,
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH);
        
        // Strip base path if running in a subdirectory (like local dev)
        $basePath = parse_url(APP_URL, PHP_URL_PATH) ?? '';
        if ($basePath !== '/' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }
        if (empty($path)) {
            $path = '/';
        }

        $allowedMethods = [];

        foreach ($this->routes as $route) {
            if (preg_match($route['pattern'], $path, $matches)) {
                if ($route['method'] !== $method) {
                    $allowedMethods[] = $route['method'];
                    continue;
                }

                // Filter named parameters from regex matches
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                $this->execute($route, $params);
                return; // Execution stops here
            }
        }

        if (!empty($allowedMethods)) {
            Response::error('Method Not Allowed', 405);
        }

        Response::notFound();
    }

    private function execute(array $route, array $params): void
    {
        try {
            // Build middleware pipeline
            $pipeline = function () use ($route, $params) {
                [$class, $method] = $route['handler'];
                $controller = new $class();
                return $controller->$method(...array_values($params));
            };

            $middlewareMap = [
                'session' => \App\Middleware\SessionMiddleware::class,
                'csrf'    => \App\Middleware\CsrfMiddleware::class,
                'auth'    => \App\Middleware\AuthMiddleware::class,
            ];

            // Always run session middleware first
            array_unshift($route['middleware'], 'session');
            $middlewareList = array_unique($route['middleware']);

            // Wrap pipeline from inside out
            foreach (array_reverse($middlewareList) as $alias) {
                if (!isset($middlewareMap[$alias])) {
                    continue;
                }
                $mwClass = $middlewareMap[$alias];
                $next = $pipeline;
                $pipeline = function () use ($mwClass, $next) {
                    $mw = new $mwClass();
                    return $mw->handle($next);
                };
            }

            $pipeline();

        } catch (\Throwable $e) {
            Logger::critical('Unhandled Exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            if (APP_DEBUG) {
                Response::serverError($e->getMessage());
            } else {
                Response::serverError();
            }
        }
    }
}
