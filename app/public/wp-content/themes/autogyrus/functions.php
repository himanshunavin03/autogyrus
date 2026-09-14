<?php
if (! defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
    add_theme_support('html5', array('search-form', 'gallery', 'caption', 'style', 'script'));
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'autogyrus'),
        'footer' => __('Footer Menu', 'autogyrus'),
    ));
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'autogyrus-fonts',
        'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap',
        array(),
        null
    );
    wp_enqueue_style('autogyrus-style', get_stylesheet_uri(), array(), '1.4.3');
    wp_enqueue_script('autogyrus-theme', get_template_directory_uri() . '/assets/js/theme.js', array(), '1.4.3', true);

    $vehicle_id = is_singular('vehicle') ? get_queried_object_id() : 0;

    wp_localize_script('autogyrus-theme', 'AutoDriveTheme', array(
        'restUrl' => esc_url_raw(rest_url('autogyrus/v1')),
        'nonce' => wp_create_nonce('wp_rest'),
        'vehicleId' => $vehicle_id,
        'isLoggedIn' => is_user_logged_in(),
        'archiveUrl' => get_post_type_archive_link('vehicle'),
        'siteUrl' => home_url('/'),
    ));
});

add_filter('document_title_parts', function ($title) {
    if (is_post_type_archive('vehicle')) {
        $title['title'] = 'Vehicle Inventory';
    }

    return $title;
});

add_action('wp_head', function () {
    if (! is_singular('vehicle')) {
        return;
    }

    $vehicle_id = get_the_ID();
    $data = array(
        '@context' => 'https://schema.org',
        '@type' => 'Vehicle',
        'name' => get_the_title($vehicle_id),
        'brand' => get_post_meta($vehicle_id, 'make', true),
        'model' => get_post_meta($vehicle_id, 'model', true),
        'vehicleModelDate' => get_post_meta($vehicle_id, 'year', true),
        'mileageFromOdometer' => array(
            '@type' => 'QuantitativeValue',
            'value' => (int) get_post_meta($vehicle_id, 'mileage', true),
            'unitCode' => 'KMT',
        ),
        'color' => get_post_meta($vehicle_id, 'exterior_color', true),
        'vehicleTransmission' => get_post_meta($vehicle_id, 'transmission', true),
        'fuelType' => get_post_meta($vehicle_id, 'fuel_type', true),
        'offers' => array(
            '@type' => 'Offer',
            'priceCurrency' => 'CAD',
            'price' => (float) get_post_meta($vehicle_id, 'price', true),
            'availability' => 'https://schema.org/InStock',
            'url' => get_permalink($vehicle_id),
        ),
    );
    ?>
    <script type="application/ld+json"><?php echo wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
    <?php
});

function autogyrus_format_money($amount)
{
    return '$' . number_format_i18n((float) $amount, 0);
}

function autogyrus_get_score_band($value)
{
    $raw_value = strtolower(trim((string) $value));

    if (preg_match('/(\d+)/', $raw_value, $matches)) {
        $numeric_value = (int) $matches[1];

        if ($numeric_value >= 80) {
            return 'high';
        }

        if ($numeric_value >= 50) {
            return 'medium';
        }

        return 'low';
    }

    if ($raw_value === 'pending' || $raw_value === '') {
        return 'medium';
    }

    if (preg_match('/(excellent|strong|good|high)/', $raw_value)) {
        return 'high';
    }

    if (preg_match('/(average|fair|moderate)/', $raw_value)) {
        return 'medium';
    }

    return 'low';
}

function autogyrus_get_vehicle_highlights($vehicle_id)
{
    return array(
        array(
            'label' => 'Canada Winter Score',
            'value' => (int) get_post_meta($vehicle_id, 'winter_score', true) . '/100',
        ),
        array(
            'label' => 'Reliability Score',
            'value' => (int) get_post_meta($vehicle_id, 'reliability_score', true) . '/100',
        ),
        array(
            'label' => 'Future Value',
            'value' => get_post_meta($vehicle_id, 'future_value_rating', true) ?: 'Pending',
        ),
    );
}

function autogyrus_resolve_acf_image($image, $size = 'large')
{
    if (is_numeric($image)) {
        $url = wp_get_attachment_image_url((int) $image, $size);
        return $url ? array('url' => $url, 'alt' => '') : null;
    }

    if (is_array($image)) {
        $url = $image['sizes'][$size] ?? $image['sizes']['large'] ?? $image['url'] ?? '';
        if (! $url && ! empty($image['ID'])) {
            $url = wp_get_attachment_image_url((int) $image['ID'], $size);
        }

        return $url ? array('url' => $url, 'alt' => (string) ($image['alt'] ?? '')) : null;
    }

    if (is_string($image) && $image !== '') {
        return array('url' => $image, 'alt' => '');
    }

    return null;
}
