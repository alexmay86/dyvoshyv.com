<?php

namespace MeestShipping\Traits;

use MeestShipping\Core\View;

trait Email
{
    public function sendMailByOrder($order, $parcel)
    {
        $orderData = $order->get_data();

        if (!empty($orderData['billing']['email'])) {
            $to = $orderData['billing']['email'];
            $link = $this->options['tracking_url'].$parcel->barcode;
            $subject = sprintf(__('Order #%s was shipped!', MEEST_PLUGIN_DOMAIN), $parcel->order_id);
            $message = View::part('emails/parcel', [
                'order' => $order,
                'parcel' => $parcel,
                'subject' => $subject,
                'link' => $link,
            ]);
            $headers = ['Content-Type: text/html; charset=UTF-8'];

            return wc_mail(
                $to,
                apply_filters('meest_email_subject', $subject, $parcel, $order),
                apply_filters('meest_email_message', $message, $parcel, $order),
                apply_filters('meest_email_headers', $headers, $parcel, $order),
                apply_filters('meest_email_attachments', [], $parcel, $order)
            );
        };

        return false;
    }
}
