<?php
if (! defined('ABSPATH')) exit;

final class GCBWC_Loader
{
    public function __construct()
    {
        add_filter('woocommerce_email_classes', array($this, 'register_email'));
    }

    public function register_email($emails)
    {
        require_once GCBWC_PATH . 'includes/class-gcbwc-email.php';
        $emails['GCBWC_Email_Buyer_Confirmation'] = new GCBWC_Email_Buyer_Confirmation();
        return $emails;
    }
}
new GCBWC_Loader();
