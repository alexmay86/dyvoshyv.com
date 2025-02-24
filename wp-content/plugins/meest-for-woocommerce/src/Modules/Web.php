<?php

namespace MeestShipping\Modules;

use MeestShipping\Contracts\Module;

class Web implements Module
{
    public function init()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);

        return $this;
    }

    public function enqueueScripts()
    {
        if (is_checkout()) {
            Asset::load(['meest', 'jquery-select2', 'meest-address', 'meest-checkout']);
            Asset::localize('meest-checkout');
        }
    }
}
