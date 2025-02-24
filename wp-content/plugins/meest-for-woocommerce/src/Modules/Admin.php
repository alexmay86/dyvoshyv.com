<?php

namespace MeestShipping\Modules;

use MeestShipping\Contracts\Module;
use MeestShipping\Controllers\DefaultController;
use MeestShipping\Controllers\SettingController;
use MeestShipping\Controllers\ParcelController;
use MeestShipping\Controllers\PickupController;
use MeestShipping\Models\Parcel;
use MeestShipping\Traits\Email;
use MeestShipping\Traits\Helper;

class Admin implements Module
{
    use Helper, Email;

    private $options;
    private $page;
    private $pageNames = [];

    public function __construct()
    {
        $this->options = meest_init('Option')->all();
    }

    public function init()
    {
        if (is_admin()) {
            add_action('init', [$this, 'adminInit']);
            add_action('admin_menu', [$this, 'adminMenu']);
            add_action('admin_bar_menu', [$this, 'adminBarMenu'], 999);
            add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
            add_filter('manage_edit-shop_order_columns', [$this, 'shopOrderColumns'], 1);
            add_action('manage_shop_order_posts_custom_column', [$this, 'shopOrderCustomColumn'], 1);
            add_filter('wc_order_statuses', [$this, 'orderStatuses']);
            add_action('woocommerce_order_status_shipped', [$this, 'orderStatusShipped']);
        }
    }

    public function adminInit()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function adminBarMenu($wp_admin_bar)
    {
        $wp_admin_bar->add_node([
            'id' => 'meest-new-parcel',
            'parent' => 'new-content',
            'title' => __('Parcel', MEEST_PLUGIN_DOMAIN),
            'href' => 'admin.php?page=meest_parcel&action=create',
            'meta' => [
                'title' => __('Create new parcel by Meest', MEEST_PLUGIN_DOMAIN),
            ]
        ]);
    }

    public function adminMenu()
    {
        $this->page = [
            'page_title' => __('Meest', MEEST_PLUGIN_DOMAIN),
            'menu_title' => __('Meest', MEEST_PLUGIN_DOMAIN),
            'capability' => 'manage_woocommerce',
            'menu_slug' => 'meest_parcel',
            'callback' => [ParcelController::instance(), 'index'],
            'icon_url' => MEEST_PLUGIN_URL.'public/img/icon.png',
            'position' => 60,
            'items' => [
                [
                    'parent_slug' => 'meest_parcel',
                    'page_title' => __('Parcels', MEEST_PLUGIN_DOMAIN),
                    'menu_title' => __('Parcels', MEEST_PLUGIN_DOMAIN),
                    'capability' => 'manage_woocommerce',
                    'menu_slug' => 'meest_parcel',
                    'callback' => [ParcelController::instance(), 'index']
                ],
                [
                    'parent_slug' => 'meest_parcel',
                    'page_title' => __('Pickups', MEEST_PLUGIN_DOMAIN),
                    'menu_title' => __('Pickups', MEEST_PLUGIN_DOMAIN),
                    'capability' => 'manage_woocommerce',
                    'menu_slug' => 'meest_pickup',
                    'callback' => [PickupController::instance(), 'index']
                ],
                [
                    'parent_slug'   => 'meest_parcel',
                    'page_title'    => __('Settings', MEEST_PLUGIN_DOMAIN),
                    'menu_title'    => __('Settings', MEEST_PLUGIN_DOMAIN),
                    'capability'    => 'manage_woocommerce',
                    'menu_slug'     => 'meest_setting',
                    'callback'      => [SettingController::instance(), 'edit']
                ],
                [
                    'parent_slug'   => 'meest_parcel',
                    'page_title'    => __('About', MEEST_PLUGIN_DOMAIN),
                    'menu_title'    => __('About', MEEST_PLUGIN_DOMAIN),
                    'capability'    => 'manage_woocommerce',
                    'menu_slug'     => 'meest_about',
                    'callback'      => [DefaultController::instance(), 'about']
                ]
            ]
        ];

        add_menu_page($this->page['page_title'], $this->page['menu_title'], $this->page['capability'], $this->page['menu_slug'], $this->page['callback'], $this->page['icon_url'], $this->page['position']);

        foreach ($this->page['items'] as $subpage) {
            $this->pageNames[$subpage['menu_slug']] = add_submenu_page($subpage['parent_slug'], $subpage['page_title'], $subpage['menu_title'], $subpage['capability'], $subpage['menu_slug'], $subpage['callback']);
        }

        add_action('load-'.$this->pageNames['meest_setting'], [SettingController::instance(), 'update']);
    }

    public function addMetaBoxes()
    {
        if (isset($_GET["post"])) {
            add_meta_box('meest_create_parcel', __('Meest shipping', MEEST_PLUGIN_DOMAIN), [$this, 'addMetaBoxParcel'], 'shop_order', 'side', 'core');
            add_meta_box('meest_show_invoice', __('Meest Invoice', MEEST_PLUGIN_DOMAIN), [$this, 'addMetaBoxInvoice'], 'shop_order', 'side', 'core');
        }
    }

