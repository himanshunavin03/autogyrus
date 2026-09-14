<?php

if (! defined('ABSPATH')) {
    exit;
}

class DuplicateDetectionService
{
    public function build_vehicle_hash(array $record)
    {
        return hash('sha256', wp_json_encode(array(
            'vin' => strtoupper((string) ($record['vin'] ?? '')),
            'year' => (int) ($record['year'] ?? 0),
            'make' => (string) ($record['make'] ?? ''),
            'model' => (string) ($record['model'] ?? ''),
            'trim' => (string) ($record['trim'] ?? ''),
        )));
    }
}
