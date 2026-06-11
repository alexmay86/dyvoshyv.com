<?php
/**
 * Polylang for WooCommerce: keep product prices independent per language.
 * Applies to simple products and variations. Curcy fixed prices are not synced by PLLWC anyway.
 */

/**
 * @return string[]
 */
function blankslate_pllwc_price_metas_to_exclude() {
	return array(
		'_price',
		'_regular_price',
		'_sale_price',
	);
}

/**
 * @param string[] $metas
 * @return string[]
 */
function blankslate_pllwc_exclude_price_from_copy( $metas, $sync, $from, $to, $lang ) {
	return array_diff_key( $metas, array_flip( blankslate_pllwc_price_metas_to_exclude() ) );
}
add_filter( 'pllwc_copy_post_metas', 'blankslate_pllwc_exclude_price_from_copy', 20, 5 );

/**
 * @param string[] $metas
 * @return string[]
 */
function blankslate_pllwc_exclude_price_from_post_metas( $metas, $sync, $from, $to, $lang ) {
	if ( ! in_array( get_post_type( $from ), array( 'product', 'product_variation' ), true ) ) {
		return $metas;
	}

	return array_values( array_diff( $metas, blankslate_pllwc_price_metas_to_exclude() ) );
}
add_filter( 'pll_copy_post_metas', 'blankslate_pllwc_exclude_price_from_post_metas', 20, 5 );