    public function addMetaBoxParcel()
    {
        if (isset($_GET['post'])) {
            $order = wc_get_order($_GET['post']);

            if ($order->has_shipping_method(MEEST_PLUGIN_NAME)) {
                $id = $order->get_id();
                $parcel = Parcel::find($id, 'order_id');
                if ($parcel === null) {
                    echo '<img src="'.MEEST_PLUGIN_URL.'public/img/icon.png" style="height: 25px;width: 25px; margin-right: 20px; margin-top: 2px;">';
                    echo '<a class="button button-primary send" href="admin.php?page=meest_parcel&action=create&post='.$order->get_id().'">'.__('Create parcel', MEEST_PLUGIN_DOMAIN).'</a>';
                    echo '<style>#invoice_other_fields{display:none;}</style>';

                    return true;
                }
            }

            echo '<style>#meest_create_parcel{display:none;}</style>';
        }
    }

    public function addMetaBoxInvoice()
    {
        if (isset($_GET['post'])) {
            $order = wc_get_order($_GET['post']);

            if ($order->has_shipping_method(MEEST_PLUGIN_NAME)) {
                $id = $order->get_id();
                $parcel = Parcel::find($id, 'order_id');

                if ($parcel !== null) {
                    $declarationUrl = meest_init('Api')->getUrlDeclaration($parcel->parcel_id);
                    $sticker100Url = meest_init('Api')->getUrlSticker100($parcel->parcel_id);

                    echo '<p>'.__('Invoice number', MEEST_PLUGIN_DOMAIN).': '.$parcel->barcode.'</p>';
                    echo '<a style="margin: 5px 5px 0 0;" href="'.$declarationUrl.'" class="button" target="_blank">'.__('Invoice', MEEST_PLUGIN_DOMAIN).'</a>';
                    echo '<a style="margin: 5px 5px 0 0;" href="'.$sticker100Url.'" class="button" target="_blank">'.__('Sticker', MEEST_PLUGIN_DOMAIN).'</a>';
                    echo '<a style="margin: 5px 5px 0 0;" href="admin.php?page=meest_parcel&action=tracking&id='.$parcel->id.'" class="button">'.__('Tracking', MEEST_PLUGIN_DOMAIN).'</a>';
                    echo '<a style="margin: 5px 5px 0 0;" href="" class="button" target="_blank">'.__('Send email', MEEST_PLUGIN_DOMAIN).'</a>';
                    echo '<a style="margin: 5px 5px 0 0;" href="admin.php?page=meest_parcel&action=edit&id='.$parcel->id.'" class="button">'.__('Edit', MEEST_PLUGIN_DOMAIN).'</a>';
                    echo '<style>#invoice_other_fields{display:none;}</style>';

                    return true;
                }
            }

            echo '<style>#meest_show_invoice{display:none;}</style>';
        }
    }

    public function shopOrderColumns($columns)
    {
        $columns['created_invoice'] = __('Invoice', MEEST_PLUGIN_DOMAIN);
        $columns['invoice_number'] = __('Invoice number', MEEST_PLUGIN_DOMAIN);

        return $columns;
    }

    public function shopOrderCustomColumn($column)
    {
        global $post;
        $order = wc_get_order($post->ID);

        if ($order->has_shipping_method(MEEST_PLUGIN_NAME)) {
            $parcel = Parcel::find($order->get_id(), 'order_id');

            if ($column === 'created_invoice') {
                if ($parcel !== null) {
                    $declarationUrl = meest_init('Api')->getUrlDeclaration($parcel->parcel_id);
                    echo '<a target="_blank" href="'.$declarationUrl.'"><img src="'.MEEST_PLUGIN_URL.'public\img\icon_big.png'.'"/ height="20px"></a>';
                } else {
                    echo '<img src="'.MEEST_PLUGIN_URL.'public\img\icon_big.png'.'"/ height="20px" style="filter: grayscale(100%)">';
                }
            }

            if ($column === 'invoice_number') {
                if ($parcel !== null) {
                    echo '<a href="admin.php?page=meest_parcel&action=update&id='.$parcel->id.'">'.$parcel->barcode.'</a>';
                } else {
                    echo '<a class="button button-primary send" href="admin.php?page=meest_parcel&action=create&post='.$order->get_id().'">'.__('Create parcel', MEEST_PLUGIN_DOMAIN).'</a>';
                }
            }
        }
    }

    public function orderStatuses($order_statuses)
    {
        $order_statuses['wc-shipped'] = __('Shipped', MEEST_PLUGIN_DOMAIN);

        return $order_statuses;
    }

    public function orderStatusShipped($order_id)
    {
        if ($this->options['shipping']['send_email'] == 1) {
            $order = wc_get_order($order_id);
            if ($order->has_shipping_method(MEEST_PLUGIN_NAME)) {
                $parcel = Parcel::find($order_id, 'order_id');

                if ($this->sendMailByOrder($order, $parcel)) {
                    $orderNote = sprintf(__('Sent email about parcel tracking', MEEST_PLUGIN_DOMAIN), $parcel->barcode);
                    $order->add_order_note($orderNote);
                }
            }
        }
    }
}
