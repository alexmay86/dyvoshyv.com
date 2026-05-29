<?php
/**
 * Polylang geo redirects:
 *   - Non-UA visitor on a Ukrainian (default-language) URL  → English equivalent.
 *   - UA visitor on an English URL                          → Ukrainian equivalent.
 *
 * Country lookup is header/cookie only — no WC_Geolocation::geolocate_ip(),
 * which can stall on an outbound HTTP call and freeze the page.
 * Loops are prevented by always redirecting to a URL in the *other* language
 * (so the next request no longer matches the redirect condition) and by
 * honouring `blankslate_prefs_saved` after the user picks via the modal.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return bool
 */
function blankslate_pll_geo_preferences_locked() {
	return ! empty( $_COOKIE['blankslate_prefs_saved'] );
}

/**
 * Non-default Polylang language slug (e.g. "en" or "eng"), or '' if not configured.
 *
 * @return string
 */
function blankslate_pll_non_default_lang_slug() {
	static $cached = null;

	if ( null !== $cached ) {
		return $cached;
	}

	$cached = '';

	if ( ! function_exists( 'pll_languages_list' ) || ! function_exists( 'pll_default_language' ) ) {
		return $cached;
	}

	$default = pll_default_language( 'slug' );
	foreach ( pll_languages_list( array( 'fields' => 'slug' ) ) as $slug ) {
		if ( $slug !== $default ) {
			$cached = $slug;
			break;
		}
	}

	return $cached;
}

/**
 * @param string $country_code ISO 3166-1 alpha-2.
 * @return string Language slug. UA → default language (Ukrainian); other → non-default (English).
 */
function blankslate_pll_language_for_country( $country_code ) {
	if ( ! function_exists( 'pll_default_language' ) ) {
		return '';
	}

	if ( 'UA' === strtoupper( (string) $country_code ) ) {
		return (string) pll_default_language( 'slug' );
	}

	return blankslate_pll_non_default_lang_slug();
}

/**
 * Active WMC currency this request (in-memory cookie/session). No external HTTP.
 *
 * @return string e.g. UAH, EUR, USD, or '' if unknown.
 */
function blankslate_pll_geo_currency_fast() {
	static $cached = null;

	if ( null !== $cached ) {
		return $cached;
	}

	$cached = '';

	$wmc = null;
	if ( class_exists( 'WOOMULTI_CURRENCY_Data' ) ) {
		$wmc = WOOMULTI_CURRENCY_Data::get_ins();
	}

	if ( $wmc && method_exists( $wmc, 'getcookie' ) ) {
		$value = $wmc->getcookie( 'wmc_current_currency' );
		if ( is_string( $value ) && '' !== $value ) {
			$cached = strtoupper( sanitize_text_field( $value ) );
			return $cached;
		}
	}

	if ( ! empty( $_COOKIE['wmc_current_currency'] ) ) {
		$cached = strtoupper( sanitize_text_field( wp_unslash( $_COOKIE['wmc_current_currency'] ) ) );
		return $cached;
	}

	// Fallback: Curcy's geo blob. Set in every mode after IP/header detection,
	// even when `wmc_current_currency` is not (e.g. auto_detect = 2 "approximate").
	$wmc_raw = '';
	if ( $wmc && method_exists( $wmc, 'getcookie' ) ) {
		$wmc_raw = (string) $wmc->getcookie( 'wmc_ip_info' );
	}
	if ( '' === $wmc_raw && ! empty( $_COOKIE['wmc_ip_info'] ) ) {
		$wmc_raw = (string) wp_unslash( $_COOKIE['wmc_ip_info'] );
	}
	if ( '' !== $wmc_raw ) {
		$decoded = base64_decode( $wmc_raw, true );
		if ( is_string( $decoded ) ) {
			$info = json_decode( $decoded, true );
			if ( is_array( $info ) && ! empty( $info['currency_code'] ) ) {
				$cached = strtoupper( sanitize_text_field( $info['currency_code'] ) );
				return $cached;
			}
		}
	}

	if ( function_exists( 'wmc_get_woocommerce_currency' ) ) {
		$cached = strtoupper( (string) wmc_get_woocommerce_currency() );
	}

	return $cached;
}

