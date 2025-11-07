<?php

/**
 * Plugin Name:  Gift Card Buyer Confirmation for WC
 * Plugin URI:   https://github.com/najir19/WooCommerce-Gift-Card-Buyer-Confirmation-
 * Description:  Sends a clean confirmation email to the buyer when a WooCommerce gift card is purchased. Also includes an instant fallback email to the recipient.
 * Version:      1.2.4
 * Author:       Najir Ahamed
 * Author URI:   https://najirahamed.com
 * License:      GPLv2 or later
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  gift-card-buyer-confirmation-for-wc
 * Domain Path:  /languages
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * WC requires at least: 7.9
 * WC tested up to: 9.3
 */

if (! defined('ABSPATH')) exit;

define('GCBWC_VERSION', '1.2.4');
define('GCBWC_PATH', plugin_dir_path(__FILE__));
define('GCBWC_URL', plugin_dir_url(__FILE__));

// Ensure WooCommerce exists on activation.
register_activation_hook(__FILE__, function () {
    if (! class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('WooCommerce must be installed and active for "Gift Card Buyer Confirmation for WC". The plugin has been deactivated.', 'gift-card-buyer-confirmation-for-wc'),
            esc_html__('Dependency missing', 'gift-card-buyer-confirmation-for-wc'),
            array('back_link' => true)
        );
    }
});

// Admin notice when WooCommerce is not present, else load core files.
add_action('plugins_loaded', function () {
    if (! class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>' . esc_html__('WooCommerce is required for "Gift Card Buyer Confirmation for WC".', 'gift-card-buyer-confirmation-for-wc') . '</p></div>';
        });
        return;
    }
    require_once GCBWC_PATH . 'includes/class-gcbwc-loader.php';
    require_once GCBWC_PATH . 'includes/class-gcbwc-core.php';
});

// HPOS compatibility declare (Woo 7.1+).
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});
