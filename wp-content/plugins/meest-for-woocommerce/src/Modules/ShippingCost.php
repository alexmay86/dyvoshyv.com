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

        if (CostApiResource::check($post)) {
            $cart = WC()->cart;
            $post['items'] = $cart->get_cart_contents();
            $post['totals'] = $cart->get_totals();
            $costApiData = CostApiResource::make($post);

            $response = meest_init('Api')->calculate($costApiData);
        }

        return (float) ($response['costServices'] ?? $this->cost);
    }
}
