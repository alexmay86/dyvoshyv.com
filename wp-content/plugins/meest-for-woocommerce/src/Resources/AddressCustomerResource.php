<?php
namespace MeestShipping\Resources;

use MeestShipping\Core\Resource;

class AddressCustomerResource extends Resource
{
    public function toArray(): array
    {
        $type = $this->args[0];

        $data = [
            'delivery_type' => $this->data["{$type}_delivery_type"],
            'country' => [
                'code' => $this->data["{$type}_country"],
                'id' => $this->data["{$type}_country_id"],
                'text' => $this->data["{$type}_country_text"]
            ],
            'city' => [
                'id' => $this->data["{$type}_city_id"],
                'text' => $this->data["{$type}_city_text"]
            ]
        ];

        if ($data['delivery_type'] === 'branch') {
            $data['branch'] = [
                'id' => $this->data["{$type}_branch_id"],
                'text' => $this->data["{$type}_branch_text"]
            ];
        } else {
            $data['region'] = [
                'text' => $this->data["{$type}_country_id"] !== $this->options['country_id']['ua']
                    ? $this->data["{$type}_region_text"]
                    : null
            ];
            $data['street'] = [
                'id' => $this->data["{$type}_street_id"],
                'text' => $this->data["{$type}_street_text"]
            ];
            $data['building'] = $this->data["{$type}_building"];
            $data['flat'] = $this->data["{$type}_flat"];
            $data['postcode'] = $this->data["{$type}_postcode"];
        }

        return $data;
    }
}
