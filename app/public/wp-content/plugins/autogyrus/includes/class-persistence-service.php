<?php

if (! defined('ABSPATH')) {
    exit;
}

class PersistenceService
{
    public function persist(ProviderInterface $provider, array $payload, array $options = array())
    {
        return AutoGyrus_Import_Service::import_provider_payload($provider->get_code(), $payload, $options);
    }
}
