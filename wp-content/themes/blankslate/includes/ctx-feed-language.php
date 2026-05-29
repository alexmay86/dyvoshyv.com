<?php
/**
 * CTX Feed + Polylang: limit products by feed language.
 *
 * Default (UAH) feed → default Polylang language only.
 * EUR feed          → English products only.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Dyvoshyv_Ctx_Feed_Language {

	/** @var string|null Language slug for the feed currently being built. */
	private static $active_language = null;

	public static function bootstrap() {
		add_action( 'plugins_loaded', array( __CLASS__, 'init' ), 20 );
		add_action( 'init', array( __CLASS__, 'init' ), 99 );
	}

	public static function init() {
		add_filter( 'dyvoshyv_ctx_feed_language', array( __CLASS__, 'resolve_feed_language' ), 10, 2 );

		add_action( 'before_woo_feed_get_product_information', array( __CLASS__, 'prepare_feed_language' ), 5, 1 );
		add_action( 'before_woo_feed_generate_batch_data', array( __CLASS__, 'prepare_feed_language' ), 5, 1 );

		add_filter( 'ctx_filter_arguments_for_product_query', array( __CLASS__, 'filter_product_query_args' ), 10, 2 );
		add_filter( 'ctx_validate_product_before_include', array( __CLASS__, 'validate_product_language' ), 10, 3 );
	}

	/**
	 * @param string|false $lang   Current value.
	 * @param object|null  $config CTX Feed config.
	 * @return string|false
	 */
	public static function resolve_feed_language( $lang, $config ) {
		if ( apply_filters( 'dyvoshyv_ctx_feed_is_eur_feed', false, $config ) ) {
			return self::get_english_language_slug( $config );
		}

		if ( function_exists( 'pll_default_language' ) ) {
			return pll_default_language( 'slug' );
		}

		return $lang;
	}

	/**
	 * @param object|null $config CTX Feed config.
	 */
	public static function prepare_feed_language( $config ) {
		$lang = apply_filters( 'dyvoshyv_ctx_feed_language', false, $config );

		if ( ! is_string( $lang ) || '' === $lang ) {
			self::$active_language = null;
			return;
		}

		self::$active_language = $lang;
		self::patch_config_feed_language( $config, $lang );
	}

	/**
	 * @param array  $args Query arguments.
	 * @param string $type Query type.
	 * @return array
	 */
	public static function filter_product_query_args( $args, $type ) {
		if ( ! self::$active_language || ! self::polylang_active() ) {
			return $args;
		}

		// Polylang's lang arg (handles variations via parent better than a language tax_query).
		$args['lang'] = self::$active_language;

		return $args;
	}

	/**
	 * @param bool         $valid   Whether the product is valid.
	 * @param WC_Product   $product Product.
	 * @param object|null  $config  Feed config.
	 * @return bool
	 */
	public static function validate_product_language( $valid, $product, $config ) {
		if ( ! $valid || ! $product instanceof WC_Product ) {
			return $valid;
		}

		$lang = self::$active_language;
		if ( ! $lang ) {
			$lang = apply_filters( 'dyvoshyv_ctx_feed_language', false, $config );
		}

		if ( ! is_string( $lang ) || '' === $lang || ! function_exists( 'pll_get_post_language' ) ) {
			return $valid;
		}

		$post_id = $product->get_id();
		if ( $product->is_type( 'variation' ) ) {
			$post_id = $product->get_parent_id() ? $product->get_parent_id() : $post_id;
		}

		$post_lang = pll_get_post_language( $post_id, 'slug' );

		if ( $post_lang && $post_lang === $lang ) {
			return true;
		}

		// Untranslated products belong to the default language in Polylang.
		if ( ! $post_lang && function_exists( 'pll_default_language' ) && $lang === pll_default_language( 'slug' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param object|null $config CTX Feed config.
	 * @return string
	 */
	public static function get_english_language_slug( $config = null ) {
		$override = apply_filters( 'dyvoshyv_ctx_feed_eur_language', null, $config );
		if ( is_string( $override ) && '' !== $override ) {
			return $override;
		}

		if ( self::polylang_active() && isset( PLL()->model ) ) {
			foreach ( PLL()->model->get_languages_list() as $language ) {
				$slug = isset( $language->slug ) ? (string) $language->slug : '';
				if ( in_array( $slug, array( 'en', 'eng', 'en-gb', 'en-us' ), true ) ) {
					return $slug;
				}
				if ( ! empty( $language->locale ) && 0 === strpos( strtolower( $language->locale ), 'en' ) ) {
					return $slug;
				}
			}
		}

		return 'en';
	}

	/**
	 * @param object|null $config   CTX Feed config.
	 * @param string      $language Polylang language slug.
	 */
	private static function patch_config_feed_language( $config, $language ) {
		if ( ! is_object( $config ) ) {
			return;
		}

		try {
			$ref  = new ReflectionClass( $config );
			$prop = $ref->getProperty( 'config' );
			$prop->setAccessible( true );
			$cfg = $prop->getValue( $config );
			if ( is_array( $cfg ) ) {
				$cfg['feedLanguage'] = $language;
				$prop->setValue( $config, $cfg );
			}
		} catch ( ReflectionException $e ) { // phpcs:ignore
		}
	}

	/**
	 * @return bool
	 */
	private static function polylang_active() {
		return defined( 'POLYLANG_BASENAME' ) && function_exists( 'PLL' ) && PLL();
	}
}

Dyvoshyv_Ctx_Feed_Language::bootstrap();
