<?php

if (! defined('ABSPATH')) {
    exit;
}

class AutoGyrus_Provider_Registry
{
    private static $providers = array();
    private static $bootstrapped = false;

    public static function register_defaults()
    {
        if (self::$bootstrapped) {
            return;
        }

        self::$bootstrapped = true;
        self::register_provider(new ToyotaProvider());
        self::$providers = apply_filters('autogyrus_registered_providers', self::$providers);
    }

    public static function register_provider(AutoGyrus_Provider_Interface $provider)
    {
        self::$providers[$provider->get_code()] = $provider;
    }

    public static function get_provider($provider_code)
    {
        self::register_defaults();

        return self::$providers[$provider_code] ?? null;
    }

    public static function all()
    {
        self::register_defaults();

        return self::$providers;
    }
}
