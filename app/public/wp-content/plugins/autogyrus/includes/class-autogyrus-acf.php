<?php

if (! defined('ABSPATH')) {
    exit;
}

class AutoGyrus_ACF
{
    private static $registered = false;

    public static function register()
    {
        add_action('acf/init', array(__CLASS__, 'register_field_groups'));
        add_action('acf/include_fields', array(__CLASS__, 'register_field_groups'));
        add_action('plugins_loaded', array(__CLASS__, 'maybe_register_field_groups'), 30);
        add_action('admin_init', array(__CLASS__, 'maybe_register_field_groups'));
    }

    public static function register_field_groups()
    {
        if (self::$registered || ! function_exists('acf_add_local_field_group')) {
            return;
        }

        self::$registered = true;

        acf_add_local_field_group(array(
            'key' => 'group_autogyrus_vehicle_details',
            'title' => 'Vehicle Details',
            'fields' => self::vehicle_fields(),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'vehicle',
                    ),
                ),
            ),
        ));

        acf_add_local_field_group(array(
            'key' => 'group_autogyrus_ai_profiles',
            'title' => 'AI Intelligence',
            'fields' => self::ai_fields(),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'vehicle',
                    ),
                ),
            ),
        ));

        acf_add_local_field_group(array(
            'key' => 'group_autogyrus_dealer_profile',
            'title' => 'Dealer Profile',
            'fields' => self::dealer_fields(),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'dealer',
                    ),
                ),
            ),
        ));
    }

    public static function maybe_register_field_groups()
    {
        if (function_exists('acf_add_local_field_group')) {
            self::register_field_groups();
        }
    }

    private static function vehicle_fields()
    {
        return array(
            self::text('vin', 'VIN'),
            self::text('make', 'Make'),
            self::text('model', 'Model'),
            self::number('year', 'Year'),
            self::text('trim', 'Trim'),
            self::number('mileage', 'Mileage (KM)'),
            self::number('price', 'Price'),
            self::select('transmission', 'Transmission', array('Automatic', 'Manual', 'CVT')),
            self::select('fuel_type', 'Fuel Type', array('Gasoline', 'Hybrid', 'Diesel', 'Electric', 'Plug-in Hybrid')),
            self::text('engine', 'Engine'),
            self::select('drivetrain', 'Drivetrain', array('FWD', 'RWD', 'AWD', '4WD')),
            self::select('body_type', 'Body Type', array('SUV', 'Truck', 'Sedan', 'Coupe', 'Hatchback', 'Wagon', 'Van')),
            self::text('exterior_color', 'Exterior Color'),
            self::text('interior_color', 'Interior Color'),
            self::text('tire_size', 'Tire Size'),
            self::text('province_registration_history', 'Province Registration History'),
            self::true_false('block_heater', 'Block Heater'),
            self::true_false('remote_start', 'Remote Start'),
            self::true_false('winter_tires_included', 'Winter Tires Included'),
            self::textarea('service_history', 'Service History'),
            self::textarea('service_records_notes', 'Service Records Notes'),
            self::textarea('accident_history', 'Accident History'),
            array(
                'key' => 'field_dealer_user_id',
                'label' => 'Dealer User',
                'name' => 'dealer_user_id',
                'type' => 'user',
                'role' => array('dealer', 'administrator'),
                'return_format' => 'id',
            ),
            self::text('city', 'City'),
            self::text('postal_code', 'Postal Code'),
            self::select('province', 'Province', array('AB', 'BC', 'SK', 'MB', 'ON', 'QC', 'NB', 'NS', 'PE', 'NL')),
            array(
                'key' => 'field_vehicle_primary_image',
                'label' => 'Primary Vehicle Image',
                'name' => 'vehicle_primary_image',
                'type' => 'image',
                'return_format' => 'array',
                'preview_size' => 'medium',
            ),
            self::text('warranty', 'Warranty'),
            self::true_false('financing_available', 'Financing Available'),
            self::number('ground_clearance_mm', 'Ground Clearance (mm)'),
        );
    }

    private static function ai_fields()
    {
        return array(
            self::number('winter_score', 'Winter Score'),
            self::number('reliability_score', 'Reliability Score'),
            self::text('future_value_rating', 'Future Value Rating'),
            self::textarea('future_value_summary', 'Future Value Summary'),
            self::textarea('reliability_summary', 'Reliability Summary'),
            self::textarea('known_issues', 'Known Issues'),
            self::textarea('common_complaints', 'Common Complaints'),
            self::textarea('recall_history', 'Recall History'),
            self::textarea('future_value_prediction_notes', 'Future Value Prediction Notes'),
            self::textarea('maintenance_forecast_notes', 'Maintenance Forecast Notes'),
        );
    }

    private static function dealer_fields()
    {
        return array(
            self::text('dealer_name', 'Dealer Name'),
            self::text('dealer_phone', 'Phone'),
            self::text('dealer_email', 'Email'),
            self::text('dealer_city', 'City'),
            self::select('dealer_province', 'Province', array('AB', 'BC', 'SK', 'MB', 'ON', 'QC', 'NB', 'NS', 'PE', 'NL')),
            self::textarea('dealer_about', 'About Dealer'),
        );
    }

    private static function text($name, $label)
    {
        return array(
            'key' => 'field_' . $name,
            'label' => $label,
            'name' => $name,
            'type' => 'text',
        );
    }

    private static function number($name, $label)
    {
        return array(
            'key' => 'field_' . $name,
            'label' => $label,
            'name' => $name,
            'type' => 'number',
        );
    }

    private static function textarea($name, $label)
    {
        return array(
            'key' => 'field_' . $name,
            'label' => $label,
            'name' => $name,
            'type' => 'textarea',
            'rows' => 4,
        );
    }

    private static function true_false($name, $label)
    {
        return array(
            'key' => 'field_' . $name,
            'label' => $label,
            'name' => $name,
            'type' => 'true_false',
            'ui' => 1,
        );
    }

    private static function select($name, $label, $choices)
    {
        return array(
            'key' => 'field_' . $name,
            'label' => $label,
            'name' => $name,
            'type' => 'select',
            'choices' => array_combine($choices, $choices),
            'return_format' => 'value',
        );
    }
}
