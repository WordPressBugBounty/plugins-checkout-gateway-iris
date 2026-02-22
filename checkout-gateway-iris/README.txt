=== Checkout Gateway for IRIS ===
Contributors: vgdevsolutions
Tags: woocommerce, iris, bank transfer, greek payments, qr code
Requires at least: 5.2
Tested up to: 6.8.3
Requires PHP: 7.2
Stable tag: 1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Unofficial IRIS checkout payment gateway for WooCommerce. Accept payments via IRIS and manage order statuses efficiently.

== Description ==
**Checkout Gateway for IRIS** allows store owners to accept direct IRIS payments through WooCommerce. After the customer places an order, it is set to "on hold" until the payment is manually verified.

This is ideal for Greek businesses using IRIS payments and bank transfers, allowing them to present payment instructions, QR code, VAT number, and account holder info right at checkout.

> ℹ️ This plugin is developed by VGDEV and is **not affiliated with or endorsed by IRIS or any bank**.

**Features:**
* Adds a new payment method for IRIS at WooCommerce Checkout.
* Displays bank details, reference instructions, and a QR code after order.
* Fully customizable payment labels (e.g., VAT, account name).
* Designed specifically for Greek market needs.
* Compatible with latest WooCommerce and WordPress versions.

== Installation ==
1. Upload the entire `iris-payments` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to **WooCommerce → Settings → Payments**, find "Πληρωμή IRIS", and click "Manage".
4. Fill in your business details (AFM, Account Holder, QR Code, etc.)
5. Save your changes and you're ready!

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =
Yes, WooCommerce must be installed and active.

= Can I change the QR code or labels shown? =
Yes! You can set your own QR image, AFM label, account holder label, and custom instructions.

= Is this the official IRIS plugin? =
No. It is an independent project by VGDEV to support Greek e-shops that accept IRIS payments.

= How are orders handled? =
Orders paid via IRIS are set to “on hold” after checkout until manually verified by the store admin.

= Where can I get help or report bugs? =
Visit [https://vgdevsolutions.gr](https://vgdevsolutions.gr) for support, feature requests, or updates.

== Screenshots ==
1. WooCommerce payment methods list with IRIS enabled.
2. IRIS gateway settings page with customization options. 
3. IRIS payment method shown at checkout.
4. Order confirmation page with QR code and payment instructions.

== Changelog ==

= 1.0 =
* Initial release of the Checkout Gateway for IRIS plugin.

= 1.1 =
* Fixed the email message on the completed order template.
* Updated email functionality for on-hold orders to display full payment details (reference text, VAT number, account holder, and QR code).
* Added payment cancellation handling using a GET parameter.

= 1.2 =
* Added compatibility with WooCommerce Cart/Checkout Blocks.
* Added setting to customize the completed order email message.
* Updated the checkout page payment logo.

= 1.3 =
* Refactored WooCommerce Cart/Checkout Blocks integration to follow the latest Blocks payment method registration pattern.
* Ensured the IRIS gateway is correctly registered as a compatible payment method in the Checkout Block (when enabled in WooCommerce → Payments).
* Updated the frontend Blocks script (iris-blocks.js) to load via wcSettings and wcBlocksRegistry and reliably display the IRIS title/description inside the Checkout Block.
* Minor internal cleanup and stability improvements for block-based checkouts.

= 1.4 =
* Security: Patched a security issue reported by Patchstack