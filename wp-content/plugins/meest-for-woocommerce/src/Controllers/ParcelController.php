<?php
namespace MeestShipping\Controllers;

use MeestShipping\Core\Error;
use MeestShipping\Core\Http;
use MeestShipping\Core\Controller;
use MeestShipping\Core\Request;
use MeestShipping\Core\View;
use MeestShipping\Models\User;
use MeestShipping\Models\Parcel;
use MeestShipping\Repositories\PackTypesRepository;
use MeestShipping\Resources\ParcelResource;
use MeestShipping\Resources\ParcelApiResource;
use MeestShipping\Modules\Asset;
use MeestShipping\Tables\ParcelTable;
use MeestShipping\Traits\Email;
use MeestShipping\Traits\Helper;

class ParcelController extends Controller
{
    use Helper, Email;

    public function index()
    {
        $parcelTable = new ParcelTable();
        $totalPickup = count($_SESSION['meest_pickup_parcels'] ?? []);

        if ($parcelTable->current_action() === false) {
            $parcelTable->prepare_items();

            Asset::load(['meest']);

            return View::render('views/pages/parcel_list', [
                'parcelTable' => $parcelTable,
                'totalPickup' => $totalPickup,
            ]);
        }
    }

    public function create()
    {
        if (!current_user_can('manage_options')) {
            return false;
        }

        if (!empty($_GET['post'])) {
            $orderId = sanitize_text_field($_GET['post']);
            $parcel = Parcel::find($orderId, 'order_id');
            if ($parcel !== null) {
                return wp_redirect('admin.php?page=meest_parcel&action=edit&id='.$parcel->id);
            }

            $order = wc_get_order($orderId);

            if ($order === false) {
                return wp_redirect('admin.php?page=meest_parcel&action=create');
            }
        }

        if (Request::isPost()) {
            if (!Request::isWpnonce()) {
                return false;
            }

            try {
                $request = new Request($_POST);
                $parcelApiData = ParcelApiResource::make($request->all());
                $response = meest_init('Api')->parcelCreate($parcelApiData);

                if (!empty($response)) {
                    $parcelData = ParcelResource::make($request->all());
                    $parcel = new Parcel([
                        'order_id' => $orderId ?? null,
                        'parcel_id' => $response['parcelID'],
                        'pack_type_id' => $parcelData['pack_type'],
                        'sender' => $parcelData['sender'],
                        'receiver' => $parcelData['receiver'],
                        'pay_type' => $parcelData['pay_type'],
                        'receiver_pay' => $parcelData['receiver_pay'],
                        'cod' => $parcelData['cod'],
                        'insurance' => $parcelData['insurance'],
                        'weight' => $parcelData['weight'],
                        'lwh' => $parcelData['lwh'],
                        'notation' => $parcelData['notation'],
                        'barcode' => $response['barCode'],
                        'cost_services' => $response['costServices'],
                        'delivery_date' => date("Y-m-d", strtotime($response['estimatedDeliveryDate'])),
                    ]);

                    if ($parcel->save()) {
                        do_action('meest_parcel_created', $parcel, $order ?? null);

                        Error::add('parcel-create', __('Parcel was created.', MEEST_PLUGIN_DOMAIN), 'success');

                        return wp_redirect('admin.php?page=meest_parcel');
                    }
                }
            } catch (\Exception $e) {
                Http::addSettingsError('Exception', $e->getCode(), $e->getMessage());
            }
        }

        $packTypes = PackTypesRepository::instance()->get();
        $sender = new User(array_merge($this->options['contact'], $this->options['address']));

        if (!empty($order)) {
            $orderData = $order->get_data();
            $shippingMethods = $order->get_shipping_methods();
            $orderShipping = array_shift($shippingMethods);
            $receiver = new User(array_merge($orderData['shipping'], [
                'phone' => $orderData['billing']['phone']
            ], $orderShipping->get_meta('receiver')));

            $package = [
                'quantity' => 0,
                'insurance' => 0,
                'weight' => 0,
                'lwh' => [0, 0, 0],
                'description' => []
            ];

            foreach ($order->get_items() as $item) {
                $itemData = $item->get_data();
                $product = wc_get_product($itemData['product_id']);
                $productData = $product->get_data();
                $package['quantity']++;
                $package['insurance'] += $itemData['total'];
                $package['weight'] += $productData['weight'] ?: 0.1;
                array_push($package['description'], $productData['name']);
                self::implodePack($package['lwh'], [$productData['length'], $productData['width'], $productData['height']]);
            }

            $package['order_id'] = $orderData['id'];
            $package['cod'] = $orderData['payment_method'] === 'cod' ? $orderData['total'] : 0;
            $package['notation'] = implode(', ', $package['description']).'. '.$orderData['customer_note'];
        } else {
            $receiver = new User($this->options['empty_user']);
            $package = [
                'quantity' => 1,
                'insurance' => $this->options['parcel']['insurance'],
                'weight' => $this->options['parcel']['weight'],
                'lwh' => $this->options['parcel']['lwh'],
            ];
        }

        if ($receiver->country['id'] !== $this->options['country_id']['ua']) {
            $this->options['parcel']['receiver_pay'] = 0;
        }

        $parcel = new Parcel(array_merge($package, [
            'pay_type' => $this->options['parcel']['pay_type'],
            'receiver_pay' => $this->options['parcel']['receiver_pay']
        ]));

        Asset::load(['jquery-select2', 'meest-address', 'meest-parcel', 'meest']);
        Asset::localize('meest-parcel');

        return View::render('views/pages/parcel_form', [
            'options' => $this->options,
            'sender' => $sender,
            'receiver' => $receiver,
            'parcel' => $parcel,
            'packTypes' => $packTypes,
        ]);
    }

