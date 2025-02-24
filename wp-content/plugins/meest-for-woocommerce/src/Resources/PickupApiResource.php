<?php
namespace MeestShipping\Resources;

use DateTime;
use MeestShipping\Core\Resource;

class PickupApiResource extends Resource
{
    public function toArray(): array
    {
        return [
            'payType' => $this->data['pickup']['pay_type'] == 1 ? 'cash' : 'noncash',
            'receiverPay' => (bool) $this->data['pickup']['payer'],
            'notation' => $this->data['pickup']['notation'],
            'expectedPickUpDate' => [
                'date' => self::convertDate($this->data['pickup']['expected_date']),
                'timeFrom' => self::convertTime($this->data['pickup']['expected_time_from']),
                'timeTo' => self::convertTime($this->data['pickup']['expected_time_to'])
            ],
            'sender' => array_merge(
                $this->getUser($this->data, 'sender'),
                $this->getAddress($this->data, 'sender')
            ),
            'parcelsItems' => self::getParcelIds($this->data['parcels'] ?? [])
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

        return $arr;
    }

    private static function getParcelIds($parcels): array
    {
        return array_map(function ($item) {
            return [
                'parcelID' => $item['parcel_id']
            ];
        }, $parcels);
    }

    private static function convertDate($date): ?string
    {
        return !empty($date) ? DateTime::createFromFormat('Y-m-d', $date)->format('d.m.Y') : null;
    }

    private static function convertTime($time): ?string
    {
        try {
            return !empty($time) ? (new DateTime($time))->format('H:i') : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
