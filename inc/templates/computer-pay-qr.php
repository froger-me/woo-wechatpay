<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div id="woowechatpay_qr_code_container">
	<?php if ( $has_result ) : ?>
		<p><?php esc_html_e( 'Please scan the QR code with WeChat to finish the payment.', 'woo-wechatpay' ); ?></p>
		<p class="woowechatpay-qr-error woocommerce-error"></p>
		<div id="woowechatpay_code">
			<?php if ( $qr_img_header ) : ?>
				<img class="woowechatpay-qr-header" alt="QR header" src="<?php echo esc_url( $qr_img_header ); ?>"/>
			<?php endif; ?>
			<?php if ( $qr_placeholder ) : ?>
				<img class="woowechatpay-qr-placeholder" alt="QR placeholder" src="<?php echo esc_url( $qr_placeholder ); ?>"/>
			<?php endif; ?>
			<img id="woowechatpay_qr_code" alt="QR Code" data-oid="<?php echo esc_attr( $order_id ); ?>" src="<?php echo esc_url( $qr_url ); ?>"/>
			<?php if ( $qr_img_footer ) : ?>
				<img class="woowechatpay-qr-footer" alt="QR footer" src="<?php echo esc_url( $qr_img_footer ); ?>"/>
			<?php endif; ?>
		</div>
		<?php if ( $qr_phone_bg ) : ?>
			<img class="woowechatpay-qr-bg" alt="QR help" src="<?php echo esc_url( $qr_phone_bg ); ?>"/>
		<?php endif; ?>
	<?php else : ?>
		<p class="woocommerce-error">
			<?php esc_html_e( 'Error generating the payment QR code. If the problem persists, please contact our services.', 'woo-wechatpay' ); ?>
		</p>
		<?php if ( ! empty( $error ) ) : ?>
			<p>
				<?php echo esc_html( $error ); ?>
			</p>
			<div class="woocommerce-info">
				<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="button wc-forward">
					<?php esc_html_e( 'Return to shop', 'woocommerce' ); ?>
				</a>
				<?php esc_html_e( 'Place a new order and try again.', 'woo-wechatpay' ); ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
