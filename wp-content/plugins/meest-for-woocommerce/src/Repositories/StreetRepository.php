<?php

namespace MeestShipping\Repositories;

use MeestShipping\Models\Street;

class StreetRepository extends Repository
{
    /**
     * @param $city
     * @param null $text
     * @return array
     */
    public function search($city, $text = null): array
    {
        if (!empty($text)  && mb_strlen($text) < 2) {
            return [];
        }

        if ($this->options['dictionary']['is_db'] ?? false) {
            return $this->fromDb($text, $city);
        }

        return $this->fromApi($text, $city);
    }

    public function fromApi($text = null, $city = null): array
    {
        $items = meest_init('Api')->searchStreet([
            'cityID' => $city,
            'addressDescr' => "%$text%",
        ]);

        return array_map(function ($item) {
            return [
                'id' => $item['addressID'],
                'text' => $item['addressDescr']['descr'.$this->meestLocale] ?? null,
            ];
        }, $items);
    }

    public function fromDb($text = null, $city = null): array
    {
        $items = Street::search($text, $city);

        return array_map(function ($item) {
            $text = ($item['type_' . $this->locale] ?? $item['type_uk']) . ' ' . ($item['name_' . $this->locale] ?? $item['name_uk']);

            return [
                'id' => $item['street_uuid'],
                'text' => $text,
            ];
        }, $items);
    }
}
