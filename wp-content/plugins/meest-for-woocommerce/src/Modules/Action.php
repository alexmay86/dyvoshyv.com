<?php

namespace MeestShipping\Modules;

defined( 'ABSPATH' ) || exit;

class Action
{
    public function init()
    {
        add_action('meest_parcel_created', [$this, 'meestParcelCreated'], 1, 2);
    }

    public function meestParcelCreated($parcel, $order = null)
    {
        if (!empty($order)) {
            $orderNote = sprintf(__('Invoice #%s by Meest', MEEST_PLUGIN_DOMAIN), $parcel->barcode);
            $order->add_order_note($orderNote);
        }
    }
}
