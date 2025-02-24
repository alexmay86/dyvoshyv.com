<?php

namespace MeestShipping\Core;

defined( 'ABSPATH' ) || exit;

abstract class Controller
{
    protected static $instance = [];

    protected $options;

    public function __construct()
    {
        $this->options = meest_init('Option')->all();
    }

    public static function instance(): Controller
    {
        $class = get_called_class();

        return self::$instance[$class] ?? self::$instance[$class] = new $class;
    }
}
