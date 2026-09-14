<?php
get_header();

$featured_vehicles = new WP_Query(array(
    'post_type' => 'vehicle',
    'post_status' => 'publish',
    'posts_per_page' => 8,
    'meta_key' => 'winter_score',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
));
?>
<section class="hero-shell">
    <div class="container">
        <div class="hero-layout">
            <div class="hero-primary">
                <p class="eyebrow">Used car marketplace</p>
                <h2>AI-powered vehicle buying intelligence for faster, more informed decisions.</h2>
                <p class="hero-lead">Search by AI or keywords, compare pricing, and evaluate vehicles with clearer context before you shortlist.</p>
                <div class="hero-primary__art" aria-hidden="true">
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/hero-primary-art.png'); ?>" alt="" loading="eager" decoding="async">
                </div>
                <form class="hero-keyword-search" method="get" action="<?php echo esc_url(get_post_type_archive_link('vehicle')); ?>">
                    <p class="eyebrow">Keyword search</p>
                    <div class="hero-keyword-search__grid">
                        <label>
                            Make
                            <input type="text" name="make" placeholder="Toyota">
                        </label>
                        <label>
                            Model
                            <input type="text" name="model" placeholder="RAV4">
                        </label>
                        <label>
                            City / ZIP
                            <input type="text" name="location" placeholder="Calgary or T2P 1J9">
                        </label>
                        <label>
                            Distance
                            <select name="distance">
                                <option value="">Any</option>
                                <option value="25">25 km</option>
                                <option value="50">50 km</option>
                                <option value="100">100 km</option>
                                <option value="250">250 km</option>
                            </select>
                        </label>
                    </div>
                    <div class="hero-keyword-search__actions">
                        <button type="submit" class="button hero-keyword-search__submit">Search vehicle</button>
                        <a class="button button--ghost" href="#featured-inventory">View featured vehicles</a>
                    </div>
                </form>
            </div>
            <div class="hero-search-panel">
                <div class="hero-search-panel__inner">
                    <p class="eyebrow">AI Search</p>
                    <h3>Describe the vehicle you want</h3>
                    <p class="hero-search-panel__copy">Use plain English to search by budget, body style, mileage, winter needs, or resale preference.</p>
                    <?php echo do_shortcode('[autogyrus_search]'); ?>
                </div>
            </div>
        </div>
        <div class="quick-links">
            <a href="<?php echo esc_url(add_query_arg('body_type', 'SUV', get_post_type_archive_link('vehicle'))); ?>">SUV</a>
            <a href="<?php echo esc_url(add_query_arg('body_type', 'Sedan', get_post_type_archive_link('vehicle'))); ?>">Sedan</a>
            <a href="<?php echo esc_url(add_query_arg('body_type', 'Truck', get_post_type_archive_link('vehicle'))); ?>">Truck</a>
            <a href="<?php echo esc_url(add_query_arg('body_type', 'Hatchback', get_post_type_archive_link('vehicle'))); ?>">Hatchback</a>
            <a href="<?php echo esc_url(add_query_arg('body_type', 'Van', get_post_type_archive_link('vehicle'))); ?>">Van</a>
            <a href="<?php echo esc_url(get_post_type_archive_link('vehicle')); ?>">All inventory</a>
        </div>
    </div>
</section>

<section class="section section--compact">
    <div class="container">
        <div class="trust-strip">
            <article class="trust-strip__item">
                <strong>Future value</strong>
                <span>Estimate how well a vehicle may hold value over time.</span>
            </article>
            <article class="trust-strip__item">
                <strong>Winter readiness</strong>
                <span>Surface cold-weather fit for Canadian drivers at a glance.</span>
            </article>
            <article class="trust-strip__item">
                <strong>Ownership outlook</strong>
                <span>Review maintenance and insurance context before buying.</span>
            </article>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Why AutoGyrus</p>
                <h3>A cleaner, more practical way to shop used vehicles</h3>
            </div>
        </div>
        <div class="feature-cards">
            <article class="feature-card">
                <h4>Search faster</h4>
                <p>Use AI search or standard filters to get to relevant inventory quickly.</p>
            </article>
            <article class="feature-card">
                <h4>Compare smarter</h4>
                <p>Price, mileage, dealer info, and AI insight badges stay visible on every listing card.</p>
            </article>
            <article class="feature-card">
                <h4>Decide sooner</h4>
                <p>Reliability, winter fit, maintenance, and resale guidance reduce uncertainty earlier in the process.</p>
            </article>
            <article class="feature-card">
                <h4>Dealer-ready listings</h4>
                <p>Inventory pages feel more complete and more useful without changing your core data model.</p>
            </article>
        </div>
    </div>
</section>

<section class="section section--soft" id="featured-inventory">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Featured inventory</p>
                <h3>Browse vehicles with AI insights already attached</h3>
            </div>
            <a class="text-link" href="<?php echo esc_url(get_post_type_archive_link('vehicle')); ?>">See all vehicles</a>
        </div>
        <div class="listing-grid listing-grid--featured">
            <?php if ($featured_vehicles->have_posts()) : ?>
                <?php while ($featured_vehicles->have_posts()) : $featured_vehicles->the_post(); ?>
                    <?php get_template_part('template-parts/vehicle', 'card'); ?>
                <?php endwhile; wp_reset_postdata(); ?>
            <?php else : ?>
                <article class="panel">
                    <h4>No vehicles yet</h4>
                    <p>Use the dealer dashboard or demo seed tool to add inventory.</p>
                </article>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="dealer-banner">
            <div>
                <p class="eyebrow">For dealers</p>
                <h3>Keep the AI features. Present them in a more professional storefront.</h3>
                <p>AutoGyrus helps dealers publish cleaner inventory pages while preserving the AI tools that make the platform different.</p>
            </div>
            <div class="dealer-banner__actions">
                <?php if (current_user_can('edit_posts')) : ?>
                    <a class="button" href="<?php echo esc_url(home_url('/dealer-dashboard')); ?>">Open dealer dashboard</a>
                <?php endif; ?>
                <a class="button button--ghost" href="<?php echo esc_url(get_post_type_archive_link('vehicle')); ?>">Preview buyer experience</a>
            </div>
        </div>
    </div>
</section>
<?php get_footer(); ?>
