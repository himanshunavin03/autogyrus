<?php

if (! defined('ABSPATH')) {
    exit;
}

class ImportPipeline
{
    public function run($provider_code, array $payload, array $options = array())
    {
        return AutoGyrus_Import_Service::import_provider_payload($provider_code, $payload, $options);
    }
}
