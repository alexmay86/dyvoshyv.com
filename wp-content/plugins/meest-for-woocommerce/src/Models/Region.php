<?php

namespace MeestShipping\Models;

use MeestShipping\Core\Model;

class Region extends Model
{
    protected $table = 'meest_regions';
    protected $fields = [
        'region_uuid' => null,
        'country_uuid' => null,
        'name_uk' => null,
        'name_ru' => null,
    ];
    protected $formats = [
        'id' => '%d',
        'region_uuid' => '%s',
        'country_uuid' => '%s',
        'name_uk' => '%s',
        'name_ru' => '%s',
    ];
}
