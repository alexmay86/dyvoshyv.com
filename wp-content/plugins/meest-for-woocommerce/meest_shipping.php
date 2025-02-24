<?php
/**
 * Meest for WooCommerce
 *
 * Plugin Name: Meest for WooCommerce
 * Plugin URI:  https://github.com/meest.com/meest-for-woocommerce
 * Description: Manage parcels, pickups and more via Meest
 * Version: 1.0.6
 * Author: Meest.com
 * Author URI: https://github.com/meest.com
 * Email: webdeveloper.eu@gmail.com
 * License: GPLv2
 * Text Domain: meest-for-woocommerce
 *
 * @package Meest for WooCommerce
 * @author Meest.com
 * @author webdeveloper.eu@gmail.com
 *
 * PHP requires at least: 7.0
 * WC requires at least: 3.6.4
 * WC tested up to: 3.6.4
 */

use MeestShipping\Modules\Meest;

defined( 'ABSPATH' ) || exit;
defined('DS') || define('DS', DIRECTORY_SEPARATOR);
define('MEEST_PLUGIN_NAME', 'meest');
define('MEEST_PLUGIN_VERSION', '1.0.4');
define('MEEST_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MEEST_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MEEST_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('MEEST_PLUGIN_SLUG', 'meest-for-woocommerce');
define('MEEST_PLUGIN_DOMAIN', 'meest_for_woocommerce');

add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true);
    }
});

require_once __DIR__ . '/vendor/autoload.php';
include_once __DIR__.'/functions.php';
require_once WP_PLUGIN_DIR .'/woocommerce/woocommerce.php';
require_once __DIR__ . '/src/Core/MeestShipping.php';

Meest::instance()->init();
