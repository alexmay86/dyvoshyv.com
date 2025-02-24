<?php

namespace MeestShipping\Models;

use MeestShipping\Core\Model;

class Street extends Model
{
    const TYPE_CITY = 1;
    const TYPE_VILLAGE = 2;

    protected $table = 'meest_streets';
    protected $fields = [
        'street_uuid' => null,
        'city_uuid' => null,
        'type_id' => null,
        'postcode' => null,
        'name_uk' => null,
        'name_ru' => null,
        'type_uk' => null,
        'type_ru' => null,
    ];
    protected $formats = [
        'id' => '%d',
        'street_uuid' => '%s',
        'city_uuid' => '%s',
        'type_id' => '%d',
        'postcode' => '%s',
        'name_uk' => '%s',
        'name_ru' => '%s',
        'type_uk' => '%s',
        'type_ru' => '%s',
    ];

    public static function search($text = null, $cityUuid = null, int $limit = 25)
    {
        $self = new static();

        $query = "SELECT s.*";
        $query .= " FROM {$self->getTable()} AS s";

        if (!empty($text)) {
            $where[] = "(s.name_uk LIKE '%$text%' OR s.name_ru LIKE '%$text%')";
        }
        if (!empty($cityUuid)) {
            $where[] = "s.city_uuid = '$cityUuid'";
        }
        if (!empty($where)) {
            $query .= ' WHERE ' . implode(' AND ', $where);
        }
        $query .= " LIMIT $limit";

        return $self->db->get_results($query, ARRAY_A);
    }
}
