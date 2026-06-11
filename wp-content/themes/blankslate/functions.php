<?php
add_action( 'after_setup_theme', 'blankslate_setup' );
function blankslate_setup() {
load_theme_textdomain( 'blankslate', get_template_directory() . '/languages' );
add_theme_support( 'title-tag' );
add_theme_support( 'post-thumbnails' );
add_theme_support( 'responsive-embeds' );
add_theme_support( 'automatic-feed-links' );
add_theme_support( 'html5', array( 'search-form', 'navigation-widgets' ) );
add_theme_support( 'appearance-tools' );
add_theme_support( 'woocommerce' );
global $content_width;
if ( !isset( $content_width ) ) { $content_width = 1920; }
register_nav_menus( array( 'main-menu' => 'Main Menu' ) );
}

/**
 * WooCommerce PhotoSwipe is only needed on the single product gallery (lightbox).
 * Core WC already limits enqueue, but Elementor EA Product Slider and similar call
 * wp_enqueue_script from wp_footer for quick view — strip that everywhere else.
 *
 * @return bool
 */
function blankslate_needs_wc_photoswipe() {
	if ( apply_filters( 'blankslate_force_wc_photoswipe', false ) ) {
		return true;
	}
	if ( ! function_exists( 'is_product' ) ) {
		return false;
	}
	if ( class_exists( '\Elementor\Plugin' ) ) {
		$elementor = \Elementor\Plugin::$instance;
		if ( isset( $elementor->editor ) && method_exists( $elementor->editor, 'is_edit_mode' ) && $elementor->editor->is_edit_mode() ) {
			return true;
		}
	}
	return is_product();
}

/**
 * Remove every woocommerce_photoswipe callback (WC uses 10, EA uses 15; others may differ).
 *
 * @return void
 */
function blankslate_remove_woocommerce_photoswipe_from_footer() {
	global $wp_filter;
	if ( ! isset( $wp_filter['wp_footer'] ) || ! $wp_filter['wp_footer'] instanceof WP_Hook ) {
		return;
	}
	$remove_pr = array();
	foreach ( $wp_filter['wp_footer']->callbacks as $priority => $callbacks ) {
		foreach ( $callbacks as $cb ) {
			if ( isset( $cb['function'] ) && 'woocommerce_photoswipe' === $cb['function'] ) {
				$remove_pr[] = (int) $priority;
			}
		}
	}
	foreach ( array_unique( $remove_pr ) as $p ) {
		remove_action( 'wp_footer', 'woocommerce_photoswipe', $p );
	}
}

/**
 * @return void
 */
function blankslate_dequeue_wc_photoswipe_assets() {
	if ( is_admin() || blankslate_needs_wc_photoswipe() ) {
		return;
	}

	blankslate_remove_woocommerce_photoswipe_from_footer();

	wp_dequeue_style( 'photoswipe' );
	wp_dequeue_style( 'photoswipe-default-skin' );
	wp_dequeue_script( 'photoswipe-ui-default' );
	wp_dequeue_script( 'photoswipe' );

	wp_deregister_style( 'photoswipe' );
	wp_deregister_style( 'photoswipe-default-skin' );
	wp_deregister_script( 'photoswipe-ui-default' );
	wp_deregister_script( 'photoswipe' );
}

add_action( 'wp_enqueue_scripts', 'blankslate_dequeue_wc_photoswipe_assets', 100 );
add_action( 'wp_enqueue_scripts', 'blankslate_dequeue_wc_photoswipe_assets', 1000 );
add_action( 'wp_footer', 'blankslate_dequeue_wc_photoswipe_assets', 19 );

/**
 * Last-resort: block PhotoSwipe tags if something re-enqueues after our dequeue (or bundled output still uses WC URLs).
 *
 * @param string $tag
 * @param string $handle
 * @param string $href
 * @return string
 */
function blankslate_strip_photoswipe_style_tag( $tag, $handle, $href ) {
	if ( is_admin() || blankslate_needs_wc_photoswipe() ) {
		return $tag;
	}
	if ( in_array( $handle, array( 'photoswipe', 'photoswipe-default-skin' ), true ) ) {
		return '';
	}
	if ( is_string( $href ) && preg_match( '#woocommerce/assets/css/photoswipe/#', $href ) ) {
		return '';
	}
	return $tag;
}
add_filter( 'style_loader_tag', 'blankslate_strip_photoswipe_style_tag', 99, 3 );

/**
 * @param string $tag
 * @param string $handle
 * @param string $src
 * @return string
 */
