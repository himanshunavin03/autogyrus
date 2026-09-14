<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="AutoDrive AI is a Canada-wide AI-powered automotive marketplace for smarter vehicle buying." />
<meta property="og:site_name" content="AutoDrive AI" />
<meta property="og:title" content="<?php echo esc_attr(wp_get_document_title()); ?>" />
<meta property="og:url" content="<?php echo esc_url(is_singular() ? get_permalink() : home_url(add_query_arg(array(), $_SERVER['REQUEST_URI'] ?? '/'))); ?>" />
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
    <div class="container site-header__inner">
        <button class="site-menu-toggle" type="button" aria-controls="site-drawer" aria-expanded="false" aria-label="Open menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="site-logo">
            <span class="site-logo__wordmark">AutoGyrus</span>
            <span class="site-logo__tag">AI vehicle marketplace</span>
        </a>
        <nav class="site-nav">
            <?php wp_nav_menu(array('theme_location' => 'primary', 'container' => false, 'fallback_cb' => false)); ?>
        </nav>
        <div class="site-header__actions">
            <?php if (current_user_can('edit_posts')) : ?>
                <a class="header-link" href="<?php echo esc_url(home_url('/dealer-dashboard')); ?>">Dealer Dashboard</a>
            <?php endif; ?>
            <a class="header-cta" href="<?php echo esc_url(get_post_type_archive_link('vehicle')); ?>">Browse Inventory</a>
        </div>
    </div>
</header>
<div class="site-drawer-overlay" hidden></div>
<aside class="site-drawer" id="site-drawer" aria-hidden="true">
    <div class="site-drawer__header">
        <span>Menu</span>
        <button class="site-drawer__close" type="button" aria-label="Close menu">×</button>
    </div>
    <nav class="site-drawer__nav" aria-label="Mobile menu">
        <?php wp_nav_menu(array('theme_location' => 'primary', 'container' => false, 'fallback_cb' => false)); ?>
    </nav>
    <div class="site-drawer__actions">
        <a class="header-cta" href="<?php echo esc_url(get_post_type_archive_link('vehicle')); ?>">Browse Inventory</a>
        <?php if (current_user_can('edit_posts')) : ?>
            <a class="header-link" href="<?php echo esc_url(home_url('/dealer-dashboard')); ?>">Dealer Dashboard</a>
        <?php endif; ?>
    </div>
</aside>