    public function update()
    {
        if (!current_user_can('manage_options')) {
            return false;
        }

        $parcel = Parcel::find(sanitize_text_field($_GET['id']));
        $order = !empty($parcel->order_id) ? wc_get_order($parcel->order_id) : false;
        $request = new Request($_POST);

        if (Request::isPost()) {
            if (!Request::isWpnonce()) {
                return false;
            }

            try {
                $parcelApiData = ParcelApiResource::make($request->all());
                $response = meest_init('Api')->parcelUpdate($parcel->parcel_id, $parcelApiData);
            } catch (\Exception $e) {
                Http::addSettingsError('Exception', $e->getCode(), $e->getMessage());
            }

            if (!empty($response)) {
                $parcelData = ParcelResource::make($request->all());
                $data = [
                    'parcel_id' => $response['parcelID'],
                    'pack_type_id' => $parcelData['pack_type'],
                    'sender' => $parcelData['sender'],
                    'receiver' => $parcelData['receiver'],
                    'pay_type' => $parcelData['pay_type'],
                    'receiver_pay' => $parcelData['receiver_pay'],
                    'cod' => $parcelData['cod'],
                    'insurance' => $parcelData['insurance'],
                    'weight' => $parcelData['weight'],
                    'lwh' => $parcelData['lwh'],
                    'notation' => $parcelData['notation'],
                    'barcode' => $response['barCode'],
                    'cost_services' => $response['costServices'],
                    'delivery_date' => date("Y-m-d", strtotime($response['estimatedDeliveryDate'])),
                ];

                if ($parcel->update($data)) {
                    do_action('meest_parcel_updated', $parcel, $order ?? null);

                    Error::add('parcel-update', __('Parcel was updated.', MEEST_PLUGIN_DOMAIN), 'success');

                    return wp_redirect('admin.php?page=meest_parcel');
                }
            }
        }

        $packTypes = PackTypesRepository::instance()->get();
        $sender = new User($parcel->sender);
        $receiver = new User($parcel->receiver);

        Asset::load(['jquery-select2', 'meest-address', 'meest-parcel', 'meest']);
        Asset::localize('meest-parcel');

        return View::render('views/pages/parcel_form', [
            'options' => $this->options,
            'sender' => $sender,
            'receiver' => $receiver,
            'parcel' => $parcel,
            'packTypes' => $packTypes,
        ]);
    }

    public function delete()
    {
        $parcel = Parcel::find(sanitize_text_field($_GET['id']));

        try {
            $response = meest_init('Api')->parcelDelete($parcel->parcel_id);

            if ($response === []) {
                $parcel->delete();
            }
        } catch (\Exception $e) {
            Http::addSettingsError('Exception', $e->getCode(), $e->getMessage());
        }

        do_action('meest_parcel_deleted', $parcel, $order ?? null);

        Error::add('parcel-delete', __('Parcel was deleted.', MEEST_PLUGIN_DOMAIN), 'success');

        return wp_redirect('admin.php?page=meest_parcel');
    }

    public function email()
    {
        $parcel = Parcel::find(sanitize_text_field($_GET['id']));

        if (!empty($parcel->order_id)) {
            $order = wc_get_order($parcel->order_id);

            if ($this->sendMailByOrder($order, $parcel)) {
                $parcel->update(['is_email' => 1]);

                Error::add('email-sent', __('Email was sent.', MEEST_PLUGIN_DOMAIN), 'success');
            } else {
                Error::add('email-sent', __('Email wasn\'t sent.', MEEST_PLUGIN_DOMAIN), 'error');
            }

            return wp_safe_redirect('admin.php?page=meest_parcel');
        }
    }

    public function tracking()
    {
        if (!current_user_can('manage_options')) {
            return false;
        }

        if (empty($_GET['id'])) {
            return wp_safe_redirect('admin.php?page=meest_parcel');
        }

        $parcel = Parcel::find(sanitize_text_field($_GET['id']));

        $tracking = meest_init('Api')->tracking($parcel->barcode);

        Asset::load(['meest']);

        return View::render('views/pages/parcel_tracking', [
            'parcel' => $parcel,
            'tracking' => $tracking,
        ]);
    }

    public function pickup()
    {
        if (!empty($_GET['id'])) {
            $parcel = Parcel::find(sanitize_text_field($_GET['id']));
            if ($parcel !== null) {
                if (!isset($_SESSION['meest_pickup_parcels'])) {
                    $_SESSION['meest_pickup_parcels'] = [];
                }
                if (in_array($parcel->id, $_SESSION['meest_pickup_parcels']) === false) {
                    array_push($_SESSION['meest_pickup_parcels'], $parcel->id);
                }
            }
        }

        Error::add('parcel-pickup', __('Parcel was pickuped.', MEEST_PLUGIN_DOMAIN), 'success');

        return wp_redirect('admin.php?page=meest_parcel');
    }

    public function unPickup()
    {
        if (!empty($_SESSION['meest_pickup_parcels']) && !empty($_GET['id'])) {
            array_splice($_SESSION['meest_pickup_parcels'], array_search(sanitize_text_field($_GET['id']), $_SESSION['meest_pickup_parcels']), 1);
        }

        Error::add('parcel-unpickup', __('Parcel was unpickuped.', MEEST_PLUGIN_DOMAIN), 'success');

        return wp_redirect('admin.php?page=meest_parcel');
    }
}
