<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$jsonPath = $root . DIRECTORY_SEPARATOR . 'codex-log' . DIRECTORY_SEPARATOR . 'sample-data' . DIRECTORY_SEPARATOR . 'toyota.json';
$sqlPath = $root . DIRECTORY_SEPARATOR . 'codex-log' . DIRECTORY_SEPARATOR . 'toyota_phase8_import.sql';

if (!is_file($jsonPath)) {
    fwrite(STDERR, "Missing Toyota JSON: {$jsonPath}\n");
    exit(1);
}

$payload = json_decode((string) file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);
$results = $payload['results'][0]['hits'] ?? [];
if (!is_array($results) || empty($results)) {
    fwrite(STDERR, "No inventory records found in Toyota JSON.\n");
    exit(1);
}

$dealerHit = $results[0];
$generatedAt = gmdate('Y-m-d H:i:s');

$records = array_map(static function (array $hit): array {
    $vin = strtoupper(trim((string) ($hit['vin'] ?? '')));
    $year = (int) ($hit['year'] ?? 0);
    $make = trim((string) ($hit['make_name'] ?? ''));
    $model = trim((string) ($hit['model_name'] ?? ''));
    $trim = trim((string) ($hit['trim'] ?? ''));
    $title = trim(implode(' ', array_filter(array((string) $year, $make, $model, $trim))));
    $slugParts = array_filter(array(
        $year ?: null,
        $make ?: null,
        $model ?: null,
        $trim ?: null,
        $vin ? substr($vin, -6) : null,
    ));

    return array(
        'raw' => $hit,
        'vin' => $vin,
        'year' => $year,
        'make' => $make,
        'model' => $model,
        'trim' => $trim,
        'title' => $title ?: $vin,
        'slug' => sanitize_slug(implode('-', $slugParts)),
    );
}, $results);

function sanitize_slug(string $value, int $maxLength = 0): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? $value;
    $value = trim($value, '-');

    if ($value === '') {
        $value = 'vehicle';
    }

    if ($maxLength > 0 && strlen($value) > $maxLength) {
        $hash = substr(hash('sha1', $value), 0, 8);
        $keep = max(1, $maxLength - 9);
        $value = substr($value, 0, $keep) . '-' . $hash;
    }

    return $value;
}

