<?php
if (! defined('ABSPATH')) exit;

class GCBWC_Email_Buyer_Confirmation extends WC_Email
{

    protected $gift_meta = array();

    public function __construct()
    {
        $this->id          = 'gcbwc_buyer_confirmation';
        $this->title       = __('Buyer Gift Card Confirmation', 'gift-card-buyer-confirmation-for-wc');
        $this->description = __('Sends a confirmation to the buyer when a gift card product is purchased.', 'gift-card-buyer-confirmation-for-wc');

        /* translators: {order_id} placeholder shows numeric order id */
        $this->subject     = __('Thanks for your gift card order (#{order_id})', 'gift-card-buyer-confirmation-for-wc');
        $this->heading     = __('Your gift card order is confirmed', 'gift-card-buyer-confirmation-for-wc');

        $this->customer_email = true;

        parent::__construct();
        $this->recipient = '';
    }

    /**
     * Trigger email with placeholders and gift meta.
     *
     * @param int   $order_id
     * @param array $gift_meta
     */
    public function trigger($order_id, $gift_meta = array())
    {
        if ($order_id) {
            $this->object       = wc_get_order($order_id);
            $this->recipient    = $this->object ? $this->object->get_billing_email() : '';
            $this->placeholders = array(
                '{order_id}'   => $this->object ? $this->object->get_id() : '',
                '{order_no}'   => $this->object ? $this->object->get_order_number() : '',
                '{buyer_name}' => $this->object ? $this->object->get_formatted_billing_full_name() : '',
            );
            $this->gift_meta    = $gift_meta;
        }

        if ($this->is_enabled() && $this->get_recipient()) {
            $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
        }
    }

    public function get_content_html()
    {
        $order      = $this->object;
        $buyer_name = isset($this->placeholders['{buyer_name}']) ? $this->placeholders['{buyer_name}'] : __('Customer', 'gift-card-buyer-confirmation-for-wc');
        $order_no   = isset($this->placeholders['{order_no}']) ? $this->placeholders['{order_no}'] : '—';

        $store_name    = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $support_mail  = apply_filters('gcbwc_support_email', get_option('admin_email'));
        $support_phone = apply_filters('gcbwc_support_phone', '');

        ob_start();

        /* translators: %s: buyer name */
        echo '<p>' . esc_html(sprintf(__('Dear %s,', 'gift-card-buyer-confirmation-for-wc'), $buyer_name)) . '</p>';

        /* translators: %s: store name */
        echo '<p>' . esc_html(sprintf(__('Thank you for your gift card purchase from %s! We\'re excited for your recipient to receive their gift.', 'gift-card-buyer-confirmation-for-wc'), $store_name)) . '</p>';

        /* translators: %s: order number (human readable) */
        echo '<p>' . esc_html(sprintf(__('Your order #%s has been successfully processed and confirmed.', 'gift-card-buyer-confirmation-for-wc'), $order_no)) . '</p>';
?>
        <h3 style="margin-top:30px;"><?php esc_html_e('Order Details', 'gift-card-buyer-confirmation-for-wc'); ?></h3>
        <table border="0" cellpadding="6" cellspacing="0" style="width:100%; border:1px solid #eee;">
            <tbody>
                <tr>
                    <th><?php esc_html_e('Order Date:', 'gift-card-buyer-confirmation-for-wc'); ?></th>
                    <td><?php echo $order ? esc_html(wc_format_datetime($order->get_date_created())) : '—'; ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('From:', 'gift-card-buyer-confirmation-for-wc'); ?></th>
                    <td><?php echo isset($this->gift_meta[0]['buyer_name']) && $this->gift_meta[0]['buyer_name'] !== '' ? esc_html($this->gift_meta[0]['buyer_name']) : '—'; ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Gift Card Amount:', 'gift-card-buyer-confirmation-for-wc'); ?></th>
                    <td><?php echo isset($this->gift_meta[0]['amount']) ? wp_kses_post($this->gift_meta[0]['amount']) : '—'; ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Card to be sent to:', 'gift-card-buyer-confirmation-for-wc'); ?></th>
                    <td><?php echo isset($this->gift_meta[0]['recipient_email']) && $this->gift_meta[0]['recipient_email'] !== '' ? esc_html($this->gift_meta[0]['recipient_email']) : '—'; ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Personal Message:', 'gift-card-buyer-confirmation-for-wc'); ?></th>
                    <td><?php echo isset($this->gift_meta[0]['message']) && $this->gift_meta[0]['message'] !== '' ? esc_html($this->gift_meta[0]['message']) : '—'; ?></td>
                </tr>
            </tbody>
        </table>
<?php

        if ($support_mail || $support_phone) {
            echo '<p style="margin-top:25px;"><strong>' . esc_html__('Need help?', 'gift-card-buyer-confirmation-for-wc') . '</strong><br>';
            if ($support_mail) {
                echo esc_html__('If you have any questions, please contact us at', 'gift-card-buyer-confirmation-for-wc') . ' ';
                echo '<a href="mailto:' . esc_attr($support_mail) . '">' . esc_html($support_mail) . '</a>';
            }
            if ($support_phone) {
                echo ' ' . esc_html__('or call', 'gift-card-buyer-confirmation-for-wc') . ' ' . esc_html($support_phone) . '.';
            }
            echo '</p>';
        }

        echo '<p>' . esc_html__('Thank you again for your purchase!', 'gift-card-buyer-confirmation-for-wc') . '<br>';
        echo esc_html__('Sincerely,', 'gift-card-buyer-confirmation-for-wc') . '<br>';
        echo '<strong>' . esc_html($store_name) . '</strong></p>';

        return ob_get_clean();
    }

    public function get_content_plain()
    {
        $buyer_name = isset($this->placeholders['{buyer_name}']) ? $this->placeholders['{buyer_name}'] : __('Customer', 'gift-card-buyer-confirmation-for-wc');
        $order_no   = isset($this->placeholders['{order_no}']) ? $this->placeholders['{order_no}'] : '—';
        $store_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);

        /* translators: 1: buyer name, 2: order no, 3: store name */
        return sprintf(
            __("Dear %1\$s,\nThank you for your gift card purchase from %3\$s.\nYour order #%2\$s has been confirmed.\n", 'gift-card-buyer-confirmation-for-wc'),
            $buyer_name,
            $order_no,
            $store_name
        );
    }
}
