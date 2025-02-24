<?php

use MeestShipping\Core\View;
use MeestShipping\Helpers\Html;

$link = 'admin.php?page=meest_parcel&action='.(is_null($parcel->id) ? 'create&post='.$parcel->order_id : 'update&id='.$parcel->id);
?>
<div class="container">
    <div class="row">
        <img class="page-container-logo" src="<?php echo MEEST_PLUGIN_URL.'public\img\icon_big.png' ?>">
        <?php if (empty($parcel->id)) : ?>
            <?php if (empty($parcel->order_id)) : ?>
                <h1><?php _e('Create parcel', MEEST_PLUGIN_DOMAIN) ?></h1>
            <?php else : ?>
                <h1><?php _e('Create parcel for order #', MEEST_PLUGIN_DOMAIN) ?><?php echo $parcel->order_id ?></h1>
            <?php endif; ?>
        <?php else : ?>
            <?php if (empty($parcel->order_id)) : ?>
                <h1><?php echo sprintf(__('Edit parcel #%s', MEEST_PLUGIN_DOMAIN), $parcel->barcode) ?></h1>
            <?php else : ?>
                <h1><?php echo sprintf(__('Edit parcel #%s for order #%s', MEEST_PLUGIN_DOMAIN), $parcel->barcode, $parcel->order_id) ?></h1>
            <?php endif; ?>
        <?php endif; ?>
        <?php settings_errors(); ?>
        <hr class="wp-header-end">
        <div class="parcel-grid">
            <div class="w75">
                <div class="content">
                    <form method="post" action="<?php echo $link ?>">
                        <?php wp_nonce_field(MEEST_PLUGIN_DOMAIN) ?>
                        <input type="hidden" name="action" value="<?php echo is_null($parcel->id) ? 'create' : 'update' ?>">
                        <div class="table-container">
                            <h3 class="table-container-title"><?php _e('Parcel options', MEEST_PLUGIN_DOMAIN) ?></h3>
                            <div class="table-grid grid-2">
                                <table class="form-table">
                                    <tbody>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Insurance', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="parcel[insurance]" value="<?php echo esc_attr($parcel->insurance) ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('COD', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="parcel[cod]" value="<?php echo esc_attr($parcel->cod ?? null) ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Payer', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <select id="meest_parcel_payer" name="parcel[payer]">
                                                <option value="0" <?php echo $parcel->receiver_pay == 0 ? 'selected' : '' ?>><?php _e('Sender') ?></option>
                                                <option value="1" <?php echo $parcel->receiver_pay == 1 ? 'selected' : '' ?>><?php _e('Receiver') ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Pay type', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <select id="meest_parcel_pay_type" name="parcel[pay_type]">
                                                <option value="0" <?php echo $parcel->pay_type == 0 ? 'selected' : '' ?>><?php _e('Non cash') ?></option>
                                                <option value="1" <?php echo $parcel->pay_type == 1 ? 'selected' : '' ?>><?php _e('Cash') ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                                <table class="form-table left-border">
                                    <tbody>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Pack types', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <select id="meest_parcel_pack_type" name="parcel[pack_type]">
                                                <?php foreach ($packTypes as $packType): ?>
                                                    <option value="<?php echo $packType['id'] ?>" <?php echo $parcel->pack_type_id === $packType['id'] ? 'selected' : '' ?>><?php echo $packType['text'].": ({$packType['weight']['min']} - {$packType['weight']['max']} ".__('kg', MEEST_PLUGIN_DOMAIN).")" ?></option>
                                                <?php endforeach; ?>
                                                <option value="" <?php echo empty($parcel->pack_type_id) ? 'selected' : '' ?>><?php _e('Custom', MEEST_PLUGIN_DOMAIN) ?></option>
                                            </select>
                                            <p id="meest_parcel_lwh" style="grid-template-columns: 10fr 1fr 10fr 1fr 10fr; display: <?php echo empty($parcel->pack_type_id) ? 'grid' : 'none' ?>; grid-gap: 6px;">
                                                <span>
                                                    <input type="text" name="parcel[lwh][0]" value="<?php echo esc_attr($parcel->lwh[0] ?? 0) ?>">
                                                    <span style="margin-left:-26px;"><?php _e('cm', MEEST_PLUGIN_DOMAIN)?></span>
                                                </span>
                                                <span style="text-align: center;">x</span>
                                                <span>
                                                    <input type="text" name="parcel[lwh][1]" value="<?php echo esc_attr($parcel->lwh[1] ?? 0) ?>">
                                                    <span style="margin-left:-26px;"><?php _e('cm', MEEST_PLUGIN_DOMAIN)?></span>
                                                </span>
                                                <span style="text-align: center;">x</span>
                                                <span>
                                                    <input type="text" name="parcel[lwh][2]" value="<?php echo esc_attr($parcel->lwh[2] ?? 0) ?>">
                                                    <span style="margin-left:-26px;"><?php _e('cm', MEEST_PLUGIN_DOMAIN)?></span>
                                                </span>
                                            </p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Weight', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="parcel[weight]" value="<?php echo esc_attr($parcel->weight) ?>">
                                            <span style="margin-left:-22px;"><?php _e('kg', MEEST_PLUGIN_DOMAIN)?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Notation', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <textarea id="woocommerce_meest_notation" name="parcel[notation]"><?php echo $parcel->notation ?></textarea>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="table-container">
                            <h3 class="table-container-title"><?php _e('Sender', MEEST_PLUGIN_DOMAIN) ?></h3>
                            <div class="table-grid grid-2">
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
                                <table class="form-table left-border">
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
                                                    value="<?php echo esc_attr($sender->country['text'] ?? '') ?>"
                                            >
                                            <input
                                                    id="meest_sender_country"
                                                    type="hidden"
                                                    name="sender[country][code]"
                                                    value="<?php echo esc_attr($sender->country['code'] ?? '') ?>"
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
                                                    value="<?php echo esc_attr($sender->region['text'] ?? '') ?>"
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
                                                    value="<?php echo esc_attr($sender->city['id'] ?? '') ?>"
                                                    data-placeholder="<?php _e('Select a city', MEEST_PLUGIN_DOMAIN) ?>"
                                            >
                                                <option value="<?php echo esc_attr($sender->city['id'] ?? '') ?>"><?php echo esc_attr($sender->city['text'] ?? '') ?></option>
                                            </select>
                                            <input
                                                    id="meest_sender_city_text"
                                                    type="text"
                                                    name="sender[city][text]"
                                                    value="<?php echo esc_attr($sender->city['text'] ?? '') ?>"
                                                    style="display: none"
                                            >
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Delivery type', MEEST_PLUGIN_DOMAIN) ?> <span class="woocommerce-help-tip"></span></label>
                                        </th>
                                        <td class="table-radio">
                                            <?php echo Html::radioInput(
                                                'meest_sender_branch_delivery',
                                                'sender[delivery_type]',
                                                __('Branch', MEEST_PLUGIN_DOMAIN),
                                                'branch',
                                                $sender->delivery_type === 'branch' ? 'checked' : null
                                            ) ?>
                                            <?php echo Html::radioInput(
                                                'meest_sender_address_delivery',
                                                'sender[delivery_type]',
                                                __('Address', MEEST_PLUGIN_DOMAIN),
                                                'address',
                                                $sender->delivery_type === 'address' ? 'checked' : null
                                            ) ?>
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
                                                    value="<?php echo esc_attr($sender->street['id'] ?? '') ?>"
                                                    data-placeholder="<?php _e('Select a street') ?>"
                                            >
                                                <option value="<?php echo esc_attr($sender->street['id'] ?? '') ?>"><?php echo esc_attr($sender->street['text'] ?? '') ?></option>
                                            </select>
                                            <input
                                                    id="meest_sender_street_text"
                                                    type="text"
                                                    name="sender[street][text]"
                                                    value="<?php echo esc_attr($sender->street['text'] ?? '') ?>"
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
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Branch', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <select
                                                    id="meest_sender_branch_id"
                                                    name="sender[branch][id]"
                                                    value="<?php echo esc_attr($sender->branch['id'] ?? '') ?>"
                                                    data-placeholder="<?php _e('Select a branch', MEEST_PLUGIN_DOMAIN) ?>"
                                            >
                                                <option value="<?php echo esc_attr($sender->branch['id'] ?? '') ?>"><?php echo esc_attr($sender->branch['text'] ?? '') ?></option>
                                            </select>
                                            <input
                                                    id="meest_sender_branch_text"
                                                    type="hidden"
                                                    name="sender[branch][text]"
                                                    value="<?php echo esc_attr($sender->branch['text'] ?? '') ?>"
                                            >
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="table-container">
                            <h3 class="table-container-title"><?php _e('Receiver', MEEST_PLUGIN_DOMAIN) ?></h3>
                            <div class="table-grid grid-2">
                                <table class="form-table">
                                    <tbody>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Last name', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="receiver[last_name]" value="<?php echo esc_attr($receiver->last_name) ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('First name', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="receiver[first_name]" value="<?php echo esc_attr($receiver->first_name) ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Middle name', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="receiver[middle_name]" value="<?php echo esc_attr($receiver->middle_name) ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Phone', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="receiver[phone]" value="<?php echo esc_attr($receiver->phone) ?>">
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                                <table class="form-table left-border">
                                    <tbody>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Country', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <select
                                                    id="meest_receiver_country_id"
                                                    name="receiver[country][id]"
                                                    value="<?php echo esc_attr($receiver->country['id']) ?>"
                                                    class="input-text regular-input ui-autocomplete-input"
                                                    data-placeholder="<?php _e('Select a country', MEEST_PLUGIN_DOMAIN) ?>"
                                            >
                                                <option value="<?php echo esc_attr($receiver->country['id']) ?>"><?php echo esc_attr($receiver->country['text']) ?></option>
                                            </select>
                                            <input
                                                    id="meest_receiver_country_text"
                                                    type="hidden"
                                                    name="receiver[country][text]"
                                                    value="<?php echo esc_attr($receiver->country['text'] ?? '') ?>"
                                            >
                                            <input
                                                    id="meest_receiver_country"
                                                    type="hidden"
                                                    name="receiver[country][code]"
                                                    value="<?php echo esc_attr($receiver->country['code'] ?? '') ?>"
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
                                                    id="meest_receiver_region_text"
                                                    name="receiver[region][text]"
                                                    value="<?php echo esc_attr($receiver->region['text'] ?? '') ?>"
                                            >
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('City', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <select
                                                    id="meest_receiver_city_id"
                                                    name="receiver[city][id]"
                                                    value="<?php echo esc_attr($receiver->city['id'] ?? '') ?>"
                                                    class="input-text regular-input ui-autocomplete-input"
                                                    data-placeholder="<?php _e('Select a city', MEEST_PLUGIN_DOMAIN) ?>"
                                            >
                                                <option value="<?php echo esc_attr($receiver->city['id'] ?? '') ?>"><?php echo esc_attr($receiver->city['text'] ?? '') ?></option>
                                            </select>
                                            <input
                                                    id="meest_receiver_city_text"
                                                    type="text"
                                                    name="receiver[city][text]"
                                                    value="<?php echo esc_attr($receiver->city['text'] ?? '') ?>"
                                                    style="display: none"
                                            >
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Delivery type', MEEST_PLUGIN_DOMAIN) ?> <span class="woocommerce-help-tip"></span></label>
                                        </th>
                                        <td class="table-radio">
                                            <?php echo Html::radioInput(
                                                'meest_receiver_branch_delivery',
                                                'receiver[delivery_type]',
                                                __('Branch', MEEST_PLUGIN_DOMAIN),
                                                'branch',
                                                empty($receiver->delivery_type) || $receiver->delivery_type === 'branch' ? 'checked' : null
                                            ) ?>
                                            <?php echo Html::radioInput(
                                                'meest_receiver_address_delivery',
                                                'receiver[delivery_type]',
                                                __('Address', MEEST_PLUGIN_DOMAIN),
                                                'address',
                                                $receiver->delivery_type === 'address' ? 'checked' : null
                                            ) ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Street', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <select
                                                    id="meest_receiver_street_id"
                                                    name="receiver[street][id]"
                                                    value="<?php echo esc_attr($receiver->street['id'] ?? '') ?>"
                                                    data-placeholder="<?php _e('Select a street', MEEST_PLUGIN_DOMAIN) ?>"
                                            >
                                                <option value="<?php echo esc_attr($receiver->street['id'] ?? '') ?>"><?php echo esc_attr($receiver->street['text'] ?? '') ?></option>
                                            </select>
                                            <input
                                                    id="meest_receiver_street_text"
                                                    type="text"
                                                    name="receiver[street][text]"
                                                    value="<?php echo esc_attr($receiver->street['text'] ?? '') ?>"
                                                    style="display: none"
                                            >
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Building', MEEST_PLUGIN_DOMAIN) ?> / <?php _e('Flat', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <input id="meest_receiver_building" type="text" name="receiver[building]" value="<?php echo esc_attr($receiver->building) ?>" style="width: 48%">
                                            <input id="meest_receiver_flat" type="text" name="receiver[flat]" value="<?php echo esc_attr($receiver->flat) ?>" style="width: 48%; float: right;">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Post code', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <input id="meest_receiver_postcode" type="text" name="receiver[postcode]" value="<?php echo esc_attr($receiver->postcode) ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Branch', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <select
                                                    id="meest_receiver_branch_id"
                                                    name="receiver[branch][id]"
                                                    value="<?php echo esc_attr($receiver->branch['id'] ?? '') ?>"
                                                    data-placeholder="<?php _e('Select a branch', MEEST_PLUGIN_DOMAIN) ?>"
                                            >
                                                <option value="<?php echo esc_attr($receiver->branch['id'] ?? '') ?>"><?php echo esc_attr($receiver->branch['text'] ?? '') ?></option>
                                            </select>
                                            <input
                                                    id="meest_receiver_branch_text"
                                                    type="hidden"
                                                    name="receiver[branch][text]"
                                                    value="<?php echo esc_attr($receiver->branch['text'] ?? '') ?>"
                                            >
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <p class="submit">
                            <a class="button button-error button-large" href="?page=meest_parcel"><?php _e('Cancel') ?></a>
                            <?php if (is_null($parcel->id)) : ?>
                                <input type="submit" value="<?php _e('Create') ?>" class="button button-primary button-large">
                            <?php else : ?>
                                <input type="submit" value="<?php _e('Update') ?>" class="button button-primary button-large">
                            <?php endif; ?>
                            <?php if (!empty($parcel->updated_at)) : ?>
                            <span style="float: right;"><?php _e('Last updated at', MEEST_PLUGIN_DOMAIN).': '.$parcel->updated_at ?></span>
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
