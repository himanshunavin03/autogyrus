<?php
get_header();
$vehicle_id = get_the_ID();
$gallery = function_exists('get_field') ? (array) get_field('vehicle_gallery', $vehicle_id) : array();
$primary_image = function_exists('get_field') ? get_field('vehicle_primary_image', $vehicle_id) : null;
$service_records = function_exists('get_field') ? (array) get_field('service_records', $vehicle_id) : array();
$highlights = autogyrus_get_vehicle_highlights($vehicle_id);
$vehicle_snapshot = AutoGyrus_Services::build_vehicle_snapshot($vehicle_id);
$future_value = $vehicle_snapshot['future_value'];
$winter = $vehicle_snapshot['winter'];
$maintenance = $vehicle_snapshot['maintenance'];
$reliability = $vehicle_snapshot['reliability'];
$vehicle_year = (string) get_post_meta($vehicle_id, 'year', true);
$vehicle_make = (string) get_post_meta($vehicle_id, 'make', true);
$vehicle_body = (string) get_post_meta($vehicle_id, 'body_type', true);
$vehicle_fuel = (string) get_post_meta($vehicle_id, 'fuel_type', true);
$vehicle_city = (string) get_post_meta($vehicle_id, 'city', true);
$vehicle_province = (string) get_post_meta($vehicle_id, 'province', true);
$vehicle_trim = (string) get_post_meta($vehicle_id, 'trim', true);
$vehicle_transmission = (string) get_post_meta($vehicle_id, 'transmission', true);
$vehicle_drivetrain = (string) get_post_meta($vehicle_id, 'drivetrain', true);
$vehicle_engine = (string) get_post_meta($vehicle_id, 'engine', true);
$vehicle_color = (string) get_post_meta($vehicle_id, 'exterior_color', true);
$vehicle_price = get_post_meta($vehicle_id, 'price', true);
$vehicle_mileage = (int) get_post_meta($vehicle_id, 'mileage', true);
$vehicle_financing = ! empty(get_post_meta($vehicle_id, 'financing_available', true));

$hero_icon = static function (string $name): string {
    $icons = array(
        'share' => '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M12 5v10" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /><path d="M8.5 8.5L12 5l3.5 3.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /><path d="M6 14v4h12v-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>',
        'compare' => '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M8 4h8v16H8z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" /><path d="M12 7v10M10 9l2-2 2 2M10 15l2 2 2-2" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg>',
        'save' => '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" /></svg>',
        'mail' => '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M4 6h16v12H4z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" /><path d="M5 7l7 6 7-6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg>',
        'calendar' => '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M6 4v3M18 4v3M4 8h16M5 6h14v14H5z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg>',
        'trim' => '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M4 6h16v12H4z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" /><path d="M7 9h10M7 12h10M7 15h6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" /></svg>',
        'fuel' => '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M6 20V6.5A2.5 2.5 0 0 1 8.5 4H14l4 4v12M14 4v4h4M8 12h6M8 15h6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg>',
        'transmission' => '<svg viewBox="0 0 24 24" role="img" focusable="false"><circle cx="6" cy="6" r="2" fill="none" stroke="currentColor" stroke-width="1.7" /><circle cx="18" cy="6" r="2" fill="none" stroke="currentColor" stroke-width="1.7" /><circle cx="12" cy="18" r="2" fill="none" stroke="currentColor" stroke-width="1.7" /><path d="M6 8v4h6v4M18 8v4h-6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg>',
        'drive' => '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M4 14l2-6h12l2 6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /><path d="M6 14v4M18 14v4M8 18h8" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /></svg>',
        'engine' => '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M7 8h3l2-2h3v2h2l2 2v4l-2 2h-2v2h-3l-2-2H7V8z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" /><path d="M10 10h4v4h-4z" fill="none" stroke="currentColor" stroke-width="1.6" /></svg>',
        'doors' => '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M6 4h10v16H6z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" /><path d="M9 8h4M9 12h4M9 16h2" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /></svg>',
        'seats' => '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M8 10a3 3 0 1 1 6 0v3H8z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" /><path d="M6 15h12v5H6z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" /></svg>',
        'color' => '<svg viewBox="0 0 24 24" role="img" focusable="false"><circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="1.7" /><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.7" /></svg>',
    );

    return $icons[$name] ?? '';
};

