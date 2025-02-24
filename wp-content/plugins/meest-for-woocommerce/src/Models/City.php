<?php

namespace MeestShipping\Models;

use MeestShipping\Core\Model;

class City extends Model
{
    const TYPE_CITY = 1;
    const TYPE_VILLAGE = 2;

    protected $table = 'meest_cities';
    protected $fields = [
        'city_uuid' => null,
        'district_uuid' => null,
        'region_uuid' => null,
        'country_uuid' => null,
        'type_id' => null,
        'name_uk' => null,
        'name_ru' => null,
        'delivery_zone' => null,
    ];
    protected $formats = [
        'id' => '%d',
        'city_uuid' => '%s',
        'district_uuid' => '%s',
        'region_uuid' => '%s',
        'country_uuid' => '%s',
        'type_id' => '%d',
        'name_uk' => '%s',
        'name_ru' => '%s',
        'delivery_zone' => '%s',
    ];

    public static function search($text = null, $countryUuid = null, int $limit = 25)
    {
        $self = new static();
        $district = new District();
        $region = new Region();

        $query = "SELECT c.*, d.name_uk AS district_name_uk, d.name_uk AS district_name_ru, r.name_uk AS region_name_uk, d.name_uk AS region_name_ru";
        $query .= " FROM {$self->getTable()} AS c";
        $query .= " LEFT JOIN {$district->getTable()} AS d ON d.district_uuid = c.district_uuid";
        $query .= " LEFT JOIN {$region->getTable()} AS r ON r.region_uuid = c.region_uuid";

        if (!empty($text)) {
            $where[] = "(c.name_uk LIKE '%$text%' OR c.name_ru LIKE '%$text%')";
        }
        if (!empty($countryUuid)) {
            $where[] = "c.country_uuid = '$countryUuid'";
        }
        if (!empty($where)) {
            $query .= ' WHERE ' . implode(' AND ', $where);
        }
        $query .= " LIMIT $limit";

        return $self->db->get_results($query, ARRAY_A);
    }
}
