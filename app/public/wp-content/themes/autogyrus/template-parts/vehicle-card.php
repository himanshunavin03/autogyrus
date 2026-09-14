<?php
$vehicle_id = get_the_ID();
$highlights = autogyrus_get_vehicle_highlights($vehicle_id);
?>
<article <?php post_class('vehicle-card'); ?>>
    <a class="vehicle-card__image" href="<?php the_permalink(); ?>">
        <?php if (has_post_thumbnail()) : ?>
            <?php the_post_thumbnail('medium_large', array('loading' => 'lazy')); ?>
        <?php else : ?>
            <span>No Photo</span>
        <?php endif; ?>
    </a>
    <div class="vehicle-card__body">
        <p class="vehicle-card__eyebrow"><?php echo esc_html((string) get_post_meta($vehicle_id, 'year', true)); ?> <?php echo esc_html((string) get_post_meta($vehicle_id, 'make', true)); ?></p>
        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <p class="vehicle-card__dealer"><?php echo esc_html(AutoGyrus_Services::get_dealer_name((int) get_post_meta($vehicle_id, 'dealer_user_id', true))); ?></p>
        <div class="vehicle-card__price-row">
            <strong><?php echo esc_html(autogyrus_format_money(get_post_meta($vehicle_id, 'price', true))); ?></strong>
            <span><?php echo esc_html(number_format_i18n((int) get_post_meta($vehicle_id, 'mileage', true), 0)); ?> km</span>
        </div>
        <div class="vehicle-card__meta">
            <span><?php echo esc_html((string) get_post_meta($vehicle_id, 'body_type', true) ?: 'Vehicle'); ?></span>
            <span><?php echo esc_html((string) get_post_meta($vehicle_id, 'province', true) ?: 'Canada'); ?></span>
        </div>
        <div class="vehicle-card__scores">
            <?php foreach ($highlights as $highlight) : ?>
                <?php $score_band = autogyrus_get_score_band($highlight['value']); ?>
                <div class="vehicle-card__score vehicle-card__score--<?php echo esc_attr($score_band); ?>">
                    <span class="vehicle-card__score-icon" aria-hidden="true">
                        <?php if ($score_band === 'high') : ?>
                            <svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M12 2l1.9 5.8H20l-4.7 3.4L17.2 17 12 13.9 6.8 17l1.9-5.8L4 7.8h6.1L12 2z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" /></svg>
                        <?php elseif ($score_band === 'medium') : ?>
                            <svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M12 4v16M4 12h16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /></svg>
                        <?php else : ?>
                            <svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M12 3l7 4v5c0 4-2.4 7.5-7 9-4.6-1.5-7-5-7-9V7l7-4z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" /></svg>
                        <?php endif; ?>
                    </span>
                    <span class="vehicle-card__score-body">
                        <span class="vehicle-card__score-label"><?php echo esc_html($highlight['label']); ?></span>
                        <strong class="vehicle-card__score-value"><?php echo esc_html($highlight['value']); ?></strong>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
        <a class="vehicle-card__cta" href="<?php the_permalink(); ?>">View vehicle</a>
    </div>
</article>