function blankslate_strip_photoswipe_script_tag( $tag, $handle, $src ) {
	if ( is_admin() || blankslate_needs_wc_photoswipe() ) {
		return $tag;
	}
	if ( in_array( $handle, array( 'photoswipe', 'photoswipe-ui-default' ), true ) ) {
		return '';
	}
	if ( is_string( $src ) && preg_match( '#woocommerce/assets/js/photoswipe/#', $src ) ) {
		return '';
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'blankslate_strip_photoswipe_script_tag', 99, 3 );

/**
 * WooCommerce Products Filter (Husky): load CSS/JS only on main Shop and product category archives.
 * Extensions enqueue under the same plugin path; strip by URL on other views.
 *
 * Use add_filter( 'blankslate_force_woof_assets', '__return_true' ) if the filter appears elsewhere (shortcodes).
 *
 * @return bool
 */
function blankslate_needs_woof_catalog_assets() {
	if ( apply_filters( 'blankslate_force_woof_assets', false ) ) {
		return true;
	}
	if ( ! function_exists( 'is_shop' ) ) {
		return false;
	}
	return is_shop() || is_product_category();
}

/**
 * @return void
 */
function blankslate_detach_woof_outside_catalog() {
	if ( is_admin() || blankslate_needs_woof_catalog_assets() ) {
		return;
	}
	if ( ! isset( $GLOBALS['WOOF'] ) || ! is_object( $GLOBALS['WOOF'] ) ) {
		return;
	}
	$woof = $GLOBALS['WOOF'];
	remove_action( 'wp_enqueue_scripts', array( $woof, 'enqueue_scripts_styles' ) );
	remove_action( 'wp_head', array( $woof, 'wp_load_js' ), 999 );
	remove_action( 'wp_footer', array( $woof, 'wp_load_js' ), 11 );
	remove_action( 'wp_footer', array( $woof, 'wp_footer' ), 999 );
}
add_action( 'wp', 'blankslate_detach_woof_outside_catalog', 0 );

/**
 * Dequeue any Husky assets still queued (extensions register their own hooks).
 *
 * @return void
 */
function blankslate_dequeue_woof_plugin_queued_assets() {
	if ( is_admin() || blankslate_needs_woof_catalog_assets() ) {
		return;
	}
	global $wp_scripts, $wp_styles;
	$needle = 'woocommerce-products-filter';

	if ( $wp_scripts instanceof WP_Scripts && ! empty( $wp_scripts->queue ) ) {
		foreach ( array_values( $wp_scripts->queue ) as $handle ) {
			if ( ! isset( $wp_scripts->registered[ $handle ] ) ) {
				continue;
			}
			$src = $wp_scripts->registered[ $handle ]->src;
			if ( is_string( $src ) && strpos( $src, $needle ) !== false ) {
				wp_dequeue_script( $handle );
			}
		}
	}
	if ( $wp_styles instanceof WP_Styles && ! empty( $wp_styles->queue ) ) {
		foreach ( array_values( $wp_styles->queue ) as $handle ) {
			if ( ! isset( $wp_styles->registered[ $handle ] ) ) {
				continue;
			}
			$src = $wp_styles->registered[ $handle ]->src;
			if ( is_string( $src ) && strpos( $src, $needle ) !== false ) {
				wp_dequeue_style( $handle );
			}
		}
	}
}
add_action( 'wp_enqueue_scripts', 'blankslate_dequeue_woof_plugin_queued_assets', 99999 );
add_action( 'wp_head', 'blankslate_dequeue_woof_plugin_queued_assets', 1000 );
add_action( 'wp_footer', 'blankslate_dequeue_woof_plugin_queued_assets', 12 );

/**
 * @param string $tag
 * @param string $handle
 * @param string $href
 * @return string
 */
function blankslate_strip_woof_style_tag( $tag, $handle, $href ) {
	if ( is_admin() || blankslate_needs_woof_catalog_assets() ) {
		return $tag;
	}
	if ( is_string( $href ) && strpos( $href, 'woocommerce-products-filter' ) !== false ) {
		return '';
	}
	return $tag;
}
add_filter( 'style_loader_tag', 'blankslate_strip_woof_style_tag', 100, 3 );

/**
 * @param string $tag
 * @param string $handle
 * @param string $src
 * @return string
 */
function blankslate_strip_woof_script_tag( $tag, $handle, $src ) {
	if ( is_admin() || blankslate_needs_woof_catalog_assets() ) {
		return $tag;
	}
	if ( is_string( $src ) && strpos( $src, 'woocommerce-products-filter' ) !== false ) {
		return '';
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'blankslate_strip_woof_script_tag', 100, 3 );

/**
 * Elementor Nav Menu + SmartMenus add submenu aria-* on anchor tags.
 * For parent menu items this can fail aria-role checks when interpreted as plain links.
 * Set a role compatible with expanded/controls/haspopup at HTML generation stage.
 */
function blankslate_elementor_nav_parent_link_role( $atts, $item, $args, $depth ) {
	if ( is_admin() ) {
		return $atts;
	}

	$menu_class = isset( $args->menu_class ) ? (string) $args->menu_class : '';
	if ( false === strpos( $menu_class, 'elementor-nav-menu' ) ) {
		return $atts;
	}

	$item_classes = isset( $item->classes ) && is_array( $item->classes ) ? $item->classes : array();
	if ( ! in_array( 'menu-item-has-children', $item_classes, true ) ) {
		return $atts;
	}

	$atts['role'] = 'button';
	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'blankslate_elementor_nav_parent_link_role', 20, 4 );

/**
 * Patch Elementor-rendered HTML server-side for stable Lighthouse results.
 */
function blankslate_patch_elementor_markup_a11y( $content ) {
	if ( is_admin() || ! is_string( $content ) || '' === $content ) {
		return $content;
	}

	// Ensure one main landmark on Elementor page wrapper when none exists in this HTML fragment.
	if ( false === stripos( $content, '<main' ) && false === stripos( $content, 'role="main"' ) && false === stripos( $content, "role='main'" ) ) {
		$pattern = '/<div\b([^>]*\bdata-elementor-type=(["\'])wp-page\2[^>]*)>/i';
		$content = preg_replace_callback(
			$pattern,
			static function ( $matches ) {
				$attrs = $matches[1];
				if ( preg_match( '/\brole\s*=\s*(["\'])main\1/i', $attrs ) ) {
					return $matches[0];
				}
				$has_id = preg_match( '/\bid\s*=\s*(["\'])[^"\']+\1/i', $attrs );
				$attrs .= ' role="main"' . ( $has_id ? '' : ' id="main-content"' );
				return '<div' . $attrs . '>';
			},
			$content,
			1
		);
	}

	// Ensure scroll-to-top control has an accessible name.
	$scroll_pattern = '/<a\b([^>]*\bid=(["\'])scroll-to-top\2[^>]*)>/i';
	$content        = preg_replace_callback(
		$scroll_pattern,
		static function ( $matches ) {
			$attrs = $matches[1];
			if ( preg_match( '/\baria-label\s*=\s*(["\']).*?\1/i', $attrs ) ) {
				return $matches[0];
			}
			return '<a' . $attrs . ' aria-label="Scroll to top">';
		},
		$content
	);

	// Woo Product Slider: icon-only "view details" links need an accessible name.
	$details_pattern = '/<li\b[^>]*\bclass=(["\'])[^"\']*\bview-details\b[^"\']*\1[^>]*>\s*<a\b([^>]*)>/i';
	$content         = preg_replace_callback(
		$details_pattern,
		static function ( $matches ) {
			$anchor_attrs = $matches[2];
			if ( preg_match( '/\baria-label\s*=\s*(["\']).*?\1/i', $anchor_attrs ) ) {
				return $matches[0];
			}
			return preg_replace(
				'/<a\b([^>]*)>/i',
				'<a$1 aria-label="View product details">',
				$matches[0],
				1
			);
		},
		$content
	);

	return $content;
}
add_filter( 'the_content', 'blankslate_patch_elementor_markup_a11y', 20 );
add_filter( 'elementor/frontend/the_content', 'blankslate_patch_elementor_markup_a11y', 20 );

/**
 * theme.json / wp-fonts-local: use font-display: swap so text stays visible while fonts load (PageSpeed).
 */
function blankslate_theme_json_font_display_swap( $theme_json, $origin ) {
	if ( ! class_exists( 'WP_Theme_JSON_Data' ) || ! $theme_json instanceof WP_Theme_JSON_Data ) {
		return $theme_json;
	}
	$data = $theme_json->get_data();
	if ( empty( $data['settings']['typography']['fontFamilies'] ) || ! is_array( $data['settings']['typography']['fontFamilies'] ) ) {
		return $theme_json;
	}
	foreach ( $data['settings']['typography']['fontFamilies'] as $gk => $group ) {
		if ( ! is_array( $group ) ) {
			continue;
		}
		foreach ( $group as $fi => $family ) {
			if ( empty( $family['fontFace'] ) || ! is_array( $family['fontFace'] ) ) {
				continue;
			}
			foreach ( $family['fontFace'] as $vi => $face ) {
				if ( ! is_array( $face ) ) {
					continue;
				}
				$data['settings']['typography']['fontFamilies'][ $gk ][ $fi ]['fontFace'][ $vi ]['fontDisplay'] = 'swap';
				unset( $data['settings']['typography']['fontFamilies'][ $gk ][ $fi ]['fontFace'][ $vi ]['font-display'] );
			}
		}
	}
	return new WP_Theme_JSON_Data( $data, $origin );
}

add_filter(
	'wp_theme_json_data_default',
	function ( $theme_json ) {
		return blankslate_theme_json_font_display_swap( $theme_json, 'default' );
	},
	20
);
add_filter(
	'wp_theme_json_data_theme',
	function ( $theme_json ) {
		return blankslate_theme_json_font_display_swap( $theme_json, 'theme' );
	},
	20
);
add_filter(
	'wp_theme_json_data_user',
	function ( $theme_json ) {
		return blankslate_theme_json_font_display_swap( $theme_json, 'custom' );
	},
	20
);
add_filter(
	'wp_theme_json_data_blocks',
	function ( $theme_json ) {
		return blankslate_theme_json_font_display_swap( $theme_json, 'blocks' );
	},
	20
);

add_action( 'admin_notices', 'blankslate_notice' );
function blankslate_notice() {
$user_id = get_current_user_id();
$admin_url = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http' ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$param = ( count( $_GET ) ) ? '&' : '?';
if ( !get_user_meta( $user_id, 'blankslate_notice_dismissed_11' ) && current_user_can( 'manage_options' ) )
echo '<div class="notice notice-info"><p><a href="' . esc_url( $admin_url ), esc_html( $param ) . 'dismiss" class="alignright" style="text-decoration:none"><big>Ⓧ</big></a>' . wp_kses_post( '<big><strong>🏆 Thank you for using BlankSlate!</strong></big>' ) . '<p>Powering over 10k websites! Buy me a sandwich! 🥪</p><a href="https://github.com/bhadaway/blankslate/issues/57" class="button-primary" target="_blank"><strong>How do you use BlankSlate?</strong></a> <a href="https://opencollective.com/blankslate" class="button-primary" style="background-color:green;border-color:green" target="_blank"><strong>Donate</strong></a> <a href="https://wordpress.org/support/theme/blankslate/reviews/#new-post" class="button-primary" style="background-color:purple;border-color:purple" target="_blank"><strong>Review</strong></a> <a href="https://github.com/bhadaway/blankslate/issues" class="button-primary" style="background-color:orange;border-color:orange" target="_blank"><strong>Support</strong></a></p></div>';
}
add_action( 'admin_init', 'blankslate_notice_dismissed' );
function blankslate_notice_dismissed() {
$user_id = get_current_user_id();
if ( isset( $_GET['dismiss'] ) )
add_user_meta( $user_id, 'blankslate_notice_dismissed_11', 'true', true );
}
/**
 * Drop jQuery Migrate on the frontend: core registers `jquery` with deps jquery-core + jquery-migrate.
 * Dequeuing in wp_footer is too late; strip the dependency and deregister the script.
 */
function blankslate_disable_jquery_migrate_dependencies( $scripts ) {
	if ( is_admin() ) {
		return;
	}
	if ( isset( $scripts->registered['jquery'] ) ) {
		$scripts->registered['jquery']->deps = array_values(
			array_diff( $scripts->registered['jquery']->deps, array( 'jquery-migrate' ) )
		);
	}
}
add_action( 'wp_default_scripts', 'blankslate_disable_jquery_migrate_dependencies', 11 );

function blankslate_deregister_jquery_migrate_front() {
	if ( is_admin() ) {
		return;
	}
	wp_dequeue_script( 'jquery-migrate' );
	wp_deregister_script( 'jquery-migrate' );
}
add_action( 'wp_enqueue_scripts', 'blankslate_deregister_jquery_migrate_front', 100 );

/**
 * Elementor custom fonts can output inline @font-face with font-display:auto.
 * Fallback output-buffer patch: replace to `swap` in <style id="elementor-post-*">.
 */
function blankslate_swap_font_display_in_elementor_post_style_tags( $html ) {
	if ( ! is_string( $html ) || '' === $html ) {
		return $html;
	}

	// 1) Elementor inline custom-font blocks: force font-display:auto -> swap.
	if ( false !== stripos( $html, 'elementor-post-' ) && false !== stripos( $html, 'font-display' ) ) {
		$html = preg_replace_callback(
			'/<style\b([^>]*\bid=(["\'])elementor-post-[^"\']+\2[^>]*)>(.*?)<\/style>/is',
			static function ( $matches ) {
				$css = preg_replace( '/font-display\s*:\s*auto\s*;/i', 'font-display: swap;', $matches[3] );
				return '<style' . $matches[1] . '>' . $css . '</style>';
			},
			$html
		);
	}

	// 2) Viewport accessibility: remove user-scalable=no and unsafe maximum-scale values.
	if ( false !== stripos( $html, 'name="viewport"' ) || false !== stripos( $html, "name='viewport'" ) ) {
		$html = preg_replace_callback(
			'/<meta\b[^>]*name=(["\'])viewport\1[^>]*content=(["\'])(.*?)\2[^>]*>/is',
			static function ( $matches ) {
				$tag     = $matches[0];
				$content = $matches[3];

				$content = preg_replace( '/\s*,?\s*user-scalable\s*=\s*no\s*/i', '', $content );
				$content = preg_replace( '/\s*,?\s*maximum-scale\s*=\s*[0-4](?:\.\d+)?\s*/i', '', $content );
				$content = preg_replace( '/\s*,\s*,+/', ',', $content );
				$content = trim( preg_replace( '/\s+/', ' ', $content ), " ,\t\n\r\0\x0B" );
				if ( '' === $content ) {
					$content = 'width=device-width, initial-scale=1';
				}

				return preg_replace(
					'/content=(["\']).*?\1/is',
					'content="' . esc_attr( $content ) . '"',
					$tag,
					1
				);
			},
			$html
		);
	}

	return $html;
}

function blankslate_start_font_display_output_buffer() {
	if ( is_admin() || wp_doing_ajax() || wp_is_json_request() || is_feed() || is_embed() ) {
		return;
	}
	ob_start( 'blankslate_swap_font_display_in_elementor_post_style_tags' );
}
add_action( 'template_redirect', 'blankslate_start_font_display_output_buffer', 0 );

/**
 * Elementor stores generated post CSS in uploads/elementor/css/post-*.css.
 * Those files may contain @font-face font-display:auto for custom fonts.
 * Normalize them to swap periodically so PageSpeed stops flagging the font files.
 */
function blankslate_patch_elementor_generated_css_font_display() {
	if ( is_admin() ) {
		return;
	}

	$lock_key = 'blankslate_elementor_css_font_display_patched_at';
	$last_run = (int) get_transient( $lock_key );
	if ( $last_run && ( time() - $last_run ) < 12 * HOUR_IN_SECONDS ) {
		return;
	}

	$uploads = wp_get_upload_dir();
	if ( empty( $uploads['basedir'] ) ) {
		return;
	}

	$dir = trailingslashit( $uploads['basedir'] ) . 'elementor/css';
	if ( ! is_dir( $dir ) ) {
		set_transient( $lock_key, time(), 12 * HOUR_IN_SECONDS );
		return;
	}

	$files = glob( $dir . '/post-*.css' );
	if ( ! is_array( $files ) || empty( $files ) ) {
		set_transient( $lock_key, time(), 12 * HOUR_IN_SECONDS );
		return;
	}

	foreach ( $files as $file ) {
		if ( ! is_string( $file ) || ! is_file( $file ) || ! is_readable( $file ) || ! is_writable( $file ) ) {
			continue;
		}

		$css = file_get_contents( $file );
		if ( ! is_string( $css ) || false === stripos( $css, 'font-display' ) ) {
			continue;
		}

		$patched = preg_replace( '/font-display\s*:\s*(auto|fallback|block)\s*;/i', 'font-display: swap;', $css );
		if ( is_string( $patched ) && $patched !== $css ) {
			file_put_contents( $file, $patched, LOCK_EX );
		}
	}

	set_transient( $lock_key, time(), 12 * HOUR_IN_SECONDS );
}
add_action( 'wp_loaded', 'blankslate_patch_elementor_generated_css_font_display', 20 );

/**
 * Elementor ships Font Awesome under assets/lib/font-awesome/css with font-display:block.
 * Lighthouse expects swap/optional; patch those files in place (restored on Elementor update).
 */
function blankslate_patch_elementor_font_awesome_css_font_display() {
	if ( is_admin() ) {
		return;
	}

	$lock_key = 'blankslate_fa_font_display_patched_at';
	$last_run = (int) get_transient( $lock_key );
	if ( $last_run && ( time() - $last_run ) < 12 * HOUR_IN_SECONDS ) {
		return;
	}

	$dir = WP_PLUGIN_DIR . '/elementor/assets/lib/font-awesome/css';
	if ( ! is_dir( $dir ) ) {
		set_transient( $lock_key, time(), 12 * HOUR_IN_SECONDS );
		return;
	}

	$files = glob( $dir . '/*.css' );
	if ( ! is_array( $files ) ) {
		set_transient( $lock_key, time(), 12 * HOUR_IN_SECONDS );
		return;
	}

	foreach ( $files as $file ) {
		if ( ! is_string( $file ) || ! is_file( $file ) || ! is_readable( $file ) || ! is_writable( $file ) ) {
			continue;
		}
		if ( filesize( $file ) > 3 * MB_IN_BYTES ) {
			continue;
		}
		$css = file_get_contents( $file );
		if ( ! is_string( $css ) || ! preg_match( '/font-display\s*:\s*block\s*;/i', $css ) ) {
			continue;
		}
		$patched = preg_replace( '/font-display\s*:\s*block\s*;/i', 'font-display: swap;', $css );
		if ( is_string( $patched ) && $patched !== $css ) {
			file_put_contents( $file, $patched, LOCK_EX );
		}
	}

	set_transient( $lock_key, time(), 12 * HOUR_IN_SECONDS );
}
add_action( 'wp_loaded', 'blankslate_patch_elementor_font_awesome_css_font_display', 21 );

/**
 * Unload WP Armor (wpa.css) on the frontend for guests and non-editorial roles.
 * Keeps stylesheet for administrator + editor (roles that use WPA tooling in practice).
 */
function blankslate_should_load_wpa_front_styles() {
	if ( ! is_user_logged_in() ) {
		return false;
	}
	$user  = wp_get_current_user();
	$roles = (array) $user->roles;
	return (bool) array_intersect( array( 'administrator', 'editor' ), $roles );
}

function blankslate_style_src_is_wpa_plugin( $src ) {
	if ( ! is_string( $src ) || $src === '' ) {
		return false;
	}
	return ( false !== strpos( $src, 'wpa.min.css' ) || false !== strpos( $src, 'wpa.css' ) );
}

function blankslate_dequeue_wpa_front_styles() {
	if ( is_admin() || blankslate_should_load_wpa_front_styles() ) {
		return;
	}
	global $wp_styles;
	if ( ! ( $wp_styles instanceof WP_Styles ) ) {
		return;
	}
	foreach ( $wp_styles->registered as $handle => $style ) {
		if ( empty( $style->src ) || ! blankslate_style_src_is_wpa_plugin( $style->src ) ) {
			continue;
		}
		wp_dequeue_style( $handle );
		wp_deregister_style( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'blankslate_dequeue_wpa_front_styles', 99999 );

add_action( 'wp_enqueue_scripts', 'blankslate_enqueue' );
function blankslate_enqueue() {
wp_enqueue_style( 'blankslate-style', get_stylesheet_uri(), array(), filemtime(get_stylesheet_directory() . '/style.css'), 'all' );
wp_enqueue_script( 'jquery' );
wp_dequeue_style( 'wp-block-library' );
	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		wp_enqueue_script(
			'inputmask',
			get_template_directory_uri() . '/includes/inputMask.js',
			array( 'jquery' ),
			filemtime( get_template_directory() . '/includes/inputMask.js' ),
			true
		);
	}
}
add_action( 'wp_footer', 'blankslate_footer' );
function blankslate_footer() {
	wp_enqueue_script( 'theme-scripts', get_template_directory_uri() . '/includes/scripts.js', array( 'jquery' ), filemtime( get_template_directory() . '/includes/scripts.js' ), true );
	if ( ! is_admin() ) {
		$form_gtm_map = function_exists( 'blankslate_gtm_elementor_form_events_by_css_id' )
			? blankslate_gtm_elementor_form_events_by_css_id()
			: array(
				'consultation-form' => 'send_form_personal_advice',
				'contact-form'      => 'send_form_send_message',
			);
		wp_localize_script(
			'theme-scripts',
			'blankslateFormGtm',
			array(
				'byCssId' => $form_gtm_map,
			)
		);
	}
	if ( class_exists( 'WooCommerce' ) && ! is_admin() ) {
		wp_localize_script(
			'theme-scripts',
			'blankslateCartAjax',
			array(
				'wcAjaxUrl'              => WC_AJAX::get_endpoint( 'blankslate_update_cart_qty' ),
				'addToCartUrl'           => WC_AJAX::get_endpoint( 'add_to_cart' ),
				'fragmentsUrl'           => WC_AJAX::get_endpoint( 'get_refreshed_fragments' ),
				'nonceUrl'               => WC_AJAX::get_endpoint( 'blankslate_cart_nonce' ),
				'adminAjaxUrl'           => admin_url( 'admin-ajax.php' ),
				'elementorMcNonce'       => wp_create_nonce( 'elementor-menu-cart-fragments' ),
				'nonce'                  => wp_create_nonce( 'woocommerce-cart' ),
				'singleProductAtc'       => is_product(),
				'sideCartTableSelector'  => function_exists( 'blankslate_get_side_cart_table_fragment_selector' )
					? blankslate_get_side_cart_table_fragment_selector()
					: '#custom-side-cart .elementor-widget-wl-cart-table-list .elementor-widget-container',
				'sideCartTotalsSelector' => '#custom-side-cart .blankslate-side-cart-totals',
			)
		);
	}
	if ( ! is_admin() ) {
		wp_enqueue_script(
			'blankslate-a11y-accessible-names',
			get_template_directory_uri() . '/includes/a11y-accessible-names.js',
			array( 'theme-scripts' ),
			filemtime( get_template_directory() . '/includes/a11y-accessible-names.js' ),
			true
		);
	}
?>
<?php
}
add_filter( 'document_title_separator', 'blankslate_document_title_separator' );
function blankslate_document_title_separator( $sep ) {
$sep = esc_html( '|' );
return $sep;
}
add_filter( 'the_title', 'blankslate_title' );
function blankslate_title( $title ) {
if ( $title == '' ) {
return esc_html( '...' );
} else {
return wp_kses_post( $title );
}
}
function blankslate_schema_type() {
$schema = 'https://schema.org/';
if ( is_single() ) {
$type = "Article";
} elseif ( is_author() ) {
$type = 'ProfilePage';
} elseif ( is_search() ) {
$type = 'SearchResultsPage';
} else {
$type = 'WebPage';
}
echo 'itemscope itemtype="' . esc_url( $schema ) . esc_attr( $type ) . '"';
}
add_filter( 'nav_menu_link_attributes', 'blankslate_schema_url', 10 );
function blankslate_schema_url( $atts ) {
$atts['itemprop'] = 'url';
return $atts;
}
if ( !function_exists( 'blankslate_wp_body_open' ) ) {
function blankslate_wp_body_open() {
do_action( 'wp_body_open' );
}
}
//add_action( 'wp_body_open', 'blankslate_skip_link', 5 );
function blankslate_skip_link() {
echo '<a href="#content" class="skip-link screen-reader-text">' . esc_html__( 'Skip to the content', 'blankslate' ) . '</a>';
}
add_filter( 'the_content_more_link', 'blankslate_read_more_link' );
function blankslate_read_more_link() {
if ( !is_admin() ) {
return ' <a href="' . esc_url( get_permalink() ) . '" class="more-link">' . sprintf( __( '...%s', 'blankslate' ), '<span class="screen-reader-text">  ' . esc_html( get_the_title() ) . '</span>' ) . '</a>';
}
}
/*add_filter( 'excerpt_more', 'blankslate_excerpt_read_more_link' );
function blankslate_excerpt_read_more_link( $more ) {
if ( !is_admin() ) {
global $post;
return ' <a href="' . esc_url( get_permalink( $post->ID ) ) . '" class="more-link">' . sprintf( __( '...%s', 'blankslate' ), '<span class="screen-reader-text">  ' . esc_html( get_the_title() ) . '</span>' ) . '</a>';
}
}*/
function wpdocs_excerpt_more( $more ) {
    return '...';
}
add_filter( 'excerpt_more', 'wpdocs_excerpt_more' );
add_filter( 'big_image_size_threshold', '__return_false' );
add_filter( 'intermediate_image_sizes_advanced', 'blankslate_image_insert_override' );
function blankslate_image_insert_override( $sizes ) {
unset( $sizes['medium_large'] );
unset( $sizes['1536x1536'] );
unset( $sizes['2048x2048'] );
return $sizes;
}
add_action( 'widgets_init', 'blankslate_widgets_init' );
function blankslate_widgets_init() {
register_sidebar( array(
'name' => 'Sidebar Widget Area',
'id' => 'primary-widget-area',
'before_widget' => '<li id="%1$s" class="widget-container %2$s">',
'after_widget' => '</li>',
'before_title' => '<h3 class="widget-title">',
'after_title' => '</h3>',
) );
}
add_action( 'wp_head', 'blankslate_pingback_header' );
function blankslate_pingback_header() {
if ( is_singular() && pings_open() ) {
printf( '<link rel="pingback" href="%s">' . "\n", esc_url( get_bloginfo( 'pingback_url' ) ) );
}
}
add_action( 'comment_form_before', 'blankslate_enqueue_comment_reply_script' );
function blankslate_enqueue_comment_reply_script() {
if ( get_option( 'thread_comments' ) ) {
wp_enqueue_script( 'comment-reply' );
}
}
function blankslate_custom_pings( $comment ) {
?>
<li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>"><?php echo esc_url( comment_author_link() ); ?></li>
<?php
}
add_filter( 'get_comments_number', 'blankslate_comment_count', 0 );
function blankslate_comment_count( $count ) {
if ( is_admin() ) {
return $count;
}
static $cache = array();
global $id;
if ( ! isset( $cache[ $id ] ) ) {
$get_comments = get_comments( 'status=approve&post_id=' . $id );
$comments_by_type = separate_comments( $get_comments );
$cache[ $id ] = count( $comments_by_type['comment'] );
}
return $cache[ $id ];
}
add_filter('woocommerce_breadcrumb_defaults', 'wcc_change_breadcrumb_delimiter', 20);
function wcc_change_breadcrumb_delimiter($defaults) {
    // Змініть роздільник breadcrumbs з '/' на '>'
    $defaults['delimiter'] = '<span class="breadcrumb-separator"></span>';
    return $defaults;
}

function form_checkboxes_validation(){ ?> 
    <script type="text/javascript"> 
        (function($){ $("#send-request").click(function() { 
            if(! $('input[name="form_fields[suite][]"]').is(':checked')) { 
                alert("Please select at least one suite!"); return false; 
                } 
            }); 
        })(jQuery); 
    </script>
<?php } 
add_action('wp_footer', 'form_checkboxes_validation');

function add_noindex_for_elementor_library() {
    if ( isset($_GET['elementor_library']) ) {
        echo '<meta name="robots" content="noindex, nofollow" />' . "\n";
    }
}
add_action('wp_head', 'add_noindex_for_elementor_library', 1);

add_filter('woocommerce_get_breadcrumb', 'my_multilang_breadcrumb_replace', 9999, 2);
function my_multilang_breadcrumb_replace($crumbs, $breadcrumb_obj){
    foreach( $crumbs as $index => $crumb ) {
        // $crumb[0] = Текст у крихті
        // $crumb[1] = Посилання (URL) у крихті
        $title = $crumb[0];
        $link  = $crumb[1];

        // Перевіряємо, чи це крихта з "product-category"
        // тобто посилання на архів WooCommerce-категорії
        if ( str_contains($link, 'product-category') ) {

            // Витягаємо <cat-slug> за допомогою регулярки
            // знаходимо все, що після ".../product-category/" до наступного слеша
            if ( preg_match('~product-category/([^/]+)/?$~', $link, $matches) ) {
                $cat_slug = $matches[1];  // Наприклад: "velvet-roses" або "oksamytovi-troiandy"
            } else {
                // Якщо чомусь не змогли знайти slug, пропускаємо
                continue;
            }

            $custom_collection_url = '';
            $custom_collection_title = '';
            $term = get_term_by('slug', $cat_slug, 'product_cat');

            if ($term && !is_wp_error($term)) {
                $collection_page = get_field('collection_page', 'product_cat_' . $term->term_id);
                if (!empty($collection_page)) {
                    if (is_numeric($collection_page)) {
                        $page_id = (int) $collection_page;
                        $custom_collection_url = get_permalink($page_id);
                        $custom_collection_title = get_the_title($page_id);
                    } elseif (is_object($collection_page) && isset($collection_page->ID)) {
                        $page_id = (int) $collection_page->ID;
                        $custom_collection_url = get_permalink($page_id);
                        $custom_collection_title = get_the_title($page_id);
                    } elseif (is_string($collection_page)) {
                        $custom_collection_url = $collection_page;
                        $page_id = url_to_postid($collection_page);
                        if (!empty($page_id)) {
                            $custom_collection_title = get_the_title($page_id);
                        }
                    }
                }
            }

            // Override breadcrumb link only when custom page is set.
            if (!empty($custom_collection_url)) {
                $crumbs[$index][1] = $custom_collection_url;
                if (!empty($custom_collection_title)) {
                    $crumbs[$index][0] = $custom_collection_title;
                }
            }

            // Якщо хочете підмінити й текст (наприклад, на "TEST Cat Link"), зробіть так:
            // $crumbs[$index][0] = 'TEST Cat Link';
        }
    }

    return $crumbs;
}

remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );
remove_action( 'template_redirect', 'wp_shortlink_header', 11 );

/* REMOVE  META TAGS */
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
remove_action('wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head','adjacent_posts_rel_link_wp_head');
remove_action('wp_head','feed_links_extra', 3);
remove_action('xmlrpc_rsd_apis', 'rest_output_rsd');

// REMOVE EMOJI ICONS
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');

function disable_dashicons_frontend() {
    // Only dequeue on the frontend, not the admin panel
    if ( ! is_admin() ) {
        if(! is_admin_bar_showing()) wp_deregister_style( 'dashicons' );
        wp_deregister_style( 'wp-block-library' );
    }
}
add_action( 'wp_enqueue_scripts', 'disable_dashicons_frontend' );

/* DISABLE EDITED PLUGINS UPDATE */
/*add_filter('site_transient_update_plugins', 'remove_update_notification');
function remove_update_notification($value) {
    unset($value->response[ "meest-for-woocommerce/meest_shipping.php" ]);
    unset($value->response[ "wc-ukr-shipping/wc-ukr-shipping.php" ]);
    unset($value->response[ "woocommerce-products-filter/index.php" ]);
    unset($value->response[ "connect-polylang-elementor/connect-polylang-elementor.php" ]);
    unset($value->response[ "elementor-pro/elementor-pro.php" ]);
    unset($value->response[ "essential-addons-elementor/essential_adons_elementor.php" ]);
    return $value;
}*/
function remove_core_updates(){
	global $wp_version;return(object) array('last_checked'=> time(),'version_checked'=> $wp_version,);
}
add_filter('pre_site_transient_update_core','remove_core_updates'); //hide updates for WordPress itself
add_filter('pre_site_transient_update_plugins','remove_core_updates'); //hide updates for all plugins
add_filter('pre_site_transient_update_themes','remove_core_updates'); //hide updates for all themes

/* TIME DIFF FORMATS */
function declension($number, $words) {
    $number = abs($number);
    if ($number > 20) $number %= 10;
    if ($number == 1) return $words[0];
    if ($number >= 2 && $number <= 4) return $words[1];
    return $words[2];
}

/* SEARCH QUERY */
add_action('pre_get_posts', function($query) {
    if (!is_admin() && $query->is_main_query() && $query->is_search()) {
        $query->set('post_type', ['product']); // Only search for products
        $query->set('posts_per_page', -1); // Ensure proper pagination
    }
});

/* PRODUCT CATEGORY ARCHIVE: FIX PRODUCTS PER PAGE */
add_action('pre_get_posts', function($query) {
    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    // Apply only on WooCommerce product category archives.
    if (function_exists('is_product_category') && is_product_category()) {
        $query->set('posts_per_page', -1);
    }
}, 99);

/* REMOVE PRODUCTS RESULT COUNT */
add_action('init', function() {
    remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
    remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);
});

/* REMOVE WOOCOMMERCE SIDEBAR ON PRODUCT CATEGORY PAGES */
add_action('wp', function() {
    if (function_exists('is_product_category') && is_product_category()) {
        remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);
    }
});

