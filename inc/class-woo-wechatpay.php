<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Woo_WechatPay {

	protected $wc_wechatpay;
	protected $wp_weixin_auth;

	protected static $core_min_version;
	protected static $core_max_version;

	public function __construct( $wc_wechatpay, $wp_weixin_auth, $init_hooks = false ) {
		$this->wp_weixin_auth = $wp_weixin_auth;

		if ( ! $wc_wechatpay || ! wp_weixin_get_option( 'ecommerce' ) ) {

			if ( $init_hooks ) {
				add_action( 'admin_notices', array( $this, 'missing_configuration' ) );
			}
		} else {
			$this->wc_wechatpay = $wc_wechatpay;
			$plugin_base_name   = plugin_basename( WOO_WECHATPAY_PLUGIN_PATH );

			if ( $init_hooks ) {
				// Add translation
				add_action( 'init', array( $this, 'load_textdomain' ), 0, 0 );
				// Add core version checks
				add_action( 'init', array( $this, 'check_wp_weixin_version' ), 0, 0 );
				// Add main scripts & styles
				add_action( 'wp_enqueue_scripts', array( $this, 'add_frontend_scripts' ), 10, 0 );
				// Add wechatpay page endpoint
				add_action( 'wp_weixin_endpoints', array( $this, 'add_endpoints' ), 10, 0 );
				// Add wechatpay page endpoint actions
				add_action( 'parse_request', array( $this, 'parse_request' ), 10, 0 );
				// Add admin scripts
				add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ), 99, 1 );
				// Add a setting to toggle force following the Official Account before checkout and account pages
				add_filter( 'wp_weixin_settings_fields', array( $this, 'settings_fields' ), 10, 1 );
				// Alter the WP Weixin settings
				add_filter( 'wp_weixin_settings', array( $this, 'wp_weixin_settings' ), 10, 1 );
				// Show follow Official Account before checkout and on account page
				add_action( 'wp', array( $this, 'force_follow' ), 10, 0 );

				// Add wechat payment gateway
				add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ), 10, 1 );
				// Add wechat payment gateway settings page
				add_filter( 'plugin_action_links_' . $plugin_base_name, array( $this, 'plugin_edit_link' ), 10, 1 );
				// Display wechat transction number on order page
				add_filter( 'woocommerce_get_order_item_totals', array( $this, 'display_order_meta_for_customer' ), 10, 2 );
				// Add main query vars
				add_filter( 'query_vars', array( $this, 'add_query_vars' ), 10, 1 );
				// Add Alipay orphan transactions email notification
				add_filter( 'woocommerce_email_classes', array( $this, 'add_orphan_transaction_woocommerce_email' ), 10, 1 );

				if ( ! wp_weixin_is_wechat() ) {
					// Add wechat payment listener
					add_action( 'wp_ajax_woowechatpay_payment_heartbeat', array( $this->wc_wechatpay, 'payment_heartbeat_pulse' ), 10, 0 );
					add_action( 'wp_ajax_nopriv_woowechatpay_payment_heartbeat', array( $this->wc_wechatpay, 'payment_heartbeat_pulse' ), 10, 0 );
					// Empty cart on payment confirmation
					add_action( 'woocommerce_receipt_wechatpay', array( $this->wc_wechatpay, 'receipt_page' ), 10, 1 );
				}
			}
		}

		if ( $init_hooks && ! wp_weixin_is_wechat() ) {
			// Show settings section
			add_filter( 'wp_weixin_show_settings_section', array( $this, 'show_section' ), 10, 3 );
			// Add WeChat JSAPI urls help
			add_filter( 'wp_weixin_jsapi_urls', array( $this, 'wechat_jsapi_urls' ), 10, 1 );
			// Add payment notification endpoint help
			add_filter( 'wp_weixin_pay_callback_endpoint', array( $this, 'pay_notification_endpoint' ), PHP_INT_MAX - 5, 1 );
		}
	}

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	public static function activate() {
		set_transient( 'woo_wechatpay_flush', 1, 60 );
		wp_cache_flush();

		if ( ! get_option( 'woo_wechatpay_plugin_version' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';

			$plugin_data = get_plugin_data( WOO_WECHATPAY_PLUGIN_FILE );
			$version     = $plugin_data['Version'];

			update_option( 'woo_wechatpay_plugin_version', $version );
		}
	}

	public static function deactivate() {
		global $wpdb;

		$prefix = $wpdb->esc_like( '_transient_woo_wechatpay_' );
		$sql    = "DELETE FROM $wpdb->options WHERE `option_name` LIKE '%s'";

		$wpdb->query( $wpdb->prepare( $sql, $prefix . '%' ) ); // @codingStandardsIgnoreLine
	}

	public static function uninstall() {
		require_once WOO_WECHATPAY_PLUGIN_PATH . 'uninstall.php';
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'woo-wechatpay', false, 'woo-wechatpay/languages' );
	}

	public function check_wp_weixin_version() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$core_plugin_data       = get_plugin_data( WP_WEIXIN_PLUGIN_FILE );
		$plugin_data            = get_plugin_data( WOO_WECHATPAY_PLUGIN_FILE );
		$core_version           = $core_plugin_data['Version'];
		$current_version        = $plugin_data['Version'];
		self::$core_min_version = defined('WP_WEIXIN_PLUGIN_FILE') ? $plugin_data[ WP_Weixin::VERSION_REQUIRED_HEADER ] : 0;
		self::$core_max_version = defined('WP_WEIXIN_PLUGIN_FILE') ? $plugin_data[ WP_Weixin::VERSION_TESTED_HEADER ] : 0;

		if (
			! version_compare( $current_version, self::$core_min_version, '>=' ) ||
			! version_compare( $current_version, self::$core_max_version, '<=' )
		) {
			add_action( 'admin_notices', array( 'Woo_WechatPay', 'core_version_notice' ) );
			deactivate_plugins( WOO_WECHATPAY_PLUGIN_FILE );
		}
	}

	public static function core_version_notice() {
		$class   = 'notice notice-error is-dismissible';
		$message = 	sprintf(
			__(
				// translators: WP Weixin requirements - %1$s is the minimum version, %2$s is the maximum
				'Woo WeChatPay has been disabled: it requires WP Weixin with version between %1$s and %2$s to be activated.',
				'woo-wechatpay'
			),
			self::$core_min_version,
			self::$core_max_version
		);

		echo sprintf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); // WPCS: XSS ok
	}

	public function add_query_vars( $vars ) {
		global $sitepress;

		$vars[] = 'wxpayagain';
		$vars[] = 'wxpaycrossdomain';
		$vars[] = 'pay_for_order';
		$vars[] = 'blog-id';
		$vars[] = 'key';
		$vars[] = 'oid';

		if ( $sitepress ) {
			$vars[] = 'lang_check';
		}

		return $vars;
	}

	public function add_endpoints() {
		add_rewrite_rule(
			// '^woo-wechat-pay/redirect$',
			'^wxpayagain$',
			'index.php?wxpayagain=1',
			'top'
		);

		if ( get_transient( 'woo_wechatpay_flush' ) ) {
			delete_transient( 'woo_wechatpay_flush' );
			flush_rewrite_rules();
		}
	}

	public function parse_request() {
		global $wp;

		if ( isset( $wp->query_vars['wxpayagain'] ) && WP_Weixin::is_wechat_mobile() ) {
			$order_id  = isset( $wp->query_vars['oid'] ) ? absint( $wp->query_vars['oid'] ) : false;
			$blog_id   = isset( $wp->query_vars['blog-id'] ) ? absint( $wp->query_vars['blog-id'] ) : false;
			$order_key = isset( $wp->query_vars['key'] ) ? $wp->query_vars['key'] : false;

			if ( $order_id ) {
				$title   = '<h2>' . __( 'System error.', 'woo-wechatpay' ) . '</h2>';
				$message = '';

				if ( is_multisite() && $blog_id ) {
					switch_to_blog( $blog_id );
				}

				$order = wc_get_order( $order_id );
				$user  = wp_get_current_user();

				if ( ! $order ) {
					$message = sprintf(
						// translators: %1$d is Orded ID, %2$d is the blog of origin of the order
						__( 'Order #%1$s could not be found on blog ID %2$d.', 'woo-wechatpay' ),
						$order_id,
						$blog_id
					);
				} elseif ( absint( $order->get_user_id() ) !== $user->ID ) {
					$message = sprintf(
						// translators: %1$d is Orded ID
						__( 'You cannot pay order #%1$s as it appears it belongs to another user.', 'woo-wechatpay' ),
						$order_id
					);
				} elseif ( ! $order->key_is_valid( $order_key ) ) {
					$message = sprintf(
						// translators: %1$d is Orded ID
						__( 'You cannot pay order #%1$s - unauthorised access.', 'woo-wechatpay' ),
						$order_id
					);

					if ( apply_filters( 'wp_weixin_debug', (bool) ( constant( 'WP_DEBUG' ) ) ) ) {
						$message .= '<br/>"' . $order_key . '" (bad) vs. "' . $order->get_order_key() . '" (good)';
					}
				}

				if ( $message ) {
					$message  = '<p>' . $message . '<br/>';
					$message .= __( 'If the problem persists, please contact an administrator.', 'woo-wechatpay' ) . '</p>';

					wp_die( $title . $message ); // WPCS: XSS ok
				}

				if ( is_multisite() && $blog_id ) {
					restore_current_blog();
				}
			}

			set_query_var( 'order_id', $order_id );
			set_query_var( 'blog_id', $blog_id );
			set_query_var( 'key', $order_key );

			$this->wc_wechatpay->add_mobile_pay_redirected_scripts();

			remove_all_actions( 'wp_footer' );
			remove_all_actions( 'shutdown' );

			add_action( 'wp_footer', 'wp_print_footer_scripts', 20 );
			add_action( 'shutdown', 'wp_ob_end_flush_all', 1 );

			WP_Weixin::$scripts[] = 'wp-weixin-main-script';
			WP_Weixin::$scripts[] = 'wechat-api-script';
			WP_Weixin::$scripts[] = 'woo-wechatpay-mobile-redirected';
			WP_Weixin::$styles[]  = 'wp-weixin-main-style';
			WP_Weixin::$styles[]  = 'woo-wechatpay-main-style';

			add_action( 'wp_print_scripts', array( 'WP_Weixin', 'remove_all_scripts' ), 100 );
			add_action( 'wp_print_styles', array( 'WP_Weixin', 'remove_all_styles' ), 100 );

			add_action( 'template_redirect', array( $this, 'redirected_payment_page' ), 0, 0 );
		}
	}

	public function redirected_payment_page() {
		WP_Weixin::locate_template( 'redirected-pay.php', true, true, 'woo-wechatpay' );

		exit();
	}

	public function add_frontend_scripts() {
		$debug   = apply_filters( 'wp_weixin_debug', (bool) ( constant( 'WP_DEBUG' ) ) );
		$css_ext = ( $debug ) ? '.css' : '.min.css';
		$version = filemtime( WOO_WECHATPAY_PLUGIN_PATH . 'css/main' . $css_ext );

		wp_enqueue_style( 'woo-wechatpay-main-style', WOO_WECHATPAY_PLUGIN_URL . 'css/main.css', array(), $version );
	}

	public function add_admin_scripts( $hook ) {

		if ( 'woocommerce_page_wc-settings' === $hook ) {
			$debug   = apply_filters( 'wp_weixin_debug', (bool) ( constant( 'WP_DEBUG' ) ) );
			$css_ext = ( $debug ) ? '.css' : '.min.css';
			$version = filemtime( WOO_WECHATPAY_PLUGIN_PATH . 'css/admin/main' . $css_ext );

			wp_enqueue_style(
				'woo-wechatpay-main-style',
				WOO_WECHATPAY_PLUGIN_URL . 'css/admin/main' . $css_ext,
				array(),
				$version
			);
		}
	}

	public function add_gateway( $methods ) {
		$methods[] = 'WC_WechatPay';

		return $methods;
	}

	public function plugin_edit_link( $links ) {
		$url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=woo_wechatpay' );

		return array_merge(
			array(
				'settings' => '<a href="' . $url . '">' . __( 'Settings', 'woo-wechatpay' ) . '</a>',
			),
			$links
		);
	}

	public function display_order_meta_for_customer( $total_rows, $order ) {
		$trade_no = $order->get_transaction_id();

		if ( ! empty( $trade_no ) && $order->get_payment_method() === 'wechatpay' ) {
			$new_row = array(
				'wechatpay_trade_no' => array(
					'label' => __( 'Transaction:', 'woo-wechatpay' ),
					'value' => $trade_no,
				),
			);

			$total_rows = array_merge( array_splice( $total_rows, 0, 2 ), $new_row, $total_rows );
		}

		return $total_rows;
	}

	public function missing_configuration() {
		$class   = 'notice notice-error is-dismissible';
		$message = __( 'Woo WeChatPay: Please make sure the plugin WP Weixin is activated, enabled and configured properly.', 'woo-wechatpay' );

		echo sprintf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); // WPCS: XSS OK
	}

	public function show_section( $include_section, $section_name, $value ) {

		if ( 'ecommerce' === $section_name ) {
			$include_section = true;
		}

		return $include_section;
	}

	public function wechat_jsapi_urls( $jsapi_urls ) {
		$current_blog_id = get_current_blog_id();
		$pay_blog_id     = apply_filters( 'wp_weixin_ms_pay_blog_id', $current_blog_id );

		if ( is_multisite() && $pay_blog_id !== $current_blog_id ) {
			switch_to_blog( $pay_blog_id );
		}

		global $sitepress;

		$default_language = '';

		if (
			$sitepress &&
			( WPML_LANGUAGE_NEGOTIATION_TYPE_DIRECTORY === (int) $sitepress->get_setting( 'language_negotiation_type' ) )
		) {
			$default_language = apply_filters( 'wpml_default_language', null );
		}

		$checkout_url = strtok( home_url( wc_get_endpoint_url( 'checkout' ) ), '?' );
		$jsapi_urls[] = $checkout_url;

		if ( $sitepress ) {
			$page_id      = wc_get_page_id( 'checkout' );
			$trid         = $sitepress->get_element_trid( $page_id );
			$translations = ( $trid ) ? $sitepress->get_element_translations( $trid, 'post_post', true ) : false;

			if ( ! empty( $translations ) ) {

				foreach ( $translations as $key => $translation ) {

					if ( $translation->language_code !== $default_language ) {
						$url = urldecode( strtok( get_permalink( absint( $translation->element_id ) ), '?' ) );

						if ( $url !== $checkout_url ) {
							$jsapi_urls[] = $url;
						}
					}
				}
			}
		}
		$jsapi_urls[] = home_url( 'wxpayagain/' );
		// $jsapi_urls[] = home_url( 'woo-wechat-pay/redirect/' );

		if ( is_multisite() && $pay_blog_id !== $current_blog_id ) {
			restore_current_blog();
		}

		return $jsapi_urls;
	}

	public function pay_notification_endpoint( $endpoint ) {

		return 'wc-api/WC_WechatPay/';
	}

	public function force_follow() {

		if ( function_exists( 'is_checkout' ) && function_exists( 'is_account_page' ) ) {

			if ( is_checkout() || is_account_page() ) {
				$this->wp_weixin_auth->maybe_force_follow( wp_weixin_get_option( 'ecommerce_force_follower' ) );
			}
		}
	}

	public function wp_weixin_settings( $settings ) {

		if ( isset( $settings['ecommerce_force_follower'] ) ) {
			$settings['ecommerce_force_follower'] = (bool) $settings['ecommerce_force_follower'];
		}

		return $settings;
	}

	public function settings_fields( $fields ) {
		$extra_fields = array(
			array(
				'id'    => 'ecommerce_force_follower',
				'label' => __( 'Force follow (user account and checkout pages)', 'woo-wechatpay' ),
				'type'  => 'checkbox',
				'class' => '',
				'help'  => __( 'Require the user to follow the Official Account before accessing the checkout and user account pages with the WeChat browser (except administrators and admin interface).', 'woo-wechatpay' ),
			),
		);

		array_splice( $fields['ecommerce'], 1, 0, $extra_fields );

		return $fields;
	}

	public function add_orphan_transaction_woocommerce_email( $email_classes ) {
		require_once WOO_WECHATPAY_PLUGIN_PATH . 'inc/class-wc-email-wechatpay-orphan-transaction.php';

		$email_classes['WC_Email_WechatPay_Orphan_Transaction'] = new WC_Email_WechatPay_Orphan_Transaction();

		return $email_classes;
	}

}
