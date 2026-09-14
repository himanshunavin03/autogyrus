<?php

if (! defined('ABSPATH')) {
    exit;
}

interface AutoGyrus_Provider_Interface
{
    public function get_code();

    public function get_name();

    public function supports($feature);

    public function normalize_payload(array $payload);
}
