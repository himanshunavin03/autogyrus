<?php

if (! defined('ABSPATH')) {
    exit;
}

class AutoGyrus_Sample_Data
{
    public static function register()
    {
        add_action('admin_post_autogyrus_seed_demo', array(__CLASS__, 'seed_demo_data'));
    }

    public static function seed_demo_data()
    {
        if (! current_user_can('manage_options')) {
            wp_die('Not allowed.');
        }

        check_admin_referer('autogyrus_seed_demo');

        $vehicles = self::generate_vehicle_dataset();

        foreach ($vehicles as $vehicle) {
            self::insert_vehicle($vehicle);
        }

        wp_safe_redirect(admin_url('edit.php?post_type=vehicle&autogyrus_seeded=1'));
        exit;
    }

    public static function generate_vehicle_dataset()
    {
        $base = array(
            array('make' => 'Toyota', 'model' => 'RAV4', 'body_type' => 'SUV', 'drivetrain' => 'AWD', 'fuel_type' => 'Hybrid', 'engine' => '2.5L Hybrid', 'trim' => 'XLE'),
            array('make' => 'Honda', 'model' => 'CR-V', 'body_type' => 'SUV', 'drivetrain' => 'AWD', 'fuel_type' => 'Gasoline', 'engine' => '1.5L Turbo', 'trim' => 'Sport'),
            array('make' => 'Subaru', 'model' => 'Outback', 'body_type' => 'Wagon', 'drivetrain' => 'AWD', 'fuel_type' => 'Gasoline', 'engine' => '2.5L Boxer', 'trim' => 'Limited'),
            array('make' => 'Ford', 'model' => 'F-150', 'body_type' => 'Truck', 'drivetrain' => '4WD', 'fuel_type' => 'Gasoline', 'engine' => '3.5L EcoBoost', 'trim' => 'XLT'),
            array('make' => 'Mazda', 'model' => 'CX-5', 'body_type' => 'SUV', 'drivetrain' => 'AWD', 'fuel_type' => 'Gasoline', 'engine' => '2.5L', 'trim' => 'GT'),
            array('make' => 'Hyundai', 'model' => 'Tucson', 'body_type' => 'SUV', 'drivetrain' => 'AWD', 'fuel_type' => 'Hybrid', 'engine' => '1.6L Hybrid', 'trim' => 'Ultimate'),
            array('make' => 'Kia', 'model' => 'Telluride', 'body_type' => 'SUV', 'drivetrain' => 'AWD', 'fuel_type' => 'Gasoline', 'engine' => '3.8L V6', 'trim' => 'EX'),
            array('make' => 'Chevrolet', 'model' => 'Silverado 1500', 'body_type' => 'Truck', 'drivetrain' => '4WD', 'fuel_type' => 'Gasoline', 'engine' => '5.3L V8', 'trim' => 'RST'),
            array('make' => 'Jeep', 'model' => 'Grand Cherokee', 'body_type' => 'SUV', 'drivetrain' => '4WD', 'fuel_type' => 'Gasoline', 'engine' => '3.6L V6', 'trim' => 'Limited'),
            array('make' => 'Volkswagen', 'model' => 'Tiguan', 'body_type' => 'SUV', 'drivetrain' => 'AWD', 'fuel_type' => 'Gasoline', 'engine' => '2.0L Turbo', 'trim' => 'Comfortline'),
        );

        $colors = array('White', 'Black', 'Grey', 'Blue', 'Silver', 'Red');
        $interiors = array('Black', 'Grey', 'Tan');
        $cities = array('Calgary', 'Edmonton', 'Red Deer', 'Lethbridge', 'Airdrie');
        $postal_codes = array(
            'Calgary' => 'T2P 1J9',
            'Edmonton' => 'T5J 0N3',
            'Red Deer' => 'T4N 0A1',
            'Lethbridge' => 'T1J 0N8',
            'Airdrie' => 'T4B 0K3',
        );
        $records = array();

        for ($index = 0; $index < 50; $index++) {
            $template = $base[$index % count($base)];
            $year = 2020 + ($index % 6);
            $mileage = 18000 + ($index * 3400);
            $price = 21995 + ($index * 980);
            $city = $cities[$index % count($cities)];
            $dealer_user_id = 0;

            $records[] = array(
                'title' => $year . ' ' . $template['make'] . ' ' . $template['model'] . ' ' . $template['trim'],
                'vin' => strtoupper(substr(md5('autodrive-' . $index), 0, 17)),
                'year' => $year,
                'make' => $template['make'],
                'model' => $template['model'],
                'trim' => $template['trim'],
                'mileage' => $mileage,
                'price' => $price,
                'transmission' => 'Automatic',
                'fuel_type' => $template['fuel_type'],
                'engine' => $template['engine'],
                'drivetrain' => $template['drivetrain'],
                'body_type' => $template['body_type'],
                'exterior_color' => $colors[$index % count($colors)],
                'interior_color' => $interiors[$index % count($interiors)],
                'tire_size' => '235/60R18',
                'province_registration_history' => 'AB',
                'block_heater' => 1,
                'remote_start' => $index % 2,
                'winter_tires_included' => ($index + 1) % 2,
                'service_history' => 'Dealer maintained with annual inspections and synthetic oil service.',
                'accident_history' => $index % 7 === 0 ? 'Minor cosmetic claim reported.' : 'No reported accidents.',
                'dealer_user_id' => $dealer_user_id,
                'city' => $city,
                'postal_code' => $postal_codes[$city] ?? '',
                'province' => 'AB',
                'warranty' => 'Powertrain warranty available',
                'financing_available' => 1,
                'ground_clearance_mm' => $template['body_type'] === 'Truck' ? 235 : 205,
            );
        }

        return $records;
    }

    public static function insert_vehicle($vehicle)
    {
        $existing = get_posts(array(
            'post_type' => 'vehicle',
            'post_status' => 'any',
            'meta_key' => 'vin',
            'meta_value' => $vehicle['vin'],
            'fields' => 'ids',
            'posts_per_page' => 1,
        ));

        if (! empty($existing)) {
            return (int) $existing[0];
        }

        $post_id = wp_insert_post(array(
            'post_type' => 'vehicle',
            'post_status' => 'publish',
            'post_title' => $vehicle['title'],
            'post_content' => 'AutoDrive AI market listing with AI-powered resale, winter, reliability, and ownership insights.',
        ));

        if (is_wp_error($post_id) || ! $post_id) {
            return 0;
        }

        foreach ($vehicle as $key => $value) {
            if ('title' === $key) {
                continue;
            }

            update_post_meta($post_id, $key, $value);
        }

        AutoGyrus_Services::refresh_vehicle_scores($post_id);

        return $post_id;
    }
}
