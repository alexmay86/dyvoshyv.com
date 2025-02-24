<?php

namespace MeestShipping\Repositories;

use MeestShipping\Models\Parcel;

class ParcelRepository extends Repository
{
    public static function findByPickup($id)
    {
        $self = new Parcel();
        $mpTable = $self->getTable();
        $mppTable = $self->getTable('meest_pickup_parcel');
        $query = "SELECT * FROM $mpTable"
            ." LEFT JOIN $mppTable AS mpp ON mpp.parcel_id = id"
            ." WHERE mpp.pickup_id = ".$id;

        $results = $self->getResults($query);

        $objects = [];
        foreach ($results as $result) {
            $object = new Parcel();
            $object->fill($result);
            $objects[] = $object;
        }

        return $objects;
    }
}
