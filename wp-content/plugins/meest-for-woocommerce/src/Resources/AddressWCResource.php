<?php
namespace MeestShipping\Resources;

use MeestShipping\Core\Resource;

class AddressWCResource extends Resource
{
    public function toArray(): array
    {
        $type = $this->args[0];

        $data = [
            //'country' => $this->data["{$type}_country"],
            'state' => $this->data["{$type}_country_id"] !== $this->options['country_id']['ua']
                ? $this->data["{$type}_region_text"]
                : null,
            'city' => $this->data["{$type}_city_text"],
        ];

        if ($this->data["{$type}_delivery_type"] === 'branch') {
            $data['address_1'] = $this->data["{$type}_branch_text"];
        } else {
            $data['address_1'] = $this->data["{$type}_street_text"]
                .', '.$this->data["{$type}_building"]
                .(!empty($this->data["{$type}_flat"]) ? ', '.$this->data["{$type}_flat"] : '');
        }

        return $data;
    }
}
