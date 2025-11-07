=== Gift Card Buyer Confirmation for WC ===
Contributors: najir29
Donate link:
Tags: woocommerce, email, order, notification, gift card
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 7.9
WC tested up to: 9.3

Send a simple confirmation email to gift card buyers in WooCommerce, with an optional instant recipient fallback.

== Description ==
This plugin sends a reliable confirmation email to the *buyer* whenever a gift card product is purchased in WooCommerce. It does not replace the official recipient email from WooCommerce Gift Cards.

**Highlights**
- Triggers on completed/processing orders
- Uses WooCommerce Email API (HTML + plain text fallback)
- Dynamic store details (store name, admin email)
- Placeholder fallbacks for test previews
- HPOS compatible

**Dependencies**
Works with WooCommerce. Built to integrate with the official **WooCommerce Gift Cards** extension.

== Installation ==
1. Upload `gift-card-buyer-confirmation-for-wc` to `/wp-content/plugins/`
2. Activate via **Plugins**
3. Ensure WooCommerce is active
4. Configure under **WooCommerce → Settings → Emails → Buyer Gift Card Confirmation**

== Filters ==
- `gcbwc_support_email` — default: WordPress admin email
- `gcbwc_support_phone` — default: empty

== FAQ ==
= Does it support HPOS? =
Yes — the plugin declares HPOS compatibility.

== Changelog ==
= 1.2.4 =
* Added translators comments & minor compliance tweaks.
