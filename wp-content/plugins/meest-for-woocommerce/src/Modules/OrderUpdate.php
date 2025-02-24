<?php

namespace MeestShipping\Modules;

use Exception;
use MeestShipping\Core\Request;
use WC_Order;
use WC_Order_Item_Shipping;
use MeestShipping\Traits\Helper;
use MeestShipping\Models\Parcel;
use MeestShipping\Core\View;
use MeestShipping\Resources\AddressWCResource;
use MeestShipping\Resources\AddressCustomerResource;

defined( 'ABSPATH' ) || exit;

class OrderUpdate
{
    use Helper;

    public function init()
    {
        add_action('woocommerce_checkout_update_order_meta', [$this, 'checkoutUpdateOrderMeta']);
        if (version_compare( WC_VERSION, '4.3.0', '>=')) {
            add_action('woocommerce_checkout_order_created', [$this, 'checkoutOrderCreated']);
        }
        //add_action('woocommerce_before_order_item_object_save', [$this, 'beforeOrderItemObjectSave']);
        add_action('woocommerce_thankyou', [$this, 'thankyou'], 10);
        add_action('woocommerce_view_order', [$this, 'viewOrder'], 10);
    }

    public function checkoutUpdateOrderMeta($id): void
    {
        if (self::isMeestShipping()) {
            $request = new Request($_POST);

            $address = AddressWCResource::make($request->all(), 'billing');

            $this->updateOrderData($id, $address, 'billing');

            if (self::shipToDifferentAddress() === 'shipping') {
                $address = AddressWCResource::make($request->all(), 'billing');
            }

            $this->updateOrderData($id, $address, 'shipping');

            if (version_compare( WC_VERSION, '4.3.0', '<')) {
                $order = wc_get_order($id);
                if ($order === false) {
                    throw new Exception('Server error. Please try again later.');
                }

                $this->checkoutOrderCreated($order);
            }
        }
    }

    /**
     * @param WC_Order $order
     */
    public function checkoutOrderCreated(WC_Order $order): void
    {
        if ($order->has_shipping_method(MEEST_PLUGIN_NAME)) {
            $shippingMethods = $order->get_shipping_methods();
            $item = array_shift($shippingMethods);

            $request = new Request($_POST);
            $type = self::shipToDifferentAddress();
            $address = AddressCustomerResource::make($request->all(), $type);

            $item->update_meta_data('receiver', $address);
            $item->save_meta_data();
        }
    }

    /**
     * @param mixed|WC_Order_Item_Shipping $item
     */
    public function beforeOrderItemObjectSave($item): void
    {
    }

    private function updateOrderData($id, $address, $type)
    {
        update_post_meta($id, "_{$type}_state", $address['state']);
        update_post_meta($id, "_{$type}_city", $address['city']);
        update_post_meta($id, "_{$type}_address_1", $address['address_1']);
    }

    public function thankyou($orderId)
    {
        $order = wc_get_order($orderId);

        if ($order->has_shipping_method(MEEST_PLUGIN_NAME)) {
            $orderData = $order->get_data();
            $shippingMethods = $order->get_shipping_methods();
            $orderShipping = array_shift($shippingMethods);

            echo View::part('views/parts/address_details', [
                'address' => $orderShipping->get_meta('receiver')
            ]);
        }
    }

    public function viewOrder($orderId)
    {
        $order = wc_get_order($orderId);

        if ($order->has_shipping_method(MEEST_PLUGIN_NAME)) {
            $parcel = Parcel::find($order->get_id(), 'order_id');
            if ($parcel !== null) {
                echo View::part('views/parts/shipping_details', [
                    'parcel' => $parcel
                ]);
            }
        }
    }
}
