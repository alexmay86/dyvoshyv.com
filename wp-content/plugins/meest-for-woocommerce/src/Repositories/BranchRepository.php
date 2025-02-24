<?php

namespace MeestShipping\Repositories;

class BranchRepository extends Repository
{
    /**
     * @param $city
     * @param null $text
     * @param array $limit
     * @return array
     */
    public function search($city, $text = null, $limit = []): array
    {
        $data = [
            'cityID' => $city
        ];

        if (!empty($text)) {
            if (is_numeric($text) && strlen($text) >= 4) {
                $data['branchNo'] = $text;
            } else {
                $data['cityID'] = $city;
                $data['branchDescr'] = "%$text%";
            }
        }

        $items = meest_init('Api')->searchBranch($data);
        $checkLimit = self::checkLimit($limit);

        return array_map(function ($item) use ($checkLimit) {
            $street = $item['addressDescr']['descr'.$this->meestLocale];
            $branchType = $item['branchTypeAPP'] === "3" ? __('ATM', MEEST_PLUGIN_DOMAIN) : __('Branch', MEEST_PLUGIN_DOMAIN);
            $limits = [];
            if ($this->options['shipping']['branch_limits'] == 1) {
                if (!empty($item['branchLimits']['weightTotalMax'])) {
                    $limits['weight'] = sprintf(__('weight - %s kg', MEEST_PLUGIN_DOMAIN), $item['branchLimits']['weightTotalMax']);
                }
                if (!empty($item['branchLimits']['gabaritesMax']['length'])) {
                    $limits['size'] = sprintf(
                        __('size(lwh) - %sx%sx%s cm', MEEST_PLUGIN_DOMAIN),
                        ...array_values($item['branchLimits']['gabaritesMax'])
                    );
                }
                if (!empty($item['branchLimits']['insuranceTotalMax'])) {
                    $limits['insurance'] = sprintf(__('insurance - %s', MEEST_PLUGIN_DOMAIN), $item['branchLimits']['insuranceTotalMax']);
                }
            }

            return [
                'id' => $item['branchID'],
                'text' => $street.', '.$item['building'].' - '.$branchType.' #'.$item['branchNo']
                    .(!empty($item['addressMoreInformation']) ? ' ('.$item['addressMoreInformation'].')' : ''),
                'description' => !empty($limits) ? (__('Limits', MEEST_PLUGIN_DOMAIN).': '.implode(', ', $limits)) : '',
                'type' => $item['branchType'],
                'type_app' => $item['branchTypeAPP'],
                'weight' => $item['branchLimits']['weightTotalMax'],
                'insurance' => $item['branchLimits']['insuranceTotalMax'],
                'volume' => $item['branchLimits']['volumeTotalMax'],
                'lwh' => [
                    $item['branchLimits']['gabaritesMax']['length'],
                    $item['branchLimits']['gabaritesMax']['width'],
                    $item['branchLimits']['gabaritesMax']['height'],
                ],
                'limit_error' => $checkLimit($item),
            ];
        }, $items);
    }

    /**
     * @param array $data
     * @return array
     */
    public function types($data = []): array
    {
        $items = meest_init('Api')->getBranchTypes();
        $checkLimit = self::checkLimit($data);

        return array_filter(array_map(function ($item) use ($checkLimit) {
            if (!$checkLimit($item)) {
                return null;
            }

            return [
                'id' => $item['branchTypeID'],
                'description' => $item['branchTypeDescr']['descrUA'],
                'type' => $item['branchTypeDescr']['type'],
                'type_app' => $item['branchTypeDescr']['typeAPP'],
                'weight' => $item['branchLimits']['weightTotalMax'],
                'insurance' => $item['branchLimits']['insuranceTotalMax'],
                'volume' => $item['branchLimits']['volumeTotalMax'],
                'length' => $item['branchLimits']['gabaritesMax']['length'],
                'width' => $item['branchLimits']['gabaritesMax']['width'],
                'height' => $item['branchLimits']['gabaritesMax']['height'],
            ];
        }, $items));
    }

    /**
     * @param array $data
     * @return \Closure
     */
    public static function checkLimit($data = []): \Closure
    {
        $isLimit = function ($key, $limit) use ($data) {
            return isset($data[$key]) && $limit !== 0 && $data[$key] > $limit;
        };

        return function ($item) use ($isLimit): ?string {
            if ($isLimit('weight', $item['branchLimits']['weightTotalMax'])) {
                return 'weight';
            }
            if ($isLimit('insurance', $item['branchLimits']['insuranceTotalMax'])) {
                return 'insurance';
            }
            if ($isLimit('volume', $item['branchLimits']['volumeTotalMax'])) {
                return 'volume';
            }
            if ($isLimit('length', $item['branchLimits']['gabaritesMax']['length'])) {
                return 'length';
            }
            if ($isLimit('width', $item['branchLimits']['gabaritesMax']['width'])) {
                return 'width';
            }
            if ($isLimit('height', $item['branchLimits']['gabaritesMax']['height'])) {
                return 'height';
            }

            return null;
        };
    }
}
