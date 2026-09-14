<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <p class="eyebrow">AutoGyrus</p>
            <h4>Professional used vehicle shopping supported by AI-driven insights.</h4>
        </div>
        <div>
            <p><a href="<?php echo esc_url(home_url('/')); ?>">Home</a></p>
            <p><a href="<?php echo esc_url(get_post_type_archive_link('vehicle')); ?>">Inventory</a></p>
            <?php if (current_user_can('edit_posts')) : ?>
                <p><a href="<?php echo esc_url(home_url('/dealer-dashboard')); ?>">Dealer Dashboard</a></p>
            <?php endif; ?>
        </div>
        <div>
            <p>Future value, winter readiness, maintenance, reliability, and insurance tools remain built into the buying experience.</p>
        </div>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
