<?php
namespace MeestShipping\Resources;

use MeestShipping\Core\Resource;

class ParcelApiResource extends Resource
{
    public function toArray(): array
    {
        return [
            'COD' => $this->options['shipping']['auto_cod'] == 1 ? $this->data['parcel']['cod'] : null,
            'payType' => $this->data['parcel']['pay_type'] == 1 ? 'cash' : 'noncash',
            'receiverPay' => (bool) $this->data['parcel']['payer'],
            'notation' => $this->data['parcel']['notation'],
            'sender' => array_merge(
                $this->getUser($this->data, 'sender'),
                $this->getAddress($this->data, 'sender')
            ),
            'receiver' => array_merge(
                $this->getUser($this->data, 'receiver'),
                $this->getAddress($this->data, 'receiver')
            ),
            'placesItems' => $this->getItems(),
        ];
    }

    private function getUser($data, $type): array
    {
        return [
            'name' => $data[$type]['last_name'].' '.$data[$type]['first_name']
                .($data[$type]['middle_name'] ? ' '.$data[$type]['middle_name'] : null),
            'phone' => $data[$type]['phone']
        ];
    }

    private function getAddress($data, $type): array
    {
        $arr = [
            'countryId' => $data[$type]['country']['id']
        ];

        if ($data[$type]['delivery_type'] === 'branch') {
            $arr['service'] = 'Branch';
            $arr['branchId'] = $data[$type]['branch']['id'];
        } else {
            $arr['service'] = 'Door';
            if ($data[$type]['country']['id'] === $this->options['country_id']['ua']) {
                $arr['cityId'] = $data[$type]['city']['id'];
                $arr['addressId'] = $data[$type]['street']['id'];
            } else {
                $arr['regionDescr'] = $data[$type]['region']['text'];
                $arr['cityDescr'] = $data[$type]['city']['text'];
                $arr['addressDescr'] = $data[$type]['street']['text'];
            }
            $arr['building'] = $data[$type]['building'];
            $arr['flat'] = $data[$type]['flat'];
        }

        return $arr;
    }

    private function getItems(): array
    {
        $arr[] = [
            'quantity' => 1,
            'insurance' => $this->data['parcel']['insurance'],
            'weight' => $this->data['parcel']['weight'],
            'pack_type' => $this->data['parcel']['pack_type'],
            'length' => $this->data['parcel']['lwh'][0],
            'width' => $this->data['parcel']['lwh'][1],
            'height' => $this->data['parcel']['lwh'][2],
        ];

        return $arr;
    }
}
