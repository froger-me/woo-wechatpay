<?php
/**
 * WeChat Pay Orphan Transaction email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/admin-cancelled-order.php.
 *
 * HOWEVER, on occasion Woo WeChatPay will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

?>
<?php
printf(
	/* translators: %1$s: Website name  */
	esc_html__( 'WeChat Pay returned a notification of successful payment but the order does not appear to exist on %1$s.', 'woo-wechatpay' ),
	esc_html( $site_name )
);
echo "\n";

if ( 'auto_refund_error' === $type ) {
	esc_html_e( 'Woo WeChatPay attempted to automatically refund the order but encountered an error.', 'woo-wechatpay' );
} elseif ( 'transaction_closed' === $type ) {
	esc_html_e( 'Woo WeChatPay could not automatically refund the order because WeChat Pay closed the transaction.', 'woo-wechatpay' );
}

echo "\n\n";
esc_html_e(
	'You may need to double check the WeChat Pay transactions in the merchant platform - see the following basic information:',
	'woo-wechatpay'
);
echo "\n\n";
echo '-';
/* translators: %1$s: the order ID  */
printf( esc_html__( 'Order ID: %1$s' ), esc_html( $order_id ) );
echo "\n";
echo '-';
/* translators: %1$s: the WeChat Pay transaction ID  */
printf( esc_html__( 'WeChat Pay transaction ID: %1$s' ), esc_html( $transaction_id ) );
echo "\n\n";
printf(
	/* translators: %1$s: Log path  */
	esc_html__(
		'The complete error data can be found in the log files on the server at the following location: %1$s',
		'woo-wechatpay'
	),
	'<code>' . esc_html( $log_path ) . '</code>'
);

echo "\n\n----------------------------------------\n\n";

if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