/* PRODUCT CATEGORY CONTENT AFTER LOOP */
add_action('woocommerce_after_shop_loop', function() {
    if (!function_exists('is_product_category') || !is_product_category()) {
        return;
    }

    echo do_shortcode(pll__('[elementor-template id="10621"]'));
}, 20);

/* FONTS INCLUSION ON NON-ELEMENTOR PAGES */
function fonts_inclusion() {
    if(is_admin()) return;
    if(is_category() || is_singular(array('post', 'product')) || is_search() || is_page(pll_get_post(wc_get_page_id('cart')))) { ?>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
        <style>
            @font-face {
                font-family: 'Quincy CF';
                src: url('<?php echo get_template_directory_uri(); ?>/fonts/QuincyCF-Regular.eot');
                src: local('Quincy CF'),
                    url('<?php echo get_template_directory_uri(); ?>/fonts/QuincyCF-Regular.eot?#iefix') format('embedded-opentype'),
                    url('<?php echo get_template_directory_uri(); ?>/fonts/QuincyCF-Regular.woff2') format('woff2'),
                    url('<?php echo get_template_directory_uri(); ?>/fonts/QuincyCF-Regular.woff') format('woff'),
                    url('<?php echo get_template_directory_uri(); ?>/fonts/QuincyCF-Regular.ttf') format('truetype');
                font-weight: normal;
                font-style: normal;
            }
            @font-face {
                font-family: 'Quincy CF';
                src: url('<?php echo get_template_directory_uri(); ?>/fonts/QuincyCF-RegularItalic.eot');
                src: local('Quincy CF'),
                    url('<?php echo get_template_directory_uri(); ?>/fonts/QuincyCF-RegularItalic.eot?#iefix') format('embedded-opentype'),
                    url('<?php echo get_template_directory_uri(); ?>/fonts/QuincyCF-RegularItalic.woff2') format('woff2'),
                    url('<?php echo get_template_directory_uri(); ?>/fonts/QuincyCF-RegularItalic.woff') format('woff'),
                    url('<?php echo get_template_directory_uri(); ?>/fonts/QuincyCF-RegularItalic.ttf') format('truetype');
                font-weight: normal;
                font-style: italic;
            }
        </style>
    <?php }
}
add_action('wp_footer', 'fonts_inclusion');

