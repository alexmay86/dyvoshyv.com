<?php
namespace MeestShipping\Resources;

use MeestShipping\Core\Resource;
use MeestShipping\Traits\AddressTrait;

class PickupResource extends Resource
{
    use AddressTrait;

    public function toArray(): array
    {
        return [
            'pay_type' => $this->data['pickup']['pay_type'],
            'receiver_pay' => $this->data['pickup']['payer'],
            'notation' => $this->data['pickup']['notation'],
            'expected_date' => $this->data['pickup']['expected_date'],
            'expected_time_from' => $this->data['pickup']['expected_time_from'],
            'expected_time_to' => $this->data['pickup']['expected_time_to'],
            'sender' => array_merge(
                $this->getUser($this->data, 'sender'),
                $this->getAddress($this->data, 'sender')
            ),
            'parcel_ids' => self::getParcelIds($this->data['parcels'] ?? [])
        ];
    }

    private static function getParcelIds(array $parcels): array
    {
        return array_map(function ($item) {
            return ['parcel_id' => $item['id']];
        }, $parcels);
    }
}
