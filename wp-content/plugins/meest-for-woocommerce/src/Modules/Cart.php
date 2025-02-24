<?php

namespace MeestShipping\Modules;

defined( 'ABSPATH' ) || exit;

class Cart
{
    public function init()
    {
        add_action('woocommerce_before_shipping_calculator', [$this, 'beforeShippingCalculator']);
    }

    public function beforeShippingCalculator()
    {
        $shippings = WC()->cart->calculate_shipping();
        if (!empty($shippings)) {
            $shipping = @array_shift($shippings);
            if ($shipping->get_method_id() === MEEST_PLUGIN_NAME) {
                echo '<style>.woocommerce-shipping-calculator {display: none;}</style>';
            } else {
                echo '<style>.woocommerce-shipping-calculator {display: block;}</style>';
            }
        }
    }
}