/* PREFERENCES SHORTCODE */
function user_preferences() {
    ob_start(); ?>
    <form action="<?php echo admin_url( 'admin-ajax.php' ); ?>" method="POST" id="preferences" name="preferences" class="preferences">
        <?php $countries = new WC_Countries();
        $countries_active = $countries->get_allowed_countries();
        if($countries_active) { ?>
            <div class="preferences__title"><?php pll_e('Your Shipping Destination'); ?></div>
            <?php
            $chosen_country = function_exists( 'blankslate_get_visitor_country_code' )
                ? blankslate_get_visitor_country_code()
                : '';
            if ( ! $chosen_country && function_exists( 'WC' ) && WC()->customer ) {
                $chosen_country = WC()->customer->get_shipping_country() ? WC()->customer->get_shipping_country() : WC()->customer->get_billing_country();
            }
            if ( ! $chosen_country ) {
                $default_country = get_option( 'woocommerce_default_country', 'UA' );
                $chosen_country  = is_string( $default_country ) ? strtok( $default_country, ':' ) : 'UA';
            }
            $woocommerce_countries = WC()->countries->countries;
            $chosen_country_name = isset( $woocommerce_countries[ $chosen_country ] ) ? $woocommerce_countries[ $chosen_country ] : '';
            if ( ! $chosen_country_name ) {
                $chosen_country_name = pll__( 'Ukraine' );
            }
            ?>
            <div class="preferences__dropdown">
                <div class="preferences__dropdown__current">
                    <div class="preferences__dropdown__current__name"><?php echo $chosen_country_name; ?></div>
                    <svg width="9" height="6" viewBox="0 0 9 6" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M8.01882 0.898047C8.24321 0.70065 8.60008 0.70065 8.82447 0.898047C9.06003 1.10527 9.06023 1.45117 8.8146 1.65795L4.89964 5.10195C4.78421 5.2035 4.63336 5.25 4.49681 5.25C4.34949 5.25 4.20703 5.2014 4.09398 5.10195L0.176418 1.65566C-0.058806 1.44873 -0.0588061 1.10498 0.176418 0.898048C0.400809 0.700651 0.757684 0.700651 0.982075 0.898048L4.50389 3.9962L8.01882 0.898047ZM4.50408 4.3587L0.790025 1.09143C0.673733 0.98913 0.48476 0.98913 0.368469 1.09143C0.252177 1.19373 0.252177 1.35997 0.368469 1.46228L4.28603 4.90857C4.34418 4.95972 4.41686 4.98529 4.49681 4.98529C4.56949 4.98529 4.64944 4.95972 4.70759 4.90857L8.62515 1.46228C8.74871 1.35997 8.74871 1.19373 8.63242 1.09143C8.51613 0.98913 8.32716 0.98913 8.21087 1.09143L4.50408 4.3587Z" fill="#353D3B"/><path d="M4.50408 4.3587L0.790025 1.09143C0.673733 0.98913 0.48476 0.98913 0.368469 1.09143C0.252177 1.19373 0.252177 1.35997 0.368469 1.46228L4.28603 4.90857C4.34418 4.95972 4.41686 4.98529 4.49681 4.98529C4.56949 4.98529 4.64944 4.95972 4.70759 4.90857L8.62515 1.46228C8.74871 1.35997 8.74871 1.19373 8.63242 1.09143C8.51613 0.98913 8.32716 0.98913 8.21087 1.09143L4.50408 4.3587Z" fill="#353D3B"/>
                    </svg>
                </div>
                <div class="preferences__dropdown__list">
                    <?php foreach($countries_active as $key => $value) { ?>
                        <div class="preferences__dropdown__list__item">
                            <input type="radio" value="<?php echo $key; ?>" name="preferences_country" id="country_<?php echo $key; ?>" style="opacity:0;position:absolute"<?php echo ($key === $chosen_country ? ' checked' : ''); ?>>
                            <label for="country_<?php echo $key; ?>"><span class="preferences__dropdown__list__item__label"><?php echo $value; ?></span></label>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php }
        $languages = pll_the_languages(array('raw' => 1));
        $current_lang = pll_current_language('name');
        if($languages) { ?>
            <div class="preferences__title"><?php pll_e('Your Language'); ?></div>
            <div class="preferences__dropdown">
                <div class="preferences__dropdown__current">
                    <div class="preferences__dropdown__current__name"><?php echo $current_lang; ?></div>
                    <svg width="9" height="6" viewBox="0 0 9 6" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M8.01882 0.898047C8.24321 0.70065 8.60008 0.70065 8.82447 0.898047C9.06003 1.10527 9.06023 1.45117 8.8146 1.65795L4.89964 5.10195C4.78421 5.2035 4.63336 5.25 4.49681 5.25C4.34949 5.25 4.20703 5.2014 4.09398 5.10195L0.176418 1.65566C-0.058806 1.44873 -0.0588061 1.10498 0.176418 0.898048C0.400809 0.700651 0.757684 0.700651 0.982075 0.898048L4.50389 3.9962L8.01882 0.898047ZM4.50408 4.3587L0.790025 1.09143C0.673733 0.98913 0.48476 0.98913 0.368469 1.09143C0.252177 1.19373 0.252177 1.35997 0.368469 1.46228L4.28603 4.90857C4.34418 4.95972 4.41686 4.98529 4.49681 4.98529C4.56949 4.98529 4.64944 4.95972 4.70759 4.90857L8.62515 1.46228C8.74871 1.35997 8.74871 1.19373 8.63242 1.09143C8.51613 0.98913 8.32716 0.98913 8.21087 1.09143L4.50408 4.3587Z" fill="#353D3B"/><path d="M4.50408 4.3587L0.790025 1.09143C0.673733 0.98913 0.48476 0.98913 0.368469 1.09143C0.252177 1.19373 0.252177 1.35997 0.368469 1.46228L4.28603 4.90857C4.34418 4.95972 4.41686 4.98529 4.49681 4.98529C4.56949 4.98529 4.64944 4.95972 4.70759 4.90857L8.62515 1.46228C8.74871 1.35997 8.74871 1.19373 8.63242 1.09143C8.51613 0.98913 8.32716 0.98913 8.21087 1.09143L4.50408 4.3587Z" fill="#353D3B"/>
                    </svg>
                </div>
                <div class="preferences__dropdown__list">
                    <?php foreach($languages as $language) { ?>
                        <div class="preferences__dropdown__list__item">
                            <input type="radio" value="<?php echo $language['slug']; ?>" name="preferences_language" id="language_<?php echo $language['slug']; ?>" style="opacity:0;position:absolute"<?php echo ($language['name'] === $current_lang ? ' checked' : ''); ?>>
                            <label for="language_<?php echo $language['slug']; ?>"><span class="preferences__dropdown__list__item__label"><?php echo $language['name']; ?></span></label>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php }
        
        $wmc_data = WOOMULTI_CURRENCY_Data::get_ins();
        $currencies = get_option('woo_multi_currency_params');
        if ($currencies && isset($currencies['currency'])) {
            $current_currency = function_exists( 'blankslate_get_visitor_currency_code' )
                ? blankslate_get_visitor_currency_code()
                : ( function_exists( 'wmc_get_woocommerce_currency' ) ? wmc_get_woocommerce_currency() : get_woocommerce_currency() ); ?>
            <div class="preferences__title"><?php pll_e('Your Currency'); ?></div>
            <div class="preferences__dropdown">
                <div class="preferences__dropdown__current">
                    <div class="preferences__dropdown__current__name"><?php echo $current_currency; ?></div>
                    <svg width="9" height="6" viewBox="0 0 9 6" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M8.01882 0.898047C8.24321 0.70065 8.60008 0.70065 8.82447 0.898047C9.06003 1.10527 9.06023 1.45117 8.8146 1.65795L4.89964 5.10195C4.78421 5.2035 4.63336 5.25 4.49681 5.25C4.34949 5.25 4.20703 5.2014 4.09398 5.10195L0.176418 1.65566C-0.058806 1.44873 -0.0588061 1.10498 0.176418 0.898048C0.400809 0.700651 0.757684 0.700651 0.982075 0.898048L4.50389 3.9962L8.01882 0.898047ZM4.50408 4.3587L0.790025 1.09143C0.673733 0.98913 0.48476 0.98913 0.368469 1.09143C0.252177 1.19373 0.252177 1.35997 0.368469 1.46228L4.28603 4.90857C4.34418 4.95972 4.41686 4.98529 4.49681 4.98529C4.56949 4.98529 4.64944 4.95972 4.70759 4.90857L8.62515 1.46228C8.74871 1.35997 8.74871 1.19373 8.63242 1.09143C8.51613 0.98913 8.32716 0.98913 8.21087 1.09143L4.50408 4.3587Z" fill="#353D3B"/><path d="M4.50408 4.3587L0.790025 1.09143C0.673733 0.98913 0.48476 0.98913 0.368469 1.09143C0.252177 1.19373 0.252177 1.35997 0.368469 1.46228L4.28603 4.90857C4.34418 4.95972 4.41686 4.98529 4.49681 4.98529C4.56949 4.98529 4.64944 4.95972 4.70759 4.90857L8.62515 1.46228C8.74871 1.35997 8.74871 1.19373 8.63242 1.09143C8.51613 0.98913 8.32716 0.98913 8.21087 1.09143L4.50408 4.3587Z" fill="#353D3B"/>
                    </svg>
                </div>
                <div class="preferences__dropdown__list">
                    <?php foreach ($currencies['currency'] as $k => $currency_data) {
                        $currency_code = strtolower($wmc_data->get_country_data($currency_data)['code']); ?>
                        <div class="preferences__dropdown__list__item">
                            <input type="radio" value="<?php echo esc_attr( $currency_data ); ?>" name="preferences_currency" id="currency_<?php echo esc_attr( $currency_code ); ?>" style="opacity:0;position:absolute"<?php echo ( $currency_data === $current_currency ? ' checked' : '' ); ?>>
                            <label for="currency_<?php echo $currency_code; ?>">
                                <i class="flag flag-<?php echo $currency_code; ?>"></i>
                                <span class="preferences__dropdown__list__item__label"><?php echo $currency_data; ?></span> (<?php echo get_woocommerce_currency_symbol( $currency_data ); ?>)
                            </label>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>

        <input type="submit" value="<?php pll_e('Save Preferences'); ?>" class="preferences__button">
        <input type="hidden" name="action" value="preferences">

        <?php $page_type = '';
        $post_id = '';
        $term_id = '';
        $taxonomy = '';

        if (is_singular()) {
            $page_type = 'singular';
            $post_id = get_the_ID();
        } elseif (is_category() || is_tag() || is_tax()) {
            $page_type = 'term';
            $term = get_queried_object();
            $term_id = $term->term_id ?? '';
            $taxonomy = $term->taxonomy ?? '';
        } elseif (is_search()) {
            $page_type = 'search';
        } elseif (is_404()) {
            $page_type = '404';
        } else {
            $page_type = 'other';
        } ?>

        <input type="hidden" name="page_type" value="<?php echo esc_attr($page_type); ?>">
        <input type="hidden" name="current_post_id" value="<?php echo esc_attr($post_id); ?>">
        <input type="hidden" name="current_term_id" value="<?php echo esc_attr($term_id); ?>">
        <input type="hidden" name="current_taxonomy" value="<?php echo esc_attr($taxonomy); ?>">

    </form>
    <?php return ob_get_clean();
}
add_shortcode('preferences', 'user_preferences');

/* PREFERENCES SELECTION */
add_action( 'wp_footer', 'preferences_selection' );
function preferences_selection() { ?>
	<script>
	    jQuery(function($) {
            /* Sync preferences modal with live cookies (works around HTML page cache). */
            (function syncPreferencesFromCookies() {
                function readCookie(name) {
                    var m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()\[\]\\\/+^])/g, '\\$1') + '=([^;]*)'));
                    return m ? decodeURIComponent(m[1]) : '';
                }
                var currency = (readCookie('wmc_current_currency') || '').toUpperCase();
                if (!currency) {
                    var raw = readCookie('wmc_ip_info');
                    if (raw) {
                        try { var info = JSON.parse(atob(raw)); if (info && info.currency_code) currency = String(info.currency_code).toUpperCase(); } catch (e) {}
                    }
                }
                var country = (readCookie('country') || '').toUpperCase();
                if (!country) {
                    var raw2 = readCookie('wmc_ip_info');
                    if (raw2) {
                        try { var info2 = JSON.parse(atob(raw2)); if (info2 && info2.country) country = String(info2.country).toUpperCase(); } catch (e) {}
                    }
                }
                var lang = (readCookie('pll_language') || '').toLowerCase();

                if (currency) {
                    var $cur = $('#preferences input[name="preferences_currency"][value="' + currency + '"]');
                    if ($cur.length) {
                        $cur.prop('checked', true);
                        $cur.closest('.preferences__dropdown').find('.preferences__dropdown__current__name').text(currency);
                    }
                }
                if (country) {
                    var $cn = $('#preferences input[name="preferences_country"][value="' + country + '"]');
                    if ($cn.length) {
                        $cn.prop('checked', true);
                        var label = $cn.next('label').find('.preferences__dropdown__list__item__label').text();
                        if (label) $cn.closest('.preferences__dropdown').find('.preferences__dropdown__current__name').text(label);
                    }
                }
                if (lang) {
                    var $lg = $('#preferences input[name="preferences_language"][value="' + lang + '"]');
                    if ($lg.length) {
                        $lg.prop('checked', true);
                        var label2 = $lg.next('label').find('.preferences__dropdown__list__item__label').text();
                        if (label2) $lg.closest('.preferences__dropdown').find('.preferences__dropdown__current__name').text(label2);
                    }
                }
            })();

            $(document).on('click', '.preferences__dropdown', function() {
                $(this).toggleClass('opened');
            });
			$('.preferences__dropdown__list__item input').on('change', function() {
                let val = $(this).next().find('.preferences__dropdown__list__item__label').text();
                $(this).closest('.preferences__dropdown').removeClass('opened').find('.preferences__dropdown__current__name').text(val);
            });
			$('#preferences').on('submit', function() {
				var filter = $(this);
				$.ajax({
					url:filter.attr("action"),
					data:filter.serialize(), // form data
					type:filter.attr("method"), // POST
					success:function(data){
						setCookie('country', filter.find('input[name="preferences_country"]:checked').val(), {expires:30, path: '/'});
                        setCookie('pll_language', filter.find('input[name="preferences_language"]:checked').val(), {expires:365, path: '<?php echo COOKIEPATH; ?>'/*, domain:'<?php echo parse_url(home_url(), PHP_URL_HOST); ?>', secure:<?php echo (is_ssl() ? 'true' : 'false'); ?>,httponly:false, samesite:'Lax'*/});
                        setCookie('wmc_current_currency', filter.find('input[name="preferences_currency"]:checked').val(), {expires:1, path: '/'});
                        setCookie('wmc_current_currency_old', filter.find('input[name="preferences_currency"]:checked').val(), {expires:1, path: '/'});
                        window.location.href = data.data.redirect_url;
					}
				});
				return false;
			});
		});
	</script>
