<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/../config/dispatcher_config.php';
require_once __DIR__ . '/../app/DispatchState.php';

$state = DispatchState::getState($config);

$drivers = $state['drivers'];
$services = $state['services'];
$metrics = $state['metrics'];

$response = [
    'meta' => [
        'company' => $config['company'],
        'city' => $config['city'],
        'serverTime' => date(DATE_ATOM),
    ],
    'metrics' => $metrics,
    'stats' => [
        'total' => count($drivers),
        'free' => count(array_filter($drivers, static fn(array $d): bool => ($d['status'] ?? '') === 'free' && ($d['connected'] ?? false))),
        'busy' => count(array_filter($drivers, static fn(array $d): bool => ($d['status'] ?? '') === 'busy' && ($d['connected'] ?? false))),
        'offline' => count(array_filter($drivers, static fn(array $d): bool => !($d['connected'] ?? false) || ($d['status'] ?? '') === 'offline')),
        'connected' => count(array_filter($drivers, static fn(array $d): bool => (bool) ($d['connected'] ?? false))),
        'unassigned' => count(array_filter($services, static fn(array $s): bool => ($s['status'] ?? '') === 'unassigned')),
        'active' => count(array_filter($services, static fn(array $s): bool => ($s['status'] ?? '') === 'active')),
    ],
    'drivers' => $drivers,
    'services' => $services,
    'zoneQueues' => $state['zoneQueues'],
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
