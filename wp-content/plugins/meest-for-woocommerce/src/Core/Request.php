<?php

namespace MeestShipping\Core;

defined( 'ABSPATH' ) || exit;

class Request
{
    private $data;

    public function __construct(array $data = [])
    {
        $this->data = meest_sanitize_text_field($data ?: $_REQUEST);
    }

    public function __get($name)
    {
        return array_key_exists($name, $this->data) ? $this->data[$name] : null;
    }

    public function __set($name, $value)
    {
        $this->data[$name] = meest_sanitize_text_field($value);
    }

    public function all()
    {
        return $this->data;
    }

    public static function isGet(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    public static function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    public static function isWpnonce(): bool
    {
        return !empty($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], MEEST_PLUGIN_DOMAIN);
    }
}
