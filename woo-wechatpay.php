<?php
/*
Plugin Name: Payment gateway for WooCommerce - Woo WeChatPay
Plugin URI: https://github.com/froger-me/woo-wechatpay
Description: Integrate Woocommerce with WeChat Pay.
Version: 1.3.11
Author: Alexandre Froger
Author URI: https://froger.me
Text Domain: woo-wechatpay
Domain Path: /languages
WC tested up to: 4.0.0
WP Weixin minimum required version: 1.3.10
WP Weixin tested up to: 1.3.11
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! defined( 'WOO_WECHATPAY_PLUGIN_FILE' ) ) {
	define( 'WOO_WECHATPAY_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'WOO_WECHATPAY_PLUGIN_PATH' ) ) {
	define( 'WOO_WECHATPAY_PLUGIN_PATH', plugin_dir_path( WOO_WECHATPAY_PLUGIN_FILE ) );
}

if ( ! defined( 'WOO_WECHATPAY_PLUGIN_URL' ) ) {
	define( 'WOO_WECHATPAY_PLUGIN_URL', plugin_dir_url( WOO_WECHATPAY_PLUGIN_FILE ) );
}

require_once WOO_WECHATPAY_PLUGIN_PATH . 'inc/class-woo-wechatpay.php';

register_activation_hook( WOO_WECHATPAY_PLUGIN_FILE, array( 'Woo_WechatPay', 'activate' ) );
register_deactivation_hook( WOO_WECHATPAY_PLUGIN_FILE, array( 'Woo_WechatPay', 'deactivate' ) );
register_uninstall_hook( WOO_WECHATPAY_PLUGIN_FILE, array( 'Woo_WechatPay', 'uninstall' ) );

function woo_wechatpay_extension( $wechat, $wp_weixin_settings, $wp_weixin, $wp_weixin_auth, $wp_weixin_responder, $wp_weixin_menu ) {

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {

		return;
	}

	$wc_wechatpay  = false;
	$use_ecommerce = wp_weixin_get_option( 'ecommerce' );

	if ( wp_weixin_get_option( 'enabled' ) ) {
		require_once WOO_WECHATPAY_PLUGIN_PATH . 'inc/class-wc-wechatpay.php';

		$wc_wechatpay = new WC_WechatPay( true );
	}

	$woowechatpay = new Woo_WechatPay( $wc_wechatpay, $wp_weixin_auth, true );
}

function woo_wechatpay_run() {
	add_action( 'wp_weixin_extensions', 'woo_wechatpay_extension', 0, 6 );
}
add_action( 'plugins_loaded', 'woo_wechatpay_run', 0, 0 );
