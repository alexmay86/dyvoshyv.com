<?php

namespace MeestShipping\Modules;

use MeestShipping\Core\Customer;
use MeestShipping\Core\Request;
use MeestShipping\Helpers\Html;
use MeestShipping\Traits\Helper;
use MeestShipping\Resources\AddressCustomerResource;

defined( 'ABSPATH' ) || exit;

class Checkout
{
    use Helper;

    private $customer;
    private $options;

    public function __construct()
    {
        $this->options = meest_init('Option')->all();
        $this->customer = Customer::instance();
    }

    public function init()
    {
        add_action('woocommerce_after_checkout_billing_form', [$this, 'afterCheckoutBillingForm']);
        add_action('woocommerce_after_checkout_shipping_form', [$this, 'afterCheckoutShippingForm']);
        add_filter('woocommerce_checkout_fields', [$this, 'checkoutFields']);
        add_action('woocommerce_checkout_process', [$this, 'checkoutProcess'], 10, 2);
    }

    public function afterCheckoutBillingForm()
    {
        $this->checkoutForm('billing');
    }

    public function afterCheckoutShippingForm()
    {
        $this->checkoutForm('shipping');
    }

    private function checkoutForm($type)
    {
        $address = $this->getAddress($type);
        ?>
        <div id="<?php echo $type; ?>_meest_form" style="clear: both;">
            <h3><?php _e('Enter delivery address', MEEST_PLUGIN_DOMAIN); ?></h3>
            <?php
            woocommerce_form_field("{$type}_country_id", [
                'label' => __('Country', MEEST_PLUGIN_DOMAIN),
                'type' => 'select',
                'id' => "{$type}_meest_country_id",
                'options' => [$address['country']['id'] => $address['country']['text']],
                'default' => $address['country']['id'],
                'required' => true,
                'class' => [],
                'placeholder' => __('Select a country', MEEST_PLUGIN_DOMAIN),
                'input_class' => ['input-text'],
            ]);
            echo Html::hiddenTextInput("{$type}_meest_country_text", "{$type}_country_text", $address['country']['text']);
            echo Html::hiddenTextInput("{$type}_meest_country", "{$type}_country", $address['country']['code']);

            woocommerce_form_field('shipping_country', [
                "type" => "country",
                "label" => "Країна/Регіон",
                "required" => true,
                "class" => [
                0 => "form-row-wide",
                1 => "address-field",
                2 => "update_totals_on_change"
                ],
                "autocomplete" => "country",
                "priority" => 40
            ]);

            woocommerce_form_field("{$type}_region_text", [
                'label' => __('Region', MEEST_PLUGIN_DOMAIN),
                'type' => 'text',
                'id' => "{$type}_meest_region_text",
                'default' => $address['region']['text'],
                'required' => true,
                'placeholder' => __('Region', MEEST_PLUGIN_DOMAIN),
            ]);

            woocommerce_form_field("{$type}_city_id", [
                'label' => __('City', MEEST_PLUGIN_DOMAIN),
                'type' => 'select',
                'id' => "{$type}_meest_city_id",
                'options' => [$address['city']['id'] => $address['city']['text']],
                'default' => $address['city']['id'],
                'required' => true,
                'class' => [],
                'placeholder' => __('Select a city', MEEST_PLUGIN_DOMAIN),
                'input_class' => ['input-text'],
            ]);
            woocommerce_form_field("{$type}_city_text", [
                'label' => __('City', MEEST_PLUGIN_DOMAIN),
                'type' => 'text',
                'id' => "{$type}_meest_city_text",
                'default' => $address['city']['text'],
                'required' => true,
                'placeholder' => __('City', MEEST_PLUGIN_DOMAIN),
            ]);

            if (empty($this->options['shipping']['delivery_type'])) {
                woocommerce_form_field("{$type}_delivery_type", [
                    'label' => __('Delivery type', MEEST_PLUGIN_DOMAIN),
                    'type' => 'radio',
                    'id' => "{$type}_meest_delivery_type",
                    'options' => [
                        'branch' => __('Branch', MEEST_PLUGIN_DOMAIN),
                        'address' => __('Address', MEEST_PLUGIN_DOMAIN),
                    ],
                    'default' => $address['delivery_type'],
                    'required' => true,
                    'class' => ['radio-inline'],
                ]);
            } else {
                echo '<div class="hidden" style="display: none;">';
                woocommerce_form_field("{$type}_delivery_type", [
                    'type' => 'radio',
                    'id' => "{$type}_meest_delivery_type",
                    'options' => [
                        'branch' => __('Branch', MEEST_PLUGIN_DOMAIN),
                        'address' => __('Address', MEEST_PLUGIN_DOMAIN),
                    ],
                    'default' => $this->options['shipping']['delivery_type'],
                    'required' => true,
                ]);
                echo '</div>';
            }

            woocommerce_form_field("{$type}_street_id", [
                'label' => __('Street', MEEST_PLUGIN_DOMAIN),
                'type' => 'select',
                'id' => "{$type}_meest_street_id",
                'options' => [$address['street']['id'] => $address['street']['text']],
                'default' => $address['street']['id'],
                'required' => true,
                'class' => [],
                'placeholder' => __('Select a street', MEEST_PLUGIN_DOMAIN),
                'input_class' => ['input-text'],
            ]);
            woocommerce_form_field("{$type}_street_text", [
                'label' => __('Street', MEEST_PLUGIN_DOMAIN),
                'type' => 'text',
                'id' => "{$type}_meest_street_text",
                'default' => $address['street']['text'],
                'required' => true,
                'placeholder' => __('Street', MEEST_PLUGIN_DOMAIN),
            ]);

            woocommerce_form_field("{$type}_building", [
                'label' => __('Building', MEEST_PLUGIN_DOMAIN),
                'type' => 'text',
                'id' => "{$type}_meest_building",
                'default' => $address['building'],
                'required' => true,
                'class' => ['form-row-first'],
                'input_class' => ['input-text'],
                'placeholder' => __('Building', MEEST_PLUGIN_DOMAIN),
            ]);
            woocommerce_form_field("{$type}_flat", [
                'label' => __('Flat', MEEST_PLUGIN_DOMAIN),
                'type' => 'text',
                'id' => "{$type}_meest_flat",
                'default' => $address['flat'],
                'required' => false,
                'class' => ['form-row-last'],
                'input_class' => ['input-text'],
                'placeholder' => __('Flat', MEEST_PLUGIN_DOMAIN),
            ]);

            woocommerce_form_field("{$type}_postcode", [
                'label' => pll__('Postcode'),
                'type' => 'text',
                'id' => "{$type}_postcode",
                'default' => $address['postcode'],
                'required' => false,
                'class' => ['form-row-wide'],
                'input_class' => ['input-text'],
                'placeholder' => pll__('Postcode'),
            ]);

            woocommerce_form_field("{$type}_branch_id", [
                'label' => __('Branch', MEEST_PLUGIN_DOMAIN),
                'type' => 'select',
                'id' => "{$type}_meest_branch_id",
                'options' => [$address['branch']['id'] => $address['branch']['text']],
                'default' => $address['branch']['id'],
                'required' => true,
                'class' => [],
                'placeholder' => __('Select a branch', MEEST_PLUGIN_DOMAIN),
                'input_class' => ['input-text'],
            ]);
            echo Html::hiddenTextInput("{$type}_meest_branch_text", "{$type}_branch_text", $address['branch']['text']);
            ?>
        </div>
        <?php
    }

