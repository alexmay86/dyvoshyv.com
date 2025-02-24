<?php

namespace MeestShipping\Traits;

trait AddressTrait
{
    private function getUser($data, $type): array
    {
        return [
            'last_name' => $data[$type]['last_name'],
            'first_name' => $data[$type]['first_name'],
            'middle_name' => $data[$type]['middle_name'],
            'phone' => $data[$type]['phone']
        ];
    }

    private function getAddress($data, $type): array
    {
        $arr = [
            'delivery_type' => $data[$type]['delivery_type'] ?? 'address',
            'country' => [
                'id' => $data[$type]['country']['id'],
                'text' => $data[$type]['country']['text'],
                'code' => $data[$type]['country']['code'],
            ]
        ];

        if (isset($data[$type]['delivery_type']) && $data[$type]['delivery_type'] === 'branch') {
            $arr['city'] = [
                'id' => $data[$type]['city']['id'],
                'text' => $data[$type]['city']['text'],
            ];
            $arr['branch'] = [
                'id' => $data[$type]['branch']['id'],
                'text' => $data[$type]['branch']['text'],
            ];
        } else {
            if ($data[$type]['country']['id'] === $this->options['country_id']['ua']) {
                $arr['city'] = [
                    'id' => $data[$type]['city']['id'],
                    'text' => $data[$type]['city']['text'],
                ];
                $arr['street'] = [
                    'id' => $data[$type]['street']['id'],
                    'text' => $data[$type]['street']['text'],
                ];
            } else {
                $arr['region'] = [
                    'text' => $data[$type]['region']['text'],
                ];
                $arr['city'] = [
                    'id' => null,
                    'text' => $data[$type]['city']['text'],
                ];
                $arr['street'] = [
                    'id' => null,
                    'text' => $data[$type]['street']['text'],
                ];
            }
            $arr['building'] = $data[$type]['building'];
            $arr['flat'] = $data[$type]['flat'];
            $arr['postcode'] = $data[$type]['postcode'];
        }

        return $arr;
    }
}
