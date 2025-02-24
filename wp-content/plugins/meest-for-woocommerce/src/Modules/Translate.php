<?php

namespace MeestShipping\Modules;

class Translate
{
    public function init()
    {
        add_action('plugins_loaded', [$this, 'pluginsLoaded'], 1);
    }

    public function pluginsLoaded()
    {
        load_plugin_textdomain(MEEST_PLUGIN_DOMAIN, false, MEEST_PLUGIN_SLUG.DS.'resources'.DS.'langs');
    }
}