<?php }

/* PREFERENCES SWITCHER */
add_action('wp_ajax_preferences', 'preferences_function');
add_action('wp_ajax_nopriv_preferences', 'preferences_function');
function preferences_function() {
    if (isset($_POST['preferences_country']) && !empty($_POST['preferences_country'])) {
		if (!WC()->session->has_session()) {
			WC()->session->set_customer_session_cookie(true);
		}
		WC()->customer->set_shipping_country($_POST['preferences_country']);
		WC()->customer->set_billing_country($_POST['preferences_country']);
		WC()->customer->save();
    }

    $lang = sanitize_text_field($_POST['preferences_language']);
    $page_type = sanitize_text_field($_POST['page_type']);
    $redirect_url = '';

    switch ($page_type) {
        case 'singular':
            $post_id = intval($_POST['current_post_id'] ?? 0);
            if ($post_id) {
                $translated_id = pll_get_post($post_id, $lang);
                if ($translated_id) {
                    $redirect_url = get_permalink($translated_id);
                }
            }
            break;

        case 'term':
            $term_id = intval($_POST['current_term_id'] ?? 0);
            $taxonomy = sanitize_text_field($_POST['current_taxonomy'] ?? '');
            if ($term_id && $taxonomy) {
                $translated_term_id = pll_get_term($term_id, $lang);
                if ($translated_term_id) {
                    $redirect_url = get_term_link($translated_term_id, $taxonomy);
                }
            }
            break;

        default:
            // Replace current lang base URL with the new one
            $current_url = home_url(add_query_arg([], $_SERVER['REQUEST_URI']));
            $current_lang = pll_current_language();
            $new_home = pll_home_url($lang);
            $old_home = pll_home_url($current_lang);
            $redirect_url = str_replace($old_home, $new_home, $current_url);
            break;
    }

    if (!$redirect_url) {
        $redirect_url = pll_home_url($lang);
    }

    $cookie_path   = defined( 'COOKIEPATH' ) ? COOKIEPATH : '/';
    $cookie_domain = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '';
    setcookie( 'blankslate_prefs_saved', '1', time() + YEAR_IN_SECONDS, $cookie_path, $cookie_domain, is_ssl(), true );

    wp_send_json_success(['redirect_url' => esc_url_raw($redirect_url)]);

    wp_die();
}

