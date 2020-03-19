<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div id="woowechatpay_h5_link_container">
	<?php if ( $has_result ) : ?>
		<div id="woowechatpay_link">
			<a class="button h5-pay-link" href="<?php echo esc_url( $link_url ); ?>"><?php esc_html_e( 'Click to pay with WeChat', 'woo-wechatpay' ); ?></a>
		</div>
	<?php else : ?>
		<p class="woocommerce-error">
			<?php esc_html_e( 'Error generating the payment link. If the problem persists, please contact our services.', 'woo-wechatpay' ); ?>
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
