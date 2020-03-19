<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_WechatPay extends WC_Payment_Gateway {

	protected static $log_enabled = false;
	protected static $log         = false;
	protected static $refund_id;

	protected $current_currency;
	protected $multi_currency_enabled;
	protected $supported_currencies;
	protected $charset;
	protected $wechat;
	protected $pay_notify_result;
	protected $is_pay_handler = false;
	protected $refundable_status;

	public function __construct( $init_hooks = false ) {
		$active_plugins         = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
		$is_wpml                = in_array( 'woocommerce-multilingual/wpml-woocommerce.php', $active_plugins, true );
		$multi_currency_options = 'yes' === get_option( 'icl_enable_multi_currency' );

		$this->wechat                 = wp_weixin_get_wechat();
		$this->charset                = strtolower( get_bloginfo( 'charset' ) );
		$this->id                     = 'wechatpay';
		$this->title                  = __( 'WeChat Pay', 'woo-wechatpay' );
		$this->description            = $this->get_option( 'description' );
		$this->method_title           = $this->title;
		$this->method_description     = __( 'WeChat Pay is a simple, secure and fast online payment method.', 'woo-wechatpay' );
		$this->exchange_rate          = $this->get_option( 'exchange_rate' );
		$this->current_currency       = get_option( 'woocommerce_currency' );
		$this->multi_currency_enabled = $is_wpml && $multi_currency_options;
		$this->supported_currencies   = array( 'RMB', 'CNY' );
		$this->order_button_text      = __( 'Pay with WeChat', 'woo-wechatpay' );
		$this->order_title_format     = $this->get_option( 'order_title_format' );
		$this->order_prefix           = $this->get_option( 'order_prefix' );
		$this->has_fields             = false;
		$this->form_submission_method = ( $this->get_option( 'form_submission_method' ) === 'yes' );
		$this->notify_url             = WC()->api_request_url( 'WC_WechatPay' );
		$this->debug                  = ( 'yes' === $this->get_option( 'debug', 'no' ) );
		$this->mobile_h5              = ( 'yes' === $this->get_option( 'mobile_h5', 'no' ) );
		$this->supports               = array(
			'products',
			'refunds',
		);

		self::$log_enabled = $this->debug;

		if ( ! in_array( $this->charset, array( 'gbk', 'utf-8' ), true ) ) {
			$this->charset = 'utf-8';
		}

		$this->setup_form_fields();
		$this->init_settings();

		$this->qr_img_header  = apply_filters(
			'woo_wechatpay_qr_img_header',
			WOO_WECHATPAY_PLUGIN_URL . 'images/wechatpay-logo.png'
		);
		$this->qr_img_footer  = apply_filters(
			'woo_wechatpay_qr_img_footer',
			WOO_WECHATPAY_PLUGIN_URL . 'images/browser-qr-footer.png'
		);
		$this->qr_phone_bg    = apply_filters(
			'woo_wechatpay_qr_phone_bg',
			WOO_WECHATPAY_PLUGIN_URL . 'images/phone-bg.png'
		);
		$this->qr_placeholder = apply_filters(
			'woo_wechatpay_qr_placeholder',
			WOO_WECHATPAY_PLUGIN_URL . 'images/qr-placeholder.png'
		);

		if ( $init_hooks ) {
			// Add save gateway options callback
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ), 10, 0 );

			if ( $this->is_woowechatpay_enabled() ) {
				// Add checkout scripts
				add_action( 'wp_enqueue_scripts', array( $this, 'add_checkout_scripts' ), 10, 0 );
				// Check wechat response to see if payment is complete
				add_action( 'woocommerce_api_wc_wechatpay', array( $this, 'check_wechatpay_response' ), 10, 0 );
				// Remember the refund info at creation for later use
				add_action( 'woocommerce_create_refund', array( $this, 'remember_refund_info' ), 10, 2 );
				// Add payment hold callback
				add_action( 'wp_ajax_woowechatpay_hold', array( $this, 'order_hold' ), 10, 0 );
				add_action( 'wp_ajax_nopriv_woowechatpay_hold', array( $this, 'order_hold' ), 10, 0 );
				// Handle automatic refunds in case of payment error
				add_action( 'wp_weixin_handle_auto_refund', array( $this, 'handle_auto_refund' ), 10, 2 );
				// Handle the transaction if the notification was captured by another endpoint
				add_action( 'wp_weixin_handle_payment_notification', array( $this, 'handle_notify' ), 10, 0 );

				// Stricter user sanitation
				add_filter( 'sanitize_user', array( $this, 'sanitize_user_strict' ), 10, 3 );
				// Filter the allowed payment gateways
				add_filter( 'woocommerce_available_payment_gateways', array( $this, 'set_wechat_allowed_gateways' ), 10, 1 );

				if ( $this->mobile_h5 ) {
					// Add description of H5 API domain setting
					add_filter( 'wp_weixin_ecommerce_description', array( $this, 'add_h5_settings_help' ), 10,1 );
				}
			}

			if ( $this->is_woowechatpay_enabled() && wp_weixin_is_wechat() ) {
				$this->description = $this->title;
			}

			$this->validate_settings();
		}
	}

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	public function is_available() {
		$is_available = ( 'yes' === $this->enabled ) ? true : false;

		if ( $this->is_crossdomain() ) {

			return $is_available;
		}

		if ( $this->multi_currency_enabled ) {

			if (
				! in_array( get_woocommerce_currency(), $this->supported_currencies, true ) &&
				! $this->exchange_rate
			) {
				$is_available = false;
			}
		} elseif (
			! in_array( $this->current_currency, $this->supported_currencies, true ) &&
			! $this->exchange_rate
		) {
			$is_available = false;
		}

		return $is_available;
	}

	public function is_crossdomain() {
		$is_crossdomain = false;

		if ( is_multisite() ) {
			$protocol       = ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . '://';
			$current_url    = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$path           = wp_parse_url( $current_url, PHP_URL_PATH );
			$is_crossdomain = strpos( $path, 'wxpayagain/?blog-id=' );
		}

		return $is_crossdomain;
	}

	public function process_admin_options() {
		$saved = parent::process_admin_options();

		if ( 'yes' !== $this->get_option( 'debug', 'no' ) ) {

			if ( empty( self::$log ) ) {
				self::$log = wc_get_logger();
			}

			self::$log->clear( 'wechatpay' );
		}

		return $saved;
	}

	public function can_refund_order( $order ) {
		$this->refundable_status = array(
			'refundable' => (bool) $order,
			'code'       => ( (bool) $order ) ? 'ok' : 'invalid_order',
			'reason'     => ( (bool) $order ) ? '' : __( 'Invalid order', 'woo-wechatpay' ),
		);

		if ( $order ) {

			if ( ! $this->wechat->cert_files_exist() ) {
				$this->refundable_status['refundable'] = false;
				$this->refundable_status['code']       = 'cert_files';
				$this->refundable_status['reason']     = __( 'the WeChatPay PEM certificates were not found. Please check the configuration', 'woo-wechatpay' );
			} elseif ( ! $order->get_transaction_id() ) {
				$this->refundable_status['refundable'] = false;
				$this->refundable_status['code']       = 'transaction_id';
				$this->refundable_status['reason']     = __( 'transaction not found.', 'woo-wechatpay' );
			}
		}

		return $this->refundable_status['refundable'];
	}

	public function remember_refund_info( $refund, $args ) {
		$prefix = '';
		$suffix = '-' . current_time( 'timestamp' );

		if ( is_multisite() ) {
			$prefix = get_current_blog_id() . '-';
		}

		self::$refund_id = str_pad( $prefix . $refund->get_id() . $suffix, 64, '0', STR_PAD_LEFT );
	}

	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order  = wc_get_order( $order_id );
		$extend = array();

		if ( ! $this->can_refund_order( $order ) ) {

			return new WP_Error( 'error', __( 'Refund failed', 'woocommerce' ) . ' - ' . $this->refundable_status['reason'] );
		}

		$unified_order_id = $order->get_meta( 'unified_order_id' );
		$total_fee        = $this->maybe_convert_amount( $order->get_total() );
		$amount           = $this->maybe_convert_amount( $amount );

		if ( $amount <= 0 || $amount > $total_fee ) {
			return new WP_Error( 'error', __( 'Refund failed - incorrect refund amount (must be more than 0 and less than the total amount of the order).', 'woo-wechatpay' ) );
		}

		if ( ! empty( $reason ) ) {
			$extend['refund_desc'] = esc_html( $reason );
		}

		$result = $this->wechat->refundOrder( $unified_order_id, self::$refund_id, $total_fee, $amount, $extend );

		if ( $result ) {
			self::log( 'Refund Result: ' . wc_print_r( $result, true ) );

			$amount = number_format( $result['refund_fee'] / 100, 2, '.', '' );
			$result = true;

			$order->add_order_note(
				sprintf(
					/* translators: 1: Refund amount, 2: Payment method title, 3: Refund ID */
					__( 'Refunded %1$s via %2$s - Refund ID: %3$s', 'woo-wechatpay' ),
					$amount,
					$this->method_title,
					'#' . ltrim( self::$refund_id, '0' )
				)
			);
		} else {
			$error = $this->wechat->getError();

			self::log( 'Refund Result: ' . wc_print_r( $result, true ), 'error' );
			self::log( 'Refund Error: ' . wc_print_r( $error, true ), 'error' );

			if ( empty( $error ) && empty( $result ) ) {
				$error = array(
					'message' => __(
						'The payment interface returned an empty response. Please check the configuration of the PEM certificate files.',
						'woo-wechatpay'
					),
				);
			}

			$result = new WP_Error( 'error', $error['message'] );
		}

		self::$refund_id = null;

		return $result;
	}

	public function sanitize_user_strict( $username, $raw_username, $strict ) {

		if ( ! $strict ) {

			return $username;
		}

		return sanitize_user( stripslashes( $raw_username ), false );
	}

	public function set_wechat_allowed_gateways( $available_gateways ) {

		if ( wp_weixin_is_wechat() ) {
			$filtered_gateways = $available_gateways;

			foreach ( $filtered_gateways as $key => $value ) {

				if (
					false !== strpos( strtolower( $key ), 'alipay' ) ||
					false !== strpos( strtolower( $key ), 'zhifubao' )
				) {
					unset( $filtered_gateways[ $key ] );
				}
			}

			return apply_filters( 'woowechatpay_filter_wechat_gateways', $filtered_gateways, $available_gateways );
		}

		return $available_gateways;
	}

	public function add_h5_settings_help( $ecommerce_description ) {
		$current_blog_id = get_current_blog_id();
		$pay_blog_id     = apply_filters( 'wp_weixin_ms_pay_blog_id', $current_blog_id );

		if ( $pay_blog_id !== $current_blog_id ) {
			$url = get_home_url( $pay_blog_id );

		} else {
			$url = home_url();
		}

		$url_data = wp_parse_url( $url );
		$domain   = $url_data['host'];

		$ecommerce_description .= '<strong>' . __( 'H5 Payment Domain Name', 'woo-wechatpay' ) . '</strong>';
		$ecommerce_description .= '<ul><li>' . $domain . '</li></ul>';

		return $ecommerce_description;
	}

	public function add_checkout_scripts() {
		$debug  = apply_filters( 'wp_weixin_debug', (bool) ( constant( 'WP_DEBUG' ) ) );
		$js_ext = ( $debug ) ? '.js' : '.min.js';

		if ( wp_weixin_is_wechat() ) {

			if ( ! is_checkout_pay_page() && ! is_order_received_page() ) {

				if ( is_checkout() && $this->is_woowechatpay_enabled() ) {
					$version      = filemtime( WOO_WECHATPAY_PLUGIN_PATH . 'js/woo-wechatpay-mobile' . $js_ext );
					$dependencies = array(
						'jquery',
						'woocommerce',
						'wc-country-select',
						'wc-address-i18n',
					);

					wp_deregister_script( 'wc-checkout' );
					wp_enqueue_script(
						'wc-checkout',
						WOO_WECHATPAY_PLUGIN_URL . 'js/woo-wechatpay-mobile' . $js_ext,
						$dependencies,
						$version
					);
				}
			}
		} else {
			$order_id = get_query_var( 'order-pay' );
			$order    = wc_get_order( $order_id );

			if ( $order && 'wechatpay' === $order->get_payment_method() ) {

				if ( is_checkout_pay_page() && ! get_query_var( 'pay_for_order', false ) ) {
					$version_heartbeat = filemtime( WOO_WECHATPAY_PLUGIN_PATH . 'js/woo-wechatpay-heartbeat' . $js_ext );
					$params            = array(
						'ajax_url'      => WC()->ajax_url(),
						'order_error'   => __( 'Something went wrong... Please reload the page. If the problem persists, please contact our services.', 'woo-wechatpay' ),
						'default_error' => __( 'An unexpected error occured. Please check your internet connection.', 'woo-wechatpay' ),
						'expired_error' => __( 'The system could not detect payment validation. Please reload the page for confirmation.', 'woo-wechatpay' ),
						'nonce'         => wp_create_nonce( 'woo-wechatpay' ),
					);

					if ( $this->mobile_h5 ) {
						$version_h5_link = filemtime( WOO_WECHATPAY_PLUGIN_PATH . 'js/woo-wechatpay-h5-link' . $js_ext );
						wp_enqueue_script(
							'woo-wechatpay-h5-link',
							WOO_WECHATPAY_PLUGIN_URL . 'js/woo-wechatpay-h5-link' . $js_ext,
							array( 'jquery' ),
							$version_h5_link
						);
					}

					wp_enqueue_script(
						'woo-wechatpay-heartbeat',
						WOO_WECHATPAY_PLUGIN_URL . 'js/woo-wechatpay-heartbeat' . $js_ext,
						array( 'jquery' ),
						$version_heartbeat
					);
					wp_localize_script( 'woo-wechatpay-heartbeat', 'WooWechatpay', $params );
				}
			}
		}
	}

	public function validate_settings() {
		$valid = true;

		if ( $this->requires_exchange_rate() && ! $this->exchange_rate && ! $this->is_crossdomain() ) {
			add_action( 'admin_notices', array( $this, 'missing_exchange_rate_notice' ), 10, 0 );

			$valid = false;
		}

		return $valid;
	}

	public function requires_exchange_rate() {

		return ( ! in_array( $this->current_currency, $this->supported_currencies, true ) );
	}

	public function missing_exchange_rate_notice() {
		$message = __( 'WeChat Pay is enabled, but the store currency is not set to Chinese Yuan.', 'woo-wechatpay' );
		// translators: %1$s is the URL of the link and %2$s is the currency name
		$message .= __( ' Please <a href="%1$s">set the %2$s against the Chinese Yuan exchange rate</a>.', 'woo-wechatpay' );

		$page = 'admin.php?page=wc-settings&tab=checkout&section=wc_wechatpay#woocommerce_wechatpay_exchange_rate';
		$url  = admin_url( $page );

		echo '<div class="error"><p>' . sprintf( $message, $url, $this->current_currency . '</p></div>' ); // WPCS: XSS OK
	}

	public function payment_heartbeat_pulse() {

		if ( ! wp_verify_nonce( $_POST['nonce'], 'woo-wechatpay' ) ) {
			$error = new WP_Error( __METHOD__, 'Unauthorised access' );

			wp_send_json_error( $error );
		}

		$order = wc_get_order( absint( filter_input( INPUT_POST, 'orderId', FILTER_SANITIZE_NUMBER_INT ) ) );

		if ( $order ) {
			$is_paid = ! $order->needs_payment();

			if ( $is_paid ) {
				$return_url = urldecode( $this->get_return_url( $order ) );
				$response   = array(
					'status'  => 'paid',
					'message' => $return_url,
				);

				wp_send_json_success( $response );
			} else {
				$response = array(
					'status' => 'nPaid',
				);

				wp_send_json_success( $response );
			}
		} else {
			$message = __( 'Invalid order ID', 'woo-wechatpay' );
			$error   = new WP_Error( __METHOD__, $message );

			wp_send_json_error( $error );
		}

		wp_die();
	}

	public function get_icon() {

		return '<span class="wechatpay"></span>';
	}

	public function receipt_page( $order_id ) {

		if ( $this->mobile_h5 && $this->is_mobile() ) {
			$this->generate_h5_link( $order_id );
		} else {
			$this->generate_qr( $order_id );
		}
	}

	protected function is_mobile() {
		$ua = strtolower( $_SERVER['HTTP_USER_AGENT'] );

		if ( strpos( $ua, 'ipad' ) || strpos( $ua, 'iphone' ) || strpos( $ua, 'android' ) ) {

			return true;
		}

		return false;
	}

	protected function generate_h5_link( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {

			return '';
		}

		$url        = $this->get_h5_link_uri( $order );
		$return_url = $this->get_return_url( $order );
		$error      = $this->wechat->getError();

		if ( $error ) {
			self::log( __METHOD__ . ': ' . wc_print_r( $error, true ), 'error' );
			$order->update_status( 'failed', $error['message'] );
			WC()->cart->empty_cart();

			$error = __( 'The order has failed. Reason: ', 'woo-wechatpay' ) . $error['message'];
		} elseif ( empty( $url ) ) {
			$status_message = __( 'Link url is empty', 'woo-wechatpay' );

			$order->update_status( 'failed', $status_message );
			WC()->cart->empty_cart();

			$error = __( 'The order has failed. Reason: ', 'woo-wechatpay' ) . $status_message;
		}

		set_query_var( 'link_url', $url . '&redirect_url=' . rawurlencode( $return_url ) );
		set_query_var( 'has_result', ( ! $this->wechat->getError() && ! empty( $url ) ) );
		set_query_var( 'order_id', $order_id );
		set_query_var( 'error', $error );

		ob_start();
		WP_Weixin::locate_template( 'browser-pay-link.php', true, true, 'woo-wechatpay' );

		$html = ob_get_clean();

		echo $html; // WPCS: XSS OK
	}

	protected function get_h5_link_uri( $order ) {
		$total_fee = $this->maybe_convert_amount( $order->get_total() );
		$extend    = $this->get_unified_order_extend( $order, $total_fee );

		if ( is_multisite() ) {
			$blog_id          = get_current_blog_id();
			$unified_order_id = 'WooWP' . $blog_id . '-' . $order->get_id() . '-' . current_time( 'timestamp' );
		} else {
			$unified_order_id = 'WooWP' . $order->get_id() . '-' . current_time( 'timestamp' );
		}

		$order->update_meta_data( 'unified_order_id', $unified_order_id );
		$order->save_meta_data();

		return $this->wechat->mobileUnifiedOrder(
			$order->get_id(),
			get_bloginfo( 'name' ) . ' - #' . $order->get_id(),
			$unified_order_id,
			$this->maybe_convert_amount( $order->get_total() ),
			$this->notify_url,
			$extend
		);
	}

	public function admin_options() {
		echo '<h3>' . esc_html( __( 'WeChat Pay', 'woo-wechatpay' ) ) . '</h3>';
		echo '<p>' . esc_html( __( 'WeChat Pay is a simple, secure and fast online payment method.', 'woo-wechatpay' ) ) . '</p>';
		echo '<table class="form-table">';

		$this->generate_settings_html();

		echo '</table>';
	}

	public function check_wechatpay_response() {
		$qr_data = filter_input( INPUT_GET, 'QRData', FILTER_SANITIZE_STRING );

		if ( $qr_data && strrpos( $qr_data, 'weixin://', -strlen( $qr_data ) ) !== false ) {
			QRcode::png( $qr_data );

			exit();
		}

		$this->is_pay_handler = true;

		do_action( 'wp_weixin_handle_payment_notification' );
	}

	public function handle_notify() {
		$pay_blog_id = get_current_blog_id();
		$blog_id     = false;
		$success     = false;
		$order       = false;
		$error       = $this->wechat->getError();
		$result      = $this->wechat->getNotify();
		$result      = is_array( $result ) ? $result : array( $result );

		if ( apply_filters( 'wp_weixin_debug', (bool) ( constant( 'WP_DEBUG' ) ) ) ) {
			WP_Weixin::log( $result );
		}

		if ( isset( $result['out_trade_no'] ) && 0 === strpos( $result['out_trade_no'], 'WooWP' ) ) {
			$success            = true;
			$out_trade_no_parts = explode( '-', str_replace( 'WooWP', '', $result['out_trade_no'] ) );

			if ( is_multisite() ) {
				$blog_id = absint( array_shift( $out_trade_no_parts ) );

				switch_to_blog( $blog_id );
			}

			$order_id = absint( array_shift( $out_trade_no_parts ) );
			$order    = wc_get_order( $order_id );
		}

		if ( $error ) {
			$error = isset( $error['code'] ) ? $error['code'] . ': ' . $error['message'] : $error['message'];

			if ( $success ) {

				if ( ! $order instanceof WC_Order ) {
					self::log( 'WeChat error - Order not found after payment.', 'error' );
				} else {
					self::log( 'Found order #' . $order_id . ' and change status to "failed".', 'error' );
					$order->update_status( 'failed', $error );
				}
			}

			self::log( 'Payment status: ' . $result['return_code'], 'error' );
		} elseif ( $order instanceof WC_Order && ! $order->meta_exists( 'wxpay_processed' ) ) {
			$order_nonce_str       = $order->get_meta( 'wechatpay_nonce_str' );
			$no_pay_order_statuses = array(
				'failed',
				'cancelled',
				'refunded',
			);

			if (
				! in_array( $order->get_status(), $no_pay_order_statuses, true ) &&
				$result['nonce_str'] === $order_nonce_str &&
				$this->wechat->getOrderInfo( $result['transaction_id'], true )
			) {
				self::log( 'Found order #' . $order_id );
				self::log( 'Payment status: ' . $result['return_code'] );
				$order->payment_complete( wc_clean( $result['transaction_id'] ) );
				$order->add_order_note( __( 'WeChat Pay payment completed', 'woo-wechatpay' ) );
				WC()->cart->empty_cart();
			} elseif ( $result['nonce_str'] !== $order_nonce_str ) {
				$error    = __( 'Invalid WeChat Response', 'woo-wechatpay' );
				$message .= $error .= ': mismatch nonce_str';
				$message .= ' ( ' . $order_nonce_str;
				$message .= ' vs. ' . $result['nonce_str'] . ' ).';

				$order->update_status( 'failed', $message );
				self::log( $message . ' Order status changed to "failed".', 'error' );
			}

			$order->update_meta_data( 'wxpay_processed', true );
			$order->save_meta_data();
		} elseif ( $success && ! $order instanceof WC_Order ) {
			$error = __( 'Invalid order ID', 'woo-wechatpay' );

			self::log( 'Order not found after payment.', 'error' );
		}

		if ( $order && ! $error ) {
			$sent = $this->send_templated_message( $order, $result, $pay_blog_id );

			if ( ! $sent ) {
				self::log(
					'Could not send templated message - Unified order ID: ' . $result['out_trade_no'] . ' ',
					'error'
				);
			}
		} elseif ( $error ) {

			$force = ! ( (bool) $order );

			self::log( "API request data: \n" . wc_print_r( $result, true ) . "\n", 'error', $force );
			self::log( 'Error: ' . $error, 'error' );
		}

		$this->pay_notify_result = array(
			'success'      => $success,
			'data'         => $result,
			'refund'       => $error,
			'notify_error' => ( $success ) ? false : $error,
			'blog_id'      => get_current_blog_id(),
			'pay_handler'  => $this->is_pay_handler,
			'order'        => $order,
		);

		if ( is_multisite() && $blog_id ) {
			restore_current_blog();
		}

		add_filter( 'wp_weixin_pay_notify_results', array( $this, 'add_pay_notify_result' ), 10, 1 );
	}

	public function add_pay_notify_result( $results ) {

		if ( ! empty( $this->pay_notify_result ) ) {
			$results[] = $this->pay_notify_result;
		}

		return $results;
	}

	public function order_hold() {
		$order_id = absint( filter_input( INPUT_POST, 'orderId', FILTER_SANITIZE_NUMBER_INT ) );
		$blog_id  = absint( filter_input( INPUT_POST, 'blogId', FILTER_SANITIZE_NUMBER_INT ) );
		$key      = filter_input( INPUT_POST, 'key', FILTER_SANITIZE_STRING );

		if ( is_multisite() && $blog_id ) {
			switch_to_blog( $blog_id );
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			$error = new WP_Error( __METHOD__, __( 'Invalid order ID', 'woo-wechatpay' ) );

			wp_send_json_error( $error );
		} elseif ( ! $order->key_is_valid( $key ) ) {
			$error = new WP_Error( __METHOD__, 'Unauthorised access' );

			wp_send_json_error( $error );
		}

		if ( 'pending' === $order->get_status() ) {
			$updated = $order->update_status( 'on-hold' );

			if ( ! $updated ) {
				$error = new WP_Error( __METHOD__, __( 'Update status event failed.', 'woocommerce' ) );

				wp_send_json_error( $error );
			}
		}

		wp_send_json_success();
	}

	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $order ) {

			if ( wp_weixin_is_wechat() ) {
				$current_blog_id = get_current_blog_id();
				$pay_blog_id     = apply_filters( 'wp_weixin_ms_pay_blog_id', $current_blog_id );

				if ( absint( $pay_blog_id ) !== absint( $current_blog_id ) ) {

					return array(
						'result'   => 'success',
						'redirect' => $this->get_pay_redirect_url( $order, $pay_blog_id, $current_blog_id ),
					);
				} elseif ( is_checkout_pay_page() && get_query_var( 'pay_for_order', false ) ) {
					$pay_redirect_url = $this->get_pay_redirect_url( $order );

					return array(
						'result'   => 'success',
						'redirect' => $pay_redirect_url,
					);
				} else {

					return $this->mobile_pay( $order_id, $current_blog_id );
				}
			} else {

				return array(
					'result'   => 'success',
					'redirect' => $order->get_checkout_payment_url( true ),
				);
			}
		}
	}

	public function add_mobile_pay_redirected_scripts() {
		$user   = wp_get_current_user();
		$openid = wp_weixin_get_user_wechat_openid( $user->ID );

		if ( $openid ) {
			$debug              = apply_filters( 'wp_weixin_debug', (bool) ( constant( 'WP_DEBUG' ) ) );
			$js_ext             = ( $debug ) ? '.js' : '.min.js';
			$order_id           = get_query_var( 'order_id', false );
			$key                = get_query_var( 'key', false );
			$blog_id            = get_query_var( 'blog_id', false );
			$script_name        = 'woo-wechatpay-mobile-redirected';
			$gateway_error_text = __( 'Payment gateway error. Please try again. If the problem persists, please contact an administrator.', 'woo-wechatpay' );
			$version            = filemtime( WOO_WECHATPAY_PLUGIN_PATH . 'js/' . $script_name . $js_ext );
			$wechat_data        = $this->mobile_pay( $order_id, $blog_id, true );
			$wechat_data        = array_merge(
				$wechat_data,
				array(
					'blogId'      => $blog_id,
					'orderId'     => $order_id,
					'debug'       => $debug,
					'ajax_url'    => admin_url( 'admin-ajax.php' ),
					'gatewayFail' => $gateway_error_text,
					'key'         => $key,
				)
			);

			if ( isset( $wechat_data['message'] ) && 'failed' === $wechat_data['message'] ) {
				$wechat_data['message'] = __( 'Invalid order ID', 'woo-wechatpay' );
			}

			wp_enqueue_script(
				$script_name,
				WOO_WECHATPAY_PLUGIN_URL . 'js/' . $script_name . $js_ext,
				array( 'jquery' ),
				$version
			);
			wp_localize_script( $script_name, 'wechatData', $wechat_data );
		}
	}

	public function handle_auto_refund( $refund_result, $notification_data ) {

		if ( $refund_result && isset( $notification_data['order'] ) && $notification_data['order'] ) {
			$notification_data['order']->update_status(
				'refunded',
				__(
					'NOTICE: WeChat Pay transaction failed - order automatically refunded.',
					'woo-wechatpay'
				)
			);
		} elseif ( isset( $notification_data['order'] ) && $notification_data['order'] ) {
			$notification_data['order']->add_order_note(
				__(
					'WARNING: WeChat Pay transaction failed - automatic refund failed!',
					'woo-wechatpay'
				)
			);
		}

		if (
			isset( $notification_data['pay_handler'] ) &&
			$this->pay_handler === $notification_data['pay_handler'] &&
			! $refund_result &&
			! isset( $notification_data['order'] ) &&
			isset(
				$notification_data['data'],
				$notification_data['data']['out_trade_no'],
				$notification_data['data']['transaction_id']
			)
		) {
			$out_trade_no_parts = explode( '-', str_replace( 'WooWP', '', $notification_data['data']['out_trade_no'] ) );
			$blog_id            = false;

			if ( is_multisite() ) {
				$blog_id = absint( array_shift( $out_trade_no_parts ) );

				switch_to_blog( $blog_id );
			}

			do_action(
				'woowechatpay_orphan_transaction_notification',
				absint( array_shift( $out_trade_no_parts ) ),
				$notification_data['data']['transaction_id'],
				WC_Log_Handler_File::get_log_file_path( $this->id ),
				'auto_refund_error'
			);

			if ( is_multisite() && $blog_id ) {
				restore_current_blog();
			}
		}
	}

	/*******************************************************************
	 * Protected methods
	 *******************************************************************/

	protected function is_woowechatpay_enabled() {
		$wechat_options = get_option( 'woocommerce_wechatpay_settings' );

		return ( 'yes' === $wechat_options['enabled'] );
	}

	protected function mobile_pay( $order_id, $blog_id, $redirect_on_failure = false ) {
		wp_weixin_ajax_safe();

		if ( is_multisite() && false !== $blog_id ) {
			switch_to_blog( $blog_id );
		}

		$order   = wc_get_order( $order_id );
		$failure = array(
			'result'  => 'failure',
			'message' => 'failed',
		);

		if ( ! $order ) {

			if ( is_multisite() && false !== $blog_id ) {
				restore_current_blog();
			}

			return $failure;
		}

		$total_fee  = $this->maybe_convert_amount( $order->get_total() );
		$openid     = $this->get_current_user_openid();
		$body       = get_bloginfo( 'name' ) . ' - #' . $order_id;
		$notify_url = WC()->api_request_url( 'WC_WechatPay' );
		$extend     = $this->get_unified_order_extend( $order, $total_fee );
		$return_url = $this->get_return_url( $order );
		$shop_url   = esc_url( get_permalink( wc_get_page_id( 'shop' ) ) );

		if ( is_multisite() && false !== $blog_id ) {
			$unified_order_id = 'WooWP' . $blog_id . '-' . $order_id . '-' . current_time( 'timestamp' );
		} else {
			$unified_order_id = 'WooWP' . $order_id . '-' . current_time( 'timestamp' );
		}

		$order->update_meta_data( 'unified_order_id', $unified_order_id );
		$order->save_meta_data();

		if ( is_multisite() && false !== $blog_id ) {
			restore_current_blog();
		}

		$result = $this->wechat->unifiedOrder(
			$openid,
			$body,
			$unified_order_id,
			$total_fee,
			$notify_url,
			$extend
		);
		$error  = $this->wechat->getError();

		if ( is_multisite() && false !== $blog_id ) {
			switch_to_blog( $blog_id );
		}

		if ( $error ) {
			$prefix                  = __( 'Error processing checkout. Please try again.', 'woocommerce' );
			$suffix                  = __( 'Place a new order and try again.', 'woo-wechatpay' );
			$failure['FailedPayUrl'] = $return_url;
			$failure['message']      = $prefix . "\n" . $error['message'] . "\n" . $suffix;
			$message                 = $prefix . '<br/>' . $error['code'] . ' ' . $error['message'] . '<br/>';
			$message                .= '<a href="' . $shop_url;
			$message                .= '" class="button wc-forward">' . __( 'Return to shop', 'woocommerce' );
			$message                .= '</a>' . $suffix;

			WC()->cart->empty_cart();
			$order->update_status( 'failed', $error['message'] );
			self::log( __METHOD__ . ': ' . wc_print_r( $error, true ), 'error' );
			wc_add_notice( $message, 'error' );

			if ( is_multisite() && false !== $blog_id ) {
				restore_current_blog();
			}

			return $failure;
		}

		global $sitepress;

		if ( $sitepress && get_query_var( 'lang_check', false ) ) {
			$language = esc_attr( get_query_var( 'lang_check', false ) );

			if ( $language ) {
				$sitepress->switch_lang( $language );
			}
		}

		$params = array(
			'key'      => $order->get_order_key(),
			'orderId'  => $order->get_id(),
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		);

		if ( $redirect_on_failure ) {
			$params['FailedPayUrl'] = $return_url;
		}

		if ( is_multisite() && false !== $blog_id ) {
			restore_current_blog();
		}

		$params = array_merge( $params, $this->get_js_api_params( $result, $return_url ) );

		return $params;
	}

	protected function send_templated_message( $order, $payment_result, $pay_blog_id = false ) {
		$template_id = $this->get_option( 'order_template_id' );

		if ( empty( $template_id ) ) {

			return;
		}

		global $sitepress;

		$openid = $payment_result['openid'];

		if ( $sitepress ) {
			$user_info = $this->wechat->user( $openid );
			$error     = $this->wechat->getError();

			if ( $error ) {
				self::log( __METHOD__ . wc_print_r( $error, true ), 'error' );
			} else {
				$language = isset( $user_info['language'] ) ? $user_info['language'] : false;

				if ( $sitepress ) {
					$language = $sitepress->get_language_code_from_locale( $language );

					if ( $language ) {
						$sitepress->switch_lang( $language );
					}
				}
			}
		}

		$order_items   = $order->get_items();
		$products_info = array();
		$url           = home_url( wc_get_endpoint_url( 'my-account/orders/' ) );
		$first_text    = __( 'Thank you for your order!', 'woo-wechatpay' );
		$cta           = __( 'Open to view your orders.', 'woo-wechatpay' );
		$remark_text   = '';

		foreach ( $order_items as $item ) {

			if ( $item instanceof WC_Order_Item_Product ) {
				$products_info[] = $item->get_name() . ' X ' . $item->get_quantity();
			}
		}

		$first_text    = apply_filters( 'woo_wechatpay_templated_message_intro_text', $first_text, $order );
		$cta           = apply_filters( 'woo_wechatpay_templated_message_cta_text', $cta, $order );
		$remark_text   = apply_filters( 'woo_wechatpay_templated_message_remark_text', $remark_text, $order );
		$url           = apply_filters( 'woo_wechatpay_templated_message_url', $url, $order );
		$products_info = apply_filters(
			'woo_wechatpay_templated_message_product_info_text',
			implode( ', ', $products_info ),
			$order
		);
		// translators: 1: remark text 2: remark call to action
		$remark = sprintf( __( '%1$s %2$s', 'woo-wechatpay' ), $remark_text, $cta );

		$parameters = array(
			'touser'      => $openid,
			'template_id' => $template_id,
			'url'         => $url,
			'topcolor'    => apply_filters( 'woo_wechatpay_templated_message_title_color', '#666' ),
			'data'        => array(
				'first'    => array(
					'value' => $first_text,
					'color' => apply_filters( 'woo_wechatpay_templated_message_intro_color', '#173177' ),
				),
				'keyword1' => array(
					'value' => '#' . $order->get_id(),
					'color' => apply_filters( 'woo_wechatpay_templated_message_order_num_color', '#173177' ),
				),
				'keyword2' => array(
					'value' => $products_info,
					'color' => apply_filters( 'woo_wechatpay_templated_message_product_info_color', '#173177' ),
				),
				'keyword3' => array(
					'value' => $order->get_total(),
					'color' => apply_filters( 'woo_wechatpay_templated_message_order_total_color', '#173177' ),
				),
				'remark'   => array(
					'value' => $remark,
					'color' => apply_filters( 'woo_wechatpay_templated_message_remark_color', '#173177' ),
				),
			),
		);

		$parameters = apply_filters( 'woo_wechatpay_templated_message', $parameters, $payment_result, $order );

		if ( is_multisite() && $pay_blog_id ) {
			switch_to_blog( $pay_blog_id );
		}

		$sent = $this->wechat->sendTemplate( $parameters );

		if ( is_multisite() && $wechat_blog_id ) {
			restore_current_blog();
		}

		do_action( 'woo_wechatpay_templated_message_sent', $sent, $parameters );

		return $sent;
	}

	protected function get_current_user_openid() {
		$user   = wp_get_current_user();
		$openid = wp_weixin_get_user_wechat_openid( $user->ID );

		if ( empty( $openid ) ) {
			$message = __( 'User is authenticated in WeChat browser but openid cannot be found', 'woo-wechatpay' );

			throw new WechatException( $message );
		}

		return $openid;
	}

	protected function get_js_api_params( $unified_order_result, $return_url ) {
		$parameters = json_decode( $unified_order_result['payment_params'], true );
		$is_error   = ! array_key_exists( 'appId', $parameters );
		$is_error   = $is_error || ! array_key_exists( 'prepay_id', $unified_order_result );
		$is_error   = $is_error || '' === $unified_order_result['prepay_id'];

		if ( $is_error ) {
			throw new WechatPayException( 'Invalid parameters' );
		}

		$parameters['result']        = 'success';
		$parameters['type']          = 'wechatPayMobile';
		$parameters['SuccessPayUrl'] = $return_url;

		return $parameters;
	}

	protected function get_pay_redirect_url( $order, $pay_blog_id = false, $current_blog_id = false ) {
		global $sitepress;

		if ( $pay_blog_id ) {
			$pay_redirect_url = get_home_url( $pay_blog_id, '/wxpayagain/' );
			// $pay_redirect_url = get_home_url( $pay_blog_id, '/woo-wechat-pay/redirect/' );
			$pay_redirect_url = add_query_arg( 'blog-id', $current_blog_id, $pay_redirect_url );
		} else {
			$pay_redirect_url = home_url( '/wxpayagain/' );
			// $pay_redirect_url = home_url( '/woo-wechat-pay/redirect/' );
		}

		$pay_redirect_url = add_query_arg( 'oid', $order->get_id(), $pay_redirect_url );
		$pay_redirect_url = add_query_arg( 'key', $order->get_order_key(), $pay_redirect_url );

		if ( $sitepress ) {
			$current_lang     = apply_filters( 'wpml_current_language', null );
			$pay_redirect_url = add_query_arg( 'lang_check', $current_lang, $pay_redirect_url );
		}

		return $pay_redirect_url;
	}

	protected function generate_qr( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {

			return '';
		}

		$url        = $this->get_qr_code_uri( $order );
		$return_url = $this->get_return_url( $order );
		$base_qr    = $this->notify_url . '?QRData=';
		$error      = $this->wechat->getError();

		if ( $error ) {
			self::log( __METHOD__ . ': ' . wc_print_r( $error, true ), 'error' );
			$order->update_status( 'failed', $error['message'] );
			WC()->cart->empty_cart();

			$error = __( 'The order has failed. Reason: ', 'woo-wechatpay' ) . $error['message'];
		} elseif ( empty( $url ) ) {
			$status_message = __( 'QR code url is empty', 'woo-wechatpay' );

			$order->update_status( 'failed', $status_message );
			WC()->cart->empty_cart();

			$error = __( 'The order has failed. Reason: ', 'woo-wechatpay' );
		}

		set_query_var( 'qr_url', $base_qr . $url );
		set_query_var( 'qr_img_header', $this->qr_img_header );
		set_query_var( 'qr_img_footer', $this->qr_img_footer );
		set_query_var( 'qr_phone_bg', $this->qr_phone_bg );
		set_query_var( 'qr_placeholder', $this->qr_placeholder );
		set_query_var( 'has_result', ( ! $this->wechat->getError() && ! empty( $url ) ) );
		set_query_var( 'order_id', $order_id );
		set_query_var( 'error', $error );

		ob_start();
		WP_Weixin::locate_template( 'computer-pay-qr.php', true, true, 'woo-wechatpay' );

		$html = ob_get_clean();

		echo $html; // WPCS: XSS OK
	}

	protected function get_qr_code_uri( $order ) {
		$total_fee = $this->maybe_convert_amount( $order->get_total() );
		$extend    = $this->get_unified_order_extend( $order, $total_fee );

		if ( is_multisite() ) {
			$blog_id          = get_current_blog_id();
			$unified_order_id = 'WooWP' . $blog_id . '-' . $order->get_id() . '-' . current_time( 'timestamp' );
		} else {
			$unified_order_id = 'WooWP' . $order->get_id() . '-' . current_time( 'timestamp' );
		}

		$order->update_meta_data( 'unified_order_id', $unified_order_id );
		$order->save_meta_data();

		return $this->wechat->webUnifiedOrder(
			$order->get_id(),
			get_bloginfo( 'name' ) . ' - #' . $order->get_id(),
			$unified_order_id,
			$this->maybe_convert_amount( $order->get_total() ),
			$this->notify_url,
			$extend
		);
	}

	protected function get_unified_order_extend( $order, $total_fee ) {
		$products_info = array();
		$order_items   = $order->get_items();
		$date          = new DateTime( '@' . current_time( 'timestamp' ) );
		$update_meta   = false;
		$details       = array(
			'cost_price'   => $total_fee,
			'receipt_id'   => 'wx' . current_time( 'timestamp' ),
			'goods_detail' => array(),
		);

		foreach ( $order_items as $item ) {

			if ( $item instanceof WC_Order_Item_Product ) {
				$id = $item->get_product_id();

				if ( isset( $products_info[ $id ] ) ) {
					$products_info[ $id ]['quantity'] += absint( $item->get_quantity() );
					$products_info[ $id ]['price']    += (float) $item->get_subtotal() + (float) $item->get_subtotal_tax();
				} else {
					$products_info[ $id ] = array(
						'quantity' => absint( $item->get_quantity() ),
						'name'     => $item->get_name(),
						'price'    => (float) $item->get_subtotal() + (float) $item->get_subtotal_tax(),
					);
				}
			}
		}

		foreach ( $products_info as $id => $values ) {
			$details[] = array(
				'goods_id'   => $id,
				'goods_name' => $values['name'],
				'quantity'   => $values['quantity'],
				'price'      => $this->maybe_convert_amount( $values['price'] ),
			);
		}

		if ( $order->meta_exists( 'wechatpay_nonce_str' ) ) {
			$nonce_str = $order->get_meta( 'wechatpay_nonce_str' );
		} else {
			$nonce_str   = Wechat_SDK::getNonceStr();
			$update_meta = true;

			$order->update_meta_data( 'wechatpay_nonce_str', $nonce_str );
		}

		if (
			$order->meta_exists( 'wechatpay_start_time' ) && $order->meta_exists( 'wechatpay_expired_time' ) ) {
			$start_time   = $order->get_meta( 'wechatpay_start_time' );
			$expired_time = $order->get_meta( 'wechatpay_expired_time' );
		} else {
			$date->setTimezone( new DateTimeZone( 'Asia/Shanghai' ) );

			$start_time   = $date->format( 'YmdHis' );
			$expired_time = date( 'YmdHis', strtotime( '+5 hours', strtotime( $start_time ) ) );
			$update_meta  = true;

			$order->update_meta_data( 'wechatpay_start_time', $start_time );
			$order->update_meta_data( 'wechatpay_expired_time', $expired_time );
		}

		$extend = array(
			'time_start'  => $start_time,
			'time_expire' => $expired_time,
			'nonce_str'   => $nonce_str,
			'detail'      => $this->wechat->json_encode( $details ),
		);

		return $extend;
	}

	protected function setup_form_fields() {
		$order_template_id_fields = '';

		$description = __( 'Leave blank if unused.', 'woo-wechatpay' );

		$description .= '<br/>';
		// translators: %1$s is the URL https://mp.weixin.qq.com
		$description .= sprintf( __( 'ID of a template added in the backend at %1$s.', 'woo-wechatpay' ), '<a href="https://mp.weixin.qq.com" target="_blank">https://mp.weixin.qq.com</a>' );
		$description .= '<br/>';
		$description .= __( 'Used to send a templated message to the follower after successful purchase.', 'woo-wechatpay' );
		$description .= '<br/>';

		if ( ! has_filter( 'woo_wechatpay_templated_message' ) ) {
			$description .= '<br/>';
			$description .= __( 'Make sure to use a template that includes the following fields:', 'woo-wechatpay' );

			$order_template_id_fields .= '<ul id="woowechatpay_templated_message_fields_desc">';
			$order_template_id_fields .= '<li>first => ' . __( 'Contains intro message', 'wp-weixin' ) . '</li>';
			$order_template_id_fields .= '<li>keyword1 => ' . __( 'Contains order ID', 'wp-weixin' ) . '</li>';
			$order_template_id_fields .= '<li>keyword2 => ' . __( 'Contains products info', 'wp-weixin' ) . '</li>';
			$order_template_id_fields .= '<li>keyword3 => ' . __( 'Contains order total price', 'wp-weixin' ) . '</li>';
			$order_template_id_fields .= '<li>remark => ' . __( 'Contains misc information', 'wp-weixin' ) . '</li>';
			$order_template_id_fields .= '</ul>';
		}

		$this->form_fields = array(
			'enabled'           => array(
				'title'   => __( 'Enable/Disable', 'woo-wechatpay' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable WeChat Pay', 'woo-wechatpay' ),
				'default' => 'no',
			),
			'title'             => array(
				'title'   => __( 'Checkout page title', 'woo-wechatpay' ),
				'type'    => 'text',
				'default' => __( 'WeChat Pay', 'woo-wechatpay' ),
			),
			'description'       => array(
				'title'   => __( 'Checkout page description', 'woo-wechatpay' ),
				'type'    => 'textarea',
				'default' => __( 'Pay via WeChat. If you are unable to pay with a WeChat account, please select a different payment method.', 'woo-wechatpay' ),
			),
			'order_template_id' => array(
				'title'       => __( 'WeChat Order Notification Template ID', 'woo-wechatpay' ),
				'type'        => 'text',
				'description' => $description . $order_template_id_fields,
			),
			'mobile_h5'         => array(
				'title'       => __( 'H5 payment in mobile web browsers', 'woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable', 'woocommerce' ),
				'default'     => 'no',
				'description' => __( 'If checked, customers can place an order via their mobile browsers and select WeChat Pay on their phone: instead of a QR code, WeChat Pay will be automatically called to complete the payment. This feature requires to be activated and approved in the backend at <a href="https://pay.weixin.qq.com/index.php/extend/pay_apply/apply_normal_h5_pay" target="_blank">https://pay.weixin.qq.com/index.php/extend/pay_apply/apply_normal_h5_pay</a> beforehand.', 'woo-wechatpay' ),
			),
			'debug'             => array(
				'title'       => __( 'Debug log', 'woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', 'woocommerce' ),
				'default'     => 'no',
				/* translators: %s: URL */
				'description' => sprintf( __( 'Log WeChat Pay events inside %s Note: this may log personal information. We recommend using this for debugging purposes only and deleting the logs when finished.', 'woo-wechatpay' ), '<code>' . WC_Log_Handler_File::get_log_file_path( 'wechatpay' ) . '</code>' ),
			),
		);

		if ( ! in_array( $this->current_currency, $this->supported_currencies, true ) ) {
			$description = sprintf(
				// translators: %1$s is the currency
				__( 'Set the %1$s against Chinese Yuan exchange rate <br/>(1 %1$s = [field value] Chinese Yuan)', 'woo-wechatpay' ),
				$this->current_currency
			);

			$this->form_fields['exchange_rate'] = array(
				'title'       => __( 'Exchange Rate', 'woo-wechatpay' ),
				'type'        => 'number',
				'description' => $description,
				'css'         => 'width: 80px;',
				'desc_tip'    => true,
			);
		}
	}

	protected function maybe_convert_amount( $amount ) {
		$exchange_rate    = $this->get_option( 'exchange_rate' );
		$current_currency = get_option( 'woocommerce_currency' );

		if (
			! in_array( $current_currency, $this->supported_currencies, true ) &&
			is_numeric( $exchange_rate )
		) {
			$amount = (int) ( $amount * 100 );
			$amount = round( $amount * $exchange_rate, 2 );
			$amount = round( ( $amount / 100 ), 2 );
		}

		return $amount;
	}

	protected static function log( $message, $level = 'info', $force = false ) {

		if ( self::$log_enabled || $force ) {

			if ( empty( self::$log ) ) {
				self::$log = wc_get_logger();
			}

			self::$log->log( $level, $message, array( 'source' => 'wechatpay' ) );
		}
	}

}
