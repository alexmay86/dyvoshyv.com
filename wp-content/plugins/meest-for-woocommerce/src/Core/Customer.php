<?php

namespace MeestShipping\Core;

defined( 'ABSPATH' ) || exit;

class Customer
{
    private static $instance;
    public $woo;

    private function __construct()
    {
        $this->woo = WC();
    }

    public static function instance(): Customer
    {
        return static::$instance ?? static::$instance = new static();
    }

    public function getValue($input)
    {
        if (is_callable([$this->woo->customer, "get_$input"])) {
            return $this->woo->customer->{"get_$input"}();
        }

        return null;
    }

    public function getMeta($key, $default = null)
    {
        if ($this->woo->customer->get_id()) {
            return get_user_meta($this->woo->customer->get_id(), $key, true) ?: $default;
        }

        return $this->woo->session->get($key, $default);
    }

    public function setMeta($key, $value)
    {
        if ($this->woo->customer->get_id()) {
            return update_user_meta($this->woo->customer->get_id(), $key, $value);
        }

        $this->woo->session->set($key, $value);
    }

    public function deleteMeta($key)
    {
        if ($this->woo->customer->get_id()) {
            return delete_user_meta($this->woo->customer->get_id(), $key);
        }

        $this->woo->session->set($key, null);
    }
}
