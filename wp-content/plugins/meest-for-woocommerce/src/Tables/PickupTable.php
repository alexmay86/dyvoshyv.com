<?php

namespace MeestShipping\Tables;

use MeestShipping\Core\Table;
use MeestShipping\Helpers\Html;
use MeestShipping\Models\Pickup;
use MeestShipping\Traits\Helper;
use MeestShipping\Controllers\PickupController;

class PickupTable extends Table
{
    use Helper;

    private $controller;

    public function __construct()
    {
        parent::__construct([
            'singular'  => 'pickup',
            'plural'    => 'pickups',
            'ajax'   => false,
            'screen' => 'pickup_table',
        ]);

        $this->controller = PickupController::instance();

        $this->process_bulk_action();
    }

    public function get_columns()
    {
        return [
            //'cb' => '<input type="checkbox" />',
            'register_number' => __('Number', MEEST_PLUGIN_DOMAIN),
            'sender' => __('Sender', MEEST_PLUGIN_DOMAIN),
            'receiver_pay' => __('Payer', MEEST_PLUGIN_DOMAIN),
            'pay_type' => __('Pay type', MEEST_PLUGIN_DOMAIN),
            'register_date' => __('Date', MEEST_PLUGIN_DOMAIN),
            'expected_time_from' => __('Time', MEEST_PLUGIN_DOMAIN),
            'created_at' => __('Created at', MEEST_PLUGIN_DOMAIN),
        ];
    }

    public function get_sortable_columns()
    {
        return [
            'register_number' => ['register_number', false],
            'created_at' => ['created_at', false],
            //'updated_at' => ['updated_at', false],
        ];
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'sender':
                if ($item[$column_name] !== null) {
                    return $this->getUser($item[$column_name]);
                }
                break;
            case 'expected_time_from':
                return $item['expected_time_from'].' - '.$item['expected_time_to'];
            case 'created_at':
            case 'updated_at':
                return str_replace(' ', '<br>', $item[$column_name]);
            case 'receiver_pay':
                return $item['pay_type'] == 1 ? __('Receiver', MEEST_PLUGIN_DOMAIN) : __('Sender', MEEST_PLUGIN_DOMAIN);
            case 'pay_type':
                return $item['pay_type'] == 1 ? __('Cash', MEEST_PLUGIN_DOMAIN) : __('Non cash', MEEST_PLUGIN_DOMAIN);
            case 'register_number':
            case 'register_date':
                return $item[$column_name];
            default:
                return print_r($item, true);
        }
    }

    public function column_register_number($item)
    {
        $page = sanitize_text_field($_REQUEST['page']);
        $actions = [
            'edit' => Html::link(__('Edit', MEEST_PLUGIN_DOMAIN), $page, 'update', $item['id']),
            'delete' => Html::postLink(__('Delete', MEEST_PLUGIN_DOMAIN), $page, 'delete', $item['id'])
        ];

        return sprintf(
            '%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            $item['register_number'],
            $item['id'],
            $this->row_actions($actions)
        );
    }

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

        $total_items = Pickup::total($search);
        $this->items = Pickup::page($search, $orderby.' '.$order, $current_page, $per_page);

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
            case 'remove':
                $this->controller->deleteParcel();
                break;
        }
    }
}
