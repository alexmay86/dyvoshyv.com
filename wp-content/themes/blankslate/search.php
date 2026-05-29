<?php get_header(); ?>
<article class="search-section">
    <header class="header">
        <h1 class="entry-title" itemprop="name">
            <?php pll_e( 'Search Results for:' ); ?>
            <div class="search-query">'<?php echo get_search_query(); ?>'</div>
        </h1>
    </header>
    <?php global $wp_query;
    if ( have_posts() ) { ?>
        <div class="search-count"><?php echo $wp_query->found_posts . ' ' . declension((int)$wp_query->found_posts, array(pll__('товар'), pll__('товари'), pll__('товарів'))); ?></div>
        <?php get_search_form();
        woocommerce_product_loop_start();
        while ( have_posts() ) {
            the_post();
            do_action( 'woocommerce_shop_loop' );
            wc_get_template_part( 'content', 'product' );
        }
        woocommerce_product_loop_end();
        if($wp_query->max_num_pages > 1) { ?>
            <div class="woocommerce"><nav class="woocommerce-pagination" aria-label="<?php pll_e('Product Pagination'); ?>">
                <?php echo paginate_links([
                    'total'   => $wp_query->max_num_pages,
                    'current' => max(1, get_query_var('paged')),
                    'prev_text' => is_rtl() ? '&rarr;' : '&larr;',
                    'next_text' => is_rtl() ? '&larr;' : '&rarr;',
                    'type'      => 'list',
                    'end_size'  => 3,
                    'mid_size'  => 3,
                ]); ?>
            </nav></div>
        <?php }
    } else { ?>
        <div class="entry-content not-found" itemprop="mainContentOfPage">
            <div class="not-found__text"><?php pll_e( 'No item found, please search again' ); ?></div>
            <?php get_search_form(); ?>
            <a class="search-link" href="<?php echo get_permalink(pll_get_post(6751) ); ?>"><?php pll_e('Start shopping'); ?> <svg width="7" height="12" viewBox="0 0 7 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.987034 1.70437L0.987134 1.70447L5.00554 5.99527L0.987035 10.2946C0.804323 10.4901 0.804323 10.8036 0.987035 10.9991C1.17515 11.2003 1.48508 11.2003 1.67319 10.9991L6.01297 6.35602C6.10475 6.25782 6.15 6.13447 6.15 6.00378C6.15 5.88357 6.10594 5.75101 6.01297 5.65154L1.67461 1.01002C1.48661 0.798351 1.17535 0.798411 0.987034 0.999891C0.804322 1.19537 0.804322 1.50889 0.987034 1.70437Z" fill="#353D3B" stroke="#353D3B" stroke-width="0.3"/></svg></a>
        </div>
    <?php } ?>
</article>
<?php get_footer(); ?>