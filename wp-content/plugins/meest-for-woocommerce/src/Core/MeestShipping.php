<?php

namespace MeestShipping\Modules;

defined( 'ABSPATH' ) || exit;

class Meest
{
    private $modules = [];
    private static $instance;

    public static function instance(): Meest
    {
        return self::$instance ?? self::$instance = new self();
    }

    public function init()
    {
        $this->initModule('Option');
        $this->initModule('Activator');
        $this->initModule('Translate');
        $this->initModule('ShippingMethod');
        $this->initModule('PluginMenu');
        $this->initModule('Api');
        $this->initModule('Admin');
        $this->initModule('AdminAjax');
        $this->initModule('Web');
        $this->initModule('Cart');
        $this->initModule('Checkout');
        $this->initModule('OrderUpdate');
        $this->initModule('Action');
        $this->initModule('Cron');
    }

    private function initModule($module)
    {
        $class = "\\MeestShipping\\Modules\\$module";
        $this->modules[$module] = (new $class())->init();
    }

    public function module($module)
    {
        return $this->modules[$module];
    }
}
