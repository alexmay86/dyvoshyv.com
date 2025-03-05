<?php
namespace MeestShipping\Modules;

use MeestShipping\Contracts\Module;
use MeestShipping\Core\Error;
use MeestShipping\Core\Http;

class Api implements Module
{
    private $http;

    public function __construct()
    {
        $this->http = new Http();
    }

    public function init(): self
    {
        return $this;
    }

    public function request($method, $urn, $params = [], $query = [], $file = false)
    {
        try {
            return $this->http->request($method, $urn, $params, $query, $file);
        } catch (\Exception $e) {
            Error::add('bad-request', $e->getMessage(), 'error');
            Error::show();

            wp_die();
        }
    }

    public function authGet($data)
    {
        return $this->http->request('POST', 'auth_get', $data);
    }

    public function authRefresh($data)
    {
        return $this->http->request('POST', 'auth_refresh', $data);
    }

    public function searchCountry($data = [])
    {
        return $this->request('POST', 'country_search', [
            'filters' => $data
        ]);
    }

    public function searchRegion($data = [])
    {
        return $this->request('POST', 'region_search', [
            'filters' => $data
        ]);
    }

    public function searchCity($data = [])
    {
        return $this->request('POST', 'city_search', [
            'filters' => $data
        ]);
    }

    public function searchStreet($data = [])
    {
        return $this->request('POST', 'street_search', [
            'filters' => $data
        ]);
    }

    public function searchBranch($data = [])
    {
        return $this->request('POST', 'branch_search', [
            'filters' => $data
        ]);
    }

    public function getBranchTypes()
    {
        return $this->request('GET', 'branch_types');
    }

    public function packTypes()
    {
        return $this->request('GET', 'pack_types');
    }

    public function parcelCreate($data = [])
    {
        return $this->request('POST', 'parcel_create', $data);
    }

    public function parcelUpdate($id, $data = [])
    {
        return $this->request('PUT', 'parcel_update', $data, [
            '{parcelID}' => $id,
        ]);
    }

    public function parcelDelete($id)
    {
        return $this->request('DELETE', 'parcel_delete', [], [
            '{parcelID}' => $id,
        ]);
    }

    public function pickupCreate($data = [])
    {
        return $this->request('POST', 'pickup_create', $data);
    }

    public function pickupUpdate($id, $data = [])
    {
        return $this->request('PUT', 'pickup_update', $data, [
            '{registerID}' => $id,
        ]);
    }

    public function pickupDelete($id)
    {
        return $this->request('DELETE', 'pickup_delete', [], [
            '{registerID}' => $id,
        ]);
    }

    public function printDeclaration($id, $type = 'pdf'): array
    {
        return $this->request('GET', 'print_declaration', [], [
            '{printValue}' => $id,
            '{contentType}' => $type
        ], true);
    }

    public function getUrlDeclaration($id, $type = 'pdf')
    {
        return $this->http->makeUri('print_declaration', [
            '{printValue}' => $id,
            '{contentType}' => $type
        ]);
    }

    public function printSticker100($id)
    {
        return $this->request('POST', 'print_sticker100', [
            '{printValue}' => $id,
        ]);
    }

    public function getUrlSticker100($id)
    {
        return $this->http->makeUri('print_sticker100', [
            '{printValue}' => $id,
        ]);
    }

    public function calculate($data)
    {
        return $this->request('POST', 'calculate', $data);
    }

    public function tracking($number)
    {
        return $this->request('GET', 'tracking', [], [
            '{trackNumber}' => $number
        ]);
    }
}
