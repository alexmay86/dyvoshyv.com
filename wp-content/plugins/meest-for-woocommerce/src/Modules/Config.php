<?php

namespace MeestShipping\Modules;

defined( 'ABSPATH' ) || exit;

class Config
{
    public function __construct()
    {
    }

    public function init()
    {
        register_activation_hook(MEEST_PLUGIN_BASENAME, [$this, 'activation']);
    }
}
