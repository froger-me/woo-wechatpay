<?php
/**
 * WeChat Pay Orphan Transaction email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/wechatpay-orphan-transaction.php.
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

/*
 * @hooked WC_Emails::email_header() Output the email header
*/
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p>
	<?php
	printf(
		/* translators: %1$s: Website name  */
		esc_html__(
			'WeChat Pay returned a notification of successful payment but the order does not appear to exist on %1$s.',
			'woo-wechatpay'
		),
		esc_html( $site_name )
	);
	?>
	<br/>
	<?php if ( 'auto_refund_error' === $type ) : ?>
		<?php esc_html_e( 'Woo WeChatPay attempted to automatically refund the order but encountered an error.', 'woo-wechatpay' ); ?>
	<?php elseif ( 'transaction_closed' === $type ) : ?>
		<?php
		esc_html_e(
			'Woo WeChatPay could not automatically refund the order because WeChat Pay closed the transaction.',
			'woo-wechatpay'
		);
		?>
	<?php endif; ?>
</p>
<p>
	<?php esc_html_e( 'See the following basic information:', 'woo-wechatpay' ); ?>
</p>
<ul>
	<li>
	<?php
		/* translators: %1$s: the order ID  */
		printf( esc_html__( 'Order ID: %1$s' ), esc_html( $order_id ) );
	?>
	</li>
	<li>
	<?php
		/* translators: %1$s: the WeChat Pay transaction ID  */
		printf( esc_html__( 'WeChat Pay transaction ID: %1$s' ), esc_html( $transaction_id ) );
	?>
	</li>
</ul>
<p>
	<?php
	printf(
		/* translators: %1$s: Log path  */
		esc_html__(
			'The complete error data can be found in the log files on the server at the following location: %1$s',
			'woo-wechatpay'
		),
		'<code>' . esc_html( $log_path ) . '</code>'
	);
	?>
</p>
<?php

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
