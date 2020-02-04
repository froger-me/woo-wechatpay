# Payment gateway for WooCommerce - Woo WeChatPay

* [General Description](#user-content-general-description)
	* [Requirements](#user-content-requirements)
	* [Important Notes](#user-content-important-notes)
	* [Overview](#user-content-overview)
* [Settings](#user-content-settings)
	* [Gateway settings](#user-content-gateway-settings)
	* [WP Weixin Settings](#user-content-wp-weixin-settings)
* [Hooks - actions & filters](#user-content-hooks---actions--filters)
	* [Actions](#user-content-actions)
	* [Filters](#user-content-filters)
* [Templates](#user-content-templates)

## General Description

Woo WeChatPay is a companion plugin for WP Weixin that adds a WeChat Pay payment gateway to WooCommerce-powered websites.
Customers can pay both in the WeChat browser (JSAPI), mobile browsers (H5), or from their computer via a QR code.
Upon purchase and if activated in settings, customers may receive a notification in the WeChat Official Account with a templated message if they are a follower.

### Requirements

* A [China Mainland WeChat Official Account](https://mp.weixin.qq.com) - **Service account**.
* A [China Mainland WeChat Pay account](https://pay.weixin.qq.com).
* **[WP Weixin](https://wordpress.org/plugins/wp-weixin)** installed, activated, enabled and properly configured.

### Important Notes

Does NOT support [cross-border payments](https://pay.weixin.qq.com/wechatpay_guide/intro_settle.shtml) (possibly planned for v1.4).

### Overview

This plugin adds the following major features to WooCommerce and WP Weixin:

* **Payment of WooCommerce orders in WeChat mobile app:** uses the WeChat JSAPI for a seamless experience.
* **Payment of WooCommerce orders in mobile browsers:** calls the WeChat H5 API for a seamless experience.
* **Payment of WooCommerce orders with WeChat via QR Code:** for customers using WeChat Pay in classic browsers.
* **Support for "pay again":** allows customers to continue the payment process of Pending orders.
* **Refund of WooCommerce orders:** possibility to refund orders manually in a few clicks, and support for automatic refund in case the transaction failed.
* **Templated message notification in WeChat:** send a WeChat templated message to the customer upon purchase (if following the Official Account - completely customisable via [filters](#user-content-filters)).
* **Multi-currency support:** using an exchange rate against Chinese Yuan configured in the settings.

Compatible with [WooCommerce Multilingual](https://wordpress.org/plugins/woocommerce-multilingual/), [WPML](http://wpml.org/), [Ultimate Member](https://wordpress.org/plugins/ultimate-member/), [WordPress Multisite](https://codex.wordpress.org/Create_A_Network), and [many caching plugins](https://github.com/froger-me/wp-weixin/blob/master/README.md#user-content-object-cache-considerations).

## Settings

The settings below are added to WooCommerce and WP Weixin when the plugin is active.

### Gateway settings

The following settings can be accessed in WooCommerce > Settings > Payments > WeChat Pay:

| Name                                  | Type     | Description                                                                                                                                                                                                                                                                                                                                                                                                                               |
| ------------------------------------- |:--------:| ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Enable/Disable                        | checkbox | Used to enable/disable the payment gateway                                                                                                                                                                                                                                                                                                                                                                                                |
| Checkout page title                   | text     | Title displayed for the payment gateway on the checkout page                                                                                                                                                                                                                                                                                                                                                                              |
| Checkout page description             | text     | Description displayed for the payment gateway on the checkout page                                                                                                                                                                                                                                                                                                                                                                        |
| Exchange Rate                         | text     | Exchange rate against Chinese Yuan (shows if the store currency is not set to Chinese Yuan)                                                                                                                                                                                                                                                                                                                                               |
| WeChat Order Notification Template ID | text     | ID of a template added in the WeChat backend at `https://mp.weixin.qq.com`.                                                                                                                                                                                                                                                                                                                                                               |
| H5 payment in mobile web browsers     | checkbox | If checked, customers can place an order via their mobile browsers and select WeChat Pay on their phone: instead of a QR code, WeChat Pay will be automatically called out to complete the payment. This feature requires to be activated and approved in the backend at [https://pay.weixin.qq.com/index.php/extend/pay_apply/apply_normal_h5_pay](https://pay.weixin.qq.com/index.php/extend/pay_apply/apply_normal_h5_pay) beforehand. |

### WP Weixin Settings

The settings below are only available if Woo WeChatPay is installed and activated (this behavior may be altered using the [wp_weixin_show_settings_section](#user-content-wp_weixin_show_settings_section) filter).

Name                                | Type      | Description                                                                                                             
----------------------------------- |:---------:|-------------------------------------------------------------------------------------------------------------------------
Force follow (account and checkout) | checkbox  | Require the user to follow the Official Account before accessing the checkout and account pages with the WeChat browser.

Additionally, **required settings** are located on the WP Weixin settings page, in the WeChat Pay Settings section.
See also the [WeChat Pay Settings](https://github.com/froger-me/wp-weixin/blob/master/README.md#user-content-wechat-pay-settings) of the WP Weixin plugin documentation.

## Hooks - actions & filters

Woo WeChatPay gives developers the possibilty to customise its behavior with a series of custom actions and filters. 

### Actions

Actions index:
* [woo_wechatpay_templated_message_sent](#user-content-woo_wechatpay_templated_message_sent)
___

#### woo_wechatpay_templated_message_sent

```php
do_action( 'woo_wechatpay_templated_message_sent', bool $sent, array $parameters );
```

**Description**  
Fired after attempting to send the templated message payment notification to the WeChat user.

**Parameters**  
$sent
> (bool) Wether the templated message was sent.
$parameters
> (array) The parameters and content of the templated message.
___

### Filters

Filters index:
* [woo_wechatpay_qr_img_header](#user-content-woo_wechatpay_qr_img_header)
* [woo_wechatpay_qr_img_footer](#user-content-woo_wechatpay_qr_img_footer)
* [woo_wechatpay_qr_phone_bg](#user-content-woo_wechatpay_qr_phone_bg)
* [woo_wechatpay_qr_placeholder](#user-content-woo_wechatpay_qr_placeholder)
* [woo_wechatpay_templated_message](#user-content-woo_wechatpay_templated_message)
* [woo_wechatpay_templated_message_intro_text](#user-content-woo_wechatpay_templated_message_intro_text)
* [woo_wechatpay_templated_message_cta_text](#user-content-woo_wechatpay_templated_message_cta_text)
* [woo_wechatpay_templated_message_remark_text](#user-content-woo_wechatpay_templated_message_remark_text)
* [woo_wechatpay_templated_message_url](#user-content-woo_wechatpay_templated_message_url)
* [woo_wechatpay_templated_message_product_info_text](#user-content-woo_wechatpay_templated_message_product_info_text)
* [woo_wechatpay_templated_message_title_color](#user-content-woo_wechatpay_templated_message_title_color)
* [woo_wechatpay_templated_message_intro_color](#user-content-woo_wechatpay_templated_message_intro_color)
* [woo_wechatpay_templated_message_order_num_color](#user-content-woo_wechatpay_templated_message_order_num_color)
* [woo_wechatpay_templated_message_product_info_color](#user-content-woo_wechatpay_templated_message_product_info_color)
* [woo_wechatpay_templated_message_order_total_color](#user-content-woo_wechatpay_templated_message_order_total_color)
* [woo_wechatpay_templated_message_remark_color](#user-content-woo_wechatpay_templated_message_remark_color)
* [woowechatpay_filter_wechat_gateways](#user-content-woowechatpay_filter_wechat_gateways)
___

#### woo_wechatpay_qr_img_header

```php
apply_filters( 'woo_wechatpay_qr_img_header', string $qr_img_header );
```  

**Description**  
Filter the image used as a header when displaying the payment QR code on desktops.  

**Parameters**  
$qr_img_header
> (string) Path to the image - default `WP_PLUGIN_URL . '/woo-wechatpay/images/wechatpay-logo.png'`.  
___

#### woo_wechatpay_qr_img_footer

```php
apply_filters( 'woo_wechatpay_qr_img_footer', string $qr_img_footer );
```  

**Description**  
Filter the image used as a footer when displaying the payment QR code on desktops.  

**Parameters**  
$qr_img_footer
> (string) Path to the image - default `WP_PLUGIN_URL . '/woo-wechatpay/images/browser-qr-footer.png'`.  
___

#### woo_wechatpay_qr_phone_bg

```php
apply_filters( 'woo_wechatpay_qr_phone_bg', string $qr_phone_bg );
```  

**Description**  
Filter the image used as a background when displaying the payment QR code on desktops.  

**Parameters**  
$qr_phone_bg
> (string) Path to the image - default `WP_PLUGIN_URL . '/woo-wechatpay/images/phone-bg.png'`.  
___

#### woo_wechatpay_qr_placeholder

```php
apply_filters( 'woo_wechatpay_qr_placeholder', string $qr_placeholder );
```  

**Description**  
Filter the image used as a placeholder when the payment QR code is not available on desktops.  

**Parameters**  
$qr_placeholder
> (string) Path to the image - default `WP_PLUGIN_URL . '/woo-wechatpay/images/qr-placeholder.png'`.  
___

#### woo_wechatpay_templated_message

```php
apply_filters( 'woo_wechatpay_templated_message', array $templated_message, array $payment_result, mixed $order );
```  

**Description**  
Filter the templated message values - can be used to override the order notification templated message entirely, bypassing the fields restrictions imposed by default and use any template available in WeChat backend. The filter must be added at the latest in a `plugin_loaded` action with a priority of `19` or less.  

**Parameters**  
$templated_message
> (array) An array representation of the final JSON templated message.

$payment_result
> (array) Result information returned by WeChat.

$order
> (mixed) A `WC_Order` object representing the order the customer attempted to pay.
___

#### woo_wechatpay_templated_message_intro_text

```php
apply_filters( 'woo_wechatpay_templated_message_intro_text', string $intro_text, mixed $order );
```  

**Description**  
Filter the introduction displayed in the order notification templated message.   

**Parameters**  
$intro_text
> (string) The intro text to display.

$order
> (mixed) A `WC_Order` object or the integer ID of a WooCommerce order.
___

#### woo_wechatpay_templated_message_cta_text

```php
apply_filters( 'woo_wechatpay_templated_message_cta_text', string $cta, mixed $order );
```  

**Description**  
Filter the call to action text displayed in the order notification templated message.  

**Parameters**  
$cta
> (string) The call to action text to display.

$order
> (mixed) A `WC_Order` object or the integer ID of a WooCommerce order.
___

#### woo_wechatpay_templated_message_remark_text

```php
apply_filters( 'woo_wechatpay_templated_message_remark_text', string $remark_text, mixed $order );
```  

**Description**  
Filter the remarks text displayed in the order notification templated message.  

**Parameters**  
$remark_text
> (string) The remark text to display.

$order
> (mixed) A `WC_Order` object or the integer ID of a WooCommerce order.
___

#### woo_wechatpay_templated_message_url

```php
apply_filters( 'woo_wechatpay_templated_message_url', string $url, mixed $order );
```  

**Description**  
Filter the URL the user will be redirected to when interacting with the order notification templated message.  

**Parameters**  
$url
> (string) The URL the user will be redirected to.

$order
> (mixed) A `WC_Order` object or the integer ID of a WooCommerce order.
___

#### woo_wechatpay_templated_message_product_info_text

```php
apply_filters( 'woo_wechatpay_templated_message_product_info_text', string $product_info, mixed $order );
```  

**Description**  
Filter the product information displayed in the order notification templated message.  

**Parameters**  
$product_info
> (string) The product information text to display.

$order
> (mixed) A `WC_Order` object or the integer ID of a WooCommerce order.
___

#### woo_wechatpay_templated_message_title_color

```php
apply_filters( 'woo_wechatpay_templated_message_title_color', string $color );
```  

**Description**  
Filter the color of the title in the order notification templated message.  

**Parameters**  
$color
> (string) The hexadecimal code of the color - default `'#666'`.
___

#### woo_wechatpay_templated_message_intro_color

```php
apply_filters( 'woo_wechatpay_templated_message_intro_color', string $color );
```  

**Description**  
Filter the color of the introduction in the order notification templated message.  

**Parameters**  
$color
> (string) The hexadecimal code of the color - default `'#173177'`.
___

#### woo_wechatpay_templated_message_order_num_color

```php
apply_filters( 'woo_wechatpay_templated_message_order_num_color', string $color );
```  

**Description**  
Filter the color of the order number in the order notification templated message.  

**Parameters**  
$color
> (string) The hexadecimal code of the color - default `'#173177'`.
___

#### woo_wechatpay_templated_message_product_info_color

```php
apply_filters( 'woo_wechatpay_templated_message_product_info_color', string $color );
```  

**Description**  
Filter the color of the product information in the order notification templated message.  

**Parameters**  
$color
> (string) The hexadecimal code of the color - default `'#173177'`.
___

#### woo_wechatpay_templated_message_order_total_color

```php
apply_filters( 'woo_wechatpay_templated_message_order_total_color', string $color );
```  

**Description**  
Filter the color of the total price in the order notification templated message.  

**Parameters**  
$color
> (string) The hexadecimal code of the color - default `'#173177'`.
___

#### woo_wechatpay_templated_message_remark_color

```php
apply_filters( 'woo_wechatpay_templated_message_remark_color', string $color );
```  

**Description**  
Filter the color of the remark in the order notification templated message.  

**Parameters**  
$color
> (string) The hexadecimal code of the color - default `'#173177'`.
___

#### woowechatpay_filter_wechat_gateways

```php
apply_filters( 'woowechatpay_filter_wechat_gateways', $available_gateways, $original_available_gateways )
```  

**Description**  
Filter the available gateways in the WeChat browser.  

**Parameters**  
$available_gateways
> (array) The list of gateways available in the WeChat browser.

$original_available_gateways
> (array) The list of gateways originally available in WooCommerce.
___

## Templates

The following template files are selected using the `locate_template()` and included with `load_template()` functions provided by WordPress. This means they can be overloaded in the active WordPress theme. Developers may place their custom template files in the following directories under the theme's folder (in order of selection priority):

* `plugins/wp-weixin/woo-wechatpay/`
* `wp-weixin/woo-wechatpay/`
* `plugins/woo-wechatpay/`
* `woo-wechatpay/`
* `wp-weixin/`
* at the root of the theme's folder

The available paths of the templates may be customised with the [wp_weixin_locate_template_paths](https://github.com/froger-me/wp-weixin/blob/master/README.md#user-content-wp_weixin_locate_template_paths) filter.  

Templates index:
* [computer-pay-qr](#user-content-computer-pay-qr)
* [redirected-pay](#user-content-redirected-pay)

___

### computer-pay-qr

```
computer-pay-qr.php
```  

**Description**  
The template of the page displayed when the user is paying using a QR code on a computer.  

**Variables**  
$has_result
> (bool) Wether the QR code has been generated successfully.

$qr_img_header
> (string) The image used as a header when displaying the payment QR code on desktops.  

$qr_placeholder
> (string) The image used as a placeholder when the payment QR code is not available on desktops.  

$qr_url
> (string) The URL of the WeChat Pay QR code used to pay for the order.  

$order_id
> (int) The ID of the order to pay for.  

$qr_img_footer
> (string) The image used as a footer when displaying the payment QR code on desktops.  

$qr_phone_bg
> (string) The image used as a background when displaying the payment QR code on desktops.  

$error
> (string) Description of the error if somthing wrong happened.  

**Associated style enqueued with key:**  
`woo-wechatpay-main-style`  

**Associated script enqueued with key:**  
`woo-wechatpay-heartbeat`  

___

### redirected-pay

```
redirected-pay.php
```  

**Description**  
The template of the page displayed when users are redirected before payment on mobile phones. The template acts as a placeholder before showing the native payment UI.  

**Associated style enqueued with key:**  
`woo-wechatpay-main-style`  

**Associated script enqueued with key:**  
`woo-wechatpay-mobile-redirected`  