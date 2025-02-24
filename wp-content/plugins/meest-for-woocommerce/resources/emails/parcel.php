<?php
if (!defined('ABSPATH')) {
    exit;
}

do_action('woocommerce_email_header', $subject);
?>

<p><?php echo sprintf(__('Hello %s.', MEEST_PLUGIN_DOMAIN), $order->get_billing_first_name()) ?></p>
<p><?php echo sprintf(__('Just to let you know - we`ve shipped your order #%s by Meest.', MEEST_PLUGIN_DOMAIN), $order->get_order_number()) ?></p>
<p><?php echo sprintf(__('Parcel number: <strong>%s</strong>.', MEEST_PLUGIN_DOMAIN), $parcel->barcode) ?></p>
<p><?php echo sprintf(__('You can tracking your order at: <a href="%s">%s</a>.', MEEST_PLUGIN_DOMAIN), $link, $link) ?></p>

<?php
do_action('woocommerce_email_order_details', $order);

do_action('woocommerce_email_order_meta', $order);

do_action('woocommerce_email_customer_details', $order);

do_action('woocommerce_email_footer');
