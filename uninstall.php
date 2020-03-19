<?php

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit; // Exit if accessed directly
}

global $wpdb;


if ( is_multisite() ) {
	$blog_ids = array_map( function( $site ) {

		return absint( $site->blog_id );
	}, get_sites() );
} else {
	$blog_ids = array( get_current_blog_id() );
}

foreach ( $blog_ids as $blog_id ) {

	if ( is_multisite() ) {
		switch_to_blog( $blog_id );
	}

	$option_prefix      = $wpdb->esc_like( 'woo_wechatpay_' );
	$transient_prefix   = $wpdb->esc_like( '_transient_woo_wechatpay_' );
	$wc_option_settings = 'woocommerce_wechatpay_settings';
	$sql                = "DELETE FROM $wpdb->options WHERE `option_name` LIKE '%s' OR `option_name` LIKE '%s' OR `option_name` = '%s'";

	$wpdb->query( $wpdb->prepare( $sql, $option_prefix . '%', $transient_prefix . '%', $wc_option_settings ) ); // @codingStandardsIgnoreLine

	if ( is_multisite() ) {
		restore_current_blog();
	}
}
