<?php

namespace MeestShipping\Modules;

defined( 'ABSPATH' ) || exit;

class Asset
{
    private static $_instance;
    private $options;
    public $enqueues;
    public $locale;

    public function __construct()
    {
        $this->options = meest_init('Option')->all();
        $this->locale = substr(get_locale(), 0, 2);

        $this->enqueues = [
            'jquery-select2' => [
                'css' => [
                    'src' => MEEST_PLUGIN_URL.'public/plugins/select2/select2.min.css',
                ],
                'js' => [
                    'src' => MEEST_PLUGIN_URL.'public/plugins/select2/select2.min.js',
                ]
            ],
            'flatpickr' => [
                'css' => [
                    'src' => MEEST_PLUGIN_URL.'public/plugins/flatpickr/flatpickr.min.css',
                ],
                'js' => [
                    'src' => MEEST_PLUGIN_URL.'public/plugins/flatpickr/flatpickr.min.js',
                    'locale' => MEEST_PLUGIN_URL.'public/plugins/flatpickr/l10n/%s.js'
                ],
            ],
            'meest' => [
                'css' => [
                    'src' => MEEST_PLUGIN_URL.'public/css/style.min.css',
                    'deps' => [],
                    'ver' => MEEST_PLUGIN_VERSION
                ],
            ],
            'meest-address' => [
                'js' => [
                    'src' => MEEST_PLUGIN_URL.'public/js/address.min.js',
                    'deps' => ['jquery-select2'],
                    'ver' => MEEST_PLUGIN_VERSION
                ]
            ],
            'meest-setting' =>  [
                'js' => [
                    'src' => MEEST_PLUGIN_URL.'public/js/setting.min.js',
                    'deps' => ['meest-address'],
                    'ver' => MEEST_PLUGIN_VERSION
                ],
            ],
            'meest-parcel' => [
                'js' => [
                    'src' => MEEST_PLUGIN_URL.'public/js/parcel.min.js',
                    'deps' => ['meest-address'],
                    'ver' => MEEST_PLUGIN_VERSION
                ],
            ],
            'meest-pickup' => [
                'js' => [
                    'src' => MEEST_PLUGIN_URL.'public/js/pickup.min.js',
                    'deps' => ['meest-address'],
                    'ver' => MEEST_PLUGIN_VERSION
                ],
            ],
            'meest-checkout' =>  [
                'js' => [
                    'src' => MEEST_PLUGIN_URL.'public/js/checkout.min.js',
                    'deps' => ['meest-address'],
                    'ver' => MEEST_PLUGIN_VERSION
                ],
            ],
        ];
    }

    public static function instance(): Asset
    {
        return static::$_instance ?? static::$_instance = new static();
    }

    public static function load($keys): void
    {
        $self = self::instance();

        foreach ($keys as $key) {
            if (isset($self->enqueues[$key])) {
                $item = $self->enqueues[$key];

                if (isset($item['css'])) {
                    wp_register_style($key, $item['css']['src'], $item['css']['deps'] ?? [], $item['css']['ver'] ?? false);
                    wp_enqueue_style($key);
                }
                if (isset($item['js'])) {
                    wp_register_script($key, $item['js']['src'], $item['js']['deps'] ?? [], $item['js']['ver'] ?? false);
                    wp_enqueue_script($key);

                    if (isset($item['js']['locale'])) {
                        wp_register_script("$key-locale", sprintf($item['js']['locale'], $self->locale), [$key]);
                        wp_enqueue_script("$key-locale");
                    }
                }
            } else {
                wp_enqueue_script($key);
            }
        }
    }

    public static function localize(string $handle)
    {
        $self = self::instance();

        wp_localize_script($handle, 'meest', [
            'id' => MEEST_PLUGIN_NAME,
            'ajaxUrl' => admin_url('admin-ajax.php', 'relative'),
            'actions' => [
                'get_country' => 'meest_address_country',
                'get_city' => 'meest_address_city',
                'get_street' => 'meest_address_street',
                'get_branch' => 'meest_address_branch',
                'update_dictionary' => 'meest_update_dictionary',
            ],
            'delivery_types' => [
                'branch' => 'Branch delivery',
                'address' => 'Address delivery',
            ],
            'country_id' => $self->options['country_id']
        ]);
    }
}
