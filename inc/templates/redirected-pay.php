<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<!doctype html>
<html <?php language_attributes(); ?> class="wechat">
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no">
		<?php wp_head(); ?>
	</head>
	<body class="wechat-layout blank">
		<div id="woowechatpay_redirected_pay" class="content-area">
		<div class="loader woowechatpay-loader">
			<div class="weui-mask_transparent"></div>
			<div class="weui-toast">
				<i class="weui-loading weui-icon_toast"></i>
				<p class="weui-toast__content"><?php esc_html_e( 'Loading...', 'woo-wechatpay' ); ?></p>
			</div>
		</div>
	</div>
<?php wp_footer(); ?>
	</body>
</html>
