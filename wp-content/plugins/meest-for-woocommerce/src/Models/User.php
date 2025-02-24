<?php

namespace MeestShipping\Models;

use MeestShipping\Core\Model;

class User extends Model
{
    protected $fields = [
        'first_name' => null,
        'last_name' => null,
        'middle_name' => null,
        'phone' => null,
        'delivery_type' => 'branch',
        'country' => null,
        'region' => null,
        'city' => null,
        'street' => null,
        'building' => null,
        'flat' => null,
        'postcode' => null,
        'branch' => null,
    ];
}
