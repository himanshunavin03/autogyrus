<?php
if (! defined('ABSPATH')) {
    exit;
}

class AutoGyrus_Dashboard
{
    public static function register()
    {
        add_shortcode('autogyrus_dealer_dashboard', [__CLASS__, 'render']);
    }

    public static function render()
    {
        if (! is_user_logged_in()) {
            return '<p>Please log in to view the dealer dashboard.</p>';
        }

        if (! current_user_can('edit_posts')) {
            return '<p>You do not have permission to view the dealer dashboard.</p>';
        }

        $current_user_id = get_current_user_id();
        $query_args = array(
            'post_type' => 'vehicle',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'author' => $current_user_id,
        );

        $vehicle_query = new WP_Query($query_args);

        if (! $vehicle_query->have_posts()) {
            $vehicle_query = new WP_Query(array(
                'post_type' => 'vehicle',
                'post_status' => 'publish',
                'posts_per_page' => 20,
            ));
        }
        $seed_url = wp_nonce_url(admin_url('admin-post.php?action=autogyrus_seed_demo'), 'autogyrus_seed_demo');

        ob_start(); ?>
        <section class="autodrive-dashboard">
            <div class="dashboard-heading">
                <div>
                    <p class="eyebrow">Dealer Workspace</p>
                    <h3>Inventory, leads, and AI performance</h3>
                </div>
                <?php if (current_user_can('manage_options')) : ?>
                    <a class="button" href="<?php echo esc_url($seed_url); ?>">Seed 50 Demo Vehicles</a>
                <?php endif; ?>
            </div>
            <div class="dashboard-grid">
                <article class="panel">
                    <h4>Inventory</h4>
                    <p><?php echo esc_html((string) $vehicle_query->found_posts); ?> live vehicles</p>
                    <p><a href="<?php echo esc_url(admin_url('post-new.php?post_type=vehicle')); ?>">Add Vehicle</a></p>
                    <p><a href="<?php echo esc_url(admin_url('edit.php?post_type=vehicle')); ?>">Manage Inventory</a></p>
                </article>
                <article class="panel">
                    <h4>Lead Management</h4>
                    <p><a href="<?php echo esc_url(admin_url('edit.php?post_type=lead')); ?>">View Leads</a></p>
                    <p>Track contact requests, test drives, and high-intent buyers.</p>
                </article>
                <article class="panel">
                    <h4>AI Insights</h4>
                    <p>Highlight best resale vehicles, strongest winter performers, and most reliable stock.</p>
                </article>
            </div>
            <div class="panel">
                <h4>Your Vehicles</h4>
                <div class="dashboard-table-wrap">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Vehicle</th>
                                <th>Price</th>
                                <th>Winter</th>
                                <th>Reliability</th>
                                <th>Future Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($vehicle_query->have_posts()) : ?>
                                <?php while ($vehicle_query->have_posts()) : $vehicle_query->the_post(); ?>
                                    <tr>
                                        <td><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></td>
                                        <td><?php echo esc_html('$' . number_format_i18n((float) get_post_meta(get_the_ID(), 'price', true), 0)); ?></td>
                                        <td><?php echo esc_html((string) get_post_meta(get_the_ID(), 'winter_score', true)); ?></td>
                                        <td><?php echo esc_html((string) get_post_meta(get_the_ID(), 'reliability_score', true)); ?></td>
                                        <td><?php echo esc_html((string) get_post_meta(get_the_ID(), 'future_value_rating', true)); ?></td>
                                    </tr>
                                <?php endwhile; wp_reset_postdata(); ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="5">No dealer-linked inventory yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}
