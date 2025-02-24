<?php

namespace MeestShipping\Models;

use MeestShipping\Core\Model;

class Parcel extends Model
{
    protected $table = 'meest_parcels';
    protected $fields = [
        'id' => null,
        'order_id' => null,
        'parcel_id' => null,
        'pack_type_id' => null,
        'sender' => null,
        'receiver' => null,
        'pay_type' => 1,
        'receiver_pay' => 1,
        'cod' => null,
        'insurance' => null,
        'weight' => null,
        'lwh' => null,
        'notation' => null,
        'barcode' => null,
        'cost_services' => null,
        'delivery_date' => null,
        'is_email' => null,
        'created_at' => null,
        'updated_at' => null,
    ];
    protected $formats = [
        'id' => '%d',
        'order_id' => '%d',
        'parcel_id' => '%s',
        'pack_type_id' => '%s',
        'sender' => '%s',
        'receiver' => '%s',
        'pay_type' => '%d',
        'receiver_pay' => '%d',
        'cod' => '%f',
        'insurance' => '%f',
        'weight' => '%f',
        'lwh' => '%s',
        'notation' => '%s',
        'barcode' => '%s',
        'cost_services' => '%f',
        'delivery_date' => '%s',
        'is_email' => '%d',
        'created_at' => '%s',
        'updated_at' => '%s'
    ];
    protected $casts = [
        'sender' => 'array',
        'receiver' => 'array',
        'lwh' => 'array',
    ];

    public static function total($search)
    {
        $self = new static();
        $query = "SELECT * FROM ".$self->getTable();

        if (!empty($search)) {
            $query.=" WHERE barcode LIKE '$search%'";
            if ((int)$search > 0) {
                $query.=" OR order_id = $search";
            }
            $query.=" OR JSON_EXTRACT(sender, '$.phone') LIKE '%$search%'";
            $query.=" OR JSON_EXTRACT(sender, '$.last_name') LIKE '%$search%'";
            $query.=" OR JSON_EXTRACT(receiver, '$.phone') LIKE '%$search%'";
            $query.=" OR JSON_EXTRACT(receiver, '$.last_name') LIKE '%$search%'";
        }

        return $self->db->query($query);
    }

    public static function page($search, $orderby, $current_page, $per_page)
    {
        $self = new static();
        $mpTable = $self->getTable();
        $mppTable = $self->getTable('meest_pickup_parcel');
        $query = "SELECT $mpTable.*, mpp.pickup_id AS pickup_id"
            ." FROM $mpTable"
            ." LEFT JOIN $mppTable AS mpp ON mpp.parcel_id = id";

        if (!empty($search)) {
            $query.=" WHERE barcode LIKE '$search%'";
            if ((int)$search > 0) {
                $query.=" OR order_id = $search";
            }
            $query.=" OR JSON_EXTRACT(sender, '$.phone') LIKE '%$search%'";
            $query.=" OR JSON_EXTRACT(sender, '$.last_name') LIKE '%$search%'";
            $query.=" OR JSON_EXTRACT(receiver, '$.phone') LIKE '%$search%'";
            $query.=" OR JSON_EXTRACT(receiver, '$.last_name') LIKE '%$search%'";
        }

        if (!empty($orderby)) {
            $query.=' ORDER BY '.$orderby;
        }

        if (!empty($current_page) && !empty($per_page)) {
            $offset = ($current_page - 1) * $per_page;
            $query .= ' LIMIT '.(int) $offset.','.(int) $per_page;
        }

        return $self->db->get_results($query, ARRAY_A);
    }
}
