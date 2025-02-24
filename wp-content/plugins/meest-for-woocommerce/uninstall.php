<?php
defined( 'ABSPATH' ) || exit;

define('MEEST_PLUGIN_NAME', 'meest');

global $wpdb;

$tables = include_once __DIR__.'/migrations/main.php';

delete_option(MEEST_PLUGIN_NAME.'_plugin');
delete_option(MEEST_PLUGIN_NAME.'_api');

foreach ($tables as $name => $sql) {
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}meest_{$name};");
}
