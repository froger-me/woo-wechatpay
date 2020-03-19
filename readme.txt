=== Payment gateway for WooCommerce - Woo WeChatPay ===
Contributors: frogerme
Tags: wechat, wechatpay, payments, wechat payments, weixin, 微信, 微信支付
Requires at least: 4.9.5
Tested up to: 5.3.2
Stable tag: trunk
Requires PHP: 7.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

WeChat Pay payment gateway for WooCommerce.

== Description ==

Woo WeChatPay is a companion plugin for WP Weixin that adds a WeChat Pay payment gateway to WooCommerce-powered websites.
Customers can pay both in the WeChat browser (JSAPI), mobile browsers (H5), or from their computer via a QR code.
Upon purchase and if activated in settings, customers may receive a notification in the WeChat Official Account with a templated message if they are a follower.

### Requirements

* A [China Mainland WeChat Official Account](https://mp.weixin.qq.com) - **Service account**.
* A [China Mainland WeChat Pay account](https://pay.weixin.qq.com).
* **[WP Weixin](https://wordpress.org/plugins/wp-weixin)** installed, activated, enabled and properly configured.

### Important Notes

* Does NOT support [cross-border payments](https://pay.weixin.qq.com/wechatpay_guide/intro_settle.shtml) (possibly planned for v1.4).
* Make sure to read the "TROUBLESHOOT, FEATURE REQUESTS AND 3RD PARTY INTEGRATION" section below and [the full documentation](https://github.com/froger-me/woo-wechatpay/blob/master/README.md) before contacting the author.

### Overview

This plugin adds the following major features to WooCommerce and WP Weixin:

* **Payment of WooCommerce orders in WeChat mobile app:** uses the WeChat JSAPI for a seamless experience.
* **Payment of WooCommerce orders in mobile web browser app:** calls the WeChat H5 API for a seamless experience.
* **Payment of WooCommerce orders with WeChat via QR Code:** for customers using WeChat Pay in classic browsers.
* **Support for "pay again":** allows customers to continue the payment process of Pending orders.
* **Refund of WooCommerce orders:** possibility to refund orders manually in a few clicks, and support for automatic refund in case the transaction failed.
* **Templated message notification in WeChat:** send a WeChat templated message to the customer upon purchase (if following the Official Account - completely customisable via [filters](https://github.com/froger-me/woo-wechatpay/blob/master/README.md#user-content-filters)).
* **Multi-currency support:** using an exchange rate against Chinese Yuan configured in the settings.

Compatible with [WooCommerce Multilingual](https://wordpress.org/plugins/woocommerce-multilingual/), [WPML](http://wpml.org/), [Ultimate Member](https://wordpress.org/plugins/ultimate-member/), [WordPress Multisite](https://codex.wordpress.org/Create_A_Network), and [many caching plugins](https://github.com/froger-me/wp-weixin/blob/master/README.md#user-content-object-cache-considerations).

### Troubleshoot, feature requests and 3rd party integration

Unlike most WeChat integration plugins, Woo WeChatPay is provided for free.  

Woo WeChatPay is regularly updated, and bug reports are welcome, preferably on [Github](https://github.com/froger-me/woo-wechatpay/issues). Each bug report will be addressed in a timely manner, but issues reported on WordPress may take significantly longer to receive a response.  

Woo WeChatPay has been tested with the latest version of WordPress and WooCommerce - in case of issue, please ensure you are able to reproduce it with a default installation of WordPress, WooCommerce plugin, and Storefront theme and any of the aforementioned supported plugins if used before reporting a bug.  

Feature requests (such as "it would be nice to have XYZ") or 3rd party integration requests (such as "it is not working with XYZ plugin" or "it is not working with my theme") will be considered only after receiving a red envelope (红包) of a minimum RMB 500 on WeChat (guarantee of best effort, no guarantee of result). 

To add the author on WeChat, click [here](https://froger.me/wp-content/uploads/2018/04/wechat-qr.png), scan the WeChat QR code, and add "Woo WeChatPay" as a comment in your contact request.  

== Upgrade Notice ==

* Make sure to deactivate all the WP Weixin companion plugins before updating.
* Make sure to update WP Weixin to its latest version before updating.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin files to the `/wp-content/plugins/woo-wechatpay` directory.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Edit plugin settings

== Screenshots ==
 
1. The WooCommerce checkout page with the WeChat Pay payment gateway in classic browsers.
2. The WooCommerce checkout page with the WeChat Pay payment gateway in WeChat.
3. The QR code payment screen in classic browsers.
4. The WooCommerce WeChat Pay payment gateway settings page.
5. The WeChat Pay settings added to the WP Weixin settings page.

== Changelog ==

= 1.3.11 =
* WC tested up to: 4.0.0
* WP Weixin tested up to: 1.3.11
* Translation updates

= 1.3.10 =
* WC tested up to: 3.9.2
* WP Weixin tested up to: 1.3.10

= 1.3.9 =
* WC tested up to: 3.9.1
* WP Weixin tested up to: 1.3.9

= 1.3.8 =
* WP Weixin tested up to: 1.3.8
* Minor fixes

= 1.3.7 =
* WP Weixin tested up to: 1.3.7

= 1.3.6 =
* Added email notification to admin in case of rare edge case of paid WeChat Pay transaction with order not found in WooCommerce
* Minor bugfix & optimisation
* WC tested up to: 3.9.0
* Change plugin display name to comply with WordPress guidelines more tightly

= 1.3.5 =
* Bump version to match WP Weixin
* Require WP Weixin v1.3.5
* Remove commented code
* Add call to `wp_weixin_ajax_safe`
* Add support for mobile web browsers using the H5 API, with gateway configuration and WeChat merchant platform backend help

= 1.3 =
* Major overall code refactor
* Multisite support
* Use new [WP Weixin](https://wordpress.org/plugins/wp-weixin) functions instead of using the classes directly
* Use WooCommerce logger everywhere
* Refund reason sent to WeChat
* Ensure compatibility with other plugins using WeChat Pay API
* Add `woo_wechatpay_templated_message` action hook
* Disable all the Alipay-related payment gateways (looking for `alipay` or `zhifubao` in the gateway ID) in WeChat browser by default to prevent conflicts
* Rename `pay-again.php` template file to `redirected-pay.php`.
* Add `woowechatpay_filter_wechat_gateways` filter to control the available payment gateways in WeChat browser
* Attempt to refund order automatically in case of failure
* Update checkout script (fix undefined index has_full_address warning)
* Update documentation
* Update translation

Special thanks:

* Thanks @alexlii for extensive testing, translation, suggestions and donation!
* Thanks @lssdo for translation
* Thanks @kzgzs for improvement suggestions

= 1.2 =
* Preserve cart on checkout
* Mobile WeChat payment: do not leave checkout when payment is cancelled
* Handle refunds via WeChat Pay
* Adjust methods visibility
* Add WooCommerce logger
* Fix currency rate conversion
* Add gateway description

= 1.1.1 =
* Better error log
* Fix "pay again"

= 1.1 =
* Public plugin on WordPress repository

= 1.0.5 =
* Remove WP Package Updater - no more private plugin, please update to the public plugin (v1.1)

= 1.0.4 =
* Adjust rewrite rules registration

= 1.0.3 =
* Rearrange hooks firing sequence
* Add icons and banners
* Add readme.txt

= 1.0.2 =
* Coding standards

= 1.0.1 =
* Add WP Package Updater

= 1.0 =
* First version