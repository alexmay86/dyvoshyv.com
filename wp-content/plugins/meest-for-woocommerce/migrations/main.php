<?php

return [
    'parcels' => "CREATE TABLE IF NOT EXISTS {prefix}meest_parcels (
            id int(11) NOT NULL AUTO_INCREMENT,
            order_id int(11) NULL,
            parcel_id CHAR(36) NULL,
            pack_type_id CHAR(36) NULL,
            sender JSON NULL,
            receiver JSON NULL,
            pay_type TINYINT NULL,
            receiver_pay TINYINT NULL,
            cod DECIMAL(8,2) NULL,
            insurance DECIMAL(8,2) NULL,
            weight DECIMAL(8,2) NULL,
            lwh JSON NULL,
            notation VARCHAR(255) NULL,
            barcode VARCHAR(16) NULL,
            cost_services DECIMAL(8,2) NULL,
            delivery_date DATE NULL,
            is_email TINYINT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) {collate}",
    'pickups' => "CREATE TABLE IF NOT EXISTS {prefix}meest_pickups (
            id int(11) NOT NULL AUTO_INCREMENT,
            sender JSON NULL,
            pay_type TINYINT NULL,
            receiver_pay TINYINT NULL,
            notation VARCHAR(255) NULL,
            expected_date DATE NULL,
            expected_time_from TIME NULL, 
            expected_time_to TIME NULL, 
            register_number char(16) NOT NULL, 
            register_id char(36) NOT NULL,
            register_date date NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) {collate}",
    'pickup_parcel' => "CREATE TABLE IF NOT EXISTS {prefix}meest_pickup_parcel (
            pickup_id int(11) NOT NULL,
            parcel_id int(11) NOT NULL
        ) {collate}",
    'countries' => "CREATE TABLE IF NOT EXISTS {prefix}meest_countries (
            id int(11) NOT NULL AUTO_INCREMENT,
            country_uuid CHAR(36) NULL,
            name_uk VARCHAR(255), 
            name_ru VARCHAR(255) NULL, 
            PRIMARY KEY (id)
        ) {collate}",
    'regions' => "CREATE TABLE IF NOT EXISTS {prefix}meest_regions (
            id int(11) NOT NULL AUTO_INCREMENT,
            region_uuid CHAR(36),
            country_uuid CHAR(36) NULL,
            name_uk VARCHAR(255), 
            name_ru VARCHAR(255) NULL, 
            PRIMARY KEY (id)
        ) {collate}",
    'districts' => "CREATE TABLE IF NOT EXISTS {prefix}meest_districts (
            id int(11) NOT NULL AUTO_INCREMENT,
            district_uuid CHAR(36),
            region_uuid CHAR(36) NULL,
            name_uk VARCHAR(255), 
            name_ru VARCHAR(255), 
            PRIMARY KEY (id)
        ) {collate}",
    'cities' => "CREATE TABLE IF NOT EXISTS {prefix}meest_cities (
            id int(11) NOT NULL AUTO_INCREMENT,
            type_id TINYINT NOT NULL DEFAULT '1' ,
            city_uuid CHAR(36) ,
            district_uuid CHAR(36) NULL,
            region_uuid CHAR(36) NULL,
            country_uuid CHAR(36) NULL,
            name_uk VARCHAR(255), 
            name_ru VARCHAR(255), 
            delivery_zone VARCHAR(255), 
            PRIMARY KEY (id)
        ) {collate}",
    'streets' => "CREATE TABLE IF NOT EXISTS {prefix}meest_streets (
            id int(11) NOT NULL AUTO_INCREMENT,
            street_uuid CHAR(36) ,
            city_uuid CHAR(36) ,
            type_id TINYINT NOT NULL DEFAULT '1' ,
            postcode VARCHAR(32), 
            name_uk VARCHAR(255), 
            name_ru VARCHAR(255), 
            type_uk VARCHAR(255), 
            type_ru VARCHAR(255), 
            PRIMARY KEY (id)
        ) {collate}",
    'branches' => "CREATE TABLE IF NOT EXISTS {prefix}meest_branches (
            id int(11) NOT NULL AUTO_INCREMENT,
            branch_uuid CHAR(36) ,
            city_uuid CHAR(36) ,
            name_uk VARCHAR(255), 
            description_uk VARCHAR(255), 
            PRIMARY KEY (id)
        ) {collate}",
];
