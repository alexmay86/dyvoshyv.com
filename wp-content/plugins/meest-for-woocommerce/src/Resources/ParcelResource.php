<?php
namespace MeestShipping\Resources;

use MeestShipping\Core\Resource;
use MeestShipping\Traits\AddressTrait;

class ParcelResource extends Resource
{
    use AddressTrait;

    public function toArray(): array
    {
        return [
            'pay_type' => $this->data['parcel']['pay_type'],
            'receiver_pay' => $this->data['parcel']['payer'],
            'cod' => $this->data['parcel']['cod'],
            'insurance' => $this->data['parcel']['insurance'],
            'weight' => $this->data['parcel']['weight'],
            'pack_type' => $this->data['parcel']['pack_type'],
            'lwh' => empty($pack = $this->data['parcel']['pack_type'])
                ? $this->data['parcel']['lwh']
                : [],
            'notation' => $this->data['parcel']['notation'],
            'sender' => array_merge(
                $this->getUser($this->data, 'sender'),
                $this->getAddress($this->data, 'sender')
            ),
            'receiver' => array_merge(
                $this->getUser($this->data, 'receiver'),
                $this->getAddress($this->data, 'receiver')
            )
        ];
    }
}
