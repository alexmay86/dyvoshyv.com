<?php

namespace MeestShipping\Modules;

use MeestShipping\Core\MeestShippingMethod;
use MeestShipping\Traits\Helper;

class ShippingMethod
{
    use Helper;
    private $cost = false;

    public function init()
    {
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            add_filter('woocommerce_shipping_methods', [$this, 'shippingMethod']);
            add_filter('woocommerce_shipping_rate_label', [ $this, 'shippingRateLabel'], 10, 2);
            //add_filter('woocommerce_shipping_rate_id', [ $this, 'shippingRatId'], 10, 2);
            add_filter('woocommerce_shipping_rate_cost', [$this, 'shippingRateCost'], 10, 2);
        }
    }

    public function shippingMethod($methods)
    {
        $methods[MEEST_PLUGIN_NAME] = MeestShippingMethod::class;

        return $methods;
    }

    public function shippingRateLabel($label, $rate)
    {
        if ($rate->get_method_id() === MEEST_PLUGIN_NAME) {
            $label = __('Meest', MEEST_PLUGIN_DOMAIN);
        }

        return $label;
    }

    public function shippingRatId($id, $rate)
    {
        if ($rate->get_method_id() === MEEST_PLUGIN_NAME) {
            $id = __(MEEST_PLUGIN_NAME, MEEST_PLUGIN_DOMAIN);
        }

        return $id;
    }

    public function shippingRateCost($cost, $rate)
    {
        if ($rate->get_method_id() === MEEST_PLUGIN_NAME) {
            if (
                !empty($_GET['wc-ajax'])
                && in_array($_GET['wc-ajax'], ['update_order_review', 'update_shipping_method'])
                && !empty($_POST['post_data']
            )) {
                if ($this->cost === false) {
                    try {
                        $shippingCost = new ShippingCost($cost);

                        $this->cost = $shippingCost->calc();
                    } catch (\Exception $e) {
                        wc_add_notice($e->getMessage(), 'error');

                        $this->cost = null;
                    }
                }

                return $this->cost;
            }
        }

        return $cost;
    }
}
