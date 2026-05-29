<?php
/**
 * Shared visitor country + currency (WooCommerce Multi Currency geo, cookies, WC session).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return string ISO 3166-1 alpha-2 or empty.
 */
function blankslate_get_visitor_country_code() {
	static $cached = null;

	if ( null !== $cached ) {
		return $cached;
	}

	$cached = '';

	// Saved preferences override geo (country cookie is set together with blankslate_prefs_saved).
	if ( ! empty( $_COOKIE['blankslate_prefs_saved'] ) && ! empty( $_COOKIE['country'] ) ) {
		$cached = strtoupper( sanitize_text_field( wp_unslash( $_COOKIE['country'] ) ) );
		return $cached;
	}

	// Geo before WC customer — WC defaults to UA and blocked language geo while WMC currency still worked.
	$cached = blankslate_detect_country_from_geo_sources();

	if ( '' === $cached && function_exists( 'WC' ) && WC()->customer ) {
		$wc_country = WC()->customer->get_billing_country();
		if ( ! $wc_country ) {
			$wc_country = WC()->customer->get_shipping_country();
		}
		if ( $wc_country ) {
			$cached = strtoupper( sanitize_text_field( $wc_country ) );
			return $cached;
		}
	}

	if ( '' === $cached ) {
		$default = get_option( 'woocommerce_default_country', 'UA' );
		if ( is_string( $default ) && '' !== $default ) {
			$cached = strtoupper( sanitize_text_field( strtok( $default, ':' ) ) );
		}
	}

	return $cached;
}

/**
 * Same detection order as WooCommerce Multi Currency.
 *
 * @return string
 */
function blankslate_detect_country_from_geo_sources() {
	$code = blankslate_country_from_http_headers();

	if ( '' === $code && class_exists( 'WOOMULTI_CURRENCY_Data' ) ) {
		$settings = WOOMULTI_CURRENCY_Data::get_ins();
		if ( $settings && method_exists( $settings, 'getcookie' ) ) {
			$raw = $settings->getcookie( 'wmc_ip_info' );
			if ( $raw ) {
				$ip_info = json_decode( base64_decode( $raw ), true );
				if ( ! empty( $ip_info['country'] ) ) {
					$code = strtoupper( sanitize_text_field( $ip_info['country'] ) );
				}
			}
		}
	}

	if ( '' === $code && class_exists( 'WC_Geolocation' ) ) {
		$geo = ( new WC_Geolocation() )->geolocate_ip();
		if ( ! empty( $geo['country'] ) ) {
			$code = strtoupper( sanitize_text_field( $geo['country'] ) );
		}
	}

	return apply_filters( 'blankslate_visitor_country_code', $code );
}

/**
 * @return string
 */
function blankslate_country_from_http_headers() {
	if ( class_exists( 'WOOMULTI_CURRENCY_Data' ) && method_exists( 'WOOMULTI_CURRENCY_Data', 'country_code_key_from_headers' ) ) {
		$headers = WOOMULTI_CURRENCY_Data::country_code_key_from_headers();
	} else {
		$headers = array(
			'HTTP_CF_IPCOUNTRY',
			'HTTP_GEOIP_COUNTRY_CODE',
			'GEOIP_COUNTRY_CODE',
			'HTTP_X_COUNTRY_CODE',
		);
	}

	foreach ( $headers as $header ) {
		if ( empty( $_SERVER[ $header ] ) ) {
			continue;
		}

		$code = strtoupper( sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) );
		if ( in_array( $code, array( 'XX', 'T1' ), true ) ) {
			continue;
		}

		return $code;
	}

	return '';
}

/**
 * Active WooCommerce Multi Currency code for display (preferences modal, etc.).
 *
 * @return string e.g. UAH, EUR, USD
 */