add_action('woocommerce_after_checkout_billing_form', 'apply_checkout_country');
function apply_checkout_country($checkout) {
	$chosen_country = WC()->checkout()->get_value( 'shipping_country' ) ? WC()->checkout()->get_value( 'shipping_country' ) : WC()->checkout()->get_value( 'billing_country' );
    if ($chosen_country) {
		if (!is_user_logged_in()) {
            echo '<script>
                jQuery(document).ready(function($){
                    $("#billing_country").val("' . esc_js($chosen_country) . '").change();
                    $("#shipping_country").val("' . esc_js($chosen_country) . '").change();';
					if(!(WC()->countries->get_base_country() === $chosen_country)) {
						echo '$("#billing_address_1").val("");
						$("#shipping_address_1").val("");
						$("#billing_address_2").val("");
						$("#shipping_address_2").val("");
						$("#billing_postcode").val("");
						$("#shipping_postcode").val("");
						$("#billing_city").val("");
						$("#shipping_city").val("");
						$("#billing_state").val("");
						$("#shipping_state").val("");';
					}
                echo '});
            </script>';
        } else {
			$user_id = get_current_user_id();
			$user_country = get_user_meta( $user_id, 'shipping_country', true ) ? get_user_meta( $user_id, 'shipping_country', true ) : get_user_meta( $user_id, 'billing_country', true );
			if(!($user_country === $chosen_country)) {
				echo '<script>
					jQuery(document).ready(function($){
						$("#billing_address_1").val("");
						$("#shipping_address_1").val("");
						$("#billing_address_2").val("");
						$("#shipping_address_2").val("");
						$("#billing_postcode").val("");
						$("#shipping_postcode").val("");
						$("#billing_city").val("");
						$("#shipping_city").val("");
						$("#billing_state").val("");
						$("#shipping_state").val("");
					});
				</script>';
			}
		}
	}
}

/* POPUPS LOGIC */
add_action( 'wp_footer', 'popups' );
function popups() { ?>
    <div id="preferences-popup" class="popup">
		<div class="popup-content">
            <?php echo do_shortcode(pll__('[elementor-template id="10515"]')); ?>
            <span class="popup-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15.8048 0.195194C15.6798 0.0702115 15.5102 0 15.3335 0C15.1567 0 14.9871 0.0702115 14.8621 0.195194L8 7.05732L1.13788 0.195194C1.01286 0.0702115 0.843315 0 0.666536 0C0.489757 0 0.320215 0.0702115 0.195194 0.195194C0.0702115 0.320215 0 0.489757 0 0.666536C0 0.843315 0.0702115 1.01286 0.195194 1.13788L7.05732 8L0.195194 14.8621C0.0702115 14.9871 0 15.1567 0 15.3335C0 15.5102 0.0702115 15.6798 0.195194 15.8048C0.320215 15.9298 0.489757 16 0.666536 16C0.843315 16 1.01286 15.9298 1.13788 15.8048L8 8.94268L14.8621 15.8048C14.9871 15.9298 15.1567 16 15.3335 16C15.5102 16 15.6798 15.9298 15.8048 15.8048C15.9298 15.6798 16 15.5102 16 15.3335C16 15.1567 15.9298 14.9871 15.8048 14.8621L8.94268 8L15.8048 1.13788C15.9298 1.01286 16 0.843315 16 0.666536C16 0.489757 15.9298 0.320215 15.8048 0.195194Z" fill="#353D3B"/>
                </svg>
            </span>
        </div>
		<div class="popup-overlay"></div>
    </div>
    <div id="cookie-popup" class="popup">
        <div class="popup-content">
            <?php echo do_shortcode(pll__('[elementor-template id="10509"]')); ?>
            <span class="popup-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15.8048 0.195194C15.6798 0.0702115 15.5102 0 15.3335 0C15.1567 0 14.9871 0.0702115 14.8621 0.195194L8 7.05732L1.13788 0.195194C1.01286 0.0702115 0.843315 0 0.666536 0C0.489757 0 0.320215 0.0702115 0.195194 0.195194C0.0702115 0.320215 0 0.489757 0 0.666536C0 0.843315 0.0702115 1.01286 0.195194 1.13788L7.05732 8L0.195194 14.8621C0.0702115 14.9871 0 15.1567 0 15.3335C0 15.5102 0.0702115 15.6798 0.195194 15.8048C0.320215 15.9298 0.489757 16 0.666536 16C0.843315 16 1.01286 15.9298 1.13788 15.8048L8 8.94268L14.8621 15.8048C14.9871 15.9298 15.1567 16 15.3335 16C15.5102 16 15.6798 15.9298 15.8048 15.8048C15.9298 15.6798 16 15.5102 16 15.3335C16 15.1567 15.9298 14.9871 15.8048 14.8621L8.94268 8L15.8048 1.13788C15.9298 1.01286 16 0.843315 16 0.666536C16 0.489757 15.9298 0.320215 15.8048 0.195194Z" fill="#353D3B"/>
                </svg>
            </span>
        </div>
    </div>
    <div id="subscription-popup" class="popup">
        <div class="popup-content">
            <?php echo do_shortcode(pll__('[elementor-template id="10512"]')); ?>
            <span class="popup-close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15.8048 0.195194C15.6798 0.0702115 15.5102 0 15.3335 0C15.1567 0 14.9871 0.0702115 14.8621 0.195194L8 7.05732L1.13788 0.195194C1.01286 0.0702115 0.843315 0 0.666536 0C0.489757 0 0.320215 0.0702115 0.195194 0.195194C0.0702115 0.320215 0 0.489757 0 0.666536C0 0.843315 0.0702115 1.01286 0.195194 1.13788L7.05732 8L0.195194 14.8621C0.0702115 14.9871 0 15.1567 0 15.3335C0 15.5102 0.0702115 15.6798 0.195194 15.8048C0.320215 15.9298 0.489757 16 0.666536 16C0.843315 16 1.01286 15.9298 1.13788 15.8048L8 8.94268L14.8621 15.8048C14.9871 15.9298 15.1567 16 15.3335 16C15.5102 16 15.6798 15.9298 15.8048 15.8048C15.9298 15.6798 16 15.5102 16 15.3335C16 15.1567 15.9298 14.9871 15.8048 14.8621L8.94268 8L15.8048 1.13788C15.9298 1.01286 16 0.843315 16 0.666536C16 0.489757 15.9298 0.320215 15.8048 0.195194Z" fill="#353D3B"/>
                </svg>
            </span>
        </div>
		<div class="popup-overlay"></div>
    </div>
    <?php if(is_product()) { ?>
        <div id="consult-popup" class="popup">
            <div class="popup-content">
                <?php echo do_shortcode(pll__('[elementor-template id="14322"]')); ?>
                <span class="popup-close">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15.8048 0.195194C15.6798 0.0702115 15.5102 0 15.3335 0C15.1567 0 14.9871 0.0702115 14.8621 0.195194L8 7.05732L1.13788 0.195194C1.01286 0.0702115 0.843315 0 0.666536 0C0.489757 0 0.320215 0.0702115 0.195194 0.195194C0.0702115 0.320215 0 0.489757 0 0.666536C0 0.843315 0.0702115 1.01286 0.195194 1.13788L7.05732 8L0.195194 14.8621C0.0702115 14.9871 0 15.1567 0 15.3335C0 15.5102 0.0702115 15.6798 0.195194 15.8048C0.320215 15.9298 0.489757 16 0.666536 16C0.843315 16 1.01286 15.9298 1.13788 15.8048L8 8.94268L14.8621 15.8048C14.9871 15.9298 15.1567 16 15.3335 16C15.5102 16 15.6798 15.9298 15.8048 15.8048C15.9298 15.6798 16 15.5102 16 15.3335C16 15.1567 15.9298 14.9871 15.8048 14.8621L8.94268 8L15.8048 1.13788C15.9298 1.01286 16 0.843315 16 0.666536C16 0.489757 15.9298 0.320215 15.8048 0.195194Z" fill="#353D3B"/>
                    </svg>
                </span>
            </div>
            <div class="popup-overlay"></div>
        </div>
    <?php }
}

/* ABANDONED CART EMAILS TRANSLATION */
add_action( 'init', 'register_abandoned_cart_lite_email_strings' );
function register_abandoned_cart_lite_email_strings() {
    pll_register_string( 'ac_email_subject', 'Did you forget something in your cart?', 'Abandoned Cart Lite' );
    pll_register_string( 'ac_email_body', 'Hi {{customer.firstname}}, you left some items in your cart. Come back soon to complete your purchase!', 'Abandoned Cart Lite' );
    //pll_register_string( 'ac_email_footer', 'Thank you for shopping with us!', 'Abandoned Cart Lite' );
}
add_action( 'woocommerce_add_to_cart', 'store_cart_language_for_abandoned_cart' );
function store_cart_language_for_abandoned_cart() {
    if ( is_user_logged_in() ) {
        $lang = pll_current_language();
        update_user_meta( get_current_user_id(), 'abandoned_cart_lang', $lang );
    }
}
add_filter( 'woocommerce_ac_email_body', 'translate_ac_email_body', 10, 2 );
add_filter( 'woocommerce_ac_email_subject', 'translate_ac_email_subject', 10, 2 );
//add_filter( 'woocommerce_ac_email_footer_text', 'translate_ac_email_footer', 10 );
function translate_ac_email_subject( $subject, $email_data ) {
    $lang = get_ac_user_language( $email_data );
    return pll__in( 'Did you forget something in your cart?', $lang );
}
function translate_ac_email_body( $body, $email_data ) {
    $lang = get_ac_user_language( $email_data );
    return wpautop( pll__in( 'Hi {{customer.firstname}}, you left some items in your cart. Come back soon to complete your purchase!', $lang ) );
}
/*function translate_ac_email_footer( $footer ) {
    $lang = pll_current_language(); // fallback
    return pll__in( 'Thank you for shopping with us!', $lang );
}*/
function get_ac_user_language( $email_data ) {
    if ( ! empty( $email_data['user_id'] ) ) {
        return get_user_meta( $email_data['user_id'], 'abandoned_cart_lang', true ) ?: pll_default_language();
    }
    return pll_default_language();
}
function pll__in( $string, $lang ) {
    $current_lang = pll_current_language();
    if ( $current_lang !== $lang ) {
        pll_set_language( $lang );
        $translated = pll__( $string );
        pll_set_language( $current_lang );
        return $translated;
    }
    return pll__( $string );
}

/* ADDITIONAL FUNCTIONS */
if(file_exists(get_template_directory() . '/includes/cpt.php')) require_once(get_template_directory() . '/includes/cpt.php');
if(file_exists(get_template_directory() . '/includes/ctx-feed-currency.php')) require_once(get_template_directory() . '/includes/ctx-feed-currency.php');
if(file_exists(get_template_directory() . '/includes/ctx-feed-config.php')) require_once(get_template_directory() . '/includes/ctx-feed-config.php');
if(file_exists(get_template_directory() . '/includes/ctx-feed-language.php')) require_once(get_template_directory() . '/includes/ctx-feed-language.php');
if ( file_exists( get_template_directory() . '/includes/woocommerce.php' ) ) {
	require_once get_template_directory() . '/includes/woocommerce.php';
}
if ( file_exists( get_template_directory() . '/includes/gtm-elementor-forms.php' ) ) {
	require_once get_template_directory() . '/includes/gtm-elementor-forms.php';
}
if ( file_exists( get_template_directory() . '/includes/visitor-country.php' ) ) {
	require_once get_template_directory() . '/includes/visitor-country.php';
}
if ( file_exists( get_template_directory() . '/includes/polylang-geo-language.php' ) ) {
	require_once get_template_directory() . '/includes/polylang-geo-language.php';
}
if ( file_exists( get_template_directory() . '/includes/polylang-wc-no-price-sync.php' ) ) {
	require_once get_template_directory() . '/includes/polylang-wc-no-price-sync.php';
}


/* Elementor Pro Theme Builder: Single Product → Products → Simple product / Variable product (Display Conditions). */
if ( class_exists( '\ElementorPro\Modules\ThemeBuilder\Conditions\Condition_Base' ) ) {
	final class Blankslate_Elementor_Condition_Product_Type_Simple extends \ElementorPro\Modules\ThemeBuilder\Conditions\Condition_Base {
		public static function get_type() {
			return 'singular';
		}
		public static function get_priority() {
			return 35;
		}
		public function get_name() {
			return 'product_type_simple';
		}
		public function get_label() {
			return esc_html__( 'Simple product', 'blankslate' );
		}
		public function check( $args = [] ) {
			if ( ! function_exists( 'wc_get_product' ) || ! is_singular( 'product' ) ) {
				return false;
			}
			$product = wc_get_product( get_queried_object_id() );
			return $product && $product->is_type( 'simple' );
		}
	}
	final class Blankslate_Elementor_Condition_Product_Type_Variable extends \ElementorPro\Modules\ThemeBuilder\Conditions\Condition_Base {
		public static function get_type() {
			return 'singular';
		}
		public static function get_priority() {
			return 35;
		}
		public function get_name() {
			return 'product_type_variable';
		}
		public function get_label() {
			return esc_html__( 'Variable product', 'blankslate' );
		}
		public function check( $args = [] ) {
			if ( ! function_exists( 'wc_get_product' ) || ! is_singular( 'product' ) ) {
				return false;
			}
			$product = wc_get_product( get_queried_object_id() );
			return $product && $product->is_type( 'variable' );
		}
	}
	function blankslate_register_elementor_product_type_conditions( $conditions_manager ) {
		$product = $conditions_manager->get_condition( 'product' );
		if ( ! $product ) {
			return;
		}
		$product->register_sub_condition( new Blankslate_Elementor_Condition_Product_Type_Simple() );
		$product->register_sub_condition( new Blankslate_Elementor_Condition_Product_Type_Variable() );
	}
	add_action( 'elementor/theme/register_conditions', 'blankslate_register_elementor_product_type_conditions', 100 );
}

add_action('phpmailer_init', function($phpmailer) {
    $phpmailer->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ]
    ];
});