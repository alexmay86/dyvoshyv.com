<?php

namespace MeestShipping\Core;

class MeestShippingMethod extends \WC_Shipping_Method
{
    /**
     * Constructor for your shipping class
     *
     * @access public
     * @param int $instance_id
     */
    public function __construct($instance_id = 0)
    {
        $this->instance_id = absint($instance_id);
        parent::__construct($instance_id);

        $this->id = MEEST_PLUGIN_NAME;
        $this->method_title = __('Delivery by Meest', MEEST_PLUGIN_DOMAIN);
        $this->method_description = $this->get_description();
        $this->supports = [
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        ];

        $this->init();

        $this->enabled = true;
        $this->title = __('Delivery by Meest', MEEST_PLUGIN_DOMAIN);
    }

    /**
     * Init your settings
     *
     * @access publicv
     * @return void
     */
    private function init()
    {
        $this->init_form_fields();
        $this->init_settings();

        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
    }

    /**
     * Define settings field for this shipping
     * @return void
     */
    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => array(
                'title' => __('Enable', MEEST_PLUGIN_DOMAIN),
                'type' => 'checkbox',
                'description' => __('Enable this shipping.', MEEST_PLUGIN_DOMAIN),
                'default' => 'yes'
            ),
            'title' => [
                'title' => __('Meest Shipping', MEEST_PLUGIN_DOMAIN),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', MEEST_PLUGIN_DOMAIN),
                'default' => __('Meest Shipping', MEEST_PLUGIN_DOMAIN)
            ],
            'settings' => [
                'title' => null,
                'type' => 'hidden',
                'description' => __('Other setting find available for <a href="admin.php?page=meest_setting">link</a>.', MEEST_PLUGIN_DOMAIN),
                'default' => __(' ', MEEST_PLUGIN_DOMAIN)
            ],
        ];
    }

    /**
     * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
     *
     * @access public
     * @param array $package
     * @return void
     */
    public function calculate_shipping($package = [])
    {
        $this->add_rate([
            'label' => $this->title,
            'cost' => 0,
            'package' => $package,
        ]);
    }

    /**
     * @return string
     */
    private function get_description()
    {
        $descriptions = [];
        $descriptions[] = __('Shipping with popular Ukrainian logistic company Meest Group', MEEST_PLUGIN_DOMAIN);

        return implode('<br>', $descriptions);
    }
}
