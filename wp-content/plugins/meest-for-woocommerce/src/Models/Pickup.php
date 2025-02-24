<?php

namespace MeestShipping\Models;

use MeestShipping\Core\Model;

class Pickup extends Model
{
    protected $table = 'meest_pickups';
    protected $fields = [
        'id' => null,
        'sender' => null,
        'pay_type' => null,
        'receiver_pay' => null,
        'notation' => null,
        'expected_date' => null,
        'expected_time_from' => null,
        'expected_time_to' => null,
        'register_number' => null,
        'register_id' => null,
        'register_date' => null,
        'created_at' => null,
        'updated_at' => null
    ];
    protected $formats = [
        'id' => '%d',
        'sender' => '%s',
        'pay_type' => '%d',
        'receiver_pay' => '%d',
        'notation' => '%s',
        'expected_date' => '%s',
        'expected_time_from' => '%s',
        'expected_time_to' => '%s',
        'register_number' => '%s',
        'register_id' => '%s',
        'register_date' => '%s',
        'created_at' => '%s',
        'updated_at' => '%s'
    ];

    protected $casts = [
        'sender' => 'array',
    ];
}