function blankslate_get_visitor_currency_code() {
	$settings = class_exists( 'WOOMULTI_CURRENCY_Data' ) ? WOOMULTI_CURRENCY_Data::get_ins() : null;

	if ( $settings && method_exists( $settings, 'getcookie' ) ) {
		$cookie = $settings->getcookie( 'wmc_current_currency' );
		if ( $cookie ) {
			return strtoupper( sanitize_text_field( $cookie ) );
		}
	}

	if ( ! empty( $_COOKIE['wmc_current_currency'] ) ) {
		return strtoupper( sanitize_text_field( wp_unslash( $_COOKIE['wmc_current_currency'] ) ) );
	}

	$wmc_raw = '';
	if ( $settings && method_exists( $settings, 'getcookie' ) ) {
		$wmc_raw = (string) $settings->getcookie( 'wmc_ip_info' );
	}
	if ( '' === $wmc_raw && ! empty( $_COOKIE['wmc_ip_info'] ) ) {
		$wmc_raw = (string) wp_unslash( $_COOKIE['wmc_ip_info'] );
	}
	if ( '' !== $wmc_raw ) {
		$decoded = base64_decode( $wmc_raw, true );
		if ( is_string( $decoded ) ) {
			$info = json_decode( $decoded, true );
			if ( is_array( $info ) && ! empty( $info['currency_code'] ) ) {
				return strtoupper( sanitize_text_field( $info['currency_code'] ) );
			}
		}
	}

	if ( function_exists( 'wmc_get_woocommerce_currency' ) ) {
		return strtoupper( (string) wmc_get_woocommerce_currency() );
	}

	return function_exists( 'get_woocommerce_currency' ) ? strtoupper( get_woocommerce_currency() ) : '';
}

/**
 * Persist detected country on the WC customer (guests) so checkout/preferences stay in sync.
 *
 * @return void
 */
function blankslate_sync_wc_customer_country_from_geo() {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}

	if ( ! empty( $_COOKIE['blankslate_prefs_saved'] ) ) {
		return;
	}

	if ( ! function_exists( 'WC' ) || ! WC()->customer ) {
		return;
	}

	$country = blankslate_detect_country_from_geo_sources();
	if ( '' === $country ) {
		return;
	}

	$current = WC()->customer->get_billing_country();
	if ( $current === $country ) {
		return;
	}

	WC()->customer->set_billing_country( $country );
	WC()->customer->set_shipping_country( $country );
}

add_action( 'init', 'blankslate_sync_wc_customer_country_from_geo', 11 );

/**
 * Cap outbound geolocation HTTP calls so a slow third-party never freezes the page.
 * WC_Geolocation hits ipinfo.io / ip-api.com / geoplugin.net / etc. by default with
 * a 30s timeout. We reduce it to 2s for those hosts only — they either answer fast
 * or we move on with no country (better than a multi-minute hang).
 *
 * @param array  $args HTTP request args.
 * @param string $url  Request URL.
 * @return array
 */
function blankslate_cap_geolocation_http_timeout( $args, $url ) {
	if ( ! is_string( $url ) || '' === $url ) {
		return $args;
	}

	$geo_hosts = array(
		'ipinfo.io',
		'ip-api.com',
		'geoplugin.net',
		'www.geoplugin.net',
		'ipapi.co',
		'api.hostip.info',
		'freegeoip.app',
		'freegeoip.net',
		'ip2c.org',
	);

	$host = wp_parse_url( $url, PHP_URL_HOST );
	if ( ! is_string( $host ) || '' === $host ) {
		return $args;
	}

	$host = strtolower( $host );
	foreach ( $geo_hosts as $needle ) {
		if ( $host === $needle || substr( $host, - ( strlen( $needle ) + 1 ) ) === '.' . $needle ) {
			$args['timeout']     = 2;
			$args['redirection'] = 1;
			$args['blocking']    = true;
			return $args;
		}
	}

	return $args;
}

add_filter( 'http_request_args', 'blankslate_cap_geolocation_http_timeout', 10, 2 );
