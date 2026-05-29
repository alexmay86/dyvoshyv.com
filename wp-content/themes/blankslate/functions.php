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
register_nav_menus( array( 'main-menu' => esc_html__( 'Main Menu', 'blankslate' ) ) );
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
echo '<div class="notice notice-info"><p><a href="' . esc_url( $admin_url ), esc_html( $param ) . 'dismiss" class="alignright" style="text-decoration:none"><big>' . esc_html__( 'Ⓧ', 'blankslate' ) . '</big></a>' . wp_kses_post( __( '<big><strong>🏆 Thank you for using BlankSlate!</strong></big>', 'blankslate' ) ) . '<p>' . esc_html__( 'Powering over 10k websites! Buy me a sandwich! 🥪', 'blankslate' ) . '</p><a href="https://github.com/bhadaway/blankslate/issues/57" class="button-primary" target="_blank"><strong>' . esc_html__( 'How do you use BlankSlate?', 'blankslate' ) . '</strong></a> <a href="https://opencollective.com/blankslate" class="button-primary" style="background-color:green;border-color:green" target="_blank"><strong>' . esc_html__( 'Donate', 'blankslate' ) . '</strong></a> <a href="https://wordpress.org/support/theme/blankslate/reviews/#new-post" class="button-primary" style="background-color:purple;border-color:purple" target="_blank"><strong>' . esc_html__( 'Review', 'blankslate' ) . '</strong></a> <a href="https://github.com/bhadaway/blankslate/issues" class="button-primary" style="background-color:orange;border-color:orange" target="_blank"><strong>' . esc_html__( 'Support', 'blankslate' ) . '</strong></a></p></div>';
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

	$dir = WP_PLUGIN_DIR . '/elementor/assets/lib/font-awesome/css';
	if ( ! is_dir( $dir ) ) {
		return;
	}

	$files = glob( $dir . '/*.css' );
	if ( ! is_array( $files ) ) {
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
add_filter( 'excerpt_more', 'blankslate_excerpt_read_more_link' );
function blankslate_excerpt_read_more_link( $more ) {
if ( !is_admin() ) {
global $post;
return ' <a href="' . esc_url( get_permalink( $post->ID ) ) . '" class="more-link">' . sprintf( __( '...%s', 'blankslate' ), '<span class="screen-reader-text">  ' . esc_html( get_the_title() ) . '</span>' ) . '</a>';
}
}
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
'name' => esc_html__( 'Sidebar Widget Area', 'blankslate' ),
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
if ( !is_admin() ) {
global $id;
$get_comments = get_comments( 'status=approve&post_id=' . $id );
$comments_by_type = separate_comments( $get_comments );
return count( $comments_by_type['comment'] );
} else {
return $count;
}
}
add_filter('woocommerce_breadcrumb_defaults', 'wcc_change_breadcrumb_delimiter', 20);
function wcc_change_breadcrumb_delimiter($defaults) {
    // Змініть роздільник breadcrumbs з '/' на '>'
    $defaults['delimiter'] = ' > ';
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

function custom_woocommerce_cart_totals() {
    ob_start();

    // Показувати підсумок (і кнопку) лише якщо кошик НЕ порожній
    if ( ! WC()->cart->is_empty() ) {
        // Умова, як у вас було: тільки на сторінці checkout або cart
        if ( is_checkout() || is_cart() ) {
            woocommerce_cart_totals();
        }
    }

    return ob_get_clean();
}
add_shortcode('custom_cart_totals', 'custom_woocommerce_cart_totals');


// Функція для виведення атрибутів товару
function display_product_attributes($atts) {
    // Отримати атрибути шорткоду
    $atts = shortcode_atts(array(
        'product_id' => ''
    ), $atts, 'product_attributes');

    // Отримати об'єкт товару за ID
    if (!empty($atts['product_id'])) {
        $product = wc_get_product($atts['product_id']);
    } else {
        global $product;
    }

    // Перевірка, чи є об'єкт товару
    if (!$product || !is_a($product, 'WC_Product')) {
        return 'Товар не знайдено або ID товару не вказано.';
    }

    // Отримати атрибути товару
    $attributes = $product->get_attributes();
    if (empty($attributes)) {
        return 'Атрибути товару відсутні.';
    }

    // Список дозволених атрибутів (з префіксом 'pa_' для глобальних атрибутів)
    $allowed_attributes = array('pa_color', 'pa_material', 'pa_size'); // Змініть на ваші слаги атрибутів

    // Перевірка наявності дозволених атрибутів
    $found = false;

    // Формувати вихідний HTML
    $output = '<div class="product-attributes">';
    foreach ($attributes as $attribute) {
        // Отримати слаг атрибуту
        $attr_name = $attribute->get_name();

        // Додатковий налагоджувальний вивід
        // Ви можете закоментувати ці рядки після перевірки
        // $output .= '<!-- Attribute found: ' . esc_html($attr_name) . ' -->';

        // Перевірка, чи атрибут є в списку дозволених
        if (!in_array($attr_name, $allowed_attributes)) {
            continue; // Пропустити атрибут, якщо він не в списку
        }

        $found = true; // Знайдено принаймні один дозволений атрибут

        if ($attribute->is_taxonomy()) {
            $terms = wc_get_product_terms($product->get_id(), $attr_name, array('fields' => 'names'));
            $output .= '<p><strong>' . esc_html(wc_attribute_label($attr_name)) . ':</strong> ' . esc_html(implode(', ', $terms)) . '</p>';
        } else {
            $output .= '<p><strong>' . esc_html(wc_attribute_label($attr_name)) . ':</strong> ' . esc_html(implode(', ', $attribute->get_options())) . '</p>';
        }
    }
    $output .= '</div>';

    if (!$found) {
        return 'Не знайдено дозволених атрибутів (Color, Material, Size).';
    }

    return $output;
}

// Реєстрація шорткоду
add_shortcode('product_attributes', 'display_product_attributes');


add_filter( 'woocommerce_quantity_input_args', 'custom_remove_qty_label', 10, 2 );

function custom_remove_qty_label( $args, $product ) {
    $args['input_value'] = isset( $args['input_value'] ) ? $args['input_value'] : 1; // За замовчуванням кількість 1.
    $args['label'] = ''; // Прибирає текст "QTY".
    return $args;
}

// Функция для получения номера заказа
function dyvoshyv_order_number_shortcode() {
    if ( is_order_received_page() ) {
        // Получаем ID заказа из URL
        $order_id = absint( get_query_var( 'order-received' ) );
        // Получаем объект заказа
        $order = wc_get_order( $order_id );
        if ( $order ) {
            // Возвращаем номер заказа с нужным классом
            return '<span class="order-number">' . esc_html( $order->get_order_number() ) . '</span>';
        }
    }
    return '';
}
// Регистрируем шорткод [dyvoshyv_order_number]
add_shortcode( 'dyvoshyv_order_number', 'dyvoshyv_order_number_shortcode' );

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

            // Перевіряємо, чи є в URL "/eng/" — вважаємо, що це англійська
            if ( str_contains($link, '/eng/') ) {
                // АНГЛІЙСЬКА: ведемо на /eng/collections/<cat-slug>
                $new_url = home_url( "/eng/collections/$cat_slug/" );
            } else {
                // УКРАЇНСЬКА (або інша) → /kolektsii/<cat-slug>
                $new_url = home_url( "/kolektsii/$cat_slug/" );
            }

            // Підміняємо сам лінк у крихті
            $crumbs[$index][1] = $new_url;

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

/* DISABLE LIQPAY FOR EURO CURRENCY */
add_filter('woocommerce_available_payment_gateways', function ($available_gateways) {
    if (is_admin() || !is_checkout()) {
        return $available_gateways;
    }

    // Get the current currency
    $current_currency = get_woocommerce_currency();

    // Define the currency in which LiqPay should be hidden
    if (in_array($current_currency, ['EUR', 'USD']) && isset($available_gateways['liqpay-webplus'])) {
        unset($available_gateways['liqpay-webplus']);
    }

    return $available_gateways;
});

/*  PAYMENT METHODS ICONS */
add_filter('woocommerce_gateway_icon', 'custom_woocommerce_payment_gateway_icons', 10, 2);
function custom_woocommerce_payment_gateway_icons($icon, $gateway_id) {
    if ($gateway_id === 'ppcp-gateway') {
        $icon = '<img src="' . get_template_directory_uri() . '/img/paypal.svg" alt="PayPal" width="65" height="40">';
    }

    if ($gateway_id === 'liqpay-webplus') {
        $icon = '<img src="' . get_template_directory_uri() . '/img/liqpay.svg" alt="LiqPay" width="65" height="42" style="">';
        $icon .= '<span style="display:inline-block;height:42px;border-left:1px solid #D5D8DC;margin-left:16px;margin-right:16px;vertical-align:top"></span>';
        $icon .= '<img src="' . get_template_directory_uri() . '/img/payment_methods.svg" alt="Payment Platforms" width="284" height="40" style="margin-left:0">';
    }

    return $icon;
}

/* SHIPPING METHODS STRINGS TRANSLATION */
add_filter('wcus_checkout_i18n', function ($i18n, $lang) {
    // Пример текстов для русской версии
    $i18n = [
        'shipping_method_name' => pll__('Нова Пошта по Україні'),
        'fields_title' => pll__('Вкажіть адресу доставки'),
        'shipping_type_warehouse' => pll__('На відділення (або в Поштомат)'),
        'shipping_type_doors' => pll__('На адресу'),
        'ui' => [
            'city_placeholder' => pll__('Оберіть місто'),
            'warehouse_placeholder' => pll__('Оберіть відділення'),
            'custom_address_placeholder' => pll__('Введіть адресу'),
            'text_search' => pll__('Введіть значення для пошуку'),
            'text_loading' => pll__('Завантаження...'),
            'text_more' => pll__('Завантажити ще'),
            'text_not_found' => pll__('Нічого не знайдено')
        ]
    ];

    return $i18n;
}, 10, 2);
add_filter('woocommerce_shipping_rate_label', function ($label, $rate) {
    if (function_exists('pll__') && strpos($rate->get_method_id(), 'meest') !== false) {
        return pll__('Meest Міжнародна доставка'); // Replace with translatable name
    }
    return $label;
}, 10, 2);

/* MEEST SHIPPING COST LABEL */
add_filter('woocommerce_cart_shipping_method_full_label', function ($label, $method) {
    if (function_exists('pll__')) {
        // Custom text to be added
        $custom_text = pll__('Вартість доставки в ваш регіон');
        // Get the shipping cost for the selected method
        $shipping_cost = $method->get_cost();
        
        if (!empty($shipping_cost)) {
            // Split the label into two parts: the method name and the price
            $parts = explode($method->get_label(), $label);
            
            // Reassemble the label with custom text between the method name and the price
            $label = $parts[0] . $method->get_label() . ' ' . '<br><span class="shipping-cost-text">' . $custom_text . '</span> ' . wc_price($shipping_cost);
        }
    }
    return $label;
}, 10, 2);

/* DISABLE MEEST UPDATE */
add_filter('site_transient_update_plugins', 'remove_update_notification');
function remove_update_notification($value) {
    unset($value->response[ "meest-for-woocommerce/meest_shipping.php" ]);
    unset($value->response[ "wc-ukr-shipping/wc-ukr-shipping.php" ]);
    return $value;
}

/* COUNTRIES FIELD SYNCHRONIZATION */
function synchronize_country() {
    if(is_checkout()) { ?>
        <script>
        jQuery(document).ready(function($) {
            $('#billing_meest_country_id').on('change', function() {
                const country = $(this).find('option:selected').text();
                if($('#billing_country').length) {
                    var shipping_country = $('#billing_country').find('option').filter(function() {
                        return $(this).text() === country
                    }).val();
                    console.log(shipping_country);
                    $('#billing_country').val(shipping_country).change();
                }
            });
        });
        </script>
    <?php }
}
add_action('wp_footer', 'synchronize_country');