/**
 * Target language slug based on the active WMC currency. Currency = single source
 * of truth because country detection has too many failure modes (WMC may store the
 * WC default `UA` when no real geo lookup ran). UAH → default lang. Other → non-default.
 *
 * @return string Slug or '' if it cannot be determined yet.
 */
function blankslate_pll_geo_target_lang_by_currency() {
	$currency = blankslate_pll_geo_currency_fast();
	if ( '' === $currency ) {
		return '';
	}

	if ( 'UAH' === $currency ) {
		return function_exists( 'pll_default_language' ) ? (string) pll_default_language( 'slug' ) : '';
	}

	return blankslate_pll_non_default_lang_slug();
}

/**
 * Headers + WMC cookie + manual country cookie. No external HTTP.
 *
 * @return string ISO 3166-1 alpha-2 or empty.
 */
function blankslate_pll_geo_country_fast() {
	static $cached = null;

	if ( null !== $cached ) {
		return $cached;
	}

	$cached  = '';
	$headers = array(
		'HTTP_CF_IPCOUNTRY',
		'HTTP_GEOIP_COUNTRY_CODE',
		'GEOIP_COUNTRY_CODE',
		'HTTP_X_COUNTRY_CODE',
	);

	foreach ( $headers as $header ) {
		if ( empty( $_SERVER[ $header ] ) ) {
			continue;
		}
		$code = strtoupper( sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) );
		if ( in_array( $code, array( 'XX', 'T1' ), true ) ) {
			continue;
		}
		$cached = $code;
		return $cached;
	}

	$wmc_raw = '';

	if ( class_exists( 'WOOMULTI_CURRENCY_Data' ) ) {
		$wmc = WOOMULTI_CURRENCY_Data::get_ins();
		if ( $wmc && method_exists( $wmc, 'getcookie' ) ) {
			$wmc_raw = (string) $wmc->getcookie( 'wmc_ip_info' );
		}
	}

	if ( '' === $wmc_raw && ! empty( $_COOKIE['wmc_ip_info'] ) ) {
		$wmc_raw = (string) wp_unslash( $_COOKIE['wmc_ip_info'] );
	}

	if ( '' !== $wmc_raw ) {
		$decoded = base64_decode( $wmc_raw, true );
		if ( is_string( $decoded ) ) {
			$info = json_decode( $decoded, true );
			if ( is_array( $info ) && ! empty( $info['country'] ) ) {
				$cached = strtoupper( sanitize_text_field( $info['country'] ) );
				return $cached;
			}
		}
	}

	if ( ! empty( $_COOKIE['country'] ) ) {
		$cached = strtoupper( sanitize_text_field( wp_unslash( $_COOKIE['country'] ) ) );
	}

	return $cached;
}

/**
 * The Polylang language slug whose URL prefix matches the current request,
 * or '' if the request is not under any Polylang language prefix.
 *
 * @return string
 */
function blankslate_pll_geo_current_url_lang() {
	if ( ! function_exists( 'pll_languages_list' ) || ! function_exists( 'pll_default_language' ) ) {
		return '';
	}

	if ( empty( $_SERVER['REQUEST_URI'] ) ) {
		return '';
	}

	$path = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
	$path = is_string( $path ) ? $path : '/';
	$path = str_replace( '/index.php', '', $path );

	$default_slug = pll_default_language( 'slug' );

	foreach ( pll_languages_list( array( 'fields' => 'slug' ) ) as $slug ) {
		if ( $slug === $default_slug ) {
			continue;
		}
		$prefix = '/' . $slug;
		if ( $path === $prefix || $path === $prefix . '/' || 0 === strpos( $path, $prefix . '/' ) ) {
			return $slug;
		}
	}

	return $default_slug;
}

/**
 * Translated permalink for the currently queried object in the target language,
 * with a sensible fallback to the language's home URL.
 *
 * @param string $target_lang Target language slug.
 * @return string
 */
function blankslate_pll_geo_target_url( $target_lang ) {
	$queried_id = 0;

	if ( is_singular() ) {
		$queried_id = (int) get_queried_object_id();
	} elseif ( is_front_page() || is_home() ) {
		$queried_id = (int) get_option( 'page_on_front' );
	}

	if ( $queried_id && function_exists( 'pll_get_post' ) ) {
		$translated = (int) pll_get_post( $queried_id, $target_lang );
		if ( $translated ) {
			$permalink = get_permalink( $translated );
			if ( is_string( $permalink ) && '' !== $permalink ) {
				return $permalink;
			}
		}
	}

	if ( is_tax() || is_category() || is_tag() ) {
		$term = get_queried_object();
		if ( $term && function_exists( 'pll_get_term' ) ) {
			$translated_term = (int) pll_get_term( $term->term_id, $target_lang );
			if ( $translated_term ) {
				$link = get_term_link( $translated_term, $term->taxonomy );
				if ( is_string( $link ) && '' !== $link ) {
					return $link;
				}
			}
		}
	}

	if ( function_exists( 'pll_home_url' ) ) {
		return (string) pll_home_url( $target_lang );
	}

	return home_url( '/' );
}

/**
 * One-shot session marker. Once set, no more geo redirects this browser session,
 * so the user can freely use the language switcher without being bounced back.
 *
 * @return void
 */
function blankslate_pll_geo_mark_done() {
	if ( headers_sent() ) {
		return;
	}

	$cookie_path   = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
	$cookie_domain = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '';

	setcookie( 'blankslate_geo_lang_done', '1', 0, $cookie_path, $cookie_domain, is_ssl(), false );
	$_COOKIE['blankslate_geo_lang_done'] = '1';
}

/**
 * @return void
 */
function blankslate_pll_geo_redirect() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'DOING_CRON' ) && DOING_CRON ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	if ( ! empty( $_POST ) || ! empty( $_GET ) ) {
		return;
	}

	// Preferences modal saved → always respect manual choice.
	if ( blankslate_pll_geo_preferences_locked() ) {
		return;
	}

	// Already evaluated once this session → don't fight the user.
	if ( ! empty( $_COOKIE['blankslate_geo_lang_done'] ) ) {
		return;
	}

	if ( ! function_exists( 'pll_default_language' ) || ! function_exists( 'pll_languages_list' ) ) {
		return;
	}

	$preferred   = blankslate_pll_geo_target_lang_by_currency();
	$current_url = blankslate_pll_geo_current_url_lang();

	// Currency not resolved yet, or no language URL match → wait until next request.
	if ( '' === $preferred || '' === $current_url ) {
		return;
	}

	if ( $current_url === $preferred ) {
		blankslate_pll_geo_mark_done();
		return;
	}

	$target = blankslate_pll_geo_target_url( $preferred );
	if ( ! is_string( $target ) || '' === $target ) {
		blankslate_pll_geo_mark_done();
		return;
	}

	$scheme  = is_ssl() ? 'https://' : 'http://';
	$host    = isset( $_SERVER['HTTP_HOST'] ) ? wp_unslash( $_SERVER['HTTP_HOST'] ) : '';
	$request = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$here    = untrailingslashit( $scheme . $host . $request );

	if ( untrailingslashit( $target ) === $here ) {
		blankslate_pll_geo_mark_done();
		return;
	}

	blankslate_pll_geo_mark_done();
	nocache_headers();
	wp_safe_redirect( $target, 302 );
	exit;
}

add_action( 'template_redirect', 'blankslate_pll_geo_redirect', 1 );

/**
 * JS fallback redirect. Runs in the browser at every page load, so it works
 * even when HTML is served from a page cache (where template_redirect never fires).
 * Reads cookies live, so cached HTML doesn't matter.
 *
 * @return void
 */
function blankslate_pll_geo_inline_redirect_js() {
	if ( is_admin() || ! function_exists( 'pll_default_language' ) || ! function_exists( 'pll_home_url' ) ) {
		return;
	}

	$default_slug = (string) pll_default_language( 'slug' );
	$other_slug   = blankslate_pll_non_default_lang_slug();
	if ( '' === $other_slug ) {
		return;
	}

	$default_home = (string) pll_home_url( $default_slug );
	$other_home   = (string) pll_home_url( $other_slug );

	$front_id = (int) get_option( 'page_on_front' );
	if ( $front_id && function_exists( 'pll_get_post' ) ) {
		$default_front = (int) pll_get_post( $front_id, $default_slug );
		$other_front   = (int) pll_get_post( $front_id, $other_slug );
		if ( $default_front ) {
			$permalink = get_permalink( $default_front );
			if ( is_string( $permalink ) && '' !== $permalink ) {
				$default_home = $permalink;
			}
		}
		if ( $other_front ) {
			$permalink = get_permalink( $other_front );
			if ( is_string( $permalink ) && '' !== $permalink ) {
				$other_home = $permalink;
			}
		}
	}

	$config = array(
		'defaultSlug' => $default_slug,
		'otherSlug'   => $other_slug,
		'defaultUrl'  => $default_home,
		'otherUrl'    => $other_home,
	);

	// data-* attributes tell WP Rocket / autoptimize / Litespeed not to defer, delay or minify this script.
	?>
<script data-no-optimize="1" data-no-defer="1" data-no-minify="1" data-cfasync="false">
/* blankslate geo language redirect — runs even if HTML is from page cache */
(function () {
	try {
		var cfg = <?php echo wp_json_encode( $config ); ?>;
		if (!cfg || !cfg.defaultSlug || !cfg.otherSlug) { return; }

		function readCookie(name) {
			var m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()\[\]\\\/+^])/g, '\\$1') + '=([^;]*)'));
			return m ? decodeURIComponent(m[1]) : '';
		}
		function setSessionCookie(name, value) {
			document.cookie = name + '=' + value + '; path=/';
		}

		if (window.console && console.log) {
			console.log('[geo-redirect] start, cookies:', document.cookie);
		}

		if (readCookie('blankslate_prefs_saved')) { return; }
		if (readCookie('blankslate_geo_lang_done')) { return; }

		var currency = (readCookie('wmc_current_currency') || '').toUpperCase();
		if (!currency) {
			var raw = readCookie('wmc_ip_info');
			if (raw) {
				try {
					var info = JSON.parse(atob(raw));
					if (info && info.currency_code) { currency = String(info.currency_code).toUpperCase(); }
				} catch (e) {}
			}
		}
		if (window.console && console.log) {
			console.log('[geo-redirect] currency:', currency);
		}
		if (!currency) { return; }

		var targetSlug = (currency === 'UAH') ? cfg.defaultSlug : cfg.otherSlug;
		var path = location.pathname.replace(/\/index\.php/, '');
		if (path !== '/' && path.charAt(path.length - 1) === '/') { path = path.slice(0, -1); }
		if (path === '') { path = '/'; }

		var otherPrefix = '/' + cfg.otherSlug;
		var onOther = (path === otherPrefix) || (path.indexOf(otherPrefix + '/') === 0);
		var currentSlug = onOther ? cfg.otherSlug : cfg.defaultSlug;

		if (window.console && console.log) {
			console.log('[geo-redirect] path:', path, 'currentSlug:', currentSlug, 'targetSlug:', targetSlug);
		}

		if (currentSlug === targetSlug) {
			setSessionCookie('blankslate_geo_lang_done', '1');
			return;
		}

		setSessionCookie('blankslate_geo_lang_done', '1');
		var target = (targetSlug === cfg.defaultSlug) ? cfg.defaultUrl : cfg.otherUrl;
		if (window.console && console.log) {
			console.log('[geo-redirect] redirecting to:', target);
		}
		if (target && target !== location.href) {
			location.replace(target);
		}
	} catch (e) {
		if (window.console && console.warn) { console.warn('[geo-redirect] error:', e); }
	}
})();
</script>
	<?php
}

add_action( 'wp_head', 'blankslate_pll_geo_inline_redirect_js', 1 );
