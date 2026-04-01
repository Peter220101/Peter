<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['admin_data_v1'])) {
    $_SESSION['admin_data_v1'] = [
        'users' => [],
        'vehicles' => [],
        'reports' => [],
    ];
}

$allowed = ['users', 'vehicles', 'reports'];
$module = $_GET['module'] ?? 'users';
if (!in_array($module, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Módulo no válido']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    echo json_encode(['items' => $_SESSION['admin_data_v1'][$module]], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input') ?: '{}';
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido']);
    exit;
}

$action = $payload['action'] ?? 'create';

if ($action === 'create') {
    $item = $payload['item'] ?? [];
    if (!is_array($item)) {
        http_response_code(400);
        echo json_encode(['error' => 'Item inválido']);
        exit;
    }
    $item['id'] = (int) (microtime(true) * 1000) + random_int(1, 999);
    $item['createdAt'] = date(DATE_ATOM);
    $_SESSION['admin_data_v1'][$module][] = $item;
    echo json_encode(['ok' => true, 'item' => $item], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'delete') {
    $id = (int) ($payload['id'] ?? 0);
    $_SESSION['admin_data_v1'][$module] = array_values(array_filter(
        $_SESSION['admin_data_v1'][$module],
        static fn(array $row): bool => (int) ($row['id'] ?? 0) !== $id
    ));
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Acción no soportada'], JSON_UNESCAPED_UNICODE);
