<?php
use MeestShipping\Core\View;
use MeestShipping\Helpers\Html;
use MeestShipping\Core\Error;
?>
<div class="container">
    <div class="row">
        <img class="page-container-logo" src="<?php echo MEEST_PLUGIN_URL.'public\img\icon_big.png' ?>" alt="">
        <h1><?php _e('Settings', MEEST_PLUGIN_DOMAIN) ?></h1>
        <?php Error::show(); ?>
        <hr class="wp-header-end">
        <div class="setting-grid">
            <div class="w75">
                <div class="content">
                    <form method="post">
                        <?php wp_nonce_field(MEEST_PLUGIN_DOMAIN) ?>
                        <input type="hidden" name="action" value="update">
                        <div class="table-container">
                            <h3 class="table-container-title"><?php _e('API', MEEST_PLUGIN_DOMAIN) ?></h3>
                            <div class="table-grid">
                                <table class="form-table">
                                    <tbody>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Url', MEEST_PLUGIN_DOMAIN) ?> <abbr class="required" title="required">*</abbr></label>
                                        </th>
                                        <td colspan="3">
                                            <input
                                                    type="text"
                                                    name="option[url]"
                                                    value="<?php echo esc_attr($options['url']) ?>"
                                            >
                                        </td>
                                    </tr>
                                    <tr id="meest_access_token" <?= empty($options['credential']['username']) ? '' : 'hidden' ?>>
                                        <th scope="row">
                                            <label>
                                                <select class="meest_access_type">
                                                    <option value="token" selected><?php _e('Token', MEEST_PLUGIN_DOMAIN) ?></option>
                                                    <option value="username"><?php _e('Username', MEEST_PLUGIN_DOMAIN) ?></option>
                                                </select>
                                                <abbr class="required" title="required">*</abbr>
                                            </label>
                                        </th>
                                        <td colspan="3">
                                            <input
                                                    type="password"
                                                    name="option[credential][token]"
                                                    value="<?php echo esc_attr($options['credential']['token']) ?>"
                                            >
                                        </td>
                                    </tr>
                                    <tr id="meest_access_username" <?= empty($options['credential']['username']) ? 'hidden' : '' ?>>
                                        <th scope="row">
                                            <label>
                                                <select class="meest_access_type">
                                                    <option value="token"><?php _e('Token', MEEST_PLUGIN_DOMAIN) ?></option>
                                                    <option value="username" selected><?php _e('Username', MEEST_PLUGIN_DOMAIN) ?></option>
                                                </select>
                                                <abbr class="required" title="required">*</abbr>
                                            </label>
                                        </th>
                                        <td>
                                            <input
                                                    type="text"
                                                    name="option[credential][username]"
                                                    value="<?php echo esc_attr($options['credential']['username']) ?>"
                                            >
                                        </td>
                                        <th scope="row">
                                            <label><?php _e('Password', MEEST_PLUGIN_DOMAIN) ?> <abbr class="required" title="required">*</abbr></label>
                                        </th>
                                        <td>
                                            <input
                                                    type="password"
                                                    name="option[credential][password]"
                                                    value="<?php echo esc_attr($options['credential']['password']) ?>"
                                            >
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row" colspan="4">
                                            <p class="hint"><?php _e('If you do not have an API key, you can get it by following the link', MEEST_PLUGIN_DOMAIN) ?> <a target="_blank" href="https://wiki.meest-group.com/api/ua/v3.0/openAPI">openAPI</a></p>
                                        </th>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Address data get from DB', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <?php echo Html::checkbox(
                                                'meest_dictionary_is_db',
                                                'option[dictionary][is_db]',
                                                $options['dictionary']['is_db']
                                            ) ?>
                                        </td>
                                    </tr>
                                    <?php if ($options['dictionary']['is_db']): ?>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Auto update dictionary', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <?php echo Html::checkbox(
                                                'meest_dictionary_auto_update',
                                                'option[dictionary][auto_update]',
                                                $options['dictionary']['auto_update']
                                            ) ?>
                                        </td>
                                        <th scope="row">
                                            <label><?php _e('Manual update dictionary', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <button class="wpbtn button button-warning" id="meest_dictionary_manual_update"><?= __('Update', MEEST_PLUGIN_DOMAIN) ?></button>
                                        </td>
                                    </tr>
                                    <?php endif ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php if (!empty($options['tokens'])) : ?>
                        <div class="table-container">
                            <h3 class="table-container-title"><?php _e('Agent', MEEST_PLUGIN_DOMAIN) ?></h3>
                            <div class="table-grid">
                                <table class="form-table">
                                    <tbody>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('First name', MEEST_PLUGIN_DOMAIN) ?> <abbr class="required" title="required">*</abbr></label>
                                        </th>
                                        <td>
                                            <input
                                                    id="meest_contact_first_name"
                                                    type="text"
                                                    name="option[contact][first_name]"
                                                    value="<?php echo esc_attr($options['contact']['first_name'] ?? null) ?>"
                                            >
                                        </td>
                                    </tr>
                                    <tr id="meest_access_token" <?= empty($options['credential']['username']) ? '' : 'hidden' ?>>
                                        <th scope="row">
                                            <label>
                                                <select class="meest_access_type">
                                                    <option value="token" selected><?php _e('Token', MEEST_PLUGIN_DOMAIN) ?></option>
                                                    <option value="username"><?php _e('Username', MEEST_PLUGIN_DOMAIN) ?></option>
                                                </select>
                                                <abbr class="required" title="required">*</abbr>
                                            </label>
                                        </th>
                                        <td colspan="3">
                                            <input
                                                    type="password"
                                                    name="option[credential][token]"
                                                    value="<?php echo esc_attr($options['credential']['token']) ?>"
                                            >
                                        </td>
                                    </tr>
                                    <tr id="meest_access_username" <?= empty($options['credential']['username']) ? 'hidden' : '' ?>>
                                        <th scope="row">
                                            <label><?php _e('Last name', MEEST_PLUGIN_DOMAIN) ?> <abbr class="required" title="required">*</abbr></label>
                                        </th>
                                        <td>
                                            <input
                                                    id="meest_contact_last_name"
                                                    type="text"
                                                    name="option[contact][last_name]"
                                                    value="<?php echo esc_attr($options['contact']['last_name'] ?? null) ?>"
                                            >
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Middle name', MEEST_PLUGIN_DOMAIN) ?> <abbr class="required" title="required">*</abbr></label>
                                        </th>
                                        <td>
                                            <input
                                                    id="meest_contact_middle_name"
                                                    type="text"
                                                    name="option[contact][middle_name]"
                                                    value="<?php echo esc_attr($options['contact']['middle_name'] ?? null) ?>"
                                            >
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Mobile phone', MEEST_PLUGIN_DOMAIN) ?> <abbr class="required" title="required">*</abbr></label>
                                        </th>
                                        <td>
                                            <input
                                                    id="meest_contact_phone"
                                                    type="text"
                                                    name="option[contact][phone]"
                                                    value="<?php echo esc_attr($options['contact']['phone'] ?? null) ?>"
                                                    placeholder="+XXXXXXXXXXX"
                                            >
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="table-container">
                            <h3 class="table-container-title"><?php _e('Address', MEEST_PLUGIN_DOMAIN) ?></h3>
                            <div class="table-grid">
                                <table class="form-table">
                                    <tbody>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Country', MEEST_PLUGIN_DOMAIN) ?> <abbr class="required" title="required">*</abbr></label>
                                        </th>
                                        <td>
                                            <select
                                                    id="meest_address_country_id"
                                                    name="option[address][country][id]"
                                                    value="<?php echo esc_attr($options['address']['country']['id']) ?>"
                                                    data-placeholder="<?php _e('Select a country', MEEST_PLUGIN_DOMAIN) ?>"
                                            >
                                                <option value="<?php echo esc_attr($options['address']['country']['id']) ?>"><?php echo esc_attr($options['address']['country']['text']) ?></option>
                                            </select>
                                            <input
                                                    id="meest_address_country_text"
                                                    type="hidden"
                                                    name="option[address][country][text]"
                                                    value="<?php echo esc_attr($options['address']['country']['text']) ?>"
                                            >
                                            <input
                                                    id="meest_address_country_code"
                                                    type="hidden"
                                                    name="option[address][country][code]"
                                                    value="<?php echo esc_attr($options['address']['country']['code']) ?>"
                                            >
                                            <p class="hint"><?php _e('Select from the list', MEEST_PLUGIN_DOMAIN) ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Region', MEEST_PLUGIN_DOMAIN) ?> <abbr class="required" title="required">*</abbr></label>
                                        </th>
                                        <td>
                                            <input
                                                    id="meest_address_region_text"
                                                    type="text"
                                                    name="option[address][region][text]"
                                                    value="<?php echo esc_attr($options['address']['region']['text']) ?>"
                                            >
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('City', MEEST_PLUGIN_DOMAIN) ?> <abbr class="required" title="required">*</abbr></label>
                                        </th>
                                        <td>
                                            <input
                                                    id="meest_address_city_text"
                                                    type="text"
                                                    name="option[address][city][text]"
                                                    value="<?php echo esc_attr($options['address']['city']['text']) ?>"
                                                    style="display: none"
                                            >
                                            <select
                                                    id="meest_address_city_id"
                                                    name="option[address][city][id]"
                                                    value="<?php echo esc_attr($options['address']['city']['id']) ?>"
                                                    data-placeholder="<?php _e('Select a city', MEEST_PLUGIN_DOMAIN) ?>"
                                            >
                                                <option value="<?php echo esc_attr($options['address']['city']['id']) ?>"><?php echo esc_attr($options['address']['city']['text']) ?></option>
                                            </select>
                                            <p class="hint"><?php _e('Select from the list', MEEST_PLUGIN_DOMAIN) ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Delivery type', MEEST_PLUGIN_DOMAIN) ?> <abbr class="required" title="required">*</abbr></label>
                                        </th>
                                        <td class="table-radio">
                                            <?php echo Html::radioInput(
                                                'meest_address_branch_delivery',
                                                'option[address][delivery_type]',
                                                __('Branch delivery', MEEST_PLUGIN_DOMAIN),
                                                'branch',
                                                $options['address']['delivery_type'] === 'branch' ? 'checked' : null
                                            ) ?>
                                            <?php echo Html::radioInput(
                                                'meest_address_address_delivery',
                                                'option[address][delivery_type]',
                                                __('Address delivery', MEEST_PLUGIN_DOMAIN),
                                                'address',
                                                $options['address']['delivery_type'] === 'address' ? 'checked' : null
                                            ) ?>
                                        </td>
                                    </tr>
                                    <tr <?php echo $options['address']['delivery_type'] === 'branch' ? 'hidden' : null ?>>
                                        <th scope="row">
                                            <label><?php _e('Address', MEEST_PLUGIN_DOMAIN) ?> <abbr class="required" title="required">*</abbr></label>
                                        </th>
                                        <td style="grid-template-columns: 8fr 2fr 2fr 2fr; display: grid; grid-gap: 6px;">
                                            <div>
                                                <input
                                                        id="meest_address_street_text"
                                                        type="text"
                                                        name="option[address][street][text]"
                                                        value="<?php echo esc_attr($options['address']['street']['text']) ?>"
                                                        style="display: none"
                                                >
                                                <select
                                                        id="meest_address_street_id"
                                                        name="option[address][street][id]"
                                                        value="<?php echo esc_attr($options['address']['street']['id']) ?>"
                                                        data-placeholder="<?php _e('Select a street', MEEST_PLUGIN_DOMAIN) ?>"
                                                >
                                                    <option value="<?php echo esc_attr($options['address']['street']['id']) ?>"><?php echo esc_attr($options['address']['street']['text']) ?></option>
                                                </select>
                                                <p class="hint"><?php //_e('Select from the list', MEEST_PLUGIN_DOMAIN) ?></p>
                                            </div>
                                            <div>
                                                <input
                                                        id="meest_address_building"
                                                        type="text"
                                                        name="option[address][building]"
                                                        value="<?php echo esc_attr($options['address']['building']) ?>"
                                                        placeholder="<?php _e('Building', MEEST_PLUGIN_DOMAIN) ?>"
                                                >
                                            </div>
                                            <div>
                                                <input
                                                        id="meest_address_flat"
                                                        type="text"
                                                        name="option[address][flat]"
                                                        value="<?php echo esc_attr($options['address']['flat']) ?>"
                                                        placeholder="<?php _e('Flat') ?>"
                                                >
                                            </div>
                                            <div>
                                                <input
                                                        id="meest_address_postcode"
                                                        type="text"
                                                        name="option[address][postcode]"
                                                        value="<?php echo esc_attr($options['address']['postcode']) ?>"
                                                        placeholder="<?php _e('Post code') ?>"
                                                >
                                            </div>
                                        </td>
                                    </tr>
                                    <tr <?php echo $options['address']['delivery_type'] === 'address' ? 'hidden' : null ?>>
                                        <th scope="row">
                                            <label><?php _e('Branch', MEEST_PLUGIN_DOMAIN) ?> <abbr class="required" title="required">*</abbr></label>
                                        </th>
                                        <td>
                                            <input
                                                    id="meest_address_branch_text"
                                                    type="hidden"
                                                    name="option[address][branch][text]"
                                                    value="<?php echo esc_attr($options['address']['branch']['text']) ?>"
                                            >
                                            <select
                                                    id="meest_address_branch_id"
                                                    name="option[address][branch][id]"
                                                    value="<?php echo esc_attr($options['address']['branch']['id']) ?>"
                                                    data-placeholder="<?php _e('Select a branch') ?>"
                                            >
                                                <option value="<?php echo esc_attr($options['address']['branch']['id']) ?>"><?php echo esc_attr($options['address']['branch']['text']) ?></option>
                                            </select>
                                            <p class="hint"><?php _e('Select from the list', MEEST_PLUGIN_DOMAIN) ?></p>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="table-container">
                            <h3 class="table-container-title"><?php _e('Shipping', MEEST_PLUGIN_DOMAIN) ?></h3>
                            <div class="table-grid">
                                <table class="form-table">
                                    <tbody>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Delivery type', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <?php echo Html::select(
                                                'meest_shipping_delivery_type',
                                                'option[shipping][delivery_type]',
                                                $options['shipping']['delivery_type'],
                                                [
                                                    null => __('All', MEEST_PLUGIN_DOMAIN),
                                                    'branch' => __('Branch', MEEST_PLUGIN_DOMAIN),
                                                    'address' => __('Address', MEEST_PLUGIN_DOMAIN),
                                                ]
                                            ) ?>
                                            <p class="hint"><?php _e('If select All, the delivery type will be displayed', MEEST_PLUGIN_DOMAIN) ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Calculate shipping cost', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <?php echo Html::checkbox(
                                                'meest_shipping_calc_cost',
                                                'option[shipping][calc_cost]',
                                                $options['shipping']['calc_cost']
                                            ) ?>
                                            <p class="hint"><?php _e('If checked, the delivery cost will be displayed', MEEST_PLUGIN_DOMAIN) ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Fixed shipping cost', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <input
                                                    id="meest_shipping_fixed_cost"
                                                    type="text"
                                                    name="option[shipping][fixed_cost]"
                                                    value="<?php echo esc_attr($options['shipping']['fixed_cost']) ?>"
                                            >
                                            <p class="hint"><?php _e('If the value is empty, the cost is calculated automatically', MEEST_PLUGIN_DOMAIN) ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('СOD', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <?php echo Html::checkbox(
                                                'meest_shipping_auto_cod',
                                                'option[shipping][auto_cod]',
                                                $options['shipping']['auto_cod']
                                            ) ?>
                                            <p class="hint"><?php _e('If checked, СOD will be set by price of items', MEEST_PLUGIN_DOMAIN) ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Show limits for branches', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <?php echo Html::checkbox(
                                                'meest_shipping_branch_limits',
                                                'option[shipping][branch_limits]',
                                                $options['shipping']['branch_limits']
                                            ) ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Send email after change order status ”Shipped”', MEEST_PLUGIN_DOMAIN) ?></label>
                                        </th>
                                        <td>
                                            <?php echo Html::checkbox(
                                                'meest_shipping_send_email',
                                                'option[shipping][send_email]',
                                                $options['shipping']['send_email']
                                            ) ?>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>
                        <p class="submit">
                            <button type="submit" name="submit" class="button button-primary button-large" value="Save changes"><?php _e('Save') ?></button>
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
