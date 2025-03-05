<?php
return [
    'country_id' => [
        'ua' => 'c35b6195-4ea3-11de-8591-001d600938f8',
    ],
    'credential' => [
        'username' => '',
        'password' => '',
        'token' => '',
    ],
    'address' => [
        'delivery_type' => 'address',
        'country' => [
            'id' => '',
            'text' => '',
            'code' => ''
        ],
        'region' => [
            'text' => ''
        ],
        'city' => [
            'id' => '',
            'text' => ''
        ],
        'street' => [
            'id' => '',
            'text' => ''
        ],
        'building' => '',
        'flat' => '',
        'postcode' => '',
        'branch' => [
            'id' => '',
            'text' => ''
        ],
    ],
    'empty_user' => [
        'first_name' => '',
        'last_name' => '',
        'phone' => '',
        'delivery_type' => 'branch',
        'country' => [
            'id' => '',
            'text' => '',
            'code' => '',
        ],
        'region' => [
            'text' => ''
        ],
        'city' => [
            'id' => '',
            'text' => ''
        ],
        'street' => [
            'id' => '',
            'text' => ''
        ],
        'building' => '',
        'flat' => '',
        'postcode' => '',
        'branch' => [
            'id' => '',
            'text' => ''
        ],
    ],
    'shipping' => [
        'delivery_type' => null,
        'calc_cost' => 1,
        'fixed_cost' => null,
        'auto_cod' => 1,
        'package' => true,
        'branch_limits' => true,
        'send_email' => false,
    ],
    'parcel' => [
        'weight' => 0.1,
        'lwh' => [10, 10, 10],
        'pay_type' => 1,
        'receiver_pay' => 1,
        'insurance' => 100,
    ],
    'pages' => [
        'checkout' => 'checkout'
    ],
    'cache_interval' => 86400,
    'url' => 'https://api.meest.com/v3.0/openAPI',
    'tracking_url' => 'https://t.meest-group.com/',
    'dictionary_url' => 'https://meest-group.com/media/location/locations.zip',
    'urns' => [
        'auth_get' => '/auth',
        'auth_refresh' => '/refreshToken',

        'country_search' => '/countrySearch',
        'region_search' => '/regionSearch',
        'city_search' => '/citySearch',
        'street_search' => '/addressSearch',

        'branch_search' => '/branchSearch',
        'branch_types' => '/branchTypes',

        'parcel_create' => '/parcel',
        'parcel_update' => '/parcel/{parcelID}',
        'parcel_delete' => '/parcel/{parcelID}',
        'parcel_calculate' => '/calculate',

        'pickup_create' => '/registerPickup',
        'pickup_update' => '/registerPickup/{registerID}',
        'pickup_delete' => '/registerPickup/{registerID}',

        'pack_types' => '/packTypes',

        'print_declaration' => '/print/declaration/{printValue}/{contentType}',
        'print_sticker100' => '/print/sticker100/{printValue}',

        'tracking' => '/tracking/{trackNumber}',
        'calculate' => '/priceIntercourier_standardIOSS',//'/calculate',
    ],
    'dictionary' => [
        'is_db' => false,
        'auto_update' => false,
        'cron_timestamp' => '00:00:00',
        'files' => [
            'region' => 'Области.txt',
            'district' => 'Районы.txt',
            'city' => 'Города.txt',
            'street' => 'Улицы.txt',
            'branch' => 'Подразделения.txt',
        ]
    ],
    'support' => [
        'email' => 'webdeveloper.eu@gmail.com'
    ],
    'block_countries' => [
        'ru' => 'c35b616b-4ea3-11de-8591-001d600938f8',
        'by' => 'c35b60ce-4ea3-11de-8591-001d600938f8',
    ],
];
