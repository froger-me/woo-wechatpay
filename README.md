# Woo WeChatPay - WeChat Payment gateway for WooCommerce

* [General description](#user-content-general-description)
	* [Requirements](#user-content-requirements)
	* [Overview](#user-content-overview)
	* [Screenshots](#user-content-screenshots)
* [Settings](#user-content-settings)
* [Hooks - filters](#user-content-hooks---filters)
* [Templates](#user-content-templates)

## General Description

This plugin is a companion plugin of [WP Weixin](https://wordpress.org/plugins/wp-weixin) that adds a WeChat payment gateway to WooCommerce-powered websites. Customers can pay both from their computer via a QR code and in the WeChat browser directly. Upon purchase and if activated in settings, customers may receive a notification in the WeChat Official Service Account with a templated message if they are a follower.

### Requirements

This plugin requires:
* **WP Weixin** installed, activated, enabled and properly configured.
* **WooCommerce**

### Overview

This plugin adds the following major features to WooCommerce and WP Weixin:

* **WeChat Payment of WooCommerce orders via the JSAPI:** for users using the WeChat browser, for a seamless experience.
* **WeChat Payment of WooCommerce orders via QR Code:** for users not visiting the store within WeChat.
* **Support for "pay again":** to allow customers to continue the payment process of Pending orders.
* **Templated message notification in WeChat:** send a WeChat templated message to the customer upon purchase (if following the Official Account).
* **Multi-currency support:** using an exchange rate against Chinese Yuan configured in the settings.

### Screenshots

<img src="https://ps.w.org/woo-wechatpay/assets/screenshot-1.png" alt="Checkout Desktop" width="61%"> <img src="https://ps.w.org/woo-wechatpay/assets/screenshot-2.png" alt="Checkout Handheld" width="30%"> <img src="https://ps.w.org/woo-wechatpay/assets/screenshot-3.png" alt="Desktop WeChat Payment QR code" width="40%"> <img src="https://ps.w.org/woo-wechatpay/assets/screenshot-4.png" alt="WooCommerce Settings" width="51%">

## Settings

The following settings can be accessed in WooCommerce > Settings > Checkout > WeChatpay:

| Name                                  | Type     | Description                                                                                 |
| ------------------------------------- |:--------:| ------------------------------------------------------------------------------------------- |
| Enable/Disable                        | checkbox | Used to enable/disable the payment gateway                                                  |
| Checkout page title                   | text     | Title displayed for the payment gateway on the checkout page                                |
| Checkout page description             | text     | Description displayed for the payment gateway on the checkout page                          |
| Exchange Rate                         | text     | Exchange rate against Chinese Yuan (shows if the store currency is not set to Chinese Yuan) |
| WeChat Order Notification Template ID | text     | ID of a template added in the WeChat backend at `https://mp.weixin.qq.com`.                 |


Additionally, **required** settings more closely related to WeChat integration can be found under **the section "WeChat Pay Settings" of the WP Weixin plugin configuration page**.

## Hooks - filters

Woo WeChatPay gives developers the possibilty to customise its behavior with a series of custom filters.  
___

```php
apply_filters( 'woo_wechatpay_qr_img_header', string $qr_img_header );
```  

**Description**  
Filter the image used as a header when displaying the payment QR code on desktops.  

**Parameters**  
$qr_img_header
> (string) Path to the image
___

```php
apply_filters( 'woo_wechatpay_qr_img_footer', string $qr_img_footer );
```  

**Description**  
Filter the image used as a footer when displaying the payment QR code on desktops.  

**Parameters**  
$qr_img_footer
> (string) Path to the image
___

```php
apply_filters( 'woo_wechatpay_qr_phone_bg', string $qr_phone_bg );
```  

**Description**  
Filter the image used as a background when displaying the payment QR code on desktops.  

**Parameters**  
$qr_phone_bg
> (string) Path to the image
___

```php
apply_filters( 'woo_wechatpay_qr_placeholder', string $qr_placeholder );
```  

**Description**  
Filter the image used as a placeholder when the payment QR code is not available on desktops.  

**Parameters**  
$qr_placeholder
> (string) Path to the image
___

```php
apply_filters( 'woo_wechatpay_templated_message', array $templated_message, array $payment_result, WC_Order $order );
```  

**Description**  
Filter the templated message values - can be used to override the order notification templated message entirely, bypassing the fields restrictions imposed by default and use any template available in WeChat backend. The filter must be added in a `plugin_loaded` action with a priority of `19` or less.  

**Parameters**  
$templated_message
> (array) An array reprentation of the final JSON templated message

$payment_result
> (array) Result information returned by WeChat

$order
> (WC_Order) The order the customer attempted to pay
___

```php
apply_filters( 'woo_wechatpay_templated_message_intro_text', string $intro_text, mixed $order );
```  

**Description**  
Filter the introduction displayed in the order notification templated message.   

**Parameters**  
$intro_text
> (string) The intro text to display

$order
> (mixed) The WC_Order object or the ID of a woocommerce order
___

```php
apply_filters( 'woo_wechatpay_templated_message_cta_text', string $cta, mixed $order );
```  

**Description**  
Filter the call to action text displayed in the order notification templated message.  

**Parameters**  
$cta
> (string) The call to action text to display

$order
> (mixed) The WC_Order object or the ID of a woocommerce order
___

```php
apply_filters( 'woo_wechatpay_templated_message_remark_text', string $remark_text, mixed $order );
```  

**Description**  
Filter the remarks text displayed in the order notification templated message.  

**Parameters**  
$remark_text
> (string) The remark text to display

$order
> (mixed) The WC_Order object or the ID of a woocommerce order
___

```php
apply_filters( 'woo_wechatpay_templated_message_url', string $url, mixed $order );
```  

**Description**  
Filter the url used as a destination when the user opens the order notification templated message.  

**Parameters**  
$url
> (string) The url used as a destination

$order
> (mixed) The WC_Order object or the ID of a woocommerce order
___

```php
apply_filters( 'woo_wechatpay_templated_message_product_info_text', string $product_info, mixed $order );
```  

**Description**  
Filter the product information displayed in the order notification templated message.  

**Parameters**  
$product_info
> (string) The product information text to display

$order
> (mixed) The WC_Order object or the ID of a woocommerce order
___

```php
apply_filters( 'woo_wechatpay_templated_message_title_color', string $color );
```  

**Description**  
Filter the color of the title in the order notification templated message.  

**Parameters**  
$color
> (string) The hexadecimal code of the color.
___

```php
apply_filters( 'woo_wechatpay_templated_message_intro_color', string $color );
```  

**Description**  
Filter the color of the introduction in the order notification templated message.  

**Parameters**  
$color
> (string) The hexadecimal code of the color.
___

```php
apply_filters( 'woo_wechatpay_templated_message_order_num_color', string $color );
```  

**Description**  
Filter the color of the order number in the order notification templated message.  

**Parameters**  
$color
> (string) The hexadecimal code of the color.
___

```php
apply_filters( 'woo_wechatpay_templated_message_product_info_color', string $color );
```  

**Description**  
Filter the color of the product information in the order notification templated message.  

**Parameters**  
$color
> (string) The hexadecimal code of the color.
___

```php
apply_filters( 'woo_wechatpay_templated_message_order_total_color', string $color );
```  

**Description**  
Filter the color of the total price in the order notification templated message.  

**Parameters**  
$color
> (string) The hexadecimal code of the color.
___

```php
apply_filters( 'woo_wechatpay_templated_message_remark_color', string $color );
```  

**Description**  
Filter the color of the remark in the order notification templated message.  

**Parameters**  
$color
> (string) The hexadecimal code of the color.
___

## Templates

The following plugin files are included using `locate_template()` function of WordPress. This means they can be overloaded in the active WordPress theme if a file with the same name exists at the root of the theme.
___

```
computer-pay-qr.php
```  

**Description**  
The template of the page displayed when the user is paying using a QR code on a computer.  

**Variables**  
$result
> (bool) true if the QR code has been generated successfully, false otherwise  

$qr_img_header
> (string) The image used as a header when displaying the payment QR code on desktops  

$qr_placeholder
> (string) The image used as a placeholder when the payment QR code is not available on desktops  

$qr_url
> (string) The URL of the WeChat Pay QR code used to pay for the order

$order_id
> (int) The ID of the order to pay for  

$qr_img_footer
> (string) The image used as a footer when displaying the payment QR code on desktops  

$qr_phone_bg
> (string) The image used as a background when displaying the payment QR code on desktops  

**Associated styles**  
`woo-wechatpay/css/main.css`  

**Associated scripts**  
`woo-wechatpay/js/woo-wechatpay-heartbeat.js` 

___

```
pay-again.php
```  

**Description**  
The template of the page displayed when users restart an interrupted payment on mobile phones. The template acts as a placeholder before showing the native payment UI.  

**Associated styles**  
`woo-wechatpay/css/main.css`  

**Associated scripts**  
`woo-wechatpay/js/woo-wechatpay-mobile-payagain.js` 