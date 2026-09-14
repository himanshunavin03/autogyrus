<?php
get_header();

$filters = array(
    'ai_query' => sanitize_text_field(wp_unslash($_GET['ai_query'] ?? '')),
    'make' => sanitize_text_field(wp_unslash($_GET['make'] ?? '')),
    'model' => sanitize_text_field(wp_unslash($_GET['model'] ?? '')),
    'body_type' => sanitize_text_field(wp_unslash($_GET['body_type'] ?? '')),
    'max_price' => absint($_GET['max_price'] ?? 0),
    'max_mileage' => absint($_GET['max_mileage'] ?? 0),
    'province' => sanitize_text_field(wp_unslash($_GET['province'] ?? '')),
    'drivetrain' => sanitize_text_field(wp_unslash($_GET['drivetrain'] ?? '')),
    'fuel_type' => sanitize_text_field(wp_unslash($_GET['fuel_type'] ?? '')),
    'city' => sanitize_text_field(wp_unslash($_GET['city'] ?? '')),
    'postal_code' => sanitize_text_field(wp_unslash($_GET['postal_code'] ?? '')),
    'location' => sanitize_text_field(wp_unslash($_GET['location'] ?? '')),
    'distance' => absint($_GET['distance'] ?? 0),
    'year' => absint($_GET['year'] ?? 0),
    'reliability' => sanitize_text_field(wp_unslash($_GET['reliability'] ?? '')),
);

$query_filters = array_filter(array(
    'make' => $filters['make'],
    'model' => $filters['model'],
    'bodyType' => $filters['body_type'],
    'maxPrice' => $filters['max_price'],
    'maxMileage' => $filters['max_mileage'],
    'province' => $filters['province'],
    'drivetrain' => $filters['drivetrain'],
    'fuelType' => $filters['fuel_type'],
    'city' => $filters['city'],
    'postalCode' => $filters['postal_code'],
    'location' => $filters['location'],
    'distance' => $filters['distance'],
    'year' => $filters['year'],
    'reliability' => $filters['reliability'],
));