function sql_string($value): string
{
    if ($value === null) {
        return 'NULL';
    }

    if (is_string($value) && preg_match('/^@[A-Za-z_][A-Za-z0-9_]*$/', $value)) {
        return $value;
    }

    if (is_bool($value)) {
        return $value ? '1' : '0';
    }

    if (is_int($value) || is_float($value)) {
        if (is_float($value) && !is_finite($value)) {
            return 'NULL';
        }

        return (string) $value;
    }

    if (is_array($value) || is_object($value)) {
        $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    $value = (string) $value;
    $value = str_replace(array('\\', "'"), array('\\\\', "\\'"), $value);

    return "'" . $value . "'";
}

function json_sql($value): string
{
    return sql_string(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function mysql_datetime($value): ?string
{
    if ($value === null || $value === '') {
        return null;
    }

    if (is_int($value) || (is_string($value) && ctype_digit($value))) {
        return gmdate('Y-m-d H:i:s', (int) $value);
    }

    $timestamp = strtotime((string) $value);
    if (false === $timestamp) {
        return null;
    }

    return gmdate('Y-m-d H:i:s', $timestamp);
}

function mysql_date($value): ?string
{
    $datetime = mysql_datetime($value);
    return $datetime ? substr($datetime, 0, 10) : null;
}

function first_non_empty(...$values)
{
    foreach ($values as $value) {
        if ($value !== null && $value !== '' && $value !== array()) {
            return $value;
        }
    }

    return null;
}

function cloudinary_image_url(string $photoId): string
{
    return 'https://res.cloudinary.com/goauto-images/image/upload/f_auto,c_fill,w_640,ar_14:9,q_auto/v1/' . rawurlencode($photoId) . '.jpg';
}

function uuid_from_text(string $value): string
{
    $hash = hash('sha256', $value);

    return substr($hash, 0, 8) . '-' . substr($hash, 8, 4) . '-' . substr($hash, 12, 4) . '-' . substr($hash, 16, 4) . '-' . substr($hash, 20, 12);
}

function hash_record(array $record): string
{
    $sorted = $record;
    ksort($sorted);
    return hash('sha256', json_encode($sorted, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function insert_sql(string $table, array $data): string
{
    $columns = array();
    $values = array();

    foreach ($data as $column => $value) {
        $columns[] = '`' . $column . '`';
        $values[] = sql_string($value);
    }

    return 'INSERT INTO `' . $table . '` (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ');';
}

function upsert_sql(string $table, array $data, array $updateColumns = array(), string $idColumn = 'id', string $varName = null): string
{
    $columns = array();
    $values = array();

    foreach ($data as $column => $value) {
        $columns[] = '`' . $column . '`';
        $values[] = sql_string($value);
    }

    $updates = array();
    foreach ($updateColumns as $column) {
        $updates[] = '`' . $column . '` = VALUES(`' . $column . '`)';
    }
    $updates[] = '`' . $idColumn . '` = LAST_INSERT_ID(`' . $idColumn . '`)';

    $sql = 'INSERT INTO `' . $table . '` (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ') ON DUPLICATE KEY UPDATE ' . implode(', ', $updates) . ';';
    if ($varName !== null) {
        $varName = preg_replace('/[^A-Za-z0-9_]+/', '_', $varName) ?? $varName;
        $varName = trim((string) preg_replace('/_+/', '_', $varName), '_');
        if ($varName === '') {
            $varName = 'var';
        }
        if (strlen($varName) > 48) {
            $varName = substr($varName, 0, 32) . '_' . substr(hash('sha1', $varName), 0, 12);
        }
        $sql .= PHP_EOL . 'SET @' . $varName . ' := LAST_INSERT_ID();';
    }

    return $sql;
}

function upsert_term_sql(string $name, string $slug, string $taxonomy, string $description = ''): string
{
    return
        'INSERT INTO `wp_terms` (`name`, `slug`, `term_group`) VALUES (' .
        implode(', ', array(sql_string($name), sql_string($slug), '0')) .
        ') ON DUPLICATE KEY UPDATE `term_id` = LAST_INSERT_ID(`term_id`), `name` = VALUES(`name`);' . PHP_EOL .
        'SET @term_id := LAST_INSERT_ID();' . PHP_EOL .
        'INSERT INTO `wp_term_taxonomy` (`term_id`, `taxonomy`, `description`, `parent`, `count`) VALUES (' .
        implode(', ', array('@term_id', sql_string($taxonomy), sql_string($description), '0', '0')) .
        ') ON DUPLICATE KEY UPDATE `term_taxonomy_id` = LAST_INSERT_ID(`term_taxonomy_id`), `description` = VALUES(`description`);' . PHP_EOL .
        'SET @term_taxonomy_id := LAST_INSERT_ID();' . PHP_EOL .
        'INSERT IGNORE INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`) VALUES (@object_id, @term_taxonomy_id);';
}

function unique_values(array $records, string $key): array
{
    $values = array();
    foreach ($records as $record) {
        $value = $record['raw'][$key] ?? null;
        if (is_array($value)) {
            continue;
        }

        $value = trim((string) $value);
        if ($value === '') {
            continue;
        }

        $values[$value] = true;
    }

    return array_keys($values);
}

function make_slug(string $value): string
{
    return sanitize_slug($value);
}

$makes = array();
$models = array();
$bodyTypes = array();
$fuelTypes = array();
$transmissions = array();
$driveTypes = array();
$provinces = array();
$cities = array();
$colors = array();
$conditions = array();
$listingStatuses = array();
$vehicleClasses = array();
$engineTypes = array();

foreach ($records as $record) {
    $raw = $record['raw'];
    $make = trim((string) ($raw['make_name'] ?? ''));
    $model = trim((string) ($raw['model_name'] ?? ''));
    $trim = trim((string) ($raw['trim'] ?? ''));
    $province = trim((string) ($raw['dealer_province_short'] ?? ''));
    $city = trim((string) ($raw['dealer_city_name'] ?? ''));
    $exterior = trim((string) ($raw['exterior_colour_name'] ?? ''));
    $interior = trim((string) ($raw['interior_colour_name'] ?? ''));
    $bodyType = trim((string) ($raw['body_type_name'] ?? ''));
    $bodyCategory = trim((string) ($raw['body_type_category'] ?? ''));
    $fuelName = trim((string) ($raw['fuel_type_name'] ?? ''));
    $fuelCategory = trim((string) ($raw['fuel_type_category'] ?? ''));
    $transmission = trim((string) ($raw['transmission_name'] ?? $raw['transmission_type'] ?? ''));
    $drive = trim((string) ($raw['drive_type_name'] ?? ''));
    $stockType = strtolower(trim((string) ($raw['stock_type'] ?? 'used')));
    $stockStatus = trim((string) ($raw['stock_status_name'] ?? 'in stock'));

    if ($make !== '') {
        $makes[$make] = true;
    }
    if ($model !== '') {
        $models[$make . '|' . $model] = array('make' => $make, 'model' => $model);
    }
    if ($trim !== '') {
        $models[$make . '|' . $model]['trim_' . sanitize_slug($trim)] = $trim;
    }
    if ($bodyType !== '') {
        $bodyTypes[$bodyType] = true;
    }
    if ($bodyCategory !== '') {
        $vehicleClasses[$bodyCategory] = true;
    }
    if ($fuelName !== '') {
        $fuelTypes[$fuelName] = true;
    }
    if ($fuelCategory !== '') {
        $engineTypes[$fuelCategory] = true;
    }
    if ($transmission !== '') {
        $transmissions[$transmission] = true;
    }
    if ($drive !== '') {
        $driveTypes[$drive] = true;
    }
    if ($province !== '') {
        $provinces[$province] = true;
    }
    if ($city !== '') {
        $cities[$city] = true;
    }
    if ($exterior !== '') {
        $colors['exterior|' . $exterior] = array('name' => $exterior, 'scope' => 'exterior');
    }
    if ($interior !== '') {
        $colors['interior|' . $interior] = array('name' => $interior, 'scope' => 'interior');
    }
    $conditions[$stockType] = true;
    $listingStatuses[strtolower(str_replace(' ', '-', $stockStatus))] = $stockStatus;
}

$dealer = array(
    'dealer_external_id' => (string) ($dealerHit['dealer_id'] ?? '52'),
    'dealer_name' => (string) ($dealerHit['dealer_name'] ?? 'Toyota Dealer'),
    'dealer_legal_name' => (string) ($dealerHit['dealer_name'] ?? 'Toyota Dealer'),
    'dealer_slug' => sanitize_slug((string) ($dealerHit['dealer_name'] ?? 'toyota-dealer')),
    'dealer_group_name' => null,
    'website_url' => null,
    'phone_number' => (string) ($dealerHit['dealer_phone'] ?? ''),
    'email_address' => null,
    'address_line1' => (string) ($dealerHit['dealer_address'] ?? ''),
    'address_line2' => null,
    'city' => (string) ($dealerHit['dealer_city_name'] ?? ''),
    'province' => (string) ($dealerHit['dealer_province_short'] ?? 'AB'),
    'country' => 'CA',
    'postal_code' => (string) ($dealerHit['dealer_postal_code'] ?? ''),
    'latitude' => isset($dealerHit['_geoloc']['lat']) ? (float) $dealerHit['_geoloc']['lat'] : null,
    'longitude' => isset($dealerHit['_geoloc']['lng']) ? (float) $dealerHit['_geoloc']['lng'] : null,
    'timezone' => null,
);

$dealerHash = hash('sha256', strtolower(implode('|', array_filter(array(
    $dealer['dealer_name'],
    $dealer['address_line1'],
    $dealer['city'],
    $dealer['postal_code'],
)))));

$sql = array();
$sql[] = 'SET NAMES utf8mb4;';
$sql[] = 'START TRANSACTION;';
$sql[] = '-- Generated from codex-log/sample-data/toyota.json on ' . $generatedAt . ' UTC';

$sql[] = upsert_sql('ag_provider_master', array(
    'uuid' => uuid_from_text('provider-toyota'),
    'provider_code' => 'toyota',
    'provider_name' => 'Toyota Dealer Inventory',
    'provider_category' => 'oem',
    'provider_subcategory' => 'inventory',
    'api_base_url' => null,
    'documentation_url' => null,
    'auth_type' => 'none',
    'import_mode' => 'file',
    'provider_priority' => 10,
    'version_major' => 1,
    'version_minor' => 0,
    'rate_limit_per_minute' => null,
    'rate_limit_per_day' => null,
    'supports_webhooks' => 0,
    'supports_images' => 1,
    'supports_vin' => 1,
    'supports_history' => 1,
    'supports_specifications' => 1,
    'supports_reviews' => 0,
    'supports_pricing' => 1,
    'supports_inventory' => 1,
    'supports_marketplace' => 1,
    'supports_bulk_sync' => 1,
    'supports_delta_sync' => 1,
    'provider_metadata' => array('source_file' => 'codex-log/sample-data/toyota.json'),
    'metadata' => array('source' => 'toyota'),
    'future_reserved' => array(),
    'source_system' => 'toyota',
    'is_active' => 1,
    'is_deleted' => 0,
    'is_verified' => 1,
    'version' => 1,
    'created_by' => null,
    'updated_by' => null,
    'change_reason' => 'phase8_provider_bootstrap',
    'last_synced_at' => $generatedAt,
    'last_provider_update' => $generatedAt,
    'data_quality_score' => 100,
    'confidence_score' => 100,
    'created_at' => $generatedAt,
    'updated_at' => $generatedAt,
    'deleted_at' => null,
), array('provider_name', 'provider_category', 'provider_subcategory', 'auth_type', 'import_mode', 'provider_priority', 'supports_webhooks', 'supports_images', 'supports_vin', 'supports_history', 'supports_specifications', 'supports_reviews', 'supports_pricing', 'supports_inventory', 'supports_marketplace', 'supports_bulk_sync', 'supports_delta_sync', 'provider_metadata', 'metadata', 'future_reserved', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'updated_at'), 'id', 'provider_id');
$sql[] = upsert_sql('ag_provider_master', array(
    'uuid' => uuid_from_text('provider-wordpress'),
    'provider_code' => 'wordpress',
    'provider_name' => 'WordPress Vehicle CRUD',
    'provider_category' => 'internal',
    'provider_subcategory' => 'inventory',
    'api_base_url' => null,
    'documentation_url' => null,
    'auth_type' => 'local',
    'import_mode' => 'manual',
    'provider_priority' => 1,
    'version_major' => 1,
    'version_minor' => 0,
    'rate_limit_per_minute' => null,
    'rate_limit_per_day' => null,
    'supports_webhooks' => 1,
    'supports_images' => 1,
    'supports_vin' => 1,
    'supports_history' => 1,
    'supports_specifications' => 1,
    'supports_reviews' => 1,
    'supports_pricing' => 1,
    'supports_inventory' => 1,
    'supports_marketplace' => 1,
    'supports_bulk_sync' => 1,
    'supports_delta_sync' => 1,
    'provider_metadata' => array('source_file' => 'wordpress'),
    'metadata' => array('source' => 'wordpress'),
    'future_reserved' => array(),
    'source_system' => 'wordpress',
    'is_active' => 1,
    'is_deleted' => 0,
    'is_verified' => 1,
    'version' => 1,
    'created_by' => null,
    'updated_by' => null,
    'change_reason' => 'phase8_provider_bootstrap',
    'last_synced_at' => $generatedAt,
    'last_provider_update' => $generatedAt,
    'data_quality_score' => 100,
    'confidence_score' => 100,
    'created_at' => $generatedAt,
    'updated_at' => $generatedAt,
    'deleted_at' => null,
), array('provider_name', 'provider_category', 'provider_subcategory', 'auth_type', 'import_mode', 'provider_priority', 'supports_webhooks', 'supports_images', 'supports_vin', 'supports_history', 'supports_specifications', 'supports_reviews', 'supports_pricing', 'supports_inventory', 'supports_marketplace', 'supports_bulk_sync', 'supports_delta_sync', 'provider_metadata', 'metadata', 'future_reserved', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'updated_at'), 'id', 'wordpress_provider_id');

$sql[] = upsert_sql('ag_country', array(
    'uuid' => uuid_from_text('country-ca'),
    'iso2' => 'CA',
    'iso3' => 'CAN',
    'country_name' => 'Canada',
    'source_system' => 'toyota',
    'is_active' => 1,
    'is_deleted' => 0,
    'is_verified' => 1,
    'version' => 1,
    'metadata' => array('source' => 'toyota'),
    'future_reserved' => array(),
    'created_by' => null,
    'updated_by' => null,
    'change_reason' => 'phase8_lookup_bootstrap',
    'last_synced_at' => $generatedAt,
    'last_provider_update' => $generatedAt,
    'data_quality_score' => 100,
    'confidence_score' => 100,
    'created_at' => $generatedAt,
    'updated_at' => $generatedAt,
    'deleted_at' => null,
), array('iso3', 'country_name', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'metadata', 'future_reserved', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'updated_at'), 'id', 'country_id');

$sql[] = upsert_sql('ag_state_province', array(
    'uuid' => uuid_from_text('province-ab'),
    'country_id' => '@country_id',
    'province_code' => 'AB',
    'province_name' => 'Alberta',
    'source_system' => 'toyota',
    'is_active' => 1,
    'is_deleted' => 0,
    'is_verified' => 1,
    'version' => 1,
    'metadata' => array('source' => 'toyota'),
    'future_reserved' => array(),
    'created_by' => null,
    'updated_by' => null,
    'change_reason' => 'phase8_lookup_bootstrap',
    'last_synced_at' => $generatedAt,
    'last_provider_update' => $generatedAt,
    'data_quality_score' => 100,
    'confidence_score' => 100,
    'created_at' => $generatedAt,
    'updated_at' => $generatedAt,
    'deleted_at' => null,
), array('province_name', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'metadata', 'future_reserved', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'updated_at'), 'id', 'province_id');

$sql[] = upsert_sql('ag_city', array(
    'uuid' => uuid_from_text('city-edmonton'),
    'country_id' => '@country_id',
    'province_id' => '@province_id',
    'city_name' => 'Edmonton',
    'source_system' => 'toyota',
    'is_active' => 1,
    'is_deleted' => 0,
    'is_verified' => 1,
    'version' => 1,
    'metadata' => array('source' => 'toyota'),
    'future_reserved' => array(),
    'created_by' => null,
    'updated_by' => null,
    'change_reason' => 'phase8_lookup_bootstrap',
    'last_synced_at' => $generatedAt,
    'last_provider_update' => $generatedAt,
    'data_quality_score' => 100,
    'confidence_score' => 100,
    'created_at' => $generatedAt,
    'updated_at' => $generatedAt,
    'deleted_at' => null,
), array('source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'metadata', 'future_reserved', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'updated_at'), 'id', 'city_id');

$sql[] = upsert_sql('ag_currency', array(
    'uuid' => uuid_from_text('currency-cad'),
    'currency_code' => 'CAD',
    'currency_name' => 'Canadian Dollar',
    'symbol' => '$',
    'minor_unit' => 2,
    'metadata' => array('source' => 'toyota'),
    'future_reserved' => array(),
    'created_by' => null,
    'updated_by' => null,
    'change_reason' => 'phase8_lookup_bootstrap',
    'last_synced_at' => $generatedAt,
    'last_provider_update' => $generatedAt,
    'data_quality_score' => 100,
    'confidence_score' => 100,
    'source_system' => 'toyota',
    'is_active' => 1,
    'is_deleted' => 0,
    'is_verified' => 1,
    'version' => 1,
    'created_at' => $generatedAt,
    'updated_at' => $generatedAt,
    'deleted_at' => null,
), array('currency_name', 'symbol', 'minor_unit', 'metadata', 'future_reserved', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'updated_at'), 'id', 'currency_id');

$sql[] = upsert_sql('ag_market', array(
    'uuid' => uuid_from_text('market-ca'),
    'market_code' => 'CA',
    'market_name' => 'Canada',
    'country_id' => '@country_id',
    'currency_id' => '@currency_id',
    'language_code' => 'en',
    'region_code' => 'CA',
    'market_type' => 'retail',
    'is_primary' => 1,
    'metadata' => array('source' => 'toyota'),
    'future_reserved' => array(),
    'created_by' => null,
    'updated_by' => null,
    'change_reason' => 'phase8_lookup_bootstrap',
    'last_synced_at' => $generatedAt,
    'last_provider_update' => $generatedAt,
    'data_quality_score' => 100,
    'confidence_score' => 100,
    'source_system' => 'toyota',
    'is_active' => 1,
    'is_deleted' => 0,
    'is_verified' => 1,
    'version' => 1,
    'created_at' => $generatedAt,
    'updated_at' => $generatedAt,
    'deleted_at' => null,
), array('market_name', 'country_id', 'currency_id', 'language_code', 'region_code', 'market_type', 'is_primary', 'metadata', 'future_reserved', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'updated_at'), 'id', 'market_id');

foreach (array_keys($makes) as $make) {
    $slug = sanitize_slug($make);
    $sql[] = upsert_sql('ag_manufacturer', array(
        'uuid' => uuid_from_text('manufacturer-' . $slug),
        'manufacturer_name' => $make,
        'manufacturer_slug' => $slug,
        'source_system' => 'toyota',
        'is_active' => 1,
        'is_deleted' => 0,
        'is_verified' => 1,
        'version' => 1,
        'metadata' => array('source' => 'toyota'),
        'future_reserved' => array(),
        'created_by' => null,
        'updated_by' => null,
        'change_reason' => 'phase8_lookup_bootstrap',
        'last_synced_at' => $generatedAt,
        'last_provider_update' => $generatedAt,
        'data_quality_score' => 100,
        'confidence_score' => 100,
        'created_at' => $generatedAt,
        'updated_at' => $generatedAt,
        'deleted_at' => null,
    ), array('manufacturer_name', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'metadata', 'future_reserved', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'updated_at'), 'id', 'manufacturer_id_' . $slug);
}

$modelRows = array();
foreach ($models as $modelKey => $payloadModel) {
    $modelRows[] = $payloadModel;
}

foreach ($modelRows as $payloadModel) {
    $make = $payloadModel['make'];
    $model = $payloadModel['model'];
    $makeSlug = sanitize_slug($make);
    $modelSlug = sanitize_slug($model);
    $sql[] = "SET @manufacturer_id := (SELECT id FROM ag_manufacturer WHERE manufacturer_slug = " . sql_string($makeSlug) . " LIMIT 1);";
    $sql[] = upsert_sql('ag_model', array(
        'uuid' => uuid_from_text('model-' . $makeSlug . '-' . $modelSlug),
        'manufacturer_id' => '@manufacturer_id',
        'model_name' => $model,
        'model_slug' => $modelSlug,
        'source_system' => 'toyota',
        'is_active' => 1,
        'is_deleted' => 0,
        'is_verified' => 1,
        'version' => 1,
        'metadata' => array('source' => 'toyota'),
        'future_reserved' => array(),
        'created_by' => null,
        'updated_by' => null,
        'change_reason' => 'phase8_lookup_bootstrap',
        'last_synced_at' => $generatedAt,
        'last_provider_update' => $generatedAt,
        'data_quality_score' => 100,
        'confidence_score' => 100,
        'created_at' => $generatedAt,
        'updated_at' => $generatedAt,
        'deleted_at' => null,
    ), array('manufacturer_id', 'model_name', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'metadata', 'future_reserved', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'updated_at'), 'id', 'model_id_' . $makeSlug . '_' . $modelSlug);

    foreach (array_keys(array_filter($payloadModel, static fn($key) => str_starts_with((string) $key, 'trim_'), ARRAY_FILTER_USE_KEY)) as $trimKey) {
        $trim = $payloadModel[$trimKey];
        $trimSlug = sanitize_slug($trim);
        $sql[] = upsert_sql('ag_trim', array(
            'uuid' => uuid_from_text('trim-' . $makeSlug . '-' . $modelSlug . '-' . $trimSlug),
            'model_id' => '@model_id',
            'trim_name' => $trim,
            'trim_slug' => $trimSlug,
            'source_system' => 'toyota',
            'is_active' => 1,
            'is_deleted' => 0,
            'is_verified' => 1,
            'version' => 1,
            'metadata' => array('source' => 'toyota'),
            'future_reserved' => array(),
            'created_by' => null,
            'updated_by' => null,
            'change_reason' => 'phase8_lookup_bootstrap',
            'last_synced_at' => $generatedAt,
            'last_provider_update' => $generatedAt,
            'data_quality_score' => 100,
            'confidence_score' => 100,
            'created_at' => $generatedAt,
            'updated_at' => $generatedAt,
            'deleted_at' => null,
        ), array('model_id', 'trim_name', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'metadata', 'future_reserved', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'updated_at'), 'id', 'trim_id_' . $makeSlug . '_' . $modelSlug . '_' . $trimSlug);
    }
}

foreach (array_keys($vehicleClasses) as $className) {
    $classSlug = sanitize_slug($className);
    $sql[] = upsert_sql('ag_vehicle_class', array(
        'uuid' => uuid_from_text('vehicle-class-' . $classSlug),
        'class_code' => $classSlug,
        'class_name' => $className,
        'class_group' => $className,
        'parent_class_id' => null,
        'metadata' => array('source' => 'toyota'),
        'future_reserved' => array(),
        'created_by' => null,
        'updated_by' => null,
        'change_reason' => 'phase8_lookup_bootstrap',
        'last_synced_at' => $generatedAt,
        'last_provider_update' => $generatedAt,
        'data_quality_score' => 100,
        'confidence_score' => 100,
        'source_system' => 'toyota',
        'is_active' => 1,
        'is_deleted' => 0,
        'is_verified' => 1,
        'version' => 1,
        'created_at' => $generatedAt,
        'updated_at' => $generatedAt,
        'deleted_at' => null,
    ), array('class_name', 'class_group', 'parent_class_id', 'metadata', 'future_reserved', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'updated_at'), 'id', 'vehicle_class_id_' . $classSlug);
}

foreach (array_keys($conditions) as $condition) {
    $slug = sanitize_slug($condition);
    $sql[] = upsert_sql('ag_vehicle_condition', array(
        'uuid' => uuid_from_text('vehicle-condition-' . $slug),
        'condition_code' => $slug,
        'condition_name' => ucfirst($condition),
        'condition_rank' => $slug === 'new' ? 1 : 50,
        'is_new_vehicle' => $slug === 'new' ? 1 : 0,
        'is_used_vehicle' => $slug === 'new' ? 0 : 1,
        'is_certified_vehicle' => 0,
        'metadata' => array('source' => 'toyota'),
        'future_reserved' => array(),
        'created_by' => null,
        'updated_by' => null,
        'change_reason' => 'phase8_lookup_bootstrap',
        'last_synced_at' => $generatedAt,
        'last_provider_update' => $generatedAt,
        'data_quality_score' => 100,
        'confidence_score' => 100,
        'source_system' => 'toyota',
        'is_active' => 1,
        'is_deleted' => 0,
        'is_verified' => 1,
        'version' => 1,
        'created_at' => $generatedAt,
        'updated_at' => $generatedAt,
        'deleted_at' => null,
    ), array('condition_name', 'condition_rank', 'is_new_vehicle', 'is_used_vehicle', 'is_certified_vehicle', 'metadata', 'future_reserved', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'updated_at'), 'id', 'vehicle_condition_id_' . $slug);
}

foreach ($listingStatuses as $slug => $name) {
    $sql[] = upsert_sql('ag_listing_status', array(
        'uuid' => uuid_from_text('listing-status-' . $slug),
        'status_code' => $slug,
        'status_name' => $name,
        'status_rank' => 1,
        'is_public' => 1,
        'is_terminal' => in_array($slug, array('sold', 'removed', 'inactive'), true) ? 1 : 0,
        'metadata' => array('source' => 'toyota'),
        'future_reserved' => array(),
        'created_by' => null,
        'updated_by' => null,
        'change_reason' => 'phase8_lookup_bootstrap',
        'last_synced_at' => $generatedAt,
        'last_provider_update' => $generatedAt,
        'data_quality_score' => 100,
        'confidence_score' => 100,
        'source_system' => 'toyota',
        'is_active' => 1,
        'is_deleted' => 0,
        'is_verified' => 1,
        'version' => 1,
        'created_at' => $generatedAt,
        'updated_at' => $generatedAt,
        'deleted_at' => null,
    ), array('status_name', 'status_rank', 'is_public', 'is_terminal', 'metadata', 'future_reserved', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'updated_at'), 'id', 'listing_status_id_' . $slug);
}

foreach (array_keys($bodyTypes) as $bodyType) {
    $slug = sanitize_slug($bodyType);
    $sql[] = upsert_sql('ag_body_style', array(
        'uuid' => uuid_from_text('body-style-' . $slug),
        'body_style_name' => $bodyType,
        'body_style_slug' => $slug,
        'source_system' => 'toyota',
        'is_active' => 1,
        'is_deleted' => 0,
        'is_verified' => 1,
        'version' => 1,
        'metadata' => array('source' => 'toyota'),
        'future_reserved' => array(),
        'created_by' => null,
        'updated_by' => null,
        'change_reason' => 'phase8_lookup_bootstrap',
        'last_synced_at' => $generatedAt,
        'last_provider_update' => $generatedAt,
        'data_quality_score' => 100,
        'confidence_score' => 100,
        'created_at' => $generatedAt,
        'updated_at' => $generatedAt,
        'deleted_at' => null,
    ), array('body_style_name', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'metadata', 'future_reserved', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'updated_at'), 'id', 'body_style_id_' . $slug);
}

foreach (array_keys($engineTypes) as $engineType) {
    $slug = sanitize_slug($engineType);
    $sql[] = upsert_sql('ag_engine_type', array(
        'uuid' => uuid_from_text('engine-type-' . $slug),
        'engine_type_name' => $engineType,
        'engine_type_slug' => $slug,
        'source_system' => 'toyota',
        'is_active' => 1,
        'is_deleted' => 0,
        'is_verified' => 1,
        'version' => 1,
        'metadata' => array('source' => 'toyota'),
        'future_reserved' => array(),
        'created_by' => null,
        'updated_by' => null,
        'change_reason' => 'phase8_lookup_bootstrap',
        'last_synced_at' => $generatedAt,
        'last_provider_update' => $generatedAt,
        'data_quality_score' => 100,
        'confidence_score' => 100,
        'created_at' => $generatedAt,
        'updated_at' => $generatedAt,
        'deleted_at' => null,
    ), array('engine_type_name', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'metadata', 'future_reserved', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'updated_at'), 'id', 'engine_type_id_' . $slug);
}

foreach (array_keys($transmissions) as $transmission) {
    $slug = sanitize_slug($transmission);
    $sql[] = upsert_sql('ag_transmission_type', array(
        'uuid' => uuid_from_text('transmission-type-' . $slug),
        'transmission_type_name' => $transmission,
        'transmission_type_slug' => $slug,
        'source_system' => 'toyota',
        'is_active' => 1,
        'is_deleted' => 0,
        'is_verified' => 1,
        'version' => 1,
        'metadata' => array('source' => 'toyota'),
        'future_reserved' => array(),
        'created_by' => null,
        'updated_by' => null,
        'change_reason' => 'phase8_lookup_bootstrap',
        'last_synced_at' => $generatedAt,
        'last_provider_update' => $generatedAt,
        'data_quality_score' => 100,
        'confidence_score' => 100,
        'created_at' => $generatedAt,
        'updated_at' => $generatedAt,
        'deleted_at' => null,
    ), array('transmission_type_name', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'metadata', 'future_reserved', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'updated_at'), 'id', 'transmission_type_id_' . $slug);
}

foreach (array_keys($driveTypes) as $driveType) {
    $slug = sanitize_slug($driveType);
    $sql[] = upsert_sql('ag_drive_type', array(
        'uuid' => uuid_from_text('drive-type-' . $slug),
        'drive_type_name' => $driveType,
        'drive_type_slug' => $slug,
        'source_system' => 'toyota',
        'is_active' => 1,
        'is_deleted' => 0,
        'is_verified' => 1,
        'version' => 1,
        'metadata' => array('source' => 'toyota'),
        'future_reserved' => array(),
        'created_by' => null,
        'updated_by' => null,
        'change_reason' => 'phase8_lookup_bootstrap',
        'last_synced_at' => $generatedAt,
        'last_provider_update' => $generatedAt,
        'data_quality_score' => 100,
        'confidence_score' => 100,
        'created_at' => $generatedAt,
        'updated_at' => $generatedAt,
        'deleted_at' => null,
    ), array('drive_type_name', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'metadata', 'future_reserved', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'updated_at'), 'id', 'drive_type_id_' . $slug);
}

foreach (array_keys($fuelTypes) as $fuelType) {
    $slug = sanitize_slug($fuelType);
    $sql[] = upsert_sql('ag_fuel_type', array(
        'uuid' => uuid_from_text('fuel-type-' . $slug),
        'fuel_type_name' => $fuelType,
        'fuel_type_slug' => $slug,
        'source_system' => 'toyota',
        'is_active' => 1,
        'is_deleted' => 0,
        'is_verified' => 1,
        'version' => 1,
        'metadata' => array('source' => 'toyota'),
        'future_reserved' => array(),
        'created_by' => null,
        'updated_by' => null,
        'change_reason' => 'phase8_lookup_bootstrap',
        'last_synced_at' => $generatedAt,
        'last_provider_update' => $generatedAt,
        'data_quality_score' => 100,
        'confidence_score' => 100,
        'created_at' => $generatedAt,
        'updated_at' => $generatedAt,
        'deleted_at' => null,
    ), array('fuel_type_name', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'metadata', 'future_reserved', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'updated_at'), 'id', 'fuel_type_id_' . $slug);
}

foreach (array_keys($provinces) as $province) {
    $slug = sanitize_slug($province);
    $sql[] = upsert_sql('ag_state_province', array(
        'uuid' => uuid_from_text('province-' . $slug),
        'country_id' => '@country_id',
        'province_code' => $province,
        'province_name' => $province === 'AB' ? 'Alberta' : $province,
        'source_system' => 'toyota',
        'is_active' => 1,
        'is_deleted' => 0,
        'is_verified' => 1,
        'version' => 1,
        'metadata' => array('source' => 'toyota'),
        'future_reserved' => array(),
        'created_by' => null,
        'updated_by' => null,
        'change_reason' => 'phase8_lookup_bootstrap',
        'last_synced_at' => $generatedAt,
        'last_provider_update' => $generatedAt,
        'data_quality_score' => 100,
        'confidence_score' => 100,
        'created_at' => $generatedAt,
        'updated_at' => $generatedAt,
        'deleted_at' => null,
    ), array('province_name', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'metadata', 'future_reserved', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'updated_at'), 'id', 'province_id_' . $slug);
}

foreach (array_keys($cities) as $city) {
    $slug = sanitize_slug($city);
    $sql[] = upsert_sql('ag_city', array(
        'uuid' => uuid_from_text('city-' . $slug),
        'country_id' => '@country_id',
        'province_id' => '@province_id',
        'city_name' => $city,
        'source_system' => 'toyota',
        'is_active' => 1,
        'is_deleted' => 0,
        'is_verified' => 1,
        'version' => 1,
        'metadata' => array('source' => 'toyota'),
        'future_reserved' => array(),
        'created_by' => null,
        'updated_by' => null,
        'change_reason' => 'phase8_lookup_bootstrap',
        'last_synced_at' => $generatedAt,
        'last_provider_update' => $generatedAt,
        'data_quality_score' => 100,
        'confidence_score' => 100,
        'created_at' => $generatedAt,
        'updated_at' => $generatedAt,
        'deleted_at' => null,
    ), array('source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'metadata', 'future_reserved', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'updated_at'), 'id', 'city_id_' . $slug);
}

foreach ($colors as $color) {
    $scopeSlug = sanitize_slug((string) $color['scope']);
    $name = (string) $color['name'];
    $slug = sanitize_slug($name);
    $sql[] = upsert_sql('ag_color', array(
        'uuid' => uuid_from_text('color-' . $scopeSlug . '-' . $slug),
        'color_code' => $slug,
        'color_name' => $name,
        'color_family' => null,
        'color_scope' => $color['scope'],
        'hex_value' => null,
        'manufacturer_color_code' => null,
        'metadata' => array('source' => 'toyota'),
        'future_reserved' => array(),
        'created_by' => null,
        'updated_by' => null,
        'change_reason' => 'phase8_lookup_bootstrap',
        'last_synced_at' => $generatedAt,
        'last_provider_update' => $generatedAt,
        'data_quality_score' => 100,
        'confidence_score' => 100,
        'source_system' => 'toyota',
        'is_active' => 1,
        'is_deleted' => 0,
        'is_verified' => 1,
        'version' => 1,
        'created_at' => $generatedAt,
        'updated_at' => $generatedAt,
        'deleted_at' => null,
    ), array('color_name', 'color_family', 'color_scope', 'hex_value', 'manufacturer_color_code', 'metadata', 'future_reserved', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'updated_at'), 'id', 'color_id_' . $scopeSlug . '_' . $slug);
}

foreach (array_keys($vehiclesClasses = $vehicleClasses) as $className) {
    // already handled above
}

$sql[] = upsert_sql('ag_dealer_master', array(
    'uuid' => uuid_from_text('dealer-' . $dealerHash),
    'dealer_code' => 'toyota-' . strtolower((string) $dealer['dealer_external_id']),
    'dealer_name' => $dealer['dealer_name'],
    'dealer_legal_name' => $dealer['dealer_legal_name'],
    'dealer_slug' => $dealer['dealer_slug'],
    'dealer_group_name' => $dealer['dealer_group_name'],
    'dealer_category' => 'oem',
    'ownership_type' => 'franchise',
    'network_role' => 'dealer',
    'market_id' => '@market_id',
    'country_id' => '@country_id',
    'province_id' => '@province_id',
    'city_id' => '@city_id',
    'website_url' => $dealer['website_url'],
    'phone_number' => $dealer['phone_number'],
    'email_address' => $dealer['email_address'],
    'address_line1' => $dealer['address_line1'],
    'address_line2' => $dealer['address_line2'],
    'postal_code' => $dealer['postal_code'],
    'latitude' => $dealer['latitude'],
    'longitude' => $dealer['longitude'],
    'timezone' => $dealer['timezone'],
    'canonical_dealer_hash' => $dealerHash,
    'metadata' => array('source' => 'toyota', 'dealer_external_id' => $dealer['dealer_external_id']),
    'future_reserved' => array(),
    'created_by' => null,
    'updated_by' => null,
    'change_reason' => 'dealer_sync',
    'last_synced_at' => $generatedAt,
    'last_provider_update' => $generatedAt,
    'data_quality_score' => 100,
    'confidence_score' => 100,
    'source_system' => 'toyota',
    'is_active' => 1,
    'is_deleted' => 0,
    'is_verified' => 1,
    'version' => 1,
    'created_at' => $generatedAt,
    'updated_at' => $generatedAt,
    'deleted_at' => null,
), array('dealer_name', 'dealer_legal_name', 'dealer_slug', 'dealer_group_name', 'dealer_category', 'ownership_type', 'network_role', 'market_id', 'country_id', 'province_id', 'city_id', 'website_url', 'phone_number', 'email_address', 'address_line1', 'address_line2', 'postal_code', 'latitude', 'longitude', 'timezone', 'canonical_dealer_hash', 'metadata', 'future_reserved', 'created_by', 'updated_by', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'updated_at'), 'id', 'dealer_id');

$sql[] = upsert_sql('ag_dealer_map', array(
    'uuid' => uuid_from_text('dealer-map-' . $dealerHash),
    'dealer_id' => '@dealer_id',
    'provider_id' => '@provider_id',
    'source_system' => 'toyota',
    'external_id' => $dealer['dealer_external_id'],
    'external_url' => null,
    'external_reference_type' => 'dealer',
    'provider_dealer_code' => (string) $dealer['dealer_external_id'],
    'market_id' => '@market_id',
    'source_rank' => 1,
    'source_priority' => 10,
    'is_primary' => 1,
    'sync_status' => 'active',
    'provider_metadata' => array('source' => 'toyota'),
    'metadata' => array('dealer_name' => $dealer['dealer_name']),
    'future_reserved' => array(),
    'created_by' => null,
    'updated_by' => null,
    'change_reason' => 'dealer_map_sync',
    'last_synced_at' => $generatedAt,
    'last_provider_update' => $generatedAt,
    'data_quality_score' => 100,
    'is_active' => 1,
    'is_deleted' => 0,
    'is_verified' => 1,
    'version' => 1,
    'first_seen_at' => $generatedAt,
    'last_seen_at' => $generatedAt,
    'created_at' => $generatedAt,
    'updated_at' => $generatedAt,
    'deleted_at' => null,
), array('dealer_id', 'provider_id', 'source_system', 'external_url', 'external_reference_type', 'provider_dealer_code', 'market_id', 'source_rank', 'source_priority', 'is_primary', 'sync_status', 'provider_metadata', 'metadata', 'future_reserved', 'created_by', 'updated_by', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'is_active', 'is_deleted', 'is_verified', 'version', 'first_seen_at', 'last_seen_at', 'updated_at'), 'id', 'dealer_map_id');

$sql[] = 'SET @dealer_id := (SELECT id FROM ag_dealer_master WHERE canonical_dealer_hash = ' . sql_string($dealerHash) . ' LIMIT 1);';
$sql[] = 'SET @dealer_map_id := (SELECT id FROM ag_dealer_map WHERE provider_id = @provider_id AND source_system = \'toyota\' AND external_id = ' . sql_string((string) $dealer['dealer_external_id']) . ' LIMIT 1);';

foreach ($records as $record) {
    $raw = $record['raw'];
    $vin = $record['vin'];
    $year = $record['year'];
    $make = $record['make'];
    $model = $record['model'];
    $trim = $record['trim'];
    $title = $record['title'];
    $slug = $record['slug'];
    $makeSlug = sanitize_slug($make);
    $modelSlug = sanitize_slug($model);
    $trimSlug = sanitize_slug($trim);
    $bodyStyle = trim((string) ($raw['body_type_name'] ?? ''));
    $bodyCategory = trim((string) ($raw['body_type_category'] ?? ''));
    $fuelName = trim((string) ($raw['fuel_type_name'] ?? ''));
    $fuelCategory = trim((string) ($raw['fuel_type_category'] ?? ''));
    $transmissionName = trim((string) ($raw['transmission_name'] ?? $raw['transmission_type'] ?? ''));
    $driveType = trim((string) ($raw['drive_type_name'] ?? ''));
    $province = trim((string) ($raw['dealer_province_short'] ?? 'AB'));
    $city = trim((string) ($raw['dealer_city_name'] ?? ''));
    $postal = trim((string) ($raw['dealer_postal_code'] ?? ''));
    $country = 'CA';
    $currentPrice = first_non_empty($raw['sort_price'] ?? null, $raw['list_price'] ?? null, $raw['special_price'] ?? null, $raw['regular_price'] ?? null);
    $msrp = first_non_empty($raw['msrp'] ?? null, $raw['list_price'] ?? null, $currentPrice);
    $salePrice = first_non_empty($raw['special_price'] ?? null, $raw['sort_price'] ?? null);
    $mileage = $raw['odometer'] ?? null;
    $daysInStock = $raw['days_in_stock'] ?? null;
    $dateCreated = mysql_datetime($raw['date_created'] ?? null);
    $dateModified = mysql_datetime($raw['date_modified'] ?? null) ?? $generatedAt;
    $sourceRecordHash = hash_record($raw);
    $canonicalHash = hash('sha256', json_encode(array(
        'vin' => $vin,
        'year' => $year,
        'make' => $make,
        'model' => $model,
        'trim' => $trim,
        'body_type' => $bodyStyle,
        'dealer_id' => $dealer['dealer_external_id'],
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $duplicateGroupHash = hash('sha256', strtolower(implode('|', array_filter(array($year, $make, $model, $trim, $bodyCategory ?: $bodyStyle)))));
    $stockNumber = trim((string) ($raw['stock_number'] ?? ''));
    $itemKey = trim((string) ($raw['item_key'] ?? $vin));
    $manCode = trim((string) ($raw['manufacturer_code'] ?? ''));
    $styleId = trim((string) ($raw['style_id'] ?? ''));
    $makeModelTrim = $raw['make_model_trim'] ?? array();
    $globalModelCode = $styleId !== '' ? $styleId : null;
    $regionalModelCode = is_array($makeModelTrim) && !empty($makeModelTrim['lvl1']) ? (string) $makeModelTrim['lvl1'] : null;
    $bodyCode = $bodyCategory !== '' ? $bodyCategory : ($bodyStyle !== '' ? $bodyStyle : null);

    $manufacturerIdVar = '@manufacturer_id_' . $makeSlug;
    $modelIdVar = '@model_id_' . $makeSlug . '_' . $modelSlug;
    $trimIdVar = '@trim_id_' . $makeSlug . '_' . $modelSlug . '_' . $trimSlug;
    $countryIdVar = '@country_id';
    $provinceIdVar = '@province_id';
    $cityIdVar = '@city_id';
    $currencyIdVar = '@currency_id';
    $marketIdVar = '@market_id';
    $vehicleClassIdVar = '@vehicle_class_id_' . sanitize_slug($bodyCategory !== '' ? $bodyCategory : 'unknown');
    $conditionIdVar = '@vehicle_condition_id_' . sanitize_slug($stockType = strtolower((string) ($raw['stock_type'] ?? 'used')));
    $listingStatusVar = '@listing_status_id_' . sanitize_slug(strtolower(str_replace(' ', '-', (string) ($raw['stock_status_name'] ?? 'in stock'))));
    $bodyStyleIdVar = '@body_style_id_' . sanitize_slug($bodyStyle ?: 'unknown');
    $engineTypeVar = '@engine_type_id_' . sanitize_slug($fuelCategory !== '' ? $fuelCategory : 'unknown');
    $transTypeVar = '@transmission_type_id_' . sanitize_slug($transmissionName ?: 'unknown');
    $driveTypeVar = '@drive_type_id_' . sanitize_slug($driveType ?: 'unknown');
    $fuelTypeVar = '@fuel_type_id_' . sanitize_slug($fuelName ?: 'unknown');
    $exteriorColorVar = '@color_id_exterior_' . sanitize_slug((string) ($raw['exterior_colour_name'] ?? 'unknown'));
    $interiorColorVar = '@color_id_interior_' . sanitize_slug((string) ($raw['interior_colour_name'] ?? 'unknown'));

    $sql[] = "SET @manufacturer_id := (SELECT id FROM ag_manufacturer WHERE manufacturer_slug = " . sql_string($makeSlug) . " LIMIT 1);";
    $sql[] = "SET @model_id := (SELECT id FROM ag_model WHERE manufacturer_id = @manufacturer_id AND model_slug = " . sql_string($modelSlug) . " LIMIT 1);";
    $sql[] = "SET @trim_id := (SELECT id FROM ag_trim WHERE model_id = @model_id AND trim_slug = " . sql_string($trimSlug) . " LIMIT 1);";
    $sql[] = "SET @country_id := (SELECT id FROM ag_country WHERE iso2 = 'CA' LIMIT 1);";
    $sql[] = "SET @province_id := (SELECT id FROM ag_state_province WHERE country_id = @country_id AND province_code = " . sql_string($province) . " LIMIT 1);";
    $sql[] = "SET @city_id := (SELECT id FROM ag_city WHERE country_id = @country_id AND province_id = @province_id AND city_name = " . sql_string($city) . " LIMIT 1);";
    $sql[] = "SET @currency_id := (SELECT id FROM ag_currency WHERE currency_code = 'CAD' LIMIT 1);";
    $sql[] = "SET @market_id := (SELECT id FROM ag_market WHERE market_code = 'CA' AND country_id = @country_id LIMIT 1);";
    $sql[] = "SET @vehicle_class_id := (SELECT id FROM ag_vehicle_class WHERE class_code = " . sql_string(sanitize_slug($bodyCategory !== '' ? $bodyCategory : 'unknown')) . " LIMIT 1);";
    $sql[] = "SET @vehicle_condition_id := (SELECT id FROM ag_vehicle_condition WHERE condition_code = " . sql_string(sanitize_slug($stockType)) . " LIMIT 1);";
    $sql[] = "SET @listing_status_id := (SELECT id FROM ag_listing_status WHERE status_code = " . sql_string(sanitize_slug(strtolower(str_replace(' ', '-', (string) ($raw['stock_status_name'] ?? 'in stock'))))) . " LIMIT 1);";
    $sql[] = "SET @body_style_id := (SELECT id FROM ag_body_style WHERE body_style_slug = " . sql_string(sanitize_slug($bodyStyle ?: 'unknown')) . " LIMIT 1);";
    $sql[] = "SET @engine_type_id := (SELECT id FROM ag_engine_type WHERE engine_type_slug = " . sql_string(sanitize_slug($fuelCategory !== '' ? $fuelCategory : 'unknown')) . " LIMIT 1);";
    $sql[] = "SET @transmission_type_id := (SELECT id FROM ag_transmission_type WHERE transmission_type_slug = " . sql_string(sanitize_slug($transmissionName ?: 'unknown')) . " LIMIT 1);";
    $sql[] = "SET @drive_type_id := (SELECT id FROM ag_drive_type WHERE drive_type_slug = " . sql_string(sanitize_slug($driveType ?: 'unknown')) . " LIMIT 1);";
    $sql[] = "SET @fuel_type_id := (SELECT id FROM ag_fuel_type WHERE fuel_type_slug = " . sql_string(sanitize_slug($fuelName ?: 'unknown')) . " LIMIT 1);";
    $sql[] = "SET @exterior_color_id := (SELECT id FROM ag_color WHERE color_scope = 'exterior' AND color_code = " . sql_string(sanitize_slug((string) ($raw['exterior_colour_name'] ?? 'unknown'))) . " LIMIT 1);";
    $sql[] = "SET @interior_color_id := (SELECT id FROM ag_color WHERE color_scope = 'interior' AND color_code = " . sql_string(sanitize_slug((string) ($raw['interior_colour_name'] ?? 'unknown'))) . " LIMIT 1);";

    $sql[] = upsert_sql('ag_vehicle_master', array(
        'uuid' => uuid_from_text('vehicle-' . $vin),
        'canonical_identity_hash' => $canonicalHash,
        'duplicate_group_hash' => $duplicateGroupHash,
        'identity_state' => $vin !== '' ? 'verified' : 'provisional',
        'lifecycle_state' => 'active',
        'manufacturer_id' => '@manufacturer_id',
        'model_id' => '@model_id',
        'generation_id' => null,
        'series_id' => null,
        'platform_id' => null,
        'trim_id' => '@trim_id',
        'vehicle_class_id' => '@vehicle_class_id',
        'vehicle_condition_id' => '@vehicle_condition_id',
        'listing_status_id' => '@listing_status_id',
        'body_style_id' => '@body_style_id',
        'engine_type_id' => '@engine_type_id',
        'transmission_type_id' => '@transmission_type_id',
        'drive_type_id' => '@drive_type_id',
        'fuel_type_id' => '@fuel_type_id',
        'market_id' => '@market_id',
        'currency_id' => '@currency_id',
        'manufacturing_plant_id' => null,
        'country_id' => '@country_id',
        'province_id' => '@province_id',
        'city_id' => '@city_id',
        'import_country_id' => '@country_id',
        'exterior_color_id' => '@exterior_color_id',
        'interior_color_id' => '@interior_color_id',
        'vin' => $vin,
        'vin_normalized' => $vin,
        'vin_wmi' => $vin !== '' ? substr($vin, 0, 3) : null,
        'vin_vds' => $vin !== '' ? substr($vin, 3, 6) : null,
        'vin_vis' => $vin !== '' ? substr($vin, -8) : null,
        'vin_checksum_valid' => $vin !== '' ? 1 : 0,
        'model_year' => $year,
        'production_year_start' => $year,
        'production_year_end' => $year,
        'global_model_code' => $globalModelCode,
        'regional_model_code' => $regionalModelCode,
        'body_code' => $bodyCode,
        'internal_manufacturer_code' => $manCode !== '' ? $manCode : null,
        'metadata' => array(
            'source' => 'toyota',
            'make_model_trim' => $makeModelTrim,
            'body_type_name' => $bodyStyle,
            'fuel_type_name' => $fuelName,
            'transmission_name' => $transmissionName,
        ),
        'future_reserved' => array(),
        'created_by' => null,
        'updated_by' => null,
        'change_reason' => 'vehicle_import',
        'last_synced_at' => $dateModified,
        'last_provider_update' => $dateModified,
        'data_quality_score' => 100,
        'confidence_score' => 100,
        'source_system' => 'toyota',
        'is_active' => 1,
        'is_deleted' => 0,
        'is_verified' => $vin !== '' ? 1 : 0,
        'version' => 1,
        'created_at' => $dateCreated ?? $generatedAt,
        'updated_at' => $dateModified,
        'deleted_at' => null,
    ), array('identity_state', 'lifecycle_state', 'manufacturer_id', 'model_id', 'generation_id', 'series_id', 'platform_id', 'trim_id', 'vehicle_class_id', 'vehicle_condition_id', 'listing_status_id', 'body_style_id', 'engine_type_id', 'transmission_type_id', 'drive_type_id', 'fuel_type_id', 'market_id', 'currency_id', 'manufacturing_plant_id', 'country_id', 'province_id', 'city_id', 'import_country_id', 'exterior_color_id', 'interior_color_id', 'vin_normalized', 'vin_wmi', 'vin_vds', 'vin_vis', 'vin_checksum_valid', 'model_year', 'production_year_start', 'production_year_end', 'global_model_code', 'regional_model_code', 'body_code', 'internal_manufacturer_code', 'metadata', 'future_reserved', 'created_by', 'updated_by', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'updated_at'), 'id', 'vehicle_id');

    $sql[] = upsert_sql('ag_vehicle_map', array(
        'uuid' => uuid_from_text('vehicle-map-' . $vin),
        'vehicle_id' => '@vehicle_id',
        'dealer_id' => '@dealer_id',
        'provider_id' => '@provider_id',
        'source_system' => 'toyota',
        'source_entity_type' => 'vehicle',
        'external_id' => trim((string) ($raw['item_key'] ?? $vin)),
        'external_sub_id' => $stockNumber !== '' ? $stockNumber : null,
        'external_vin' => $vin !== '' ? $vin : null,
        'external_url' => null,
        'external_reference_type' => 'vehicle',
        'provider_record_hash' => $sourceRecordHash,
        'payload_hash' => $sourceRecordHash,
        'duplicate_group_hash' => $duplicateGroupHash,
        'sync_status' => 'active',
        'mapping_status' => 'active',
        'source_rank' => 1,
        'source_priority' => 10,
        'is_primary' => 1,
        'is_active' => 1,
        'is_deleted' => 0,
        'is_verified' => 1,
        'version' => 1,
        'provider_metadata' => array('provider' => 'toyota'),
        'metadata' => array('title' => $title),
        'future_reserved' => array(),
        'created_by' => null,
        'updated_by' => null,
        'change_reason' => 'vehicle_mapping',
        'last_synced_at' => $dateModified,
        'last_provider_update' => $dateModified,
        'data_quality_score' => 100,
        'confidence_score' => 100,
        'first_seen_at' => $dateCreated ?? $generatedAt,
        'last_seen_at' => $dateModified,
        'created_at' => $dateCreated ?? $generatedAt,
        'updated_at' => $dateModified,
        'deleted_at' => null,
    ), array('vehicle_id', 'dealer_id', 'provider_id', 'source_system', 'source_entity_type', 'external_sub_id', 'external_vin', 'external_url', 'external_reference_type', 'provider_record_hash', 'payload_hash', 'duplicate_group_hash', 'sync_status', 'mapping_status', 'source_rank', 'source_priority', 'is_primary', 'is_active', 'is_deleted', 'is_verified', 'version', 'provider_metadata', 'metadata', 'future_reserved', 'created_by', 'updated_by', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'first_seen_at', 'last_seen_at', 'updated_at'), 'id', 'vehicle_map_id');

    $isFeatured = !empty($raw['is_featured']) ? 1 : 0;
    $isCertified = !empty($raw['is_certified']) ? 1 : 0;
    $isDemo = !empty($raw['is_demo_unit']) ? 1 : 0;
    $stockType = strtolower((string) ($raw['stock_type'] ?? 'used'));
    $availability = strtolower((string) ($raw['stock_status_name'] ?? 'in stock'));
    $vinStatus = $vin !== '' ? 'verified' : 'missing';

    $sql[] = upsert_sql('ag_inventory', array(
        'uuid' => uuid_from_text('inventory-' . $vin),
        'vehicle_id' => '@vehicle_id',
        'dealer_id' => '@dealer_id',
        'vehicle_map_id' => '@vehicle_map_id',
        'dealer_map_id' => '@dealer_map_id',
        'provider_id' => '@provider_id',
        'stock_number' => $stockNumber !== '' ? $stockNumber : null,
        'condition_id' => '@vehicle_condition_id',
        'availability_status' => $availability ?: 'available',
        'days_on_lot' => $daysInStock !== null ? (int) $daysInStock : null,
        'arrival_date' => null,
        'vin_status' => $vinStatus,
        'listing_status_id' => '@listing_status_id',
        'lot_status' => $availability ?: 'available',
        'odometer_km' => $mileage !== null ? (float) $mileage : null,
        'is_featured' => $isFeatured,
        'is_certified_pre_owned' => $isCertified,
        'source_record_hash' => $sourceRecordHash,
        'effective_from' => $dateCreated ?? $generatedAt,
        'effective_to' => null,
        'is_current' => 1,
        'metadata' => array('source' => 'toyota', 'stock_type' => $stockType, 'is_demo' => $isDemo, 'in_transit' => !empty($raw['in_transit'])),
        'future_reserved' => array(),
        'created_by' => null,
        'updated_by' => null,
        'change_reason' => 'inventory_sync',
        'last_synced_at' => $dateModified,
        'last_provider_update' => $dateModified,
        'data_quality_score' => 100,
        'confidence_score' => 100,
        'source_system' => 'toyota',
        'is_active' => 1,
        'is_deleted' => 0,
        'is_verified' => 1,
        'version' => 1,
        'vehicle_context_id' => '@vehicle_id',
        'dealer_context_id' => '@dealer_id',
        'created_at' => $dateCreated ?? $generatedAt,
        'updated_at' => $dateModified,
        'deleted_at' => null,
    ), array('vehicle_id', 'dealer_id', 'vehicle_map_id', 'dealer_map_id', 'provider_id', 'stock_number', 'condition_id', 'availability_status', 'days_on_lot', 'arrival_date', 'vin_status', 'listing_status_id', 'lot_status', 'odometer_km', 'is_featured', 'is_certified_pre_owned', 'source_record_hash', 'effective_from', 'effective_to', 'is_current', 'metadata', 'future_reserved', 'created_by', 'updated_by', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'vehicle_context_id', 'dealer_context_id', 'updated_at'), 'id', 'inventory_id');

    $sql[] = upsert_sql('ag_vehicle_price', array(
        'uuid' => uuid_from_text('price-' . $vin),
        'vehicle_id' => '@vehicle_id',
        'dealer_id' => '@dealer_id',
        'vehicle_map_id' => '@vehicle_map_id',
        'dealer_map_id' => '@dealer_map_id',
        'provider_id' => '@provider_id',
        'market_id' => '@market_id',
        'province_id' => '@province_id',
        'currency_id' => '@currency_id',
        'price_type' => 'current',
        'current_price' => $currentPrice !== null ? (float) $currentPrice : null,
        'msrp' => $msrp !== null ? (float) $msrp : null,
        'sale_price' => $salePrice !== null ? (float) $salePrice : null,
        'market_price' => $raw['cbb_selected_price'] ?? null,
        'dealer_price' => $raw['regular_price'] ?? null,
        'effective_date' => mysql_date($raw['date_created'] ?? null),
        'expiry_date' => null,
        'price_status' => 'active',
        'source_record_hash' => $sourceRecordHash,
        'price_hash' => hash('sha256', json_encode(array($currentPrice, $msrp, $salePrice, $raw['cbb_selected_price'] ?? null, $raw['regular_price'] ?? null), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
        'is_current' => 1,
        'metadata' => array('source' => 'toyota'),
        'future_reserved' => array(),
        'created_by' => null,
        'updated_by' => null,
        'change_reason' => 'price_sync',
        'last_synced_at' => $dateModified,
        'last_provider_update' => $dateModified,
        'data_quality_score' => 100,
        'confidence_score' => 100,
        'source_system' => 'toyota',
        'is_active' => 1,
        'is_deleted' => 0,
        'is_verified' => 1,
        'version' => 1,
        'vehicle_context_id' => '@vehicle_id',
        'dealer_context_id' => '@dealer_id',
        'created_at' => $dateCreated ?? $generatedAt,
        'updated_at' => $dateModified,
        'deleted_at' => null,
    ), array('vehicle_id', 'dealer_id', 'vehicle_map_id', 'dealer_map_id', 'provider_id', 'market_id', 'province_id', 'currency_id', 'price_type', 'current_price', 'msrp', 'sale_price', 'market_price', 'dealer_price', 'effective_date', 'expiry_date', 'price_status', 'source_record_hash', 'price_hash', 'is_current', 'metadata', 'future_reserved', 'created_by', 'updated_by', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'vehicle_context_id', 'dealer_context_id', 'updated_at'), 'id', 'price_id');

    $sql[] = "SET @price_id := (SELECT id FROM ag_vehicle_price WHERE vehicle_context_id = @vehicle_id AND dealer_context_id = @dealer_id AND provider_id = @provider_id AND market_id = @market_id AND province_id = @province_id AND currency_id = @currency_id AND price_type = 'current' AND source_system = 'toyota' AND is_current = 1 AND is_active = 1 AND is_deleted = 0 LIMIT 1);";
    $sql[] = 'INSERT INTO `ag_vehicle_price_history` (`uuid`, `price_id`, `vehicle_id`, `dealer_id`, `provider_id`, `old_price`, `new_price`, `old_currency_id`, `new_currency_id`, `change_reason`, `price_status`, `effective_date`, `source_record_hash`, `metadata`, `future_reserved`, `created_by`, `updated_by`, `last_synced_at`, `last_provider_update`, `data_quality_score`, `confidence_score`, `source_system`, `is_active`, `is_deleted`, `is_verified`, `version`, `created_at`, `updated_at`) SELECT ' .
        implode(', ', array(
            sql_string(uuid_from_text('price-history-' . $vin)),
            '@price_id',
            '@vehicle_id',
            '@dealer_id',
            '@provider_id',
            'NULL',
            sql_string($currentPrice !== null ? (float) $currentPrice : null),
            'NULL',
            '@currency_id',
            sql_string('price_sync'),
            sql_string('active'),
            sql_string(mysql_date($raw['date_created'] ?? null)),
            sql_string($sourceRecordHash),
            json_sql(array('price' => $raw)),
            json_sql(array()),
            'NULL',
            'NULL',
            sql_string($generatedAt),
            sql_string($generatedAt),
            '100',
            '100',
            sql_string('toyota'),
            '1',
            '0',
            '1',
            '1',
            sql_string($generatedAt),
            sql_string($generatedAt),
        )) . ' FROM DUAL WHERE @price_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM ag_vehicle_price_history WHERE price_id = @price_id AND source_record_hash = ' . sql_string($sourceRecordHash) . ');';

    $sql[] = upsert_sql('ag_vehicle_location', array(
        'uuid' => uuid_from_text('location-' . $vin),
        'vehicle_id' => '@vehicle_id',
        'dealer_id' => '@dealer_id',
        'vehicle_map_id' => '@vehicle_map_id',
        'dealer_map_id' => '@dealer_map_id',
        'provider_id' => '@provider_id',
        'market_id' => '@market_id',
        'country_id' => '@country_id',
        'province_id' => '@province_id',
        'city_id' => '@city_id',
        'postal_code' => $postal !== '' ? $postal : null,
        'latitude' => isset($raw['_geoloc']['lat']) ? (float) $raw['_geoloc']['lat'] : null,
        'longitude' => isset($raw['_geoloc']['lng']) ? (float) $raw['_geoloc']['lng'] : null,
        'geohash' => null,
        'location_type' => 'dealer',
        'source_record_hash' => $sourceRecordHash,
        'effective_from' => $dateCreated ?? $generatedAt,
        'effective_to' => null,
        'is_current' => 1,
        'metadata' => array('source' => 'toyota'),
        'future_reserved' => array(),
        'created_by' => null,
        'updated_by' => null,
        'change_reason' => 'location_sync',
        'last_synced_at' => $dateModified,
        'last_provider_update' => $dateModified,
        'data_quality_score' => 100,
        'confidence_score' => 100,
        'source_system' => 'toyota',
        'is_active' => 1,
        'is_deleted' => 0,
        'is_verified' => 1,
        'version' => 1,
        'vehicle_context_id' => '@vehicle_id',
        'dealer_context_id' => '@dealer_id',
        'created_at' => $dateCreated ?? $generatedAt,
        'updated_at' => $dateModified,
        'deleted_at' => null,
    ), array('vehicle_id', 'dealer_id', 'vehicle_map_id', 'dealer_map_id', 'provider_id', 'market_id', 'country_id', 'province_id', 'city_id', 'postal_code', 'latitude', 'longitude', 'geohash', 'location_type', 'source_record_hash', 'effective_from', 'effective_to', 'is_current', 'metadata', 'future_reserved', 'created_by', 'updated_by', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'vehicle_context_id', 'dealer_context_id', 'updated_at'), 'id', 'location_id');

    $metadata = array(
        'source' => 'toyota',
        'raw_record' => $raw,
        'dealer' => $dealer,
        'unmapped_fields' => array_diff_key($raw, array_flip(array(
            'sort_price', 'interior_colour_value', 'stock_status_name', 'make_model_trim', 'lowest_monthly_lease_payment', 'fuel_economy_highway', 'body_type', 'make_name', 'photo_count', 'fuel_economy_city', 'lowest_monthly_finance_payment', 'dealer_country', 'style_id', 'fuel_type_name', 'is_featured', 'dealer_province_short', 'engine_cylinders', 'interior_colour_option_code', 'carfax_report_url', 'odometer', 'dealer_name', 'exterior_colour_value', 'stock_number', 'dealer_city_name', 'is_decoded', 'cbb_selected_price', 'lowest_monthly_finance_payment_term', 'dealer_province_name', 'drive_type_desc', 'year', 'exterior_colour_name', 'exterior_colour_rgb_value', 'stripe_price_id', 'date_created', 'exterior_colour_option_code', 'lowest_biweekly_finance_payment_rate', 'is_certified', 'lowest_biweekly_finance_payment', 'carfax_badging_url', 'doors_name', 'is_on_special', 'body_type_category', 'engine_litres', 'lowest_finance_apr_term', 'vin', 'option_codes', 'trim_variation', 'photo_service_ids', 'lowest_biweekly_finance_payment_term', 'is_demo_unit', 'groups', 'list_price', 'lowest_monthly_lease_payment_rate', 'default_down_payment', 'date_inservice', 'item_key', 'engine_config_name', 'published_notes', 'cbb_clean_price', 'lowest_lease_apr', 'days_in_stock', 'in_transit', 'passengers', 'lowest_monthly_lease_payment_term', 'exterior_search_colour', 'lowest_biweekly_lease_payment_rate', 'is_just_arrived', 'towing_capacity', 'transmission_desc', 'packages', 'lowest_biweekly_lease_payment', 'special_price', 'lowest_biweekly_lease_payment_term', 'description', 'dealer_phone', 'msrp', 'video_url', 'body_type_name', 'interior_colour_name', 'detailed_pricing', 'stock_type', 'regular_price', 'dealer_postal_code', 'decode_version', 'equipment', 'drive_type_name', 'website_overlays', 'engine_compressor_name', 'vehicle_type', 'transmission_type', 'cbb_rough_price', 'special_price_expires', 'model_name', 'is_loaner', 'manufacturer_code', 'dealer_region', 'dealer_address', 'trim', '_geoloc', 'cbb_extra_clean_price', 'date_modified', 'fuel_type_category', 'transmission_name', 'interior_search_colour', 'published_trim', 'cbb_average_price', 'lowest_monthly_finance_payment_rate', 'lowest_lease_apr_term', 'dealer_id', 'craft_site_ids', 'lowest_finance_apr', '_highlightResult'
        ))),
        'photo_service_ids' => $raw['photo_service_ids'] ?? array(),
        'groups' => $raw['groups'] ?? array(),
        'packages' => $raw['packages'] ?? array(),
        'option_codes' => $raw['option_codes'] ?? array(),
        'website_overlays' => $raw['website_overlays'] ?? array(),
        'craft_site_ids' => $raw['craft_site_ids'] ?? array(),
        'published_notes' => $raw['published_notes'] ?? null,
        'detailed_pricing' => $raw['detailed_pricing'] ?? null,
        'carfax_report_url' => $raw['carfax_report_url'] ?? null,
        'carfax_badging_url' => $raw['carfax_badging_url'] ?? null,
        'video_url' => $raw['video_url'] ?? null,
        '_highlightResult' => $raw['_highlightResult'] ?? null,
    );

    $sql[] = upsert_sql('ag_vehicle_metadata', array(
        'uuid' => uuid_from_text('metadata-' . $vin),
        'vehicle_id' => '@vehicle_id',
        'dealer_id' => '@dealer_id',
        'vehicle_map_id' => '@vehicle_map_id',
        'dealer_map_id' => '@dealer_map_id',
        'provider_id' => '@provider_id',
        'metadata_namespace' => 'toyota_inventory',
        'metadata_schema_version' => '1.0',
        'metadata' => $metadata,
        'metadata_hash' => hash('sha256', json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
        'source_record_hash' => $sourceRecordHash,
        'effective_from' => $dateCreated ?? $generatedAt,
        'effective_to' => null,
        'is_current' => 1,
        'future_reserved' => array(),
        'created_by' => null,
        'updated_by' => null,
        'change_reason' => 'metadata_sync',
        'last_synced_at' => $dateModified,
        'last_provider_update' => $dateModified,
        'data_quality_score' => 100,
        'confidence_score' => 100,
        'source_system' => 'toyota',
        'is_active' => 1,
        'is_deleted' => 0,
        'is_verified' => 1,
        'version' => 1,
        'vehicle_context_id' => '@vehicle_id',
        'dealer_context_id' => '@dealer_id',
        'created_at' => $dateCreated ?? $generatedAt,
        'updated_at' => $dateModified,
        'deleted_at' => null,
    ), array('dealer_id', 'vehicle_map_id', 'dealer_map_id', 'provider_id', 'metadata_namespace', 'metadata_schema_version', 'metadata', 'metadata_hash', 'source_record_hash', 'effective_from', 'effective_to', 'is_current', 'future_reserved', 'created_by', 'updated_by', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'vehicle_context_id', 'dealer_context_id', 'updated_at'), 'id', 'metadata_id');

    $aliasName = trim($title);
    $sql[] = upsert_sql('ag_vehicle_alias', array(
        'uuid' => uuid_from_text('alias-' . $vin),
        'vehicle_id' => '@vehicle_id',
        'dealer_id' => '@dealer_id',
        'vehicle_map_id' => '@vehicle_map_id',
        'dealer_map_id' => '@dealer_map_id',
        'provider_id' => '@provider_id',
        'alias_type' => 'marketing',
        'alias_name' => $aliasName,
        'alias_name_normalized' => sanitize_slug($aliasName),
        'locale_code' => 'en-CA',
        'market_id' => '@market_id',
        'source_record_hash' => $sourceRecordHash,
        'effective_from' => $dateCreated ?? $generatedAt,
        'effective_to' => null,
        'is_current' => 1,
        'is_primary' => 1,
        'metadata' => array('source' => 'toyota'),
        'future_reserved' => array(),
        'created_by' => null,
        'updated_by' => null,
        'change_reason' => 'alias_sync',
        'last_synced_at' => $dateModified,
        'last_provider_update' => $dateModified,
        'data_quality_score' => 100,
        'confidence_score' => 100,
        'source_system' => 'toyota',
        'is_active' => 1,
        'is_deleted' => 0,
        'is_verified' => 1,
        'version' => 1,
        'vehicle_context_id' => '@vehicle_id',
        'dealer_context_id' => '@dealer_id',
        'created_at' => $dateCreated ?? $generatedAt,
        'updated_at' => $dateModified,
        'deleted_at' => null,
    ), array('dealer_id', 'vehicle_map_id', 'dealer_map_id', 'provider_id', 'alias_type', 'alias_name', 'alias_name_normalized', 'locale_code', 'market_id', 'source_record_hash', 'effective_from', 'effective_to', 'is_current', 'is_primary', 'metadata', 'future_reserved', 'created_by', 'updated_by', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'vehicle_context_id', 'dealer_context_id', 'updated_at'), 'id', 'alias_id');

    $sql[] = upsert_sql('ag_vehicle_specification', array(
        'uuid' => uuid_from_text('spec-' . $vin),
        'vehicle_id' => '@vehicle_id',
        'dealer_id' => '@dealer_id',
        'vehicle_map_id' => '@vehicle_map_id',
        'dealer_map_id' => '@dealer_map_id',
        'provider_id' => '@provider_id',
        'generation_id' => null,
        'platform_id' => null,
        'series_id' => null,
        'manufacturing_plant_id' => null,
        'production_country_id' => '@country_id',
        'market_id' => '@market_id',
        'vehicle_class_id' => '@vehicle_class_id',
        'wheelbase_mm' => null,
        'length_mm' => null,
        'width_mm' => null,
        'height_mm' => null,
        'ground_clearance_mm' => null,
        'turning_radius_m' => null,
        'cargo_capacity_l' => null,
        'passenger_volume_l' => null,
        'curb_weight_kg' => null,
        'gvwr_kg' => null,
        'payload_kg' => null,
        'towing_capacity_kg' => isset($raw['towing_capacity']) && is_numeric($raw['towing_capacity']) ? (float) $raw['towing_capacity'] : null,
        'safety_rating_overall' => null,
        'safety_rating_source' => null,
        'epa_rating_overall' => null,
        'epa_city_mpg' => null,
        'epa_highway_mpg' => null,
        'epa_combined_mpg' => null,
        'warranty_basic_months' => null,
        'warranty_basic_km' => null,
        'warranty_powertrain_months' => null,
        'warranty_powertrain_km' => null,
        'warranty_corrosion_months' => null,
        'warranty_battery_months' => null,
        'warranty_battery_km' => null,
        'source_record_hash' => $sourceRecordHash,
        'effective_from' => $dateCreated ?? $generatedAt,
        'effective_to' => null,
        'is_current' => 1,
        'metadata' => array('source' => 'toyota', 'passengers' => $raw['passengers'] ?? null, 'towing_capacity' => $raw['towing_capacity'] ?? null),
        'future_reserved' => array(),
        'created_by' => null,
        'updated_by' => null,
        'change_reason' => 'specification_sync',
        'last_synced_at' => $dateModified,
        'last_provider_update' => $dateModified,
        'data_quality_score' => 100,
        'confidence_score' => 100,
        'source_system' => 'toyota',
        'is_active' => 1,
        'is_deleted' => 0,
        'is_verified' => 1,
        'version' => 1,
        'vehicle_context_id' => '@vehicle_id',
        'created_at' => $dateCreated ?? $generatedAt,
        'updated_at' => $dateModified,
        'deleted_at' => null,
    ), array('vehicle_id', 'dealer_id', 'vehicle_map_id', 'dealer_map_id', 'provider_id', 'generation_id', 'platform_id', 'series_id', 'manufacturing_plant_id', 'production_country_id', 'market_id', 'vehicle_class_id', 'wheelbase_mm', 'length_mm', 'width_mm', 'height_mm', 'ground_clearance_mm', 'turning_radius_m', 'cargo_capacity_l', 'passenger_volume_l', 'curb_weight_kg', 'gvwr_kg', 'payload_kg', 'towing_capacity_kg', 'safety_rating_overall', 'safety_rating_source', 'epa_rating_overall', 'epa_city_mpg', 'epa_highway_mpg', 'epa_combined_mpg', 'warranty_basic_months', 'warranty_basic_km', 'warranty_powertrain_months', 'warranty_powertrain_km', 'warranty_corrosion_months', 'warranty_battery_months', 'warranty_battery_km', 'source_record_hash', 'effective_from', 'effective_to', 'is_current', 'metadata', 'future_reserved', 'created_by', 'updated_by', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'vehicle_context_id', 'updated_at'), 'id', 'specification_id');

    $engineName = first_non_empty($raw['engine_config_name'] ?? null, $fuelName, $fuelCategory, 'Engine');
    $engineCode = sanitize_slug((string) $engineName);
    $sql[] = upsert_sql('ag_vehicle_engine', array(
        'uuid' => uuid_from_text('engine-' . $vin),
        'vehicle_id' => '@vehicle_id',
        'dealer_id' => '@dealer_id',
        'vehicle_map_id' => '@vehicle_map_id',
        'dealer_map_id' => '@dealer_map_id',
        'provider_id' => '@provider_id',
        'engine_code' => $engineCode,
        'engine_name' => (string) $engineName,
        'fuel_type_id' => '@fuel_type_id',
        'displacement_cc' => null,
        'horsepower_hp' => null,
        'torque_nm' => null,
        'compression_ratio' => null,
        'is_turbocharged' => 0,
        'is_supercharged' => 0,
        'fuel_system' => $fuelName,
        'cylinder_count' => isset($raw['engine_cylinders']) && is_numeric($raw['engine_cylinders']) ? (int) $raw['engine_cylinders'] : null,
        'cylinder_layout' => null,
        'emission_standard' => null,
        'has_start_stop' => 0,
        'has_hybrid_assist' => 0,
        'battery_capacity_kwh' => null,
        'charging_type' => null,
        'motor_power_kw' => null,
        'motor_torque_nm' => null,
        'cooling_type' => null,
        'source_record_hash' => $sourceRecordHash,
        'effective_from' => $dateCreated ?? $generatedAt,
        'effective_to' => null,
        'is_current' => 1,
        'metadata' => array('source' => 'toyota'),
        'future_reserved' => array(),
        'created_by' => null,
        'updated_by' => null,
        'change_reason' => 'engine_sync',
        'last_synced_at' => $dateModified,
        'last_provider_update' => $dateModified,
        'data_quality_score' => 100,
        'confidence_score' => 100,
        'source_system' => 'toyota',
        'is_active' => 1,
        'is_deleted' => 0,
        'is_verified' => 1,
        'version' => 1,
        'vehicle_context_id' => '@vehicle_id',
        'created_at' => $dateCreated ?? $generatedAt,
        'updated_at' => $dateModified,
        'deleted_at' => null,
    ), array('vehicle_id', 'dealer_id', 'vehicle_map_id', 'dealer_map_id', 'provider_id', 'engine_code', 'engine_name', 'fuel_type_id', 'displacement_cc', 'horsepower_hp', 'torque_nm', 'compression_ratio', 'is_turbocharged', 'is_supercharged', 'fuel_system', 'cylinder_count', 'cylinder_layout', 'emission_standard', 'has_start_stop', 'has_hybrid_assist', 'battery_capacity_kwh', 'charging_type', 'motor_power_kw', 'motor_torque_nm', 'cooling_type', 'source_record_hash', 'effective_from', 'effective_to', 'is_current', 'metadata', 'future_reserved', 'created_by', 'updated_by', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'vehicle_context_id', 'updated_at'), 'id', 'engine_id');

    $sql[] = upsert_sql('ag_vehicle_transmission', array(
        'uuid' => uuid_from_text('transmission-' . $vin),
        'vehicle_id' => '@vehicle_id',
        'dealer_id' => '@dealer_id',
        'vehicle_map_id' => '@vehicle_map_id',
        'dealer_map_id' => '@dealer_map_id',
        'provider_id' => '@provider_id',
        'transmission_code' => sanitize_slug($transmissionName),
        'transmission_name' => $transmissionName,
        'gear_count' => null,
        'transmission_family' => $transmissionName,
        'is_manual' => stripos($transmissionName, 'manual') !== false ? 1 : 0,
        'is_automatic' => stripos($transmissionName, 'automatic') !== false || stripos($transmissionName, 'a/t') !== false ? 1 : 0,
        'is_cvt' => stripos($transmissionName, 'cvt') !== false ? 1 : 0,
        'is_dct' => stripos($transmissionName, 'dct') !== false ? 1 : 0,
        'has_transfer_case' => stripos($driveType, '4wd') !== false ? 1 : 0,
        'axle_ratio' => null,
        'source_record_hash' => $sourceRecordHash,
        'effective_from' => $dateCreated ?? $generatedAt,
        'effective_to' => null,
        'is_current' => 1,
        'metadata' => array('source' => 'toyota'),
        'future_reserved' => array(),
        'created_by' => null,
        'updated_by' => null,
        'change_reason' => 'transmission_sync',
        'last_synced_at' => $dateModified,
        'last_provider_update' => $dateModified,
        'data_quality_score' => 100,
        'confidence_score' => 100,
        'source_system' => 'toyota',
        'is_active' => 1,
        'is_deleted' => 0,
        'is_verified' => 1,
        'version' => 1,
        'vehicle_context_id' => '@vehicle_id',
        'created_at' => $dateCreated ?? $generatedAt,
        'updated_at' => $dateModified,
        'deleted_at' => null,
    ), array('vehicle_id', 'dealer_id', 'vehicle_map_id', 'dealer_map_id', 'provider_id', 'transmission_code', 'transmission_name', 'gear_count', 'transmission_family', 'is_manual', 'is_automatic', 'is_cvt', 'is_dct', 'has_transfer_case', 'axle_ratio', 'source_record_hash', 'effective_from', 'effective_to', 'is_current', 'metadata', 'future_reserved', 'created_by', 'updated_by', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'vehicle_context_id', 'updated_at'), 'id', 'transmission_id');

    $sql[] = upsert_sql('ag_vehicle_fuel_economy', array(
        'uuid' => uuid_from_text('fuel-economy-' . $vin),
        'vehicle_id' => '@vehicle_id',
        'dealer_id' => '@dealer_id',
        'vehicle_map_id' => '@vehicle_map_id',
        'dealer_map_id' => '@dealer_map_id',
        'provider_id' => '@provider_id',
        'city_l_per_100km' => isset($raw['fuel_economy_city']) && is_numeric($raw['fuel_economy_city']) ? (float) $raw['fuel_economy_city'] : null,
        'highway_l_per_100km' => isset($raw['fuel_economy_highway']) && is_numeric($raw['fuel_economy_highway']) ? (float) $raw['fuel_economy_highway'] : null,
        'combined_l_per_100km' => isset($raw['fuel_economy_combined']) && is_numeric($raw['fuel_economy_combined']) ? (float) $raw['fuel_economy_combined'] : null,
        'city_mpg' => null,
        'highway_mpg' => null,
        'combined_mpg' => null,
        'fuel_tank_l' => isset($raw['fuel_economy_fuel_tank']) && is_numeric($raw['fuel_economy_fuel_tank']) ? (float) $raw['fuel_economy_fuel_tank'] : null,
        'electric_range_km' => null,
        'hybrid_range_km' => null,
        'battery_range_km' => null,
        'mpge' => null,
        'consumption_l_per_100km' => null,
        'energy_consumption_kwh_per_100km' => null,
        'source_record_hash' => $sourceRecordHash,
        'effective_from' => $dateCreated ?? $generatedAt,
        'effective_to' => null,
        'is_current' => 1,
        'metadata' => array('source' => 'toyota'),
        'future_reserved' => array(),
        'created_by' => null,
        'updated_by' => null,
        'change_reason' => 'fuel_economy_sync',
        'last_synced_at' => $dateModified,
        'last_provider_update' => $dateModified,
        'data_quality_score' => 100,
        'confidence_score' => 100,
        'source_system' => 'toyota',
        'is_active' => 1,
        'is_deleted' => 0,
        'is_verified' => 1,
        'version' => 1,
        'vehicle_context_id' => '@vehicle_id',
        'created_at' => $dateCreated ?? $generatedAt,
        'updated_at' => $dateModified,
        'deleted_at' => null,
    ), array('vehicle_id', 'dealer_id', 'vehicle_map_id', 'dealer_map_id', 'provider_id', 'city_l_per_100km', 'highway_l_per_100km', 'combined_l_per_100km', 'city_mpg', 'highway_mpg', 'combined_mpg', 'fuel_tank_l', 'electric_range_km', 'hybrid_range_km', 'battery_range_km', 'mpge', 'consumption_l_per_100km', 'energy_consumption_kwh_per_100km', 'source_record_hash', 'effective_from', 'effective_to', 'is_current', 'metadata', 'future_reserved', 'created_by', 'updated_by', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'vehicle_context_id', 'updated_at'), 'id', 'fuel_economy_id');

    $equipment = $raw['equipment'] ?? array();
    if (is_array($equipment)) {
        $order = 0;
        foreach ($equipment as $item) {
            $name = trim((string) $item);
            if ($name === '') {
                continue;
            }
            $featureCode = sanitize_slug($name, 100);
            $sql[] = upsert_sql('ag_vehicle_feature', array(
                'uuid' => uuid_from_text('feature-' . $featureCode),
                'feature_code' => $featureCode,
                'feature_name' => $name,
                'feature_category' => 'equipment',
                'feature_group' => 'equipment',
                'feature_scope' => 'vehicle',
                'description' => null,
                'is_standard' => 0,
                'is_optional' => 1,
                'source_record_hash' => null,
                'metadata' => array('source' => 'toyota'),
                'future_reserved' => array(),
                'created_by' => null,
                'updated_by' => null,
                'change_reason' => 'feature_sync',
                'last_synced_at' => $generatedAt,
                'last_provider_update' => $generatedAt,
                'data_quality_score' => 100,
                'confidence_score' => 100,
                'source_system' => 'toyota',
                'is_active' => 1,
                'is_deleted' => 0,
                'is_verified' => 1,
                'version' => 1,
                'created_at' => $generatedAt,
                'updated_at' => $generatedAt,
                'deleted_at' => null,
            ), array('feature_name', 'feature_category', 'feature_group', 'feature_scope', 'description', 'is_standard', 'is_optional', 'source_record_hash', 'metadata', 'future_reserved', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'updated_at'), 'id', 'feature_id_' . $featureCode);

            $sql[] = "SET @feature_id := (SELECT id FROM ag_vehicle_feature WHERE feature_code = " . sql_string($featureCode) . " LIMIT 1);";
            $sql[] = upsert_sql('ag_vehicle_feature_value', array(
                'uuid' => uuid_from_text('feature-value-' . $vin . '-' . $featureCode),
                'vehicle_id' => '@vehicle_id',
                'dealer_id' => '@dealer_id',
                'vehicle_map_id' => '@vehicle_map_id',
                'dealer_map_id' => '@dealer_map_id',
                'provider_id' => '@provider_id',
                'feature_id' => '@feature_id',
                'value_type' => 'boolean',
                'value_boolean' => 1,
                'value_number' => null,
                'value_text' => $name,
                'value_json' => null,
                'unit_of_measure' => null,
                'evidence_text' => null,
                'sort_order' => $order,
                'is_primary' => 0,
                'source_record_hash' => $sourceRecordHash,
                'effective_from' => $dateCreated ?? $generatedAt,
                'effective_to' => null,
                'is_current' => 1,
                'metadata' => array('source' => 'toyota'),
                'future_reserved' => array(),
                'created_by' => null,
                'updated_by' => null,
                'change_reason' => 'feature_sync',
                'last_synced_at' => $dateModified,
                'last_provider_update' => $dateModified,
                'data_quality_score' => 100,
                'confidence_score' => 100,
                'source_system' => 'toyota',
                'is_active' => 1,
                'is_deleted' => 0,
                'is_verified' => 1,
                'version' => 1,
                'vehicle_context_id' => '@vehicle_id',
                'created_at' => $dateCreated ?? $generatedAt,
                'updated_at' => $dateModified,
                'deleted_at' => null,
            ), array('vehicle_id', 'dealer_id', 'vehicle_map_id', 'dealer_map_id', 'provider_id', 'feature_id', 'value_type', 'value_boolean', 'value_number', 'value_text', 'value_json', 'unit_of_measure', 'evidence_text', 'sort_order', 'is_primary', 'source_record_hash', 'effective_from', 'effective_to', 'is_current', 'metadata', 'future_reserved', 'created_by', 'updated_by', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'vehicle_context_id', 'updated_at'), 'id', 'feature_value_id_' . $vin . '_' . $featureCode);
            $order++;
        }
    }

    $photoIds = $raw['photo_service_ids'] ?? array();
    if (is_array($photoIds)) {
        foreach ($photoIds as $index => $photoId) {
            $photoId = trim((string) $photoId);
            if ($photoId === '') {
                continue;
            }
            $imageRole = $index === 0 ? 'primary' : 'gallery';
            $sourceUrl = cloudinary_image_url($photoId);
            $imageHash = hash('sha256', $vin . '|' . $photoId);
            $sql[] = upsert_sql('ag_vehicle_image', array(
                'uuid' => uuid_from_text('image-' . $vin . '-' . $photoId),
                'vehicle_id' => '@vehicle_id',
                'dealer_id' => '@dealer_id',
                'vehicle_map_id' => '@vehicle_map_id',
                'dealer_map_id' => '@dealer_map_id',
                'provider_id' => '@provider_id',
                'image_role' => $imageRole,
                'image_kind' => 'original',
                'original_image_url' => $sourceUrl,
                'thumbnail_image_url' => $sourceUrl,
                'gallery_image_url' => $sourceUrl,
                'alt_text' => $title,
                'caption' => null,
                'resolution_width' => null,
                'resolution_height' => null,
                'file_size_bytes' => null,
                'image_hash' => $imageHash,
                'source_media_id' => $photoId,
                'sort_order' => $index,
                'is_primary' => $index === 0 ? 1 : 0,
                'is_provider_image' => 1,
                'is_ai_image' => 0,
                'source_record_hash' => $sourceRecordHash,
                'effective_from' => $dateCreated ?? $generatedAt,
                'effective_to' => null,
                'is_current' => 1,
                'metadata' => array('source' => 'toyota', 'photo_service_id' => $photoId),
                'future_reserved' => array(),
                'created_by' => null,
                'updated_by' => null,
                'change_reason' => 'image_sync',
                'last_synced_at' => $dateModified,
                'last_provider_update' => $dateModified,
                'data_quality_score' => 100,
                'confidence_score' => 100,
                'source_system' => 'toyota',
                'is_active' => 1,
                'is_deleted' => 0,
                'is_verified' => 1,
                'version' => 1,
                'vehicle_context_id' => '@vehicle_id',
                'created_at' => $dateCreated ?? $generatedAt,
                'updated_at' => $dateModified,
                'deleted_at' => null,
            ), array('vehicle_id', 'dealer_id', 'vehicle_map_id', 'dealer_map_id', 'provider_id', 'image_role', 'image_kind', 'original_image_url', 'thumbnail_image_url', 'gallery_image_url', 'alt_text', 'caption', 'resolution_width', 'resolution_height', 'file_size_bytes', 'image_hash', 'source_media_id', 'sort_order', 'is_primary', 'is_provider_image', 'is_ai_image', 'source_record_hash', 'effective_from', 'effective_to', 'is_current', 'metadata', 'future_reserved', 'created_by', 'updated_by', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'vehicle_context_id', 'updated_at'), 'id', 'image_id_' . $vin . '_' . $index);
        }
    }

    $postTitle = $title;
    $postSlug = sanitize_slug($slug . '-' . substr($vin, -6));
    $postDate = $dateCreated ?? $generatedAt;
    $postModified = $dateModified;
    $excerptSource = trim((string) ($raw['published_notes'] ?? $raw['description'] ?? ''));
    $excerpt = substr($excerptSource, 0, 220);
    $description = (string) ($raw['description'] ?? '');
    $vehicleArrayForScores = array(
        'make' => $make,
        'model' => $model,
        'year' => $year,
        'mileage' => $mileage !== null ? (int) $mileage : 0,
        'price' => $currentPrice !== null ? (float) $currentPrice : 0,
        'drivetrain' => $driveType,
        'body_type' => $bodyCategory ?: $bodyStyle,
        'block_heater' => false,
        'remote_start' => false,
        'winter_tires_included' => false,
        'ground_clearance_mm' => (int) ($raw['ground_clearance_mm'] ?? 180),
    );
    $winterScore = 45;
    if (stripos($driveType, 'AWD') !== false) {
        $winterScore += 20;
    }
    if (stripos($driveType, '4WD') !== false) {
        $winterScore += 24;
    }
    if (in_array($make, array('Toyota', 'Subaru', 'Mazda', 'Honda'), true)) {
        $winterScore += 6;
    }
    if (in_array(strtolower($bodyCategory ?: $bodyStyle), array('suv', 'truck'), true)) {
        $winterScore += 4;
    }
    $winterScore = max(0, min(100, $winterScore));
    $reliabilityScore = 76;
    $reliabilityScore += in_array($make, array('Toyota', 'Honda', 'Mazda', 'Subaru'), true) ? array('Toyota' => 12, 'Honda' => 10, 'Mazda' => 8, 'Subaru' => 6)[$make] : 0;
    $reliabilityScore -= (int) floor(((int) $vehicleArrayForScores['mileage']) / 50000) * 3;
    $reliabilityScore = max(52, min(96, $reliabilityScore));
    $rating = 'Average';
    if ($reliabilityScore >= 88) {
        $rating = 'Excellent';
    } elseif ($reliabilityScore >= 78) {
        $rating = 'Good';
    } elseif ($reliabilityScore < 65) {
        $rating = 'Poor';
    }
    $futureRating = $reliabilityScore >= 84 ? 'Strong' : ($reliabilityScore >= 74 ? 'Moderate' : 'Caution');
    $futureSummary = sprintf('%s %s is tracked as %s for future value trends based on market heuristics.', $year, $make, strtolower($futureRating));
    $reliabilitySummary = sprintf('%d %s %s is rated %s for long-term ownership with strong confidence for Canadian daily driving.', $year, $make, $model, strtolower($rating));
    $knownIssues = 'Monitor battery health in extreme cold; inspect suspension bushings during winter tire changeovers.';
    $commonComplaints = 'Infotainment lag reported on some trims; road noise rises as all-season tires wear.';
    $recallHistory = 'Check VIN-specific recall status before delivery.';

    $sql[] = 'SET @object_id := (SELECT ID FROM wp_posts p INNER JOIN wp_postmeta pm ON pm.post_id = p.ID AND pm.meta_key = \'vin\' AND pm.meta_value = ' . sql_string($vin) . ' WHERE p.post_type = \'vehicle\' LIMIT 1);';
    $sql[] = 'UPDATE wp_posts SET post_author = 1, post_date = ' . sql_string($postDate) . ', post_date_gmt = ' . sql_string($postDate) . ', post_content = ' . sql_string($description) . ', post_title = ' . sql_string($postTitle) . ', post_excerpt = ' . sql_string($excerpt) . ', post_status = \'publish\', comment_status = \'closed\', ping_status = \'closed\', post_password = \'\', post_name = ' . sql_string($postSlug) . ', to_ping = \'\', pinged = \'\', post_modified = ' . sql_string($postModified) . ', post_modified_gmt = ' . sql_string($postModified) . ', post_content_filtered = \'\', post_parent = 0, guid = ' . sql_string('https://autogyrus.local/vehicle/' . $postSlug . '/') . ', menu_order = 0, post_type = \'vehicle\', post_mime_type = \'\', comment_count = 0 WHERE ID = @object_id;';
    $sql[] = 'INSERT INTO wp_posts (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count) SELECT 1, ' . sql_string($postDate) . ', ' . sql_string($postDate) . ', ' . sql_string($description) . ', ' . sql_string($postTitle) . ', ' . sql_string($excerpt) . ', \'publish\', \'closed\', \'closed\', \'\', ' . sql_string($postSlug) . ', \'\', \'\', ' . sql_string($postModified) . ', ' . sql_string($postModified) . ', \'\', 0, ' . sql_string('https://autogyrus.local/vehicle/' . $postSlug . '/') . ', 0, \'vehicle\', \'\', 0 FROM DUAL WHERE @object_id IS NULL;';
    $sql[] = 'SET @object_id := COALESCE(@object_id, LAST_INSERT_ID());';
    $sql[] = 'DELETE FROM wp_postmeta WHERE post_id = @object_id AND meta_key IN (\'vin\', \'make\', \'model\', \'year\', \'trim\', \'mileage\', \'price\', \'transmission\', \'fuel_type\', \'engine\', \'drivetrain\', \'body_type\', \'exterior_color\', \'interior_color\', \'city\', \'postal_code\', \'province\', \'ground_clearance_mm\', \'autogyrus_vehicle_id\', \'autogyrus_vehicle_map_id\', \'dealer_user_id\', \'winter_score\', \'reliability_score\', \'future_value_rating\', \'future_value_summary\', \'reliability_summary\', \'known_issues\', \'common_complaints\', \'recall_history\', \'block_heater\', \'remote_start\', \'winter_tires_included\', \'photo_service_ids\', \'source_system\');';

    $postMeta = array(
        'vin' => $vin,
        'make' => $make,
        'model' => $model,
        'year' => $year,
        'trim' => $trim,
        'mileage' => $mileage !== null ? (int) $mileage : '',
        'price' => $currentPrice !== null ? (float) $currentPrice : '',
        'transmission' => $transmissionName,
        'fuel_type' => $fuelName,
        'engine' => $engineName,
        'drivetrain' => $driveType,
        'body_type' => $bodyStyle ?: $bodyCategory,
        'exterior_color' => trim((string) ($raw['exterior_colour_name'] ?? '')),
        'interior_color' => trim((string) ($raw['interior_colour_name'] ?? '')),
        'city' => $city,
        'postal_code' => $postal,
        'province' => $province,
        'ground_clearance_mm' => '',
        'autogyrus_vehicle_id' => '@vehicle_id',
        'autogyrus_vehicle_map_id' => '@vehicle_map_id',
        'dealer_user_id' => '',
        'winter_score' => $winterScore,
        'reliability_score' => $reliabilityScore,
        'future_value_rating' => $futureRating,
        'future_value_summary' => $futureSummary,
        'reliability_summary' => $reliabilitySummary,
        'known_issues' => $knownIssues,
        'common_complaints' => $commonComplaints,
        'recall_history' => $recallHistory,
        'block_heater' => !empty($raw['equipment']) && is_array($raw['equipment']) && (bool) array_filter($raw['equipment'], static fn($item) => stripos((string) $item, 'block heater') !== false),
        'remote_start' => !empty($raw['equipment']) && is_array($raw['equipment']) && (bool) array_filter($raw['equipment'], static fn($item) => stripos((string) $item, 'remote start') !== false),
        'winter_tires_included' => false,
        'photo_service_ids' => json_encode($raw['photo_service_ids'] ?? array(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'source_system' => 'toyota',
    );
    foreach ($postMeta as $metaKey => $metaValue) {
        if ($metaValue === '' || $metaValue === null) {
            continue;
        }
        $sql[] = 'INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES (@object_id, ' . sql_string($metaKey) . ', ' . sql_string($metaValue) . ');';
    }

    foreach (array(
        'make' => $make,
        'model' => $model,
        'body_type' => $bodyStyle ?: $bodyCategory,
        'fuel_type' => $fuelName,
        'transmission' => $transmissionName,
        'province' => $province,
        'city' => $city,
        'drivetrain' => $driveType,
    ) as $taxonomy => $termName) {
        if ($termName === '') {
            continue;
        }
        $sql[] = upsert_term_sql($termName, sanitize_slug($termName), $taxonomy);
    }

    foreach (array(
        'make' => $make,
        'model' => $model,
        'body_type' => $bodyStyle ?: $bodyCategory,
        'fuel_type' => $fuelName,
        'transmission' => $transmissionName,
        'province' => $province,
        'city' => $city,
        'drivetrain' => $driveType,
    ) as $taxonomy => $termName) {
        if ($termName === '') {
            continue;
        }
        $sql[] = 'SET @object_id := @object_id;';
        $sql[] = 'INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (@object_id, (SELECT term_taxonomy_id FROM wp_term_taxonomy tt INNER JOIN wp_terms t ON t.term_id = tt.term_id WHERE tt.taxonomy = ' . sql_string($taxonomy) . ' AND t.slug = ' . sql_string(sanitize_slug($termName)) . ' LIMIT 1)) ON DUPLICATE KEY UPDATE object_id = VALUES(object_id);';
    }

    $sql[] = upsert_sql('ag_import_log', array(
        'uuid' => uuid_from_text('import-log-' . $vin),
        'batch_id' => '@batch_id',
        'job_id' => '@job_id',
        'vehicle_id' => '@vehicle_id',
        'dealer_id' => '@dealer_id',
        'vehicle_map_id' => '@vehicle_map_id',
        'dealer_map_id' => '@dealer_map_id',
        'provider_id' => '@provider_id',
        'source_row_number' => null,
        'log_level' => 'info',
        'log_code' => 'record_imported',
        'log_message' => 'Vehicle imported successfully.',
        'event_type' => 'record_imported',
        'duplicate_hash' => $duplicateGroupHash,
        'skip_reason' => null,
        'raw_payload' => $raw,
        'normalized_payload' => array(
            'vin' => $vin,
            'title' => $title,
            'year' => $year,
            'make' => $make,
            'model' => $model,
            'trim' => $trim,
        ),
        'log_status' => 'open',
        'event_at' => $dateModified,
        'metadata' => array('source' => 'toyota'),
        'future_reserved' => array(),
        'created_by' => null,
        'updated_by' => null,
        'change_reason' => 'record_imported',
        'last_synced_at' => $dateModified,
        'last_provider_update' => $dateModified,
        'data_quality_score' => 100,
        'confidence_score' => 100,
        'source_system' => 'toyota',
        'is_active' => 1,
        'is_deleted' => 0,
        'is_verified' => 1,
        'version' => 1,
        'created_at' => $generatedAt,
        'updated_at' => $generatedAt,
    ), array('batch_id', 'job_id', 'vehicle_id', 'dealer_id', 'vehicle_map_id', 'dealer_map_id', 'provider_id', 'source_row_number', 'log_level', 'log_code', 'log_message', 'event_type', 'duplicate_hash', 'skip_reason', 'raw_payload', 'normalized_payload', 'log_status', 'event_at', 'metadata', 'future_reserved', 'created_by', 'updated_by', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'updated_at'), 'id', 'log_id');
}

$sql[] = upsert_sql('ag_dealer_master', array(
    'uuid' => uuid_from_text('dealer-' . $dealerHash),
    'dealer_code' => (string) $dealer['dealer_external_id'],
    'dealer_name' => $dealer['dealer_name'],
    'dealer_legal_name' => $dealer['dealer_legal_name'],
    'dealer_slug' => $dealer['dealer_slug'],
    'dealer_group_name' => null,
    'dealer_category' => 'franchise',
    'ownership_type' => 'franchise',
    'network_role' => 'dealer',
    'market_id' => '@market_id',
    'country_id' => '@country_id',
    'province_id' => '@province_id',
    'city_id' => '@city_id',
    'website_url' => null,
    'phone_number' => $dealer['phone_number'],
    'email_address' => null,
    'address_line1' => $dealer['address_line1'],
    'address_line2' => null,
    'postal_code' => $dealer['postal_code'],
    'latitude' => $dealer['latitude'],
    'longitude' => $dealer['longitude'],
    'timezone' => $dealer['timezone'],
    'canonical_dealer_hash' => $dealerHash,
    'metadata' => array('source' => 'toyota'),
    'future_reserved' => array(),
    'created_by' => null,
    'updated_by' => null,
    'change_reason' => 'dealer_resolution',
    'last_synced_at' => $generatedAt,
    'last_provider_update' => $generatedAt,
    'data_quality_score' => 100,
    'confidence_score' => 100,
    'source_system' => 'toyota',
    'is_active' => 1,
    'is_deleted' => 0,
    'is_verified' => 1,
    'version' => 1,
    'created_at' => $generatedAt,
    'updated_at' => $generatedAt,
    'deleted_at' => null,
), array('dealer_name', 'dealer_legal_name', 'dealer_slug', 'dealer_group_name', 'dealer_category', 'ownership_type', 'network_role', 'market_id', 'country_id', 'province_id', 'city_id', 'website_url', 'phone_number', 'email_address', 'address_line1', 'address_line2', 'postal_code', 'latitude', 'longitude', 'timezone', 'canonical_dealer_hash', 'metadata', 'future_reserved', 'created_by', 'updated_by', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'updated_at'), 'id', 'dealer_id');

$sql[] = upsert_sql('ag_dealer_map', array(
    'uuid' => uuid_from_text('dealer-map-' . $dealerHash),
    'dealer_id' => '@dealer_id',
    'provider_id' => '@provider_id',
    'source_system' => 'toyota',
    'external_id' => (string) $dealer['dealer_external_id'],
    'external_url' => null,
    'external_reference_type' => 'dealer',
    'provider_dealer_code' => (string) $dealer['dealer_external_id'],
    'market_id' => '@market_id',
    'source_rank' => 1,
    'source_priority' => 10,
    'is_primary' => 1,
    'sync_status' => 'active',
    'provider_metadata' => array('source' => 'toyota'),
    'metadata' => array('dealer_name' => $dealer['dealer_name']),
    'future_reserved' => array(),
    'created_by' => null,
    'updated_by' => null,
    'change_reason' => 'dealer_resolution',
    'last_synced_at' => $generatedAt,
    'last_provider_update' => $generatedAt,
    'data_quality_score' => 100,
    'confidence_score' => 100,
    'is_active' => 1,
    'is_deleted' => 0,
    'is_verified' => 1,
    'version' => 1,
    'first_seen_at' => $generatedAt,
    'last_seen_at' => $generatedAt,
    'created_at' => $generatedAt,
    'updated_at' => $generatedAt,
    'deleted_at' => null,
), array('dealer_id', 'provider_id', 'source_system', 'external_url', 'external_reference_type', 'provider_dealer_code', 'market_id', 'source_rank', 'source_priority', 'is_primary', 'sync_status', 'provider_metadata', 'metadata', 'future_reserved', 'created_by', 'updated_by', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'is_active', 'is_deleted', 'is_verified', 'version', 'first_seen_at', 'last_seen_at', 'updated_at'), 'id', 'dealer_map_id');

$sql[] = 'SET @provider_id := (SELECT id FROM ag_provider_master WHERE provider_code = \'toyota\' LIMIT 1);';
$sql[] = 'SET @wordpress_provider_id := (SELECT id FROM ag_provider_master WHERE provider_code = \'wordpress\' LIMIT 1);';
$sql[] = 'SET @dealer_id := (SELECT id FROM ag_dealer_master WHERE canonical_dealer_hash = ' . sql_string($dealerHash) . ' LIMIT 1);';
$sql[] = 'SET @dealer_map_id := (SELECT id FROM ag_dealer_map WHERE provider_id = @provider_id AND source_system = \'toyota\' AND external_id = ' . sql_string((string) $dealer['dealer_external_id']) . ' LIMIT 1);';

$sql[] = upsert_sql('ag_import_batch', array(
    'uuid' => uuid_from_text('import-batch-toyota-' . $generatedAt),
    'batch_code' => 'BATCH-' . gmdate('YmdHis') . '-TOYOTA',
    'batch_type' => 'toyota_inventory',
    'provider_id' => '@provider_id',
    'dealer_id' => '@dealer_id',
    'dealer_map_id' => '@dealer_map_id',
    'market_id' => '@market_id',
    'import_source' => 'toyota',
    'source_file_name' => 'toyota.json',
    'source_file_uri' => 'codex-log/sample-data/toyota.json',
    'source_file_hash' => hash('sha256', (string) file_get_contents($jsonPath)),
    'rows_total' => count($records),
    'rows_processed' => count($records),
    'rows_inserted' => count($records),
    'rows_updated' => 0,
    'rows_ignored' => 0,
    'rows_duplicates' => 0,
    'rows_errors' => 0,
    'rows_warnings' => 0,
    'batch_status' => 'completed',
    'started_at' => $generatedAt,
    'finished_at' => $generatedAt,
    'duration_seconds' => 0,
    'metadata' => array('provider' => 'toyota'),
    'future_reserved' => array(),
    'created_by' => 1,
    'updated_by' => 1,
    'change_reason' => 'batch_created',
    'last_synced_at' => $generatedAt,
    'last_provider_update' => $generatedAt,
    'data_quality_score' => 100,
    'confidence_score' => 100,
    'source_system' => 'toyota',
    'is_active' => 1,
    'is_deleted' => 0,
    'is_verified' => 1,
    'version' => 1,
    'created_at' => $generatedAt,
    'updated_at' => $generatedAt,
    'deleted_at' => null,
), array('batch_type', 'provider_id', 'dealer_id', 'dealer_map_id', 'market_id', 'import_source', 'source_file_name', 'source_file_uri', 'source_file_hash', 'rows_total', 'rows_processed', 'rows_inserted', 'rows_updated', 'rows_ignored', 'rows_duplicates', 'rows_errors', 'rows_warnings', 'batch_status', 'started_at', 'finished_at', 'duration_seconds', 'metadata', 'future_reserved', 'created_by', 'updated_by', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'updated_at'), 'id', 'batch_id');

$sql[] = upsert_sql('ag_import_job', array(
    'uuid' => uuid_from_text('import-job-toyota-' . $generatedAt),
    'batch_id' => '@batch_id',
    'job_code' => 'JOB-' . gmdate('YmdHis') . '-TOYOTA',
    'job_type' => 'toyota_inventory',
    'queue_name' => 'default',
    'job_status' => 'completed',
    'provider_id' => '@provider_id',
    'dealer_id' => '@dealer_id',
    'dealer_map_id' => '@dealer_map_id',
    'market_id' => '@market_id',
    'attempt_count' => 1,
    'max_attempts' => 3,
    'rows_total' => count($records),
    'rows_success' => count($records),
    'rows_failed' => 0,
    'rows_duplicates' => 0,
    'rows_skipped' => 0,
    'started_at' => $generatedAt,
    'finished_at' => $generatedAt,
    'duration_seconds' => 0,
    'next_run_at' => null,
    'error_summary' => null,
    'metadata' => array('provider' => 'toyota'),
    'future_reserved' => array(),
    'created_by' => 1,
    'updated_by' => 1,
    'change_reason' => 'job_created',
    'last_synced_at' => $generatedAt,
    'last_provider_update' => $generatedAt,
    'data_quality_score' => 100,
    'confidence_score' => 100,
    'source_system' => 'toyota',
    'is_active' => 1,
    'is_deleted' => 0,
    'is_verified' => 1,
    'version' => 1,
    'created_at' => $generatedAt,
    'updated_at' => $generatedAt,
    'deleted_at' => null,
), array('batch_id', 'job_type', 'queue_name', 'job_status', 'provider_id', 'dealer_id', 'dealer_map_id', 'market_id', 'attempt_count', 'max_attempts', 'rows_total', 'rows_success', 'rows_failed', 'rows_duplicates', 'rows_skipped', 'started_at', 'finished_at', 'duration_seconds', 'next_run_at', 'error_summary', 'metadata', 'future_reserved', 'created_by', 'updated_by', 'change_reason', 'last_synced_at', 'last_provider_update', 'data_quality_score', 'confidence_score', 'source_system', 'is_active', 'is_deleted', 'is_verified', 'version', 'updated_at'), 'id', 'job_id');

$sql[] = 'COMMIT;';

file_put_contents($sqlPath, implode(PHP_EOL, $sql) . PHP_EOL);
echo $sqlPath . PHP_EOL;

