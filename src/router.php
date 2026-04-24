<?php
declare(strict_types=1);

function router(): array {
    static $routes = [];
    return $routes;
}

function route(string $method, string $path, callable $handler): void {
    $GLOBALS['__cos_routes'][] = ['method' => strtoupper($method), 'path' => $path, 'handler' => $handler];
}

function dispatch(): void {
    $routes = $GLOBALS['__cos_routes'] ?? [];
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $uri    = rtrim($uri, '/') ?: '/';

    foreach ($routes as $r) {
        if ($r['method'] !== $method && !($r['method'] === 'GET' && $method === 'HEAD')) continue;
        $params = route_match($r['path'], $uri);
        if ($params === null) continue;
        ($r['handler'])($params);
        return;
    }

    http_response_code(404);
    echo '<h1>404 Not Found</h1>';
}

function route_match(string $pattern, string $uri): ?array {
    $pattern = rtrim($pattern, '/') ?: '/';
    if ($pattern === $uri) return [];
    $regex = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', function ($m) {
        return '(?P<' . $m[1] . '>[^/]+)';
    }, $pattern);
    $regex = '#^' . $regex . '$#';
    if (preg_match($regex, $uri, $m)) {
        return array_filter($m, fn($k) => is_string($k), ARRAY_FILTER_USE_KEY);
    }
    return null;
}

function redirect(string $path): never {
    header('Location: ' . $path);
    exit;
}