$custom_query = ! empty($query_filters) ? AutoGyrus_Services::query_vehicles($query_filters) : null;
$loop = $custom_query instanceof WP_Query ? $custom_query : $wp_query;
?>
<main class="section inventory-page" id="vehicle-archive">
    <div class="container">
        <section class="inventory-header">
            <div class="inventory-header__content">
                <p class="eyebrow">Vehicle inventory</p>
                <h2>Browse used vehicles</h2>
                <p class="inventory-header__lead">Use AI search, standard filters, or both. The goal is a faster path from first search to confident shortlist.</p>
                <div class="inventory-header__art" aria-hidden="true">
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/hero-primary-art.png'); ?>" alt="" loading="eager" decoding="async">
                </div>
                <div class="inventory-header__art-anchor" aria-hidden="true"></div>
                <?php if (! empty($filters['ai_query'])) : ?>
                    <p class="results-meta">AI search: "<?php echo esc_html($filters['ai_query']); ?>"</p>
                <?php endif; ?>
                <?php if (! empty($filters['location']) || ! empty($filters['postal_code'])) : ?>
                    <p class="results-meta">Location: <?php echo esc_html($filters['location'] ?: $filters['postal_code']); ?><?php echo $filters['distance'] ? ' within ' . esc_html((string) $filters['distance']) . ' km' : ''; ?></p>
                <?php endif; ?>
            </div>
            <div class="inventory-header__search inventory-mobile-panel" id="inventory-ai-search">
                <?php echo do_shortcode('[autogyrus_search]'); ?>
            </div>
        </section>

        <div class="inventory-mobile-tools" aria-label="Browse tools">
            <button type="button" class="inventory-mobile-tools__button" data-mobile-toggle="inventory-filters" aria-controls="inventory-filters" aria-expanded="false">Filters</button>
            <button type="button" class="inventory-mobile-tools__button" data-mobile-toggle="inventory-ai-search" aria-controls="inventory-ai-search" aria-expanded="false">AI Search</button>
        </div>

        <section class="inventory-toolbar inventory-mobile-panel" id="inventory-filters">
            <form method="get" action="<?php echo esc_url(get_post_type_archive_link('vehicle')); ?>" class="inventory-toolbar__form">
                <label>Make
                    <input type="text" name="make" value="<?php echo esc_attr($filters['make']); ?>" placeholder="Toyota">
                </label>
                <label>Model
                    <input type="text" name="model" value="<?php echo esc_attr($filters['model']); ?>" placeholder="RAV4">
                </label>
                <label>Body Type
                    <select name="body_type">
                        <option value="">Any</option>
                        <?php foreach (array('SUV', 'Truck', 'Sedan', 'Coupe', 'Wagon', 'Van', 'Hatchback') as $body_type) : ?>
                            <option value="<?php echo esc_attr($body_type); ?>" <?php selected($filters['body_type'], $body_type); ?>><?php echo esc_html($body_type); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Max Price
                    <input type="number" name="max_price" value="<?php echo esc_attr((string) $filters['max_price']); ?>" placeholder="40000">
                </label>
                <label>Max Mileage
                    <input type="number" name="max_mileage" value="<?php echo esc_attr((string) $filters['max_mileage']); ?>" placeholder="90000">
                </label>
                <label>Province
                    <select name="province">
                        <option value="">Any</option>
                        <?php foreach (array('AB', 'BC', 'SK', 'MB', 'ON', 'QC') as $province) : ?>
                            <option value="<?php echo esc_attr($province); ?>" <?php selected($filters['province'], $province); ?>><?php echo esc_html($province); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Drivetrain
                    <select name="drivetrain">
                        <option value="">Any</option>
                        <?php foreach (array('AWD', '4WD', 'FWD', 'RWD') as $drivetrain) : ?>
                            <option value="<?php echo esc_attr($drivetrain); ?>" <?php selected($filters['drivetrain'], $drivetrain); ?>><?php echo esc_html($drivetrain); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Fuel Type
                    <select name="fuel_type">
                        <option value="">Any</option>
                        <?php foreach (array('Gasoline', 'Hybrid', 'Electric', 'Diesel', 'Plug-in Hybrid') as $fuel_type) : ?>
                            <option value="<?php echo esc_attr($fuel_type); ?>" <?php selected($filters['fuel_type'], $fuel_type); ?>><?php echo esc_html($fuel_type); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Minimum Year
                    <input type="number" name="year" value="<?php echo esc_attr((string) $filters['year']); ?>" placeholder="2022">
                </label>
                <label>City / Postal Code
                    <input type="text" name="location" value="<?php echo esc_attr($filters['location'] ?: $filters['city'] ?: $filters['postal_code']); ?>" placeholder="Calgary or T2P 1J9">
                </label>
                <label>Distance
                    <select name="distance">
                        <option value="">Any</option>
                        <?php foreach (array(25, 50, 100, 250, 500) as $distance) : ?>
                            <option value="<?php echo esc_attr((string) $distance); ?>" <?php selected($filters['distance'], $distance); ?>><?php echo esc_html($distance . ' km'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Reliability
                    <select name="reliability">
                        <option value="">Any</option>
                        <option value="high" <?php selected($filters['reliability'], 'high'); ?>>High</option>
                    </select>
                </label>
                <input type="hidden" name="ai_query" value="<?php echo esc_attr($filters['ai_query']); ?>">
                <button type="submit">Apply Filters</button>
            </form>
        </section>

        <section class="inventory-results" id="inventory-results-section" tabindex="-1">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Results</p>
                    <h3><?php echo esc_html((string) $loop->found_posts); ?> vehicles available</h3>
                </div>
                <div class="results-meta">AI-driven features stay attached to every vehicle card and detail page.</div>
            </div>
            <div class="listing-grid listing-grid--inventory">
                <?php if ($loop->have_posts()) : ?>
                    <?php while ($loop->have_posts()) : $loop->the_post(); ?>
                        <?php get_template_part('template-parts/vehicle', 'card'); ?>
                    <?php endwhile; wp_reset_postdata(); ?>
                <?php else : ?>
                    <article class="panel">
                        <h4>No matching vehicles</h4>
                        <p>Adjust filters or try the AI search box using a plain-English request.</p>
                    </article>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>
<?php get_footer(); ?>
