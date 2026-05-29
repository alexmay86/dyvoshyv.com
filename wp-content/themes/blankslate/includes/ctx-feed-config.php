<?php
/**
 * Which CTX Feed exports use EUR (CURCY fixed prices).
 *
 * Feeds listed in $eur_feed_option_names always export EUR prices.
 * Other feeds keep the store default currency (UAH).
 */

add_filter(
	'dyvoshyv_ctx_feed_is_eur_feed',
	static function ( $is_eur, $config ) {
		if ( $is_eur || ! is_object( $config ) ) {
			return $is_eur;
		}

		// Slugs with or without wf_feed_ prefix.
		$eur_feed_option_names = array(
			'facebookfeed-2',
			'wf_feed_facebookfeed-2',
		);

		$identifiers = array();

		if ( method_exists( $config, 'get_feed_option_name' ) ) {
			$identifiers[] = $config->get_feed_option_name( false );
			$identifiers[] = $config->get_feed_option_name( true );
		}

		if ( method_exists( $config, 'get_feed_name' ) ) {
			$identifiers[] = $config->get_feed_name();
		}

		if ( isset( $config->feed_info['option_name'] ) ) {
			$identifiers[] = $config->feed_info['option_name'];
		}

		foreach ( $identifiers as $identifier ) {
			if ( ! is_string( $identifier ) || '' === $identifier ) {
				continue;
			}

			$short = str_replace( array( 'wf_feed_', 'wf_config' ), '', $identifier );

			if ( in_array( $identifier, $eur_feed_option_names, true ) || in_array( $short, $eur_feed_option_names, true ) ) {
				return true;
			}
		}

		return $is_eur;
	},
	10,
	2
);
