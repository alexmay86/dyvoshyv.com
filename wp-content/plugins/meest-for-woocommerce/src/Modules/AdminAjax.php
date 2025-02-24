<?php

namespace MeestShipping\Modules;

use MeestShipping\Repositories\CountryRepository;
use MeestShipping\Repositories\CityRepository;
use MeestShipping\Repositories\StreetRepository;
use MeestShipping\Repositories\BranchRepository;

class AdminAjax
{
    private $options;

    public function __construct()
    {
        $this->options = meest_init('Option')->all();
    }

    public function init()
    {
        add_action('wp_ajax_meest_address_country', [$this, 'getAddressCountry']);
        add_action('wp_ajax_nopriv_meest_address_country', [$this, 'getAddressCountry']);

        add_action('wp_ajax_meest_address_city', [$this, 'getAddressCity']);
        add_action('wp_ajax_nopriv_meest_address_city', [$this, 'getAddressCity']);

        add_action('wp_ajax_meest_address_street', [$this, 'getAddressStreet']);
        add_action('wp_ajax_nopriv_meest_address_street', [$this, 'getAddressStreet']);

        add_action('wp_ajax_meest_address_branch', [$this, 'getAddressBranch']);
        add_action('wp_ajax_nopriv_meest_address_branch', [$this, 'getAddressBranch']);

        add_action('wp_ajax_meest_update_dictionary', [$this, 'updateDictionary']);
    }

    public function getAddressCountry()
    {
        $text = !empty($_POST['text']) ? sanitize_text_field($_POST['text']) : null;
        $countries = CountryRepository::instance()->search($text);
        $blockCountries = $this->options['block_countries'];
        $countries = array_filter($countries, static function ($item) use ($blockCountries) {
            return !in_array($item['id'], $blockCountries);
        });

        $this->jsonResponse(array_values($countries));
    }

    public function getAddressCity()
    {
        $country = !empty($_POST['country']) ? sanitize_text_field($_POST['country']) : null;
        $text = !empty($_POST['text']) ? sanitize_text_field($_POST['text']) : null;
        $cities = CityRepository::instance()->search($country, $text);

        $this->jsonResponse($cities);
    }

    public function getAddressStreet()
    {
        $city = !empty($_POST['city']) ? sanitize_text_field($_POST['city']) : null;
        $text = !empty($_POST['text']) ? sanitize_text_field($_POST['text']) : null;
        $streets = StreetRepository::instance()->search($city, $text);

        $this->jsonResponse($streets);
    }

    public function getAddressBranch()
    {
        $city = !empty($_POST['city']) ? sanitize_text_field($_POST['city']) : null;
        $text = !empty($_POST['text']) ? sanitize_text_field($_POST['text']) : null;
        $branches = BranchRepository::instance()->search($city, $text);

        $this->jsonResponse($branches);
    }

    public function updateDictionary()
    {
        try {
            (new Dictionary($this->options))->init();
            $this->jsonResponse([
                'status' => 'success',
                'message' => 'Dictionary has been updated'
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Dictionary has not been updated',
                'error' => $e->getMessage()
            ]);
        }
    }

    private function jsonResponse($data)
    {
        header('Content-Type:application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
