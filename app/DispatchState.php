<?php

declare(strict_types=1);

final class DispatchState
{
    private const SESSION_KEY = 'dispatch_state_v1';

    public static function getState(array $config): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = self::seedState();
        }

        $state = $_SESSION[self::SESSION_KEY];

        if ((bool) ($config['simulate_live_data'] ?? false)) {
            $state = self::tick($state, (int) $config['max_drivers']);
        }
        $_SESSION[self::SESSION_KEY] = $state;

        return $state;
    }

    private static function seedState(): array
    {
        return [
            'metrics' => ['cancelled' => 0, 'noVehicles' => 0, 'dispatched' => 0, 'created' => 0],
            'drivers' => [],
            'services' => [],
            'zoneQueues' => [
                ['id' => '1', 'values' => [['text' => '68', 'cls' => 'tag-lime'], ['text' => '51', 'cls' => 'tag-lime']]],
                ['id' => 'BASE', 'values' => [['text' => '70', 'cls' => 'tag-blue']]],
                ['id' => '2', 'values' => [['text' => '32', 'cls' => 'tag-green'], ['text' => '63', 'cls' => 'tag-green']]],
                ['id' => 'SO', 'values' => [['text' => '55', 'cls' => 'tag-orange'], ['text' => '2', 'cls' => 'tag-orange']]],
            ],
        ];
    }

    private static function tick(array $state, int $maxDrivers): array
    {
        $state['drivers'] = self::moveDrivers($state['drivers']);
        $state = self::maybeAddDriver($state, $maxDrivers);
        $state = self::maybeToggleConnection($state);
        $state = self::maybeCreateService($state);
        $state = self::maybeDispatchService($state);
        $state = self::maybeUpdateMetrics($state);

        return $state;
    }

    private static function moveDrivers(array $drivers): array
    {
        foreach ($drivers as &$driver) {
            if (($driver['connected'] ?? false) && ($driver['status'] ?? '') !== 'offline') {
                $driver['lat'] += self::randFloat(-0.0015, 0.0015);
                $driver['lng'] += self::randFloat(-0.0015, 0.0015);

                if (self::chance(12)) {
                    $driver['status'] = self::pick(['free', 'busy', 'pending']);
                }
            }
        }

        return $drivers;
    }

    private static function maybeAddDriver(array $state, int $maxDrivers): array
    {
        if (!self::chance(50) || count($state['drivers']) >= $maxDrivers) {
            return $state;
        }

        $names = ['Carlos Mite', 'Ana Solis', 'Ricardo Cedeño', 'Marlon Vera', 'Jessica Ortiz', 'Luis Falconi', 'Pedro Villao', 'Diana Mendoza'];
        $zones = ['Kennedy Norte', 'Urdesa', 'Ceibos', 'Centro', 'Mapasingue', 'Samanes', 'Alborada', 'Sauces'];

        $maxId = max(array_column($state['drivers'], 'id'));
        $id = $maxId + 1;

        $state['drivers'][] = [
            'id' => $id,
            'code' => $id,
            'name' => self::pick($names),
            'plate' => 'GYA-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT),
            'phone' => '09' . (string) random_int(100000000, 999999999),
            'status' => self::pick(['free', 'free', 'busy', 'pending']),
            'services' => random_int(0, 3),
            'wait' => random_int(0, 2),
            'freeUnits' => random_int(0, 30),
            'origin' => self::pick($zones),
            'destination' => '-',
            'passenger' => '-',
            'connected' => true,
            'lat' => -2.17 + self::randFloat(-0.065, 0.065),
            'lng' => -79.92 + self::randFloat(-0.065, 0.065),
        ];

        return $state;
    }

    private static function maybeToggleConnection(array $state): array
    {
        if (!self::chance(30) || count($state['drivers']) <= 8) {
            return $state;
        }

        $index = array_rand($state['drivers']);
        $state['drivers'][$index]['connected'] = !$state['drivers'][$index]['connected'];

        if (!$state['drivers'][$index]['connected']) {
            $state['drivers'][$index]['status'] = 'offline';
        } elseif ($state['drivers'][$index]['status'] === 'offline') {
            $state['drivers'][$index]['status'] = 'free';
        }

        return $state;
    }

    private static function maybeCreateService(array $state): array
    {
        if (!self::chance(45)) {
            return $state;
        }

        $zones = ['Kennedy Norte', 'Urdesa', 'Ceibos', 'Centro', 'Mapasingue', 'Samanes', 'Alborada', 'Sauces'];
        $destinations = ['Aeropuerto', 'Mall del Sol', 'Hospital', 'Terminal'];

        $id = 'SRV-' . (2400 + count($state['services']) + 1);
        $state['services'][] = [
            'id' => $id,
            'when' => date('j/n H:i'),
            'duration' => (string) random_int(10, 35) . 'min',
            'km' => (string) random_int(0, 5) . ' km',
            'passenger' => self::pick(['Ramirez', 'Vera', 'Mendoza', 'Mite']) . ', ' . self::pick(['Ana', 'Luis', 'Jorge', 'Rosa']),
            'phone' => '09' . (string) random_int(100000000, 999999999),
            'origin' => self::pick($zones),
            'destination' => self::pick($destinations),
            'operator' => self::pick(['Operador Ana', 'Operador Luis', 'Operador Carlos']),
            'status' => 'unassigned',
            'lat' => -2.17 + self::randFloat(-0.065, 0.065),
            'lng' => -79.92 + self::randFloat(-0.065, 0.065),
            'priority' => self::pick(['Alta', 'Normal']),
        ];
        $state['metrics']['created']++;

        return $state;
    }

    private static function maybeDispatchService(array $state): array
    {
        if (!self::chance(30)) {
            return $state;
        }

        foreach ($state['services'] as &$service) {
            if ($service['status'] === 'unassigned') {
                $service['status'] = 'active';
                $state['metrics']['dispatched']++;
                break;
            }
        }

        return $state;
    }

    private static function maybeUpdateMetrics(array $state): array
    {
        if (self::chance(14)) {
            $state['metrics']['cancelled']++;
        }

        if (self::chance(14)) {
            $state['metrics']['noVehicles']++;
        }

        return $state;
    }

    private static function chance(int $percent): bool
    {
        return random_int(1, 100) <= $percent;
    }

    private static function pick(array $items)
    {
        return $items[array_rand($items)];
    }

    private static function randFloat(float $min, float $max): float
    {
        return $min + (lcg_value() * ($max - $min));
    }
}
