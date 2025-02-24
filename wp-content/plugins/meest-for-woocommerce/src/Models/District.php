<?php

namespace MeestShipping\Models;

use MeestShipping\Core\Model;

class District extends Model
{
    protected $table = 'meest_districts';
    protected $fields = [
        'district_uuid' => null,
        'region_uuid' => null,
        'name_uk' => null,
        'name_ru' => null,
    ];
    protected $formats = [
        'id' => '%d',
        'district_uuid' => '%s',
        'region_uuid' => '%s',
        'name_uk' => '%s',
        'name_ru' => '%s',
    ];
}
