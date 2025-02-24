<?php

use MeestShipping\Core\View;
use MeestShipping\Helpers\Html;

$link = 'admin.php?page=meest_pickup&action='.(is_null($pickup->id) ? 'create' : 'update&id='.$pickup->id);
?>
<div class="container">
    <div class="row">
        <img class="page-container-logo" src="<?php echo MEEST_PLUGIN_URL . 'public\img\icon_big.png' ?>">
        <?php if (is_null($pickup->id)) : ?>
            <h1><?php _e('Create pickup', MEEST_PLUGIN_DOMAIN) ?></h1>
        <?php else : ?>
            <h1><?php echo sprintf(__('Edit pickup #%s', MEEST_PLUGIN_DOMAIN), $pickup->register_number) ?></h1>
        <?php endif; ?>
        <?php settings_errors() ?>
        <hr class="wp-header-end">
        <div class="pickup-grid">
            <div class="w75">
                <div class="content">
                    <form method="post" action="<?php echo $link ?>">
                        <?php wp_nonce_field(MEEST_PLUGIN_DOMAIN) ?>
                        <input type="hidden" name="action" value="<?php echo is_null($pickup->id) ? 'create' : 'update' ?>">

                        <div class="table-grid grid-2">
                            <div class="table-container">
                                <h3 class="table-container-title"><?php _e('Sender', MEEST_PLUGIN_DOMAIN) ?></h3>
                                <table class="form-table">
                                    <tbody>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Last name', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="sender[last_name]" value="<?php echo esc_attr($sender->last_name) ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('First name', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="sender[first_name]" value="<?php echo esc_attr($sender->first_name) ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Middle name', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="sender[middle_name]" value="<?php echo esc_attr($sender->middle_name) ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Phone', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="sender[phone]" value="<?php echo esc_attr($sender->phone) ?>">
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                                <hr style="margin: 10px;">
                                <table class="form-table">
                                    <tbody>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Country', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <select
                                                    id="meest_sender_country_id"
                                                    name="sender[country][id]"
                                                    value="<?php echo esc_attr($sender->country['id']) ?>"
                                                    data-placeholder="<?php _e('Select a country', MEEST_PLUGIN_DOMAIN) ?>"
                                            >
                                                <option value="<?php echo esc_attr($sender->country['id']) ?>"><?php echo esc_attr($sender->country['text']) ?></option>
                                            </select>
                                            <input
                                                    id="meest_sender_country_text"
                                                    type="hidden"
                                                    name="sender[country][text]"
                                                    value="<?php echo esc_attr($sender->country['text']) ?>"
                                            >
                                            <input
                                                    id="meest_sender_country"
                                                    type="hidden"
                                                    name="sender[country][code]"
                                                    value="<?php echo esc_attr($sender->country['code'] ?? null) ?>"
                                            >
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Region', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <input
                                                    type="text"
                                                    id="meest_sender_region_text"
                                                    name="sender[region][text]"
                                                    value="<?php echo esc_attr($sender->region['text']) ?>"
                                            >
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('City', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <select
                                                    id="meest_sender_city_id"
                                                    name="sender[city][id]"
                                                    value="<?php echo esc_attr($sender->city['id']) ?>"
                                                    data-placeholder="<?php _e('Select a city', MEEST_PLUGIN_DOMAIN) ?>"
                                            >
                                                <option value="<?php echo esc_attr($sender->city['id']) ?>"><?php echo esc_attr($sender->city['text']) ?></option>
                                            </select>
                                            <input
                                                    id="meest_sender_city_text"
                                                    type="text"
                                                    name="sender[city][text]"
                                                    value="<?php echo esc_attr($sender->city['text']) ?>"
                                                    style="display: none"
                                            >
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Street', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <select
                                                    id="meest_sender_street_id"
                                                    name="sender[street][id]"
                                                    value="<?php echo esc_attr($sender->street['id']) ?>"
                                                    data-placeholder="<?php _e('Select a street', MEEST_PLUGIN_DOMAIN) ?>"
                                            >
                                                <option value="<?php echo esc_attr($sender->street['id']) ?>"><?php echo esc_attr($sender->street['text']) ?></option>
                                            </select>
                                            <input
                                                    id="meest_sender_street_text"
                                                    type="text"
                                                    name="sender[street][text]"
                                                    value="<?php echo esc_attr($sender->street['text']) ?>"
                                                    style="display: none"
                                            >
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Building', MEEST_PLUGIN_DOMAIN) ?> / <?php _e('Flat', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <input id="meest_sender_building" type="text" name="sender[building]" value="<?php echo esc_attr($sender->building) ?>" style="width: 48%">
                                            <input id="meest_sender_flat" type="text" name="sender[flat]" value="<?php echo esc_attr($sender->flat) ?>" style="width: 48%; float: right;">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Post code', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <input id="meest_sender_postcode" type="text" name="sender[postcode]" value="<?php echo esc_attr($sender->postcode) ?>">
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                                <table class="form-table">
                                    <h3 class="table-container-title"><?php _e('Pickup information', MEEST_PLUGIN_DOMAIN) ?></h3>
                                    <tbody>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Date', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <input
                                                    id="meest_pickup_expected_date"
                                                    type="text"
                                                    name="pickup[expected_date]"
                                                    value="<?php echo esc_attr($pickup->expected_date) ?>"
                                            >
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Time period', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <input id="meest_pickup_expected_time_from" type="text" name="pickup[expected_time_from]" value="<?php echo esc_attr($pickup->expected_time_from) ?>" style="width: 48%">
                                            <input id="meest_pickup_expected_time_to" type="text" name="pickup[expected_time_to]" value="<?php echo esc_attr($pickup->expected_time_to) ?>" style="width: 48%; float: right;">
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                                <hr style="margin: 10px;">
                                <table class="form-table full-width-input">
                                    <tbody>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Payer', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <select id="meest_pickup_payer" name="pickup[payer]">
                                                <option value="0" <?php echo $pickup->receiver_pay == 0 ? 'selected' : '' ?>><?php _e('Sender') ?></option>
                                                <option value="1" <?php echo $pickup->receiver_pay == 1 ? 'selected' : '' ?>><?php _e('Receiver') ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Pay type', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <select id="meest_pickup_pay_type" name="pickup[pay_type]">
                                                <option value="0" <?php echo $pickup->receiver_pay == 0 ? 'selected' : '' ?>><?php _e('Non cash') ?></option>
                                                <option value="1" <?php echo $pickup->receiver_pay == 1 ? 'selected' : '' ?>><?php _e('Cash') ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Notation', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <textarea
                                                    id="meest_pickup_notation"
                                                    name="pickup[notation]"
                                                    rows="2"
                                            ><?php echo esc_attr($pickup->notation) ?></textarea>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="table-container">
                                <h3 class="table-container-title"><?php _e('Parcels', MEEST_PLUGIN_DOMAIN) ?></h3>
                                <table class="wp-data-table widefat fixed striped table-view-list">
                                    <form hidden method="post"></form>
                                    <thead>
                                    <th><?php _e('ID', MEEST_PLUGIN_DOMAIN) ?></th>
                                    <th><?php _e('Order', MEEST_PLUGIN_DOMAIN) ?></th>
                                    <th><?php _e('Barcode', MEEST_PLUGIN_DOMAIN) ?></th>
                                    <th><?php _e('Date', MEEST_PLUGIN_DOMAIN) ?></th>
                                    <th></th>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($parcels as $parcel): ?>
                                        <tr>
                                            <td scope="row">
                                                <a href="admin.php?page=meest_parcel&action=edit&id=<?php echo esc_attr($parcel->id) ?>"><?php echo esc_attr($parcel->id) ?></a>
                                            </td>
                                            <td>
                                                <a href="post.php?post=<?php echo esc_attr($parcel->order_id) ?>&action=edit"><?php echo esc_attr($parcel->order_id) ?></a>
                                            </td>
                                            <td><?php echo esc_attr($parcel->barcode) ?></td>
                                            <td><?php echo esc_attr($parcel->created_at) ?></td>
                                            <td><?php echo Html::postLink(__('Delete', MEEST_PLUGIN_DOMAIN), 'meest_pickup', 'remove', $parcel->id) ?>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <p class="submit">
                            <a class="button button-error button-large" href="?page=meest_pickup"><?php _e('Cancel') ?></a>
                            <?php if (is_null($pickup->id)) : ?>
                                <input type="submit" value="<?php _e('Create') ?>" class="button button-primary button-large">
                            <?php else : ?>
                                <input type="submit" value="<?php _e('Update') ?>" class="button button-primary button-large">
                            <?php endif; ?>

                            <?php if (!empty($pickup->updated_at)) : ?>
                            <span style="float: right;"><?php _e('Last updated at', MEEST_PLUGIN_DOMAIN).': '.$pickup->updated_at ?></span>
                            <?php endif; ?>
                        </p>
                    </form>
                </div>
            </div>
            <div class="w25">
                <?php echo View::part('views/parts/support') ?>
            </div>
        </div>
    </div>
</div>
