<?php
/**
 * CTX Feed + CURCY: per-feed currency.
 *
 * Default feed  → store currency (UAH), unchanged.
 * EUR feed      → CURCY fixed EUR prices (name feed *-eur* or list it in ctx-feed-config.php).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Dyvoshyv_Ctx_Feed_Currency {

	const EUR = 'EUR';

	/** @var bool */
	private static $initialized = false;

	/** @var bool */
	private static $feed_active = false;

	/** @var string|null */
	private static $saved_currency = null;

	/** @var string|null */
	private static $export_currency = null;

	/** @var object|null */
	private static $current_config = null;

	/** @var string|null */
	private static $active_feed_key = null;

	/** @var bool */
	private static $wc_hooks_added = false;

	public static function bootstrap() {
		add_action( 'plugins_loaded', array( __CLASS__, 'init' ), 20 );
		add_action( 'init', array( __CLASS__, 'init' ), 99 );
	}

	public static function init() {
		if ( self::$initialized ) {
			return;
		}
		self::$initialized = true;

		if ( class_exists( 'WOOMULTI_CURRENCY_Data' ) ) {
			add_filter( 'wmc_get_price_condition', array( __CLASS__, 'allow_price_conversion_during_feed' ), 20 );
		}

		add_action( 'before_woo_feed_generate_batch_data', array( __CLASS__, 'on_feed_start' ), 1, 1 );
		add_action( 'after_woo_feed_generate_batch_data', array( __CLASS__, 'on_feed_end' ), 999, 1 );
		add_action( 'before_woo_feed_get_product_information', array( __CLASS__, 'on_feed_start' ), 1, 1 );
		add_action( 'after_woo_feed_get_product_information', array( __CLASS__, 'on_feed_end' ), 999, 1 );
		add_action( 'woo_feed_before_product_loop', array( __CLASS__, 'on_product_loop_start' ), 1, 3 );
		add_action( 'woo_feed_after_product_loop', array( __CLASS__, 'on_feed_end' ), 999, 3 );

		$prio = 999999;
		foreach ( array(
			'woo_feed_filter_product_regular_price',
			'woo_feed_filter_product_sale_price',
			'woo_feed_filter_product_price',
			'woo_feed_filter_product_regular_price_with_tax',
			'woo_feed_filter_product_sale_price_with_tax',
			'woo_feed_filter_product_price_with_tax',
			'woo_feed_parent_product_sale_price',
		) as $hook ) {
			add_filter( $hook, array( __CLASS__, 'apply_curcy_price' ), $prio, 5 );
		}

		add_filter( 'woo_feed_get_attribute', array( __CLASS__, 'filter_feed_attribute_output' ), $prio, 5 );
		add_filter( 'woo_feed_filter_product_currency', array( __CLASS__, 'feed_currency_column' ), $prio, 3 );
		add_action( 'wp_ajax_dyvoshyv_ctx_feed_debug', array( __CLASS__, 'ajax_debug' ) );
	}

	public static function allow_price_conversion_during_feed( $condition ) {
		return self::$feed_active ? false : $condition;
	}

	/**
	 * @param string       $currency Store currency from CTX.
	 * @param WC_Product   $product  Product.
	 * @param object|null  $config   Feed config.
	 * @return string
	 */
	public static function feed_currency_column( $currency, $product = null, $config = null ) {
		if ( ! self::should_apply_for_feed( $config ) ) {
			return $currency;
		}
		return self::EUR;
	}

	/**
	 * @param object|null $config CTXFeed config.
	 */
	public static function on_feed_start( $config = null ) {
		$feed_key = self::get_feed_key( $config );

		// Default-currency feed: tear down any EUR session left from a previous feed in this request.
		if ( ! self::should_apply_for_feed( $config ) ) {
			if ( self::$feed_active ) {
				self::on_feed_end( $config );
			}
			// CTX may still read feedCurrency=EUR from saved rules — force store currency for this run.
			self::patch_config_feed_currency( $config, get_woocommerce_currency() );
			return;
		}

		// Switching from another feed in the same request.
		if ( self::$feed_active && self::$active_feed_key !== $feed_key ) {
			self::on_feed_end( $config );
		}

		if ( self::$feed_active ) {
			return;
		}

		self::$current_config  = $config;
		self::$active_feed_key = $feed_key;
		self::$export_currency = self::EUR;

		if ( class_exists( 'WOOMULTI_CURRENCY_Data' ) ) {
			$data                 = WOOMULTI_CURRENCY_Data::get_ins();
			self::$saved_currency = $data->get_current_currency();
			$data->set_current_currency( self::EUR );
		}

		self::$feed_active = true;
		self::patch_config_feed_currency( $config, self::EUR );
		self::add_wc_price_hooks();
	}

	public static function on_product_loop_start( $product_ids, $feed_rules, $config = null ) {
		self::on_feed_start( self::is_config( $config ) ? $config : null );
	}

	public static function on_feed_end( $config = null ) {
		self::remove_wc_price_hooks();

		if ( self::$feed_active && class_exists( 'WOOMULTI_CURRENCY_Data' ) ) {
			$data    = WOOMULTI_CURRENCY_Data::get_ins();
			$restore = self::$saved_currency ? self::$saved_currency : $data->get_default_currency();
			$data->set_current_currency( $restore );
		}

		self::$feed_active     = false;
		self::$saved_currency  = null;
		self::$export_currency = null;
		self::$current_config  = null;
		self::$active_feed_key = null;
	}

	public static function apply_curcy_price( $price, $product, $config, $with_tax, $price_type ) {
		if ( ! $product instanceof WC_Product || ! self::should_apply_for_feed( $config ) ) {
			return $price;
		}

		self::on_feed_start( self::is_config( $config ) ? $config : null );

		$resolved = self::resolve_export_price( $product, $config, self::map_price_type( $product, $price_type ) );
		return null !== $resolved ? $resolved : $price;
	}

	public static function filter_feed_attribute_output( $output, $product, $config, $product_attribute, $merchant_attribute = '' ) {
		if ( ! $product instanceof WC_Product || ! self::should_apply_for_feed( $config ) ) {
			return $output;
		}

		if ( self::is_currency_feed_attribute( $product_attribute, $merchant_attribute ) ) {
			return self::EUR;
		}

		if ( ! self::is_price_feed_attribute( $product_attribute, $merchant_attribute ) ) {
			return $output;
		}

		self::on_feed_start( $config );

		$price_type = self::map_price_type_from_attribute( $product, $product_attribute );
		$resolved   = self::resolve_export_price( $product, $config, $price_type );

		if ( null === $resolved ) {
			return $output;
		}

		// Runs after ProductHelper::add_prefix_suffix(); replacing $output drops the currency suffix.
		return self::format_price_for_feed( $resolved, $output, $config, $product_attribute, $merchant_attribute );
	}

	/**
	 * True when this feed should use non-default (EUR) CURCY prices.
	 *
	 * @param object|null $config CTXFeed config.
	 * @return bool
	 */
	public static function should_apply_for_feed( $config ) {
		return self::is_eur_feed( $config );
	}

	/**
	 * Only whitelisted feeds (ctx-feed-config.php) — not feedCurrency from saved rules.
	 *
	 * @param object|null $config CTXFeed config.
	 * @return bool
	 */
	public static function is_eur_feed( $config ) {
		return (bool) apply_filters( 'dyvoshyv_ctx_feed_is_eur_feed', false, $config );
	}

	/**
	 * @param object|null $config CTXFeed config.
	 * @return string
	 */
	public static function get_export_currency( $config = null ) {
		if ( self::is_eur_feed( $config ) ) {
			$override = apply_filters( 'dyvoshyv_ctx_feed_currency', null, $config );
			if ( is_string( $override ) && '' !== $override ) {
				return strtoupper( $override );
			}
			return self::EUR;
		}

		return get_woocommerce_currency();
	}

	/**
	 * @param object|null $config CTXFeed config.
	 * @return string
	 */
	private static function get_feed_key( $config ) {
		if ( ! self::is_config( $config ) ) {
			return '';
		}

		if ( method_exists( $config, 'get_feed_option_name' ) ) {
			$key = $config->get_feed_option_name( false );
			if ( $key ) {
				return $key;
			}
		}

		if ( method_exists( $config, 'get_feed_name' ) ) {
			return (string) $config->get_feed_name();
		}

		return '';
	}

	private static function resolve_export_price( WC_Product $product, $config, $price_type ) {
		$currency = self::get_export_currency( $config );
		$fixed    = self::resolve_fixed_price( $product, $currency, $price_type, $config );

		return null !== $fixed ? $fixed : null;
	}

	private static function patch_config_feed_currency( $config, $currency ) {
		self::patch_feed_config_array( $config, static function ( array $cfg ) use ( $currency ) {
			$cfg['feedCurrency'] = $currency;
			return $cfg;
		} );
	}

	/**
	 * @param object|null $config   CTXFeed config.
	 * @param callable    $callback Receives config array, returns modified array.
	 */
	private static function patch_feed_config_array( $config, callable $callback ) {
		if ( ! is_object( $config ) ) {
			return;
		}

		try {
			$ref  = new ReflectionClass( $config );
			$prop = $ref->getProperty( 'config' );
			$prop->setAccessible( true );
			$cfg = $prop->getValue( $config );
			if ( is_array( $cfg ) ) {
				$cfg = $callback( $cfg );
				$prop->setValue( $config, $cfg );
			}
		} catch ( ReflectionException $e ) { // phpcs:ignore
		}
	}

	/**
	 * @param string $product_attribute  CTX attribute.
	 * @param string $merchant_attribute Merchant column.
	 * @return bool
	 */
	/**
	 * Build feed price string with column suffix (Facebook: "12.50 EUR").
	 *
	 * @param float|string $price               Numeric price.
	 * @param mixed        $previous_output     Value after CTX prefix/suffix (may include currency).
	 * @param object|null  $config              Feed config.
	 * @param string       $product_attribute   CTX attribute.
	 * @param string       $merchant_attribute  Merchant column.
	 * @return string
	 */
	private static function format_price_for_feed( $price, $previous_output, $config, $product_attribute, $merchant_attribute ) {
		$formatted = wc_format_decimal( $price, wc_get_price_decimals() );
		$suffix    = '';

		if ( self::is_config( $config ) && method_exists( $config, 'get_prefix_suffix' ) ) {
			$ps     = $config->get_prefix_suffix( $product_attribute, $merchant_attribute );
			$suffix = trim( (string) ( $ps['suffix'] ?? '' ) );
		}

		if ( '' === $suffix ) {
			$previous = trim( (string) $previous_output );
			if ( preg_match( '/^[\d.,]+\s+([A-Za-z]{3})\s*$/u', $previous, $matches ) ) {
				$suffix = $matches[1];
			}
		}

		if ( self::should_apply_for_feed( $config ) ) {
			$export = self::get_export_currency( $config );
			if ( '' === $suffix || strtoupper( $suffix ) !== $export ) {
				$suffix = $export;
			}
		}

		if ( '' === $suffix ) {
			return $formatted;
		}

		return $formatted . ( preg_match( '/^\s/', $suffix ) ? '' : ' ' ) . $suffix;
	}

	private static function is_currency_feed_attribute( $product_attribute, $merchant_attribute ) {
		$attr = strtolower( (string) $product_attribute );
		$col  = strtolower( (string) $merchant_attribute );

		if ( in_array( $attr, array( 'currency', 'store_currency' ), true ) ) {
			return true;
		}

		if ( '' !== $col && false !== strpos( $col, 'currency' ) && false === strpos( $col, 'price' ) ) {
			return true;
		}

		return false;
	}

	private static function add_wc_price_hooks() {
		if ( self::$wc_hooks_added ) {
			return;
		}
		self::$wc_hooks_added = true;
		$prio                 = 99999;

		add_filter( 'woocommerce_product_get_regular_price', array( __CLASS__, 'wc_get_regular_price' ), $prio, 2 );
		add_filter( 'woocommerce_product_get_sale_price', array( __CLASS__, 'wc_get_sale_price' ), $prio, 2 );
		add_filter( 'woocommerce_product_get_price', array( __CLASS__, 'wc_get_price' ), $prio, 2 );
		add_filter( 'woocommerce_product_variation_get_regular_price', array( __CLASS__, 'wc_get_regular_price' ), $prio, 2 );
		add_filter( 'woocommerce_product_variation_get_sale_price', array( __CLASS__, 'wc_get_sale_price' ), $prio, 2 );
		add_filter( 'woocommerce_product_variation_get_price', array( __CLASS__, 'wc_get_price' ), $prio, 2 );
		add_filter( 'woocommerce_variation_prices', array( __CLASS__, 'wc_variation_prices' ), $prio, 3 );
	}

	private static function remove_wc_price_hooks() {
		if ( ! self::$wc_hooks_added ) {
			return;
		}
		self::$wc_hooks_added = false;
		$prio                 = 99999;

		remove_filter( 'woocommerce_product_get_regular_price', array( __CLASS__, 'wc_get_regular_price' ), $prio );
		remove_filter( 'woocommerce_product_get_sale_price', array( __CLASS__, 'wc_get_sale_price' ), $prio );
		remove_filter( 'woocommerce_product_get_price', array( __CLASS__, 'wc_get_price' ), $prio );
		remove_filter( 'woocommerce_product_variation_get_regular_price', array( __CLASS__, 'wc_get_regular_price' ), $prio );
		remove_filter( 'woocommerce_product_variation_get_sale_price', array( __CLASS__, 'wc_get_sale_price' ), $prio );
		remove_filter( 'woocommerce_product_variation_get_price', array( __CLASS__, 'wc_get_price' ), $prio );
		remove_filter( 'woocommerce_variation_prices', array( __CLASS__, 'wc_variation_prices' ), $prio );
	}

	public static function wc_get_regular_price( $price, $product ) {
		if ( ! self::$feed_active || ! $product instanceof WC_Product ) {
			return $price;
		}
		$fixed = self::resolve_fixed_price( $product, self::get_export_currency( self::$current_config ), 'regular_price', self::$current_config );
		return null !== $fixed ? $fixed : $price;
	}

	public static function wc_get_sale_price( $price, $product ) {
		if ( ! self::$feed_active || ! $product instanceof WC_Product ) {
			return $price;
		}
		$fixed = self::resolve_fixed_price( $product, self::get_export_currency( self::$current_config ), 'sale_price', self::$current_config );
		return null !== $fixed ? $fixed : $price;
	}

	public static function wc_get_price( $price, $product ) {
		if ( ! self::$feed_active || ! $product instanceof WC_Product ) {
			return $price;
		}
		$type  = $product->is_on_sale( 'edit' ) ? 'sale_price' : 'regular_price';
		$fixed = self::resolve_fixed_price( $product, self::get_export_currency( self::$current_config ), $type, self::$current_config );
		return null !== $fixed ? $fixed : $price;
	}

	public static function wc_variation_prices( $prices, $product, $for_display ) {
		if ( ! self::$feed_active || ! is_array( $prices ) || ! $product instanceof WC_Product ) {
			return $prices;
		}

		$currency = self::get_export_currency( self::$current_config );

		foreach ( $prices as $price_type => $values ) {
			if ( ! is_array( $values ) ) {
				continue;
			}
			foreach ( $values as $variation_id => $ignored ) {
				$variation = wc_get_product( $variation_id );
				if ( ! $variation instanceof WC_Product ) {
					continue;
				}
				$map = ( 'sale_price' === $price_type ) ? 'sale_price' : 'regular_price';
				if ( 'price' === $price_type ) {
					$map = $variation->is_on_sale( 'edit' ) ? 'sale_price' : 'regular_price';
				}
				$fixed = self::read_wmcp_meta_price( $variation, $currency, $map );
				if ( null === $fixed ) {
					$fixed = self::read_wmcp_meta_price( $product, $currency, $map );
				}
				if ( null !== $fixed ) {
					$prices[ $price_type ][ $variation_id ] = $fixed;
				}
			}
		}

		return $prices;
	}

	public static function ajax_debug() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
		}

		$id      = isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : 13269;
		$product = wc_get_product( $id );
		$config  = null;

		if ( ! empty( $_GET['feed_option'] ) ) {
			$option_name = sanitize_text_field( wp_unslash( $_GET['feed_option'] ) );
			if ( 0 !== strpos( $option_name, 'wf_feed_' ) ) {
				$option_name = 'wf_feed_' . $option_name;
			}
			$feed_data = get_option( $option_name );
			if ( $feed_data && class_exists( 'CTXFeed\V5\Utility\Config' ) ) {
				$config = new CTXFeed\V5\Utility\Config(
					array(
						'option_name'  => $option_name,
						'option_value' => $feed_data,
					)
				);
			}
		}

		$out = array(
			'product_id'       => $id,
			'store_currency'   => get_woocommerce_currency(),
			'wmcp_regular'     => get_post_meta( $id, '_regular_price_wmcp', true ),
			'uah_regular'      => $product ? $product->get_regular_price() : null,
			'eur_from_meta'    => $product ? self::read_wmcp_meta_price( $product, 'EUR', 'regular_price' ) : null,
			'is_eur_feed'      => $config ? self::is_eur_feed( $config ) : null,
			'export_currency'  => $config ? self::get_export_currency( $config ) : null,
			'should_apply'     => $config ? self::should_apply_for_feed( $config ) : null,
		);

		wp_send_json_success( $out );
	}

	private static function map_price_type( WC_Product $product, $price_type ) {
		if ( 'sale_price' === $price_type ) {
			return 'sale_price';
		}
		if ( 'price' === $price_type && $product->is_on_sale( 'edit' ) ) {
			return 'sale_price';
		}
		return 'regular_price';
	}

	private static function map_price_type_from_attribute( WC_Product $product, $attribute ) {
		$attribute = (string) $attribute;
		if ( false !== strpos( $attribute, 'sale_price' ) ) {
			return 'sale_price';
		}
		if ( in_array( $attribute, array( 'price', 'current_price', 'wf_cattr__price' ), true ) && $product->is_on_sale( 'edit' ) ) {
			return 'sale_price';
		}
		return 'regular_price';
	}

	private static function is_price_feed_attribute( $product_attribute, $merchant_attribute ) {
		$attr = strtolower( (string) $product_attribute );
		$col  = strtolower( (string) $merchant_attribute );

		$exact = array(
			'price', 'regular_price', 'sale_price', 'current_price',
			'price_with_tax', 'regular_price_with_tax', 'sale_price_with_tax',
			'wf_cattr__price', 'wf_cattr__regular_price', 'wf_cattr__sale_price',
		);

		if ( in_array( $attr, $exact, true ) ) {
			return true;
		}

		return (bool) preg_match( '/(^|[:\/])(g:)?(sale_)?price$/', $col );
	}

	private static function resolve_fixed_price( WC_Product $product, $currency, $price_type, $config = null ) {
		if ( $product->is_type( 'variable' ) ) {
			$variable_price = self::get_variable_fixed_price( $product, $currency, $price_type, $config );
			if ( null !== $variable_price ) {
				return $variable_price;
			}
		}

		foreach ( self::get_wmcp_product_candidates( $product ) as $source ) {
			$fixed = self::read_wmcp_meta_price( $source, $currency, $price_type );
			if ( null !== $fixed ) {
				return $fixed;
			}
		}

		return null;
	}

	private static function get_wmcp_product_candidates( WC_Product $product ) {
		$candidates = array();
		$seen       = array();

		$add = static function ( $p ) use ( &$candidates, &$seen ) {
			if ( $p instanceof WC_Product ) {
				$id = $p->get_id();
				if ( $id && ! isset( $seen[ $id ] ) ) {
					$seen[ $id ]    = true;
					$candidates[] = $p;
				}
			}
		};

		$add( $product );

		if ( $product->is_type( 'variation' ) ) {
			$add( wc_get_product( $product->get_parent_id() ) );
		}

		if ( function_exists( 'pll_get_post' ) && function_exists( 'pll_default_language' ) ) {
			$default_id = pll_get_post( $product->get_id(), pll_default_language( 'slug' ) );
			if ( $default_id ) {
				$add( wc_get_product( $default_id ) );
			}
		}

		if ( class_exists( 'SitePress' ) ) {
			$default_lang = apply_filters( 'wpml_default_language', null );
			if ( $default_lang ) {
				$default_id = apply_filters( 'wpml_object_id', $product->get_id(), 'product', true, $default_lang );
				if ( $default_id ) {
					$add( wc_get_product( $default_id ) );
				}
			}
		}

		return $candidates;
	}

	private static function get_variable_fixed_price( WC_Product $product, $currency, $price_type, $config = null ) {
		$variation_ids = $product->get_children();
		if ( empty( $variation_ids ) ) {
			return null;
		}

		$prices = array();
		foreach ( $variation_ids as $variation_id ) {
			$variation = wc_get_product( $variation_id );
			if ( ! $variation instanceof WC_Product ) {
				continue;
			}
			foreach ( self::get_wmcp_product_candidates( $variation ) as $source ) {
				$fixed = self::read_wmcp_meta_price( $source, $currency, $price_type );
				if ( null !== $fixed ) {
					$prices[] = $fixed;
					break;
				}
			}
		}

		if ( empty( $prices ) ) {
			return null;
		}

		$mode = 'min';
		if ( self::is_config( $config ) && ! empty( $config->variable_price ) ) {
			$mode = $config->variable_price;
		}

		if ( 'max' === $mode ) {
			return (float) max( $prices );
		}
		if ( 'first' === $mode ) {
			return (float) $prices[0];
		}
		return (float) min( $prices );
	}

	private static function is_config( $config ) {
		return is_object( $config ) && method_exists( $config, 'get_feed_currency' );
	}

	private static function read_wmcp_meta_price( WC_Product $product, $currency, $price_type ) {
		$meta_key = ( 'sale_price' === $price_type ) ? '_sale_price_wmcp' : '_regular_price_wmcp';
		$raw      = get_post_meta( $product->get_id(), $meta_key, true );
		if ( '' === $raw || null === $raw ) {
			$raw = $product->get_meta( $meta_key, true );
		}

		$prices = self::decode_wmcp_prices( $raw );
		if ( ! $prices ) {
			return null;
		}

		$value = self::get_price_for_currency( $prices, $currency );
		if ( null === $value || '' === $value ) {
			return null;
		}

		return (float) $value;
	}

	private static function decode_wmcp_prices( $raw ) {
		if ( ! $raw ) {
			return null;
		}

		if ( class_exists( 'WOOMULTI_CURRENCY_Frontend_Price' ) && method_exists( 'WOOMULTI_CURRENCY_Frontend_Price', 'static_json_price_meta' ) ) {
			$prices = WOOMULTI_CURRENCY_Frontend_Price::static_json_price_meta( $raw );
		} elseif ( is_string( $raw ) ) {
			$prices = json_decode( $raw, true );
		} else {
			$prices = $raw;
		}

		if ( is_string( $prices ) ) {
			$prices = json_decode( $prices, true );
		}

		if ( function_exists( 'wmc_adjust_fixed_price' ) && is_array( $prices ) ) {
			$prices = wmc_adjust_fixed_price( $prices );
		}

		return is_array( $prices ) ? $prices : null;
	}

	private static function get_price_for_currency( array $prices, $currency ) {
		$currency = strtoupper( $currency );

		if ( isset( $prices[ $currency ] ) && '' !== $prices[ $currency ] && null !== $prices[ $currency ] ) {
			return $prices[ $currency ];
		}

		foreach ( $prices as $code => $value ) {
			if ( strtoupper( (string) $code ) === $currency && '' !== $value && null !== $value ) {
				return $value;
			}
		}

		return null;
	}
}

Dyvoshyv_Ctx_Feed_Currency::bootstrap();
