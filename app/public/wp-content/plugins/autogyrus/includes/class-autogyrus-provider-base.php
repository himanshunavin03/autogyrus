<?php

if (! defined('ABSPATH')) {
    exit;
}

abstract class AutoGyrus_Provider_Base implements AutoGyrus_Provider_Interface
{
    public function supports($feature)
    {
        return in_array((string) $feature, array('import', 'normalize', 'vehicle_inventory'), true);
    }

    protected function get_value(array $source, $path, $default = null)
    {
        if (is_array($path)) {
            foreach ($path as $candidate) {
                $value = $this->get_value($source, $candidate, null);
                if (null !== $value && '' !== $value && array() !== $value) {
                    return $value;
                }
            }

            return $default;
        }

        if (! is_string($path) || '' === $path) {
            return $default;
        }

        $segments = explode('.', $path);
        $value = $source;

        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return (null === $value || '' === $value) ? $default : $value;
    }

    protected function flatten_records(array $payload)
    {
        $paths = array('vehicles', 'inventory', 'items', 'results', 'data', 'listings', 'response.vehicles', 'response.inventory');

        foreach ($paths as $path) {
            $records = $this->get_value($payload, $path, null);
            if (! is_array($records) || empty($records)) {
                continue;
            }

            if ($this->is_assoc($records)) {
                $records = array_values($records);
            }

            $records = array_values(array_filter($records, 'is_array'));
            if (! empty($records)) {
                return $records;
            }
        }

        if ($this->is_assoc($payload)) {
            return array($payload);
        }

        return array();
    }

    protected function is_assoc(array $array)
    {
        if (array() === $array) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    protected function normalize_text($value, $default = '')
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        $value = trim((string) $value);

        return '' === $value ? $default : $value;
    }

    protected function normalize_upper($value, $default = '')
    {
        $value = $this->normalize_text($value, $default);
        return '' === $value ? $default : strtoupper($value);
    }

    protected function normalize_int($value, $default = null)
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        if (null === $value || '' === $value || ! is_numeric($value)) {
            return $default;
        }

        return (int) round((float) $value);
    }

    protected function normalize_float($value, $default = null)
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        if (null === $value || '' === $value || ! is_numeric($value)) {
            return $default;
        }

        return (float) $value;
    }

    protected function normalize_bool($value)
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_numeric($value)) {
            return ((int) $value) > 0 ? 1 : 0;
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, array('1', 'true', 'yes', 'y', 'on', 'available', 'standard'), true) ? 1 : 0;
    }

    protected function normalize_date($value)
    {
        $value = $this->normalize_text($value, '');
        if ('' === $value) {
            return null;
        }

        $timestamp = strtotime($value);
        if (! $timestamp) {
            return null;
        }

        return gmdate('Y-m-d', $timestamp);
    }

    protected function normalize_datetime($value)
    {
        $value = $this->normalize_text($value, '');
        if ('' === $value) {
            return null;
        }

        $timestamp = strtotime($value);
        if (! $timestamp) {
            return null;
        }

        return gmdate('Y-m-d H:i:s', $timestamp);
    }

    protected function slugify($value)
    {
        return sanitize_title($this->normalize_text($value));
    }

    protected function hash_record(array $record)
    {
        $normalized = $this->recursive_ksort($record);
        return hash('sha256', wp_json_encode($normalized));
    }

    protected function recursive_ksort(array $array)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->recursive_ksort($value);
            }
        }

        ksort($array);

        return $array;
    }

    protected function map_unmapped_fields(array $record, array $mapped_keys)
    {
        $remaining = $record;

        foreach ($mapped_keys as $key) {
            unset($remaining[$key]);
        }

        return $remaining;
    }

    protected function coerce_array($value)
    {
        if (empty($value)) {
            return array();
        }

        if (is_array($value)) {
            return $this->is_assoc($value) ? array($value) : $value;
        }

        return array($value);
    }
}
