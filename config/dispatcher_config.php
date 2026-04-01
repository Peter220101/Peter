<?php

declare(strict_types=1);

return [
    'company' => 'SERTAEJ S.A.',
    'city' => 'Guayaquil',
    'google_maps_api_key' => getenv('GOOGLE_MAPS_API_KEY') ?: 'YOUR_GOOGLE_MAPS_API_KEY',
    'google_maps_map_id' => getenv('GOOGLE_MAPS_MAP_ID') ?: 'DEMO_MAP_ID',
    'enable_google_maps' => filter_var(getenv('ENABLE_GOOGLE_MAPS') ?: 'false', FILTER_VALIDATE_BOOL),
    'polling_ms' => 5000,
    'max_drivers' => 35,
    'simulate_live_data' => false,
];