    public function checkoutFields($fields)
    {
        if (self::isMeestShipping()) {
            self::disableValidateFields($fields, 'billing');
            if (self::shipToDifferentAddress() === 'shipping') {
                self::disableValidateFields($fields, 'shipping');
            }
        }

        return $fields;
    }

    public static function disableValidateFields(&$fields, $type)
    {
        isset($fields[$type]["{$type}_state"]) && $fields[$type][$type.'_state']['required'] = false;
        isset($fields[$type]["{$type}_city"]) && $fields[$type][$type.'_city']['required'] = false;
        isset($fields[$type]["{$type}_address_1"]) && $fields[$type][$type.'_address_1']['required'] = false;
        isset($fields[$type]["{$type}_address_2"]) && $fields[$type][$type.'_address_2']['required'] = false;
        isset($fields[$type]["{$type}_postcode"]) && $fields[$type][$type.'_postcode']['required'] = false;
        
        /*unset($fields[$type]["{$type}_state"]);
        unset($fields[$type]["{$type}_city"]);
        unset($fields[$type]["{$type}_address_1"]);
        unset($fields[$type]["{$type}_address_2"]);
        unset($fields[$type]["{$type}_postcode"]);*/
    }

    public function checkoutProcess()
    {
        if (self::isMeestShipping()) {
            $type = self::shipToDifferentAddress();

            $request = new Request($_POST);
            $address = AddressCustomerResource::make($request->all(), $type);

            $this->customer->setMeta("{$type}_meest_address", $address);

            if($address['country']['code'] === "UA") {
                if($address['delivery_type'] === "branch") {
                    $meest_fields = [
                        'billing_city_id'    => pll__('City'),
                        'billing_branch_id'  => pll__('Branch'),
                    ];
                } else {
                    $meest_fields = [
                        'billing_city_id'    => pll__('City'),
                        'billing_street_id'  => pll__('Street'),
                        'billing_building'   => pll__('Building'),
                    ];
                }
            } else {
                $meest_fields = [
                    'billing_region_text'  => pll__('Region'),
                    'billing_city_text'    => pll__('City'),
                    'billing_street_text'  => pll__('Street'),
                    'billing_building'     => pll__('Building'),
                    'billing_postcode'     => pll__('Postcode'),
                ];
            }
        
            foreach ($meest_fields as $field_key => $label) {
                if (empty($_POST[$field_key])) {
                    wc_add_notice( sprintf( __( '%s is a required field.', 'woocommerce' ), $label ), 'error', array( 'id' => $field_key ) );
                }
            }
        }
    }

    public function getAddress($type): array
    {
        $address = $this->customer->getMeta("{$type}_meest_address", null);

        if (empty($address['country'])) {
            $address['country'] = $this->options['address']['country'];
        }

        return [
            'delivery_type' => $address['delivery_type'] ?? 'branch',
            'country' => [
                'code' => $address['country']['code'] ?? null,
                'id' => $address['country']['id'] ?? null,
                'text' => $address['country']['text'] ?? null,
            ],
            'city' => [
                'id' => $address['city']['id'] ?? null,
                'text' => $address['city']['text'] ?? null,
            ],
            'region' => [
                'text' => $address['region']['text'] ?? null,
            ],
            'street' => [
                'id' => $address['street']['id'] ?? null,
                'text' => $address['street']['text'] ?? null,
            ],
            'building' => $address['building'] ?? null,
            'flat' => $address['flat'] ?? null,
            'postcode' => $address['postcode'] ?? null,
            'branch' => [
                'id' => $address['branch']['id'] ?? null,
                'text' => $address['branch']['text'] ?? null,
            ]
        ];
    }
}
