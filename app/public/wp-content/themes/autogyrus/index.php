<?php get_header(); ?>
<main class="container" style="padding:40px 0;">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <article <?php post_class('panel'); ?>>
            <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
            <?php the_excerpt(); ?>
        </article>
    <?php endwhile; endif; ?>
</main>
<?php get_footer(); ?>
