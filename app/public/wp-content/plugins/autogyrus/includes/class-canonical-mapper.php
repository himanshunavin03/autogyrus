<?php

if (! defined('ABSPATH')) {
    exit;
}

class CanonicalMapper
{
    public function normalize(ProviderInterface $provider, array $payload)
    {
        return $provider->normalize_payload($payload);
    }
}
