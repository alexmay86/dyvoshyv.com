<?php
namespace MeestShipping\Controllers;

use MeestShipping\Core\Controller;
use MeestShipping\Core\Error;
use MeestShipping\Core\Http;
use MeestShipping\Core\Request;
use MeestShipping\Core\View;
use MeestShipping\Models\Parcel;
use MeestShipping\Models\Pickup;
use MeestShipping\Models\PickupParcel;
use MeestShipping\Models\User;
use MeestShipping\Modules\Asset;
use MeestShipping\Repositories\ParcelRepository;
use MeestShipping\Resources\PickupApiResource;
use MeestShipping\Resources\PickupResource;
use MeestShipping\Tables\PickupTable;
use MeestShipping\Traits\Helper;

class PickupController extends Controller
{
    use Helper;

    public function index()
    {
        $pickupTable = new PickupTable();
        $totalPickup = count($_SESSION['meest_pickup_parcels'] ?? []);

        if ($pickupTable->current_action() === false) {
            $pickupTable->prepare_items();

            Asset::load(['meest']);

            return View::render('views/pages/pickup_list', [
                'pickupTable' => $pickupTable,
                'totalPickup' => $totalPickup,
            ]);
        }
    }

    public function create()
    {
        if (!current_user_can('manage_options')) {
            return false;
        }

        if (Request::isPost()) {
            if (!Request::isWpnonce()) {
                return false;
            }

            $request = new Request($_POST);

            try {
                $parcels = $this->getParcels($_SESSION['meest_pickup_parcels']);
                $request->parcels = array_map(function ($parcel) {
                    return [
                        'id' => $parcel->id,
                        'parcel_id' => $parcel->parcel_id
                    ];
                }, $parcels);
                $pickupData = PickupResource::make($request->all());
                $pickupApiData = PickupApiResource::make($request->all());

                $response = meest_init('Api')->pickupCreate($pickupApiData);
            } catch (\Exception $e) {
                Http::addSettingsError('UnauthorizedRequestException', $e->getCode(), $e->getMessage());
            }

            if (!empty($response)) {
                $data = [
                    'sender' => $pickupData['sender'],
                    'pay_type' => $pickupData['pay_type'],
                    'receiver_pay' => $pickupData['receiver_pay'],
                    'notation' => $pickupData['notation'],
                    'expected_date' => $pickupData['expected_date'],
                    'expected_time_from' => $pickupData['expected_time_from'],
                    'expected_time_to' => $pickupData['expected_time_to'],
                    'register_number' => $response['registerNumber'],
                    'register_id' => $response['registerID'],
                    'register_date' => \DateTime::createFromFormat('d.m.Y', $response['pickUpDate'])->format('Y-m-d'),
                ];

                $pickup = new Pickup($data);

                if ($pickup->save()) {
                    foreach ($pickupData['parcel_ids'] as $item) {
                        $pickup->sync(PickupParcel::class, $item);
                    }
                    $this->emptyParcel();

                    do_action('meest_pickup_created', $pickup, $order ?? null);

                    Error::add('pickup-create', __('Pickup was created.', MEEST_PLUGIN_DOMAIN), 'success');

                    return wp_redirect('admin.php?page=meest_pickup');
                }
            }
        }

        $sender = new User(array_merge($this->options['contact'], $this->options['address']));

        $pickupParcels = $_SESSION['meest_pickup_parcels'];
        if (!empty($pickupParcels)) {
            $parcels = Parcel::all($pickupParcels);
            $date = current_datetime();

            $pickup = new Pickup([
                'pay_type' => true,
                'receiver_pay' => true,
                'expected_date' => $date->format('Y-m-d'),
                'expected_time_from' => $date->modify('+1 hours')->format('H'),
                'expected_time_to' => $date->modify('+2 hours')->format('H'),
            ]);

            Asset::load(['flatpickr', 'jquery-select2', 'meest-address', 'meest-pickup', 'meest']);
            Asset::localize('meest-pickup');

            return View::render('views/pages/pickup_form', [
                'pickup' => $pickup,
                'sender' => $sender,
                'parcels' => $parcels
            ]);
        }

        return wp_redirect('admin.php?page=meest_pickup');
    }

    public function update()
    {
        if (!current_user_can('manage_options')) {
            return false;
        }
        $pickup = Pickup::find(sanitize_text_field($_GET['id']));

        if ($pickup !== null) {
            if (Request::isPost()) {
                if (!Request::isWpnonce()) {
                    return false;
                }

                $request = new Request($_POST);

                try {
                    $pickupData = PickupResource::make($request->all());
                    $pickupApiData = PickupApiResource::make($request->all());

                    $response = meest_init('Api')->pickupUpdate($pickup->register_id, $pickupApiData);
                } catch (\Exception $e) {
                    Http::addSettingsError('UnauthorizedRequestException', $e->getCode(), $e->getMessage());
                }

                if (!empty($response)) {
                    $data = [
                        'sender' => $pickupData['sender'],
                        'pay_type' => $pickupData['pay_type'],
                        'receiver_pay' => $pickupData['receiver_pay'],
                        'notation' => $pickupData['notation'],
                        'expected_date' => $pickupData['expected_date'],
                        'expected_time_from' => $pickupData['expected_time_from'],
                        'expected_time_to' => $pickupData['expected_time_to'],
                        'register_date' => \DateTime::createFromFormat('d.m.Y', $response['pickUpDate'])->format('Y-m-d'),
                    ];

                    if ($pickup->update($data)) {
                        //TODO edit parcels

                        Error::add('pickup-update', __('Pickup was updated.', MEEST_PLUGIN_DOMAIN), 'success');

                        return wp_redirect('admin.php?page=meest_pickup');
                    }
                }
            }

            if ($pickup->sender === null) {
                $sender = new User($this->options['contact']);
                $sender->fill($this->options['address']);
            } else {
                $sender = new User($pickup->sender);
            }

            $parcels = ParcelRepository::findByPickup($pickup->id);

            Asset::load(['jquery-select2', 'meest-address', 'meest-parcel', 'meest']);
            Asset::localize('meest-parcel');

            return View::render('views/pages/pickup_form', [
                'pickup' => $pickup,
                'sender' => $sender,
                'parcels' => $parcels
            ]);
        }

        return wp_redirect('admin.php?page=meest_pickup');
    }

    public function delete()
    {
        $pickup = Pickup::find(sanitize_text_field($_GET['id']));

        $response = meest_init('Api')->pickupDelete($pickup->register_id);

        if ($response === []) {
            if ($pickup->delete()) {
                $pickup->desync(PickupParcel::class);
            }
        }

        Error::add('pickup-delete', __('Pickup was deleted.', MEEST_PLUGIN_DOMAIN), 'success');

        return wp_redirect('admin.php?page=meest_pickup');
    }

    public function deleteParcel()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!empty($_SESSION['meest_pickup_parcels']) && !empty($_GET['id'])) {
                array_splice($_SESSION['meest_pickup_parcels'], array_search(sanitize_text_field($_GET['id']), $_SESSION['meest_pickup_parcels']), 1);
            } else {
                return wp_redirect('admin.php?page=meest_pickup');
            }
        }

        return wp_redirect('admin.php?page=meest_pickup&action=create');
    }

    private function emptyParcel()
    {
        unset($_SESSION['meest_pickup_parcels']);
    }

    private function getParcels($pickupParcels)
    {
        return Parcel::all($pickupParcels, 'id');
    }
}
