<?php

namespace MeestShipping\Models;

use MeestShipping\Core\Model;

class PickupParcel extends Model
{
    protected $table = 'meest_pickup_parcel';
    protected $fields = [
        'pickup_id' => null,
        'parcel_id' => null,
    ];
    protected $formats = [
        'pickup_id' => '%d',
        'parcel_id' => '%d',
    ];
}
