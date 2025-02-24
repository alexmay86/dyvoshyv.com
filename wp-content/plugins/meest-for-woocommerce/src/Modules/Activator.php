<?php

namespace MeestShipping\Modules;

use MeestShipping\Core\Migration;

defined( 'ABSPATH' ) || exit;

class Activator
{
    public function init()
    {
        register_activation_hook(MEEST_PLUGIN_BASENAME, [$this, 'activation']);
    }

    public function activation()
    {
        (new Migration())->create(['parcels', 'pickups', 'pickup_parcel']);
    }
}