$resolved_gallery = array();
foreach ($gallery as $image) {
    $resolved_image = autogyrus_resolve_acf_image($image, 'large');
    if ($resolved_image) {
        $resolved_gallery[] = $resolved_image;
    }
}
?>
<main class="section vehicle-page">
    <div class="container">
        <section class="vehicle-hero">
            <div class="vehicle-gallery">
                <?php if (! empty($resolved_gallery)) : ?>
                    <?php foreach ($resolved_gallery as $resolved_image) : ?>
                        <figure class="vehicle-gallery__item <?php echo count($resolved_gallery) === 1 ? 'vehicle-gallery__item--single' : ''; ?>">
                            <img src="<?php echo esc_url($resolved_image['url']); ?>" alt="<?php echo esc_attr($resolved_image['alt'] ?: get_the_title()); ?>" loading="lazy" decoding="async">
                        </figure>
                    <?php endforeach; ?>
                <?php elseif ($resolved_primary = autogyrus_resolve_acf_image($primary_image, 'large')) : ?>
                    <figure class="vehicle-gallery__item vehicle-gallery__item--single">
                        <img src="<?php echo esc_url($resolved_primary['url']); ?>" alt="<?php echo esc_attr($resolved_primary['alt'] ?: get_the_title()); ?>" loading="lazy" decoding="async">
                    </figure>
                <?php elseif (has_post_thumbnail()) : ?>
                    <figure class="vehicle-gallery__item vehicle-gallery__item--single">
                        <?php the_post_thumbnail('large'); ?>
                    </figure>
            <?php else : ?>
                    <div class="vehicle-gallery__fallback">No photos uploaded yet</div>
                <?php endif; ?>
            </div>
            <div class="vehicle-summary vehicle-summary--marketplace">
                <div class="vehicle-summary__top-row">
                    <p class="vehicle-summary__make"><?php echo esc_html(strtoupper($vehicle_year . ' ' . $vehicle_make)); ?></p>
                    <div class="vehicle-summary__action-row">
                        <button type="button" class="vehicle-summary__icon-button" aria-label="Share vehicle">
                            <span aria-hidden="true"><?php echo $hero_icon('share'); ?></span>
                            <small>Share</small>
                        </button>
                        <button type="button" class="vehicle-summary__icon-button" aria-label="Compare vehicle">
                            <span aria-hidden="true"><?php echo $hero_icon('compare'); ?></span>
                            <small>Compare</small>
                        </button>
                        <button type="button" class="vehicle-summary__icon-button" aria-label="Save vehicle">
                            <span aria-hidden="true"><?php echo $hero_icon('save'); ?></span>
                            <small>Save</small>
                        </button>
                    </div>
                </div>

                <h2><?php the_title(); ?></h2>
                <p class="vehicle-summary__subtitle"><?php echo esc_html(trim((string) get_post_meta($vehicle_id, 'body_type', true) . ' | ' . (string) get_post_meta($vehicle_id, 'fuel_type', true) . ' | ' . (string) get_post_meta($vehicle_id, 'city', true) . ', ' . (string) get_post_meta($vehicle_id, 'province', true), " | ,")); ?></p>

                <div class="vehicle-summary__price-row">
                    <div>
                        <p class="vehicle-summary__price-label">Price</p>
                        <strong class="vehicle-summary__price"><?php echo esc_html(autogyrus_format_money(get_post_meta($vehicle_id, 'price', true))); ?></strong>
                    </div>
                    <div class="vehicle-summary__mileage">
                        <span><?php echo esc_html(number_format_i18n((int) get_post_meta($vehicle_id, 'mileage', true), 0)); ?> km</span>
                    </div>
                </div>

                <div class="vehicle-summary__spec-grid">
                    <div class="vehicle-summary__spec">
                        <span class="vehicle-summary__spec-label"><?php echo $hero_icon('trim'); ?> Trim</span>
                        <strong><?php echo esc_html($vehicle_trim ?: '—'); ?></strong>
                    </div>
                    <div class="vehicle-summary__spec">
                        <span class="vehicle-summary__spec-label"><?php echo $hero_icon('fuel'); ?> Fuel Type</span>
                        <strong><?php echo esc_html($vehicle_fuel ?: '—'); ?></strong>
                    </div>
                    <div class="vehicle-summary__spec">
                        <span class="vehicle-summary__spec-label"><?php echo $hero_icon('transmission'); ?> Transmission</span>
                        <strong><?php echo esc_html($vehicle_transmission ?: '—'); ?></strong>
                    </div>
                    <div class="vehicle-summary__spec">
                        <span class="vehicle-summary__spec-label"><?php echo $hero_icon('drive'); ?> Drivetrain</span>
                        <strong><?php echo esc_html($vehicle_drivetrain ?: '—'); ?></strong>
                    </div>
                    <div class="vehicle-summary__spec">
                        <span class="vehicle-summary__spec-label"><?php echo $hero_icon('engine'); ?> Engine</span>
                        <strong><?php echo esc_html($vehicle_engine ?: '—'); ?></strong>
                    </div>
                    <div class="vehicle-summary__spec">
                        <span class="vehicle-summary__spec-label"><?php echo $hero_icon('color'); ?> Colour</span>
                        <strong><?php echo esc_html($vehicle_color ?: '—'); ?></strong>
                    </div>
                </div>

                <div class="vehicle-summary__cta-row">
                    <button type="button" class="vehicle-summary__button vehicle-summary__button--primary" data-lead-type="contact"><?php echo $hero_icon('mail'); ?><span>Contact Dealer</span></button>
                    <button type="button" class="vehicle-summary__button vehicle-summary__button--secondary" data-lead-type="test_drive"><?php echo $hero_icon('calendar'); ?><span>Request Test Drive</span></button>
                </div>
            </div>
        </section>

        <section class="section-block">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">AI Intelligence</p>
                    <h3>Future price prediction</h3>
                </div>
            </div>
            <?php
            $future_rating = (string) ($future_value['rating'] ?? 'Pending');
            $future_band = autogyrus_get_score_band($future_rating);
            $future_score = 0;

            if (is_numeric($future_rating)) {
                $future_score = max(0, min(100, (int) $future_rating));
            } elseif (is_numeric($future_value['score'] ?? null)) {
                $future_score = max(0, min(100, (int) $future_value['score']));
            } elseif ($future_band === 'high') {
                $future_score = 84;
            } elseif ($future_band === 'medium') {
                $future_score = 66;
            } elseif ($future_band === 'low') {
                $future_score = 42;
            }

            $future_predictions = (array) ($future_value['predictions'] ?? array());
            $future_prediction_values = array_filter(array_map('floatval', array_values($future_predictions)));
            $future_prediction_min = $future_prediction_values ? min($future_prediction_values) : 0.0;
            $future_prediction_max = $future_prediction_values ? max($future_prediction_values) : 0.0;
            $future_prediction_range = max(1.0, $future_prediction_max - $future_prediction_min);
            ?>
            <div class="panel future-value-panel" id="future-value">
                <div class="future-value-panel__header">
                    <div class="future-value-panel__intro">
                        <span class="future-value-panel__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" role="img" focusable="false">
                                <path d="M4 16l5-5 4 3 7-8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M16 6h4v4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <div>
                            <p class="eyebrow">Future Price Prediction</p>
                            <h4><?php echo esc_html($future_rating); ?></h4>
                            <p class="future-value-panel__summary"><?php echo esc_html($future_value['summary'] ?? ''); ?></p>
                        </div>
                    </div>
                    <div class="future-value-panel__meter future-value-panel__meter--<?php echo esc_attr($future_band); ?>" style="--score: <?php echo esc_attr((string) max(0, min(100, $future_score))); ?>;">
                        <div class="future-value-panel__meter-ring">
                            <span><?php echo esc_html((string) $future_score); ?></span>
                            <small>/100</small>
                        </div>
                        <strong><?php echo esc_html(ucfirst($future_band)); ?></strong>
                    </div>
                </div>

                <div class="future-value-panel__forecast-grid">
                    <?php foreach ($future_predictions as $label => $value) : ?>
                        <?php
                        $normalized_value = (float) $value;
                        $forecast_width = ($future_prediction_range > 0) ? (($normalized_value - $future_prediction_min) / $future_prediction_range) * 100 : 0;
                        $forecast_width = max(14, min(100, $forecast_width));
                        ?>
                        <div class="future-value-panel__forecast-card">
                            <span><?php echo esc_html(ucwords(str_replace('_', ' ', (string) $label))); ?></span>
                            <strong><?php echo esc_html(autogyrus_format_money($value)); ?></strong>
                            <div class="future-value-panel__forecast-bar">
                                <i style="width: <?php echo esc_attr((string) round($forecast_width)); ?>%;"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="future-value-panel__range">
                    <div class="future-value-panel__range-track" aria-hidden="true">
                        <i style="width: <?php echo esc_attr((string) $future_score); ?>%;"></i>
                    </div>
                    <div class="future-value-panel__range-labels">
                        <span>Poor</span>
                        <span>Average</span>
                        <span>Excellent</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="two-column-section">
            <div class="panel" id="winter-score">
                <div class="score-summary score-summary--<?php echo esc_attr(autogyrus_get_score_band((string) ($winter['score'] ?? 0))); ?>">
                    <div class="score-summary__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" role="img" focusable="false">
                            <?php if ((int) ($winter['score'] ?? 0) >= 80) : ?>
                                <path d="M12 2l1.9 5.8H20l-4.7 3.4L17.2 17 12 13.9 6.8 17l1.9-5.8L4 7.8h6.1L12 2z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                            <?php elseif ((int) ($winter['score'] ?? 0) >= 50) : ?>
                                <path d="M4 12h16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            <?php else : ?>
                                <path d="M12 3l7 4v5c0 4-2.4 7.5-7 9-4.6-1.5-7-5-7-9V7l7-4z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                            <?php endif; ?>
                        </svg>
                    </div>
                    <div class="score-summary__content">
                        <p class="eyebrow">Canada Winter Score</p>
                        <div class="score-summary__value"><?php echo esc_html((string) ($winter['score'] ?? 0)); ?>/100</div>
                        <p><?php echo esc_html($winter['badge'] ?? ''); ?></p>
                        <p><?php echo esc_html($winter['summary'] ?? ''); ?></p>
                    </div>
                </div>
            </div>
            <div class="panel" id="maintenance-forecast">
                <p class="eyebrow">Maintenance Forecast</p>
                <h4><?php echo esc_html(($maintenance['risk'] ?? 'Pending') . ' Risk'); ?></h4>
                <div class="stack-list">
                    <?php foreach (($maintenance['services'] ?? array()) as $service) : ?>
                        <div class="service-item">
                            <strong><?php echo esc_html($service['label'] ?? 'Upcoming Service'); ?></strong>
                            <span><?php echo esc_html(autogyrus_format_money($service['cost'] ?? 0) . ' | Due in ' . ($service['due_in'] ?? '')); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="prediction-grid prediction-grid--ownership">
                    <?php foreach (($maintenance['ownership_cost_forecast'] ?? array()) as $label => $value) : ?>
                        <div class="prediction-card">
                            <span><?php echo esc_html(ucwords(str_replace('_', ' ', $label))); ?></span>
                            <strong><?php echo esc_html(autogyrus_format_money($value)); ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="two-column-section">
            <div class="panel" id="reliability-score">
                <div class="score-summary score-summary--<?php echo esc_attr(autogyrus_get_score_band((string) ($reliability['score'] ?? 0))); ?>">
                    <div class="score-summary__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" role="img" focusable="false">
                            <?php if ((int) ($reliability['score'] ?? 0) >= 80) : ?>
                                <path d="M12 3l7 3v5c0 4.3-2.6 8.1-7 10-4.4-1.9-7-5.7-7-10V6l7-3z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                                <path d="M9.3 12.1l1.7 1.7 3.7-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            <?php elseif ((int) ($reliability['score'] ?? 0) >= 50) : ?>
                                <path d="M4 12h16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            <?php else : ?>
                                <path d="M12 3l7 4v5c0 4-2.4 7.5-7 9-4.6-1.5-7-5-7-9V7l7-4z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                            <?php endif; ?>
                        </svg>
                    </div>
                    <div class="score-summary__content">
                        <p class="eyebrow">Reliability and Health Score</p>
                        <div class="score-summary__value"><?php echo esc_html((string) ($reliability['score'] ?? 0)); ?>/100</div>
                        <p><?php echo esc_html($reliability['rating'] ?? 'Pending'); ?></p>
                        <p><?php echo esc_html($reliability['summary'] ?? ''); ?></p>
                    </div>
                </div>
                <div class="prediction-grid">
                    <?php foreach (($reliability['categories'] ?? array()) as $label => $value) : ?>
                        <div class="prediction-card">
                            <span><?php echo esc_html($label); ?></span>
                            <strong><?php echo esc_html((string) $value); ?>/100</strong>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="stack-list">
                    <?php foreach (($reliability['known_issues'] ?? array()) as $item) : ?>
                        <div class="service-item">
                            <strong>Known issue</strong>
                            <span><?php echo esc_html($item); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php foreach (($reliability['common_complaints'] ?? array()) as $item) : ?>
                        <div class="service-item">
                            <strong>Complaint</strong>
                            <span><?php echo esc_html($item); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php foreach (($reliability['recall_history'] ?? array()) as $item) : ?>
                        <div class="service-item">
                            <strong>Recall</strong>
                            <span><?php echo esc_html($item); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="panel">
                <h4>Insurance Cost Estimator</h4>
                <div
                    id="insurance-estimator"
                    class="insight-result"
                    data-province="<?php echo esc_attr(in_array((string) get_post_meta($vehicle_id, 'province', true), array('AB', 'ON', 'BC'), true) ? (string) get_post_meta($vehicle_id, 'province', true) : 'AB'); ?>"
                    data-driver-age="35"
                    data-driving-experience="10"
                ></div>
            </div>
        </section>

        <section class="two-column-section">
            <div class="panel">
                <h4>Service History</h4>
                <p><?php echo nl2br(esc_html((string) get_post_meta($vehicle_id, 'service_history', true) ?: 'No service history supplied yet.')); ?></p>
                <?php if (! empty($service_records)) : ?>
                    <div class="stack-list">
                        <?php foreach ($service_records as $record) : ?>
                            <?php if (! empty($record['record_file']['url'])) : ?>
                                <div class="service-item">
                                    <strong><?php echo esc_html($record['record_label'] ?: 'Service Record'); ?></strong>
                                    <span><a href="<?php echo esc_url($record['record_file']['url']); ?>" target="_blank" rel="noopener">Open document</a></span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <h4>Accident History</h4>
                <p><?php echo nl2br(esc_html((string) get_post_meta($vehicle_id, 'accident_history', true) ?: 'No accident history supplied yet.')); ?></p>
            </div>
            <div class="panel">
                <h4>Dealer Information</h4>
                <p><strong><?php echo esc_html(AutoGyrus_Services::get_dealer_name((int) get_post_meta($vehicle_id, 'dealer_user_id', true))); ?></strong></p>
                <p><?php echo esc_html((string) get_post_meta($vehicle_id, 'city', true)); ?>, <?php echo esc_html((string) get_post_meta($vehicle_id, 'province', true)); ?></p>
                <p>Warranty: <?php echo esc_html((string) get_post_meta($vehicle_id, 'warranty', true)); ?></p>
                <p>Financing: <?php echo ! empty(get_post_meta($vehicle_id, 'financing_available', true)) ? 'Available' : 'Not listed'; ?></p>
            </div>
        </section>

        <section class="section-block">
            <div class="panel">
                <div class="section-heading section-heading--compact">
                    <div>
                        <p class="eyebrow">Listing description</p>
                        <h4>Vehicle overview</h4>
                    </div>
                </div>
                <div class="listing-description" data-readmore>
                    <div class="listing-description__content">
                        <?php the_content(); ?>
                    </div>
                    <button type="button" class="text-link listing-description__toggle" data-readmore-toggle aria-expanded="false">Read more</button>
                </div>
            </div>
        </section>
    </div>
</main>
<?php get_footer(); ?>
