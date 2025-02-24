<?php

namespace MeestShipping\Tables;

use MeestShipping\Core\Table;
use MeestShipping\Helpers\Html;
use MeestShipping\Models\Parcel;
use MeestShipping\Traits\Helper;
use MeestShipping\Controllers\ParcelController;

class ParcelTable extends Table
{
    use Helper;

    private $controller;

    public function __construct()
    {
        parent::__construct([
            'singular'  => 'parcel',
            'plural'    => 'parcels',
            'ajax'   => false,
            'screen' => 'parcel_table',
        ]);

        $this->controller = ParcelController::instance();

        $this->process_bulk_action();
    }

    public function get_columns()
    {
        return [
            //'cb' => '<input type="checkbox" />',
            'barcode' => __('Barcode', MEEST_PLUGIN_DOMAIN),
            'pickup_id' => __('Pickup', MEEST_PLUGIN_DOMAIN),
            'order_id' => __('Order', MEEST_PLUGIN_DOMAIN),
            'sender' => __('Sender', MEEST_PLUGIN_DOMAIN),
            'receiver' => __('Receiver', MEEST_PLUGIN_DOMAIN),
            'receiver_pay' => __('Payer', MEEST_PLUGIN_DOMAIN),
            'pay_type' => __('Pay type', MEEST_PLUGIN_DOMAIN),
            'cod' => __('COD', MEEST_PLUGIN_DOMAIN),
            'cost_services' => __('Cost services', MEEST_PLUGIN_DOMAIN),
            'delivery_date' => __('Delivery date', MEEST_PLUGIN_DOMAIN),
            'created_at' => __('Created at', MEEST_PLUGIN_DOMAIN),
            //'updated_at' => __('Updated_at', MEEST_PLUGIN_DOMAIN)
        ];
    }

    public function get_sortable_columns()
    {
        return [
            'order_id' => ['order_id', false],
            'created_at' => ['created_at', false],
            //'updated_at' => ['updated_at', false],
        ];
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'pickup_id':
                if ($item[$column_name] !== null) {
                    return '<a href="admin.php?page=meest_pickup&action=update&id='.$item[$column_name].'">'.$item[$column_name].'</a>';
                }
                if (self::isPickup($item['id'])) {
                    return '✔';
                }
                break;
            case 'sender':
            case 'receiver':
                if ($item[$column_name] !== null) {
                    return $this->getUser($item[$column_name]);
                }
                break;
            case 'receiver_pay':
                return $item['pay_type'] == 1 ? __('Receiver', MEEST_PLUGIN_DOMAIN) : __('Sender', MEEST_PLUGIN_DOMAIN);
            case 'pay_type':
                return $item['pay_type'] == 1 ? __('Cash', MEEST_PLUGIN_DOMAIN) : __('Non cash', MEEST_PLUGIN_DOMAIN);
            case 'created_at':
            case 'updated_at':
                return str_replace(' ', '<br>', $item[$column_name]);
            case 'order_id':
                return '<a href="post.php?post='.$item[$column_name].'&action=edit">'.$item[$column_name].'</a>';
            case 'barcode':
            case 'cod':
            case 'cost_services':
            case 'delivery_date':
                return $item[$column_name];
            default:
                return print_r($item, true);
        }
    }

    public function column_barcode($item)
    {
        $page = sanitize_text_field($_REQUEST['page']);
        $declarationUrl = meest_init('Api')->getUrlDeclaration($item['parcel_id']);
        $sticker100Url = meest_init('Api')->getUrlSticker100($item['parcel_id']);

        $actions = [
            'declaration' => '<a href="'.$declarationUrl.'" target="_blank">'.__('Declaration', MEEST_PLUGIN_DOMAIN).'</a>',
            'sticker' => '<a href="'.$sticker100Url.'" target="_blank">'.__('Sticker', MEEST_PLUGIN_DOMAIN).'</a>',
            'tracking' => Html::link(__('Tracking', MEEST_PLUGIN_DOMAIN), $page, 'tracking', $item['id']),
        ];
        if (!empty($item['order_id']) && empty($item['is_email'])) {
            $actions['email'] = Html::postLink(__('Email', MEEST_PLUGIN_DOMAIN), $page, 'email', $item['id']);
        }
        if (empty($item['pickup_id']) && !self::isPickup($item['id'])) {
            $actions['pickup'] = Html::link(__('Pickup', MEEST_PLUGIN_DOMAIN), $page, 'pickup', $item['id']);
        } elseif (empty($item['pickup_id'])) {
            $actions['unpickup'] = Html::link(__('Unpickup', MEEST_PLUGIN_DOMAIN), $page, 'unpickup', $item['id']);
        }
        $actions['update'] = Html::link(__('Edit', MEEST_PLUGIN_DOMAIN), $page, 'update', $item['id']);
        $actions['delete'] = Html::postLink(__('Delete', MEEST_PLUGIN_DOMAIN), $page, 'delete', $item['id']);

        return sprintf(
            '%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            $item['barcode'],
            $item['id'],
            $this->row_actions($actions)
        );
    }

    /*public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'],
            $item['id']
        );
    }*/

    /*public function get_bulk_actions()
    {
        return [
            'delete'  => 'Delete'
        ];
    }*/

    public function prepare_items()
    {
        $per_page = 10;
        $current_page = $this->get_pagenum();
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $orderby = !empty($_GET['orderby']) ? sanitize_text_field($_GET["orderby"]) : 'id';
        $order = !empty($_GET['order']) ? sanitize_text_field($_GET["order"]) : 'DESC';
        $search = !empty($_GET['s']) ? sanitize_text_field($_GET['s']) : null;

        $total_items = Parcel::total($search);

        $this->items = Parcel::page($search, $orderby.' '.$order, $current_page, $per_page);

        $this->set_pagination_args([
            'total_items' => $total_items,
            'total_pages' => ceil($total_items / $per_page),
            'per_page' => $per_page,
        ]);
    }

    public function process_bulk_action()
    {
        switch ($this->current_action()) {
            case 'create':
                $this->controller->create();
                break;
            case 'update':
                $this->controller->update();
                break;
            case 'delete':
                $this->controller->delete();
                break;
            case 'email':
                $this->controller->email();
                break;
            case 'tracking':
                $this->controller->tracking();
                break;
            case 'pickup':
                $this->controller->pickup();
                break;
            case 'unpickup':
                $this->controller->unPickup();
                break;
        }
    }

    private static function isPickup($id): bool
    {
        return in_array($id, $_SESSION['meest_pickup_parcels'] ?? []) !== false;
    }
}
