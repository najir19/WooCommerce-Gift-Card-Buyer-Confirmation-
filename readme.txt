<<<<<<< HEAD
=== Gift Card Buyer Confirmation Email ===
Contributors: najirahamed29
Tags: woocommerce, gift cards, email, notification
Requires at least: 6.2
Tested up to: 6.6
Stable tag: 1.2.1
License: GPLv2 or later

== Description ==
A lightweight WooCommerce plugin that sends one clean confirmation email to the buyer and instantly emails the gift card recipient — even without WP Cron.

== Features ==
* One clean buyer confirmation email
* Instant recipient delivery (no WP Cron)
* Works with official WooCommerce Gift Cards plugin
* No performance impact

== Installation ==
1. Upload the folder `gift-card-buyer-email` to `/wp-content/plugins/`.
2. Activate it from the **Plugins** menu in WordPress.

== Changelog ==
= 1.2.1 =
* Added instant fallback recipient email (no WP Cron dependency)
* Suppressed duplicate Woo buyer emails
=======
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
>>>>>>> master
