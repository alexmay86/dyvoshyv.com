<?php

namespace MeestShipping\Traits;

trait Helper
{
    public static function isMeestShipping(): bool
    {
        if (!empty($_POST['shipping_method'])) {
            return strpos($_POST['shipping_method'][0], MEEST_PLUGIN_NAME) === 0;
        }

        $chosenShippingMethods = WC()->session->chosen_shipping_methods;
        if (!empty($chosenShippingMethods[0])) {
            return strpos($chosenShippingMethods[0], MEEST_PLUGIN_NAME) === 0;
        }

        return false;
    }

    public static function shipToDifferentAddress(): string
    {
        return isset($_POST['ship_to_different_address']) ? 'shipping' : 'billing';
    }

    private static function implodePack(&$package, $parcel)
    {
        $packageMax = array_search(max($package), $package);
        $packageMin = array_search(min($package), $package);
        $parcelMax = array_search(max($parcel), $parcel);
        $parcelMin = array_search(min($parcel), $parcel);

        if ($parcel[$parcelMax] > $package[$packageMax]) {
            $package[2] = (int) $parcel[$parcelMax];
        }
        if ($parcel[$parcelMin] > $package[$packageMin]) {
            $package[1] = (int) $parcel[$parcelMin];
        }
        $size = array_diff_key($parcel, array_flip([$parcelMax, $parcelMin]));
        $package[0] += (int) array_shift($size);
    }
}
