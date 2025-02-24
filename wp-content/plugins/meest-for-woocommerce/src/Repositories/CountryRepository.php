<?php

namespace MeestShipping\Repositories;

class CountryRepository extends Repository
{
    /**
     * @param null $text
     * @return array
     */
    public function search($text = null): array
    {
        $items = meest_init('Api')->searchCountry([
            'countryDescr' => "%$text%",
        ]);

        return array_map(function ($item) {
            return [
                'id' => $item['countryID'],
                'code' => $item['alfaCode2'],
                'text' => meest_ucfirst($item['countryDescr']['descr'.$this->meestLocale] ?? null),
            ];
        }, $items);
    }

    /**
     * @param null $code
     * @return array
     */
    public function getByCode($code = null): array
    {
        $items = meest_init('Api')->searchCountry([
            'alfaCode2' => $code,
        ]);

        $index = array_search($code, array_column($items, 'alfaCode2'));

        if (!empty($items[$index])) {
            return [
                'id' => $items[$index]['countryID'],
                'code' => $items[$index]['alfaCode2'],
                'text' => meest_ucfirst($items[$index]['countryDescr']['descr'.$this->meestLocale] ?? null),
            ];
        }
    }
}
