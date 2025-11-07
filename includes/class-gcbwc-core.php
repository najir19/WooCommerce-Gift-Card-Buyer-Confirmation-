<?php
if (! defined('ABSPATH')) exit;

/**
 * Detect gift-card orders, send buyer confirmation, instant recipient fallback,
 * and suppress duplicate default buyer emails.
 */
final class GCBWC_Core
{

    public function __construct()
    {
        add_action('woocommerce_thankyou', array($this, 'handle_thankyou'), 10, 1);
        add_filter('woocommerce_email_enabled_customer_on_hold_order', array($this, 'suppress_default_buyer_emails'), 10, 2);
        add_filter('woocommerce_email_enabled_customer_processing_order', array($this, 'suppress_default_buyer_emails'), 10, 2);
    }

    protected function logger()
    {
        static $logger = null;
        if (! $logger) $logger = wc_get_logger();
        return $logger;
    }

    public function handle_thankyou($order_id)
    {
        $order = wc_get_order($order_id);
        if (! $order) return;

        $gift_meta = array();
        if (! $this->order_has_giftcard($order, $gift_meta)) return;

        // 1) Buyer confirmation (custom Woo email).
        $mailer = WC()->mailer();
        if (isset($mailer->emails['GCBWC_Email_Buyer_Confirmation'])) {
            $mailer->emails['GCBWC_Email_Buyer_Confirmation']->trigger($order_id, $gift_meta);
            $this->logger()->info('Buyer confirmation sent for order ' . $order_id, array('source' => 'gcbwc'));
        }

        // 2) Instant recipient fallback (no cron dependency).
        foreach ($gift_meta as $g) {
            $to      = sanitize_email(isset($g['recipient_email']) ? $g['recipient_email'] : '');
            $from    = sanitize_text_field(isset($g['buyer_name']) ? $g['buyer_name'] : '');
            $message = sanitize_text_field(isset($g['message']) ? $g['message'] : '');
            $amount  = isset($g['amount']) ? $g['amount'] : '';

            if (! $to) continue;

            $store_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);

            $subject = sprintf(
                /* translators: %s: sender (buyer) name */
                __("You've received a Gift Card from %s!", 'gift-card-buyer-confirmation-for-wc'),
                $from ? $from : __('a friend', 'gift-card-buyer-confirmation-for-wc')
            );

            $body  = '<p>' . esc_html__('Hi there,', 'gift-card-buyer-confirmation-for-wc') . '</p>';
            $body .= '<p><strong>' . esc_html($from ? $from : __('A friend', 'gift-card-buyer-confirmation-for-wc')) . '</strong> ';
            $body .= esc_html__('has sent you a gift card worth', 'gift-card-buyer-confirmation-for-wc') . ' ' . wp_kses_post($amount) . ' ';
            $body .= esc_html__('from', 'gift-card-buyer-confirmation-for-wc') . ' <strong>' . esc_html($store_name) . '</strong>!</p>';
            if ('' !== $message) {
                $body .= '<p><em>' . esc_html__('Message:', 'gift-card-buyer-confirmation-for-wc') . '</em> ' . esc_html($message) . '</p>';
            }
            $body .= '<p>' . esc_html__('You can redeem your gift card during checkout on our website.', 'gift-card-buyer-confirmation-for-wc') . '</p>';
            $body .= '<p>' . esc_html__('Thank you,', 'gift-card-buyer-confirmation-for-wc') . '<br><strong>' . esc_html($store_name) . '</strong></p>';

            wp_mail($to, $subject, $body, array('Content-Type: text/html; charset=UTF-8'));
            $this->logger()->info('Sent instant fallback recipient email to ' . $to . ' for order ' . $order_id, array('source' => 'gcbwc'));
        }
    }

    public function suppress_default_buyer_emails($enabled, $order)
    {
        if (! $enabled) return false;
        if ($order instanceof WC_Order) {
            $tmp = array();
            if ($this->order_has_giftcard($order, $tmp)) return false;
        }
        return $enabled;
    }

    /**
     * Detect gift card line items (official Woo Gift Cards meta keys).
     * Populates $gift_meta_out with normalized fields.
     */
    public function order_has_giftcard($order, &$gift_meta_out = array())
    {
        $gift_meta_out = array();
        $has = false;

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (! $product) continue;

            $meta = array();
            foreach ($item->get_meta_data() as $m) {
                $meta[$m->key] = $m->value;
            }

            $to_email = isset($meta['wc_gc_giftcard_to_multiple']) ? (string) $meta['wc_gc_giftcard_to_multiple'] : '';
            $from     = isset($meta['wc_gc_giftcard_from'])        ? (string) $meta['wc_gc_giftcard_from']        : '';
            $message  = isset($meta['wc_gc_giftcard_message'])     ? (string) $meta['wc_gc_giftcard_message']     : '';
            $amount   = isset($meta['wc_gc_giftcard_amount'])      ? $meta['wc_gc_giftcard_amount']               : $item->get_total();

            if ($to_email || $from || $message) {
                $has = true;
                $gift_meta_out[] = array(
                    'buyer_name'      => $from,
                    'recipient_email' => $to_email,
                    'message'         => $message,
                    'amount'          => wc_price($amount),
                );
                $this->logger()->info('GC meta: ' . wp_json_encode($meta), array('source' => 'gcbwc'));
            }
        }

        return $has;
    }
}
new GCBWC_Core();
