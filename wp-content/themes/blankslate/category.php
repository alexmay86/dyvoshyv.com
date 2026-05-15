<?php get_header(); ?>
<?php $current_cat = get_queried_object();
$cat_bg = get_field('cat_bg', $current_cat);
if($cat_bg) echo wp_get_attachment_image( $cat_bg, 'full', "", array( "alt" => single_term_title("", false), "class" => "category-thumbnail" )); ?>
<div class="category-breadcrumbs"><?php woocommerce_breadcrumb(); ?></div>
<main class="category-content">
<h1><?php echo (get_field('cat_title', $current_cat) ? get_field('cat_title', $current_cat) : single_term_title("", false)); ?></h1>
<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
<?php get_template_part( 'loop' ); ?>
<?php endwhile; endif; ?>
<?php global $wp_query;
 if($wp_query->max_num_pages > 1) { ?>
    <div class="woocommerce"><nav class="woocommerce-pagination blog-pagination" aria-label="<?php pll_e('Blog Pagination'); ?>">
        <?php echo paginate_links([
            'total'   => $wp_query->max_num_pages,
            'current' => max(1, get_query_var('paged')),
            'prev_text' => '<svg width="7" height="12" viewBox="0 0 7 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.01297 1.70437L6.01287 1.70447L1.99446 5.99527L6.01297 10.2946C6.19568 10.4901 6.19568 10.8036 6.01297 10.9991C5.82485 11.2003 5.51492 11.2003 5.32681 10.9991L0.987033 6.35602C0.895251 6.25782 0.849999 6.13447 0.849999 6.00378C0.849999 5.88357 0.894063 5.75101 0.987033 5.65154L5.32539 1.01002C5.51339 0.798351 5.82465 0.798411 6.01297 0.999891C6.19568 1.19537 6.19568 1.50889 6.01297 1.70437Z" fill="#353D3B" stroke="#353D3B" stroke-width="0.3"/></svg>',
            'next_text' => '<svg width="7" height="12" viewBox="0 0 7 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.987034 1.70437L0.987134 1.70447L5.00554 5.99527L0.987035 10.2946C0.804323 10.4901 0.804323 10.8036 0.987035 10.9991C1.17515 11.2003 1.48508 11.2003 1.67319 10.9991L6.01297 6.35602C6.10475 6.25782 6.15 6.13447 6.15 6.00378C6.15 5.88357 6.10594 5.75101 6.01297 5.65154L1.67461 1.01002C1.48661 0.798351 1.17535 0.798411 0.987034 0.999891C0.804322 1.19537 0.804322 1.50889 0.987034 1.70437Z" fill="#353D3B" stroke="#353D3B" stroke-width="0.3"/></svg>',
            'type'      => 'list',
            'end_size'  => 3,
            'mid_size'  => 3,
        ]); ?>
    </nav></div>
<?php } ?>
</main>
<?php get_footer(); ?>