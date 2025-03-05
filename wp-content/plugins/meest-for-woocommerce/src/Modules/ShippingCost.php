<?php

namespace MeestShipping\Modules;

use MeestShipping\Resources\CostApiResource;

class ShippingCost
{
    private $options;
    private $cost;

    public function __construct($cost = null)
    {
        $this->options = meest_init('Option')->all();
        $this->cost = $cost;
    }

    public function calc()
    {
        if ($this->options['shipping']['calc_cost'] == 0) {
            return $this->cost;
        }

        if ($this->options['shipping']['fixed_cost'] !== null) {
            return $this->options['shipping']['fixed_cost'];
        }

        parse_str($_POST['post_data'], $post);
        if ($_GET['wc-ajax'] === 'update_order_review') {
            parse_str(sanitize_text_field($_POST['post_data']), $post);
        } elseif ($_GET['wc-ajax'] === 'checkout') {
            $post = $_POST;
        }

        if (CostApiResource::check($post)) {
            $cart = WC()->cart;
            $post['items'] = $cart->get_cart_contents();
            $post['totals'] = $cart->get_totals();
            $costApiData = CostApiResource::make($post);

            //file_put_contents('file.txt', 'Meest Cost Api Data: ' . "\n" . json_encode($costApiData) . "\n", FILE_APPEND);
            $response = meest_init('Api')->calculate($costApiData);
            //file_put_contents('file.txt', 'Meest Cost Api Response: ' . "\n" . json_encode($response) . "\n", FILE_APPEND);

            $shipping_price = $response['ParcelCostUAH'];
            if($shipping_price && function_exists('wmc_get_price')) $shipping_price = wmc_get_price( $shipping_price );
        }

        return (float) ($shipping_price ?? $this->cost);
    }
}
