<h2><?php _e('Address details', MEEST_PLUGIN_DOMAIN) ?></h2>
<table class="woocommerce-table shop_table gift_info">
    <tbody>
    <tr>
        <th><?php _e('Country', MEEST_PLUGIN_DOMAIN) ?>:</th>
        <td><?php echo esc_attr($address['country']['text']) ?></td>
    </tr>
    <?php if ($address['country']['id'] !== $options['country_id']['ua']): ?>
        <tr>
            <th><?php _e('Region', MEEST_PLUGIN_DOMAIN) ?>:</th>
            <td><?php echo esc_attr($address['region']['text']) ?></td>
        </tr>
    <?php endif; ?>
    <tr>
        <th><?php _e('City', MEEST_PLUGIN_DOMAIN) ?>:</th>
        <td><?php echo esc_attr($address['city']['text']) ?></td>
    </tr>
    <?php if ($address['delivery_type'] === 'branch'): ?>
        <tr>
            <th><?php _e('Branch', MEEST_PLUGIN_DOMAIN) ?>:</th>
            <td><?php echo esc_attr($address['branch']['text']) ?></td>
        </tr>
    <?php else: ?>
        <tr>
            <th><?php _e('Street', MEEST_PLUGIN_DOMAIN) ?>:</th>
            <td><?php echo esc_attr($address['street']['text']) ?></td>
        </tr>
        <tr>
            <th><?php _e('Building', MEEST_PLUGIN_DOMAIN) ?> / <?php _e('Flat', MEEST_PLUGIN_DOMAIN) ?>:</th>
            <td><?php echo esc_attr($address['building']) ?> / <?php echo esc_attr($address['flat']) ?></td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>