<?php

/**
 * Plugin Name: Gift Card Buyer Confirmation Email (Final v1.2.1)
 * Description: Sends exactly one confirmation email to the buyer for gift card orders (clean 5 fields). Suppresses Woo default buyer emails. Detects wc_gc_giftcard_* meta. Also sends an instant fallback email to the recipient (“To”) so it works on LocalWP without cron.
 * Author: THL Dev
 * Version: 1.2.1
 */

if (! defined('ABSPATH')) exit;

add_action('woocommerce_loaded', function () {

    /* ------------------------------------------------------------------------
     * Register custom Buyer email class
     * --------------------------------------------------------------------- */
    add_filter('woocommerce_email_classes', function ($emails) {

        if (! class_exists('WC_Email_Gift_Card_Buyer')) {

            class WC_Email_Gift_Card_Buyer extends WC_Email
            {

                public function __construct()
                {
                    $this->id             = 'gift_card_buyer_confirmation';
                    $this->title          = __('Gift Card – Purchase Confirmation (Buyer)', 'thl');
                    $this->description    = __('Sent once to the buyer when they purchase a gift card.', 'thl');
                    $this->customer_email = true;

                    $this->heading        = __('Your Gift Card Order is Confirmed!', 'thl');
                    $this->subject        = __('Your Gift Card Order #{order_number} is Confirmed!', 'thl');

                    // We render inline and wrap with WC header/footer.
                    $this->template_html  = '';
                    $this->template_plain = '';

                    parent::__construct();
                    $this->enabled = 'yes';
                }

                public function trigger($order_id, $gift_meta = array())
                {
                    if ($order_id) {
                        $this->object       = wc_get_order($order_id);
                        $this->recipient    = $this->object ? $this->object->get_billing_email() : '';
                        $this->placeholders = array(
                            '{order_number}' => $this->object ? $this->object->get_order_number() : '',
                        );
                        $this->gift_meta    = $gift_meta; // array of one or more GC line items
                    }

                    if (! $this->is_enabled() || ! $this->get_recipient()) return;

                    $this->send(
                        $this->get_recipient(),
                        $this->get_subject(),
                        $this->get_content(),
                        $this->get_headers(),
                        $this->get_attachments()
                    );
                }

                public function get_content_html()
                {
                    ob_start();
                    wc_get_template('emails/email-header.php', array('email_heading' => $this->get_heading()));

                    $order = $this->object;
                    $g     = isset($this->gift_meta[0]) ? $this->gift_meta[0] : array();
                    $first = $order ? $order->get_billing_first_name() : '';
?>
                    <p>Dear <?php echo esc_html($first ?: 'Customer'); ?>,</p>
                    <p>Thank you for your gift card purchase from <strong>The Herb Lady</strong>! We're excited for your recipient to receive their gift.</p>
                    <p>Your order <strong>#<?php echo esc_html($order->get_order_number()); ?></strong> has been successfully processed and confirmed.</p>

                    <h3 style="margin-top:30px;">Order Details</h3>
                    <table border="0" cellpadding="6" cellspacing="0" style="width:100%; border:1px solid #eee;">
                        <tbody>
                            <tr>
                                <th>Order Date:</th>
                                <td><?php echo esc_html(wc_format_datetime($order->get_date_created())); ?></td>
                            </tr>
                            <tr>
                                <th>From:</th>
                                <td><?php echo esc_html($g['buyer_name'] ?: '—'); ?></td>
                            </tr>
                            <tr>
                                <th>Gift Card Amount:</th>
                                <td><?php echo wp_kses_post($g['amount'] ?: '—'); ?></td>
                            </tr>
                            <tr>
                                <th>Card to be sent to:</th>
                                <td><?php echo esc_html($g['recipient_email'] ?: '—'); ?></td>
                            </tr>
                            <tr>
                                <th>Personal Message:</th>
                                <td><?php echo $g['message'] !== '' ? esc_html($g['message']) : '—'; ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <p style="margin-top:25px;"><strong>Need help?</strong><br>
                        If you have any questions, please contact us at <a href="mailto:info@theherbladyco.com">info@theherbladyco.com</a> or call (304) 876-2365.</p>

                    <p>Thank you again for your purchase!<br>Sincerely,<br><strong>The Team at The Herb Lady</strong></p>
<?php
                    wc_get_template('emails/email-footer.php');
                    return ob_get_clean();
                }

                public function get_content_plain()
                {
                    return wp_strip_all_tags($this->get_content_html());
                }
            }
        }

        $emails['WC_Email_Gift_Card_Buyer'] = new WC_Email_Gift_Card_Buyer();
        return $emails;
    }, 20);

    /* ------------------------------------------------------------------------
     * Helpers
     * --------------------------------------------------------------------- */
    function thl_gcb_logger()
    {
        static $l = null;
        if (! $l) $l = wc_get_logger();
        return $l;
    }

    /**
     * Detect gift card lines from official Woo Gift Cards meta:
     *   wc_gc_giftcard_to_multiple (recipient email),
     *   wc_gc_giftcard_from (buyer name),
     *   wc_gc_giftcard_message (message),
     *   wc_gc_giftcard_amount (amount)
     * Returns $gift_meta_out[] with keys: buyer_name, recipient_email, message, amount(html).
     */
    function thl_gcb_order_has_giftcard($order, &$gift_meta_out = array())
    {
        $gift_meta_out = [];
        $has           = false;

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (! $product) continue;

            $meta = [];
            foreach ($item->get_meta_data() as $m) $meta[$m->key] = $m->value;

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
                thl_gcb_logger()->info('GC meta: ' . wp_json_encode($meta), array('source' => 'thl-gcb'));
            }
        }

        return $has;
    }

    /* ------------------------------------------------------------------------
     * Send our buyer email once on thankyou + instant fallback recipient email
     * --------------------------------------------------------------------- */
    add_action('woocommerce_thankyou', function ($order_id) {
        $order = wc_get_order($order_id);
        if (! $order) return;

        $gift_meta = [];
        if (! thl_gcb_order_has_giftcard($order, $gift_meta)) return;

        $mailer = WC()->mailer();

        // 1) Send buyer confirmation (single email)
        if (isset($mailer->emails['WC_Email_Gift_Card_Buyer'])) {
            $mailer->emails['WC_Email_Gift_Card_Buyer']->trigger($order_id, $gift_meta);
            thl_gcb_logger()->info('Buyer confirmation sent for order ' . $order_id, array('source' => 'thl-gcb'));
        }

        // 2) Instant fallback: email the recipient directly (works without cron)
        foreach ($gift_meta as $g) {
            $to      = sanitize_email($g['recipient_email']);
            $from    = sanitize_text_field($g['buyer_name']);
            $message = sanitize_text_field($g['message']);
            $amount  = $g['amount']; // already formatted

            if (! $to) continue;

            $subject = sprintf("You've received a Gift Card from %s!", $from ? $from : 'a friend');
            $body    = '
                <p>Hi there,</p>
                <p><strong>' . esc_html($from ? $from : 'A friend') . '</strong> has sent you a gift card worth ' . wp_kses_post($amount) . ' from <strong>The Herb Lady</strong>!</p>'
                . ($message !== '' ? '<p><em>Message:</em> ' . esc_html($message) . '</p>' : '') .
                '<p>You can redeem your gift card during checkout on our website.</p>
                <p>Thank you,<br><strong>The Herb Lady Team</strong></p>';

            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail($to, $subject, $body, $headers);

            thl_gcb_logger()->info('Sent instant fallback recipient email to ' . $to . ' for order ' . $order_id, array('source' => 'thl-gcb'));
        }
    }, 10, 1);

    /* ------------------------------------------------------------------------
     * Suppress Woo default buyer emails (processing/on-hold) for gift-card orders
     * --------------------------------------------------------------------- */
    $suppress = function ($enabled, $order) {
        if (! $enabled) return false;
        if ($order instanceof WC_Order) {
            $tmp = [];
            if (thl_gcb_order_has_giftcard($order, $tmp)) return false;
        }
        return $enabled;
    };
    add_filter('woocommerce_email_enabled_customer_on_hold_order',    $suppress, 10, 2);
    add_filter('woocommerce_email_enabled_customer_processing_order', $suppress, 10, 2);
});
