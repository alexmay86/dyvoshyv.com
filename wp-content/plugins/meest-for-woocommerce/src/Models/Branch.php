<?php

namespace MeestShipping\Models;

use MeestShipping\Core\Model;

class Branch extends Model
{
    protected $table = 'meest_branches';
    protected $fields = [
        'branch_uuid' => null,
        'city_uuid' => null,
        'name_uk' => null,
        'description_uk' => null,
    ];
    protected $formats = [
        'id' => '%d',
        'branch_uuid' => '%s',
        'city_uuid' => '%s',
        'name_uk' => '%s',
        'description_uk' => '%s',
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
