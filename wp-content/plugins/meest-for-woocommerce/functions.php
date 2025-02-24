<?php

defined( 'ABSPATH' ) || exit;

if (!function_exists('meest_init')) {
    function meest_init($module = null)
    {
        $instance = \MeestShipping\Modules\Meest::instance();

        if ($module === null) {
            return $instance;
        } else {
            return $instance->module($module);
        }
    }
}

if (!function_exists('is_meest_shipping')) {
    function is_meest_shipping()
    {
        return !empty($_POST['shipping_method']) && strpos($_POST['shipping_method'][0], MEEST_PLUGIN_NAME) === 0;
    }
}

if (!function_exists('meest_is_checkout')) {
    function meest_is_checkout()
    {
        return function_exists('is_checkout') && is_checkout();
    }
}

if (!function_exists('meest_crypt')) {
    function meest_crypt($str, $encrypt = true)
    {
        $method = 'AES-256-CBC';
        $key = hash('sha256', 'meest_key');
        $iv = substr(hash('sha256', 'meest_iv'), 0, 16);

        return $encrypt === true
            ? openssl_encrypt($str, $method, $key, 0, $iv)
            : openssl_decrypt($str, $method, $key, 0, $iv);
    }
}

if (!function_exists('meest_ucfirst')) {
    function meest_ucfirst($str)
    {
        return $str !== null ? mb_convert_case(mb_strtolower($str), MB_CASE_TITLE, 'UTF-8') : null;
    }
}

if (!function_exists('meest_sanitize_text_field')) {
    function meest_sanitize_text_field($var)
    {
        if (is_array($var)) {
            return array_map('meest_sanitize_text_field', $var);
        } else {
            return sanitize_text_field($var);
        }
    }
}
