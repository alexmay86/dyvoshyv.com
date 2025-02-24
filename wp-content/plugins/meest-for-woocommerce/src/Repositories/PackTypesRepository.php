<?php

namespace MeestShipping\Repositories;

class PackTypesRepository extends Repository
{
    public function get(): array
    {
        if (($types = wp_cache_get('meest_pack_types', '')) === false) {
            $response = meest_init('Api')->packTypes();
            $types = array_map(function ($item) {
                return [
                    'id' => $item['packID'],
                    'text' => $item['packDescr'] ?? null,
                    'weight' => [
                        'min' => $item['packLimits']['weightMin'],
                        'max' => $item['packLimits']['weightMax']
                    ],
                    'volume' => [
                        'min' => $item['packLimits']['volumeMin'],
                        'max' => $item['packLimits']['volumeMax']
                    ],
                    'size' => [
                        'length' => $item['packLimits']['lengthMax'] / 10,
                        'width' => $item['packLimits']['widthMax'] / 10,
                        'height' => $item['packLimits']['heightMax'] / 10
                    ]
                ];
            }, $response);

            wp_cache_set('meest_pack_types', $types, '', 86400);
        }

        return $types;
    }
}
