<?php
namespace MeestShipping\Resources;

use MeestShipping\Core\Resource;

class CostApiResource extends Resource
{
    protected $sanitize = false;

    public function toArray(): array
    {
        return [
            'sendingDate' => date('d.m.Y'),
            'COD' => $this->options['shipping']['auto_cod'] == 1 && $this->data['payment_method'] === 'cod'
                ? $this->data['totals']['total']
                : null,
            'sender' => $this->getSenderAddress($this->options['address']),
            'receiver' => $this->getReceiverAddress(meest_sanitize_text_field($this->data)),
            'placesItems' => $this->getItems($this->data['items']),
        ];
    }

    public static function check($data): bool
    {
        $self = new static($data);

        $type = empty($self->data['ship_to_different_address']) ? 'billing' : 'shipping';

        if ($self->data["{$type}_delivery_type"] === 'branch') {
            return !empty($self->data["{$type}_branch_id"]);
        } else {
            if ($self->data["{$type}_country_id"] === $self->options["country_id"]['ua']) {
                return !empty($self->data["{$type}_city_id"]);
            } else {
                return !empty($self->data["{$type}_country_id"]) && !empty($self->data["{$type}_region_text"]) && !empty($self->data["{$type}_city_text"]);
            }
        }
    }

    private function getSenderAddress($data): array
    {
        $arr = [
            'countryId' => $data['country']['id']
        ];

        if ($data['delivery_type'] === 'branch') {
            $arr['service'] = 'Branch';
            $arr['branchId'] = $data['branch']['id'];
        } else {
            $arr['service'] = 'Door';
            if ($data['country']['id'] === $this->options['country_id']['ua']) {
                $arr['cityId'] = $data['city']['id'];
            } else {
                $arr['countryId'] = $data['country']['id'];
                $arr['regionDescr'] = $data['region']['text'];
                $arr['cityDescr'] = $data['city']['text'];
            }
            $arr['building'] = $data['building'];
            $arr['flat'] = $data['flat'];
        }

        return $arr;
    }

    private function getReceiverAddress($data): array
    {
        $type = empty($data['ship_to_different_address']) ? 'billing' : 'shipping';

        $arr = [
            'countryId' => $data["{$type}_country_id"]
        ];

        if ($data["{$type}_delivery_type"] === 'branch') {
            $arr['service'] = 'Branch';
            $arr['branchId'] = $data["{$type}_branch_id"];
        } else {
            $arr['service'] = 'Door';
            if ($data["{$type}_country_id"] === $this->options['country_id']['ua']) {
                $arr['cityId'] = $data["{$type}_city_id"];
            } else {
                $arr['regionDescr'] = $data["{$type}_region_text"];
                $arr['cityDescr'] = $data["{$type}_city_text"];
            }
            $arr['building'] = $data["{$type}_building"];
            $arr['flat'] = $data["{$type}_flat"];
        }

        return $arr;
    }

    private function getItems($items): array
    {
        $arr = [];
        foreach ($items as $item) {
            $arr[] = [
                'quantity' => $item['quantity'],
                'insurance' => $item['line_total'],
                'weight' => $item['data']->get_weight() ?: 0.1,
            ];
        }

        return $arr;
    }
}
