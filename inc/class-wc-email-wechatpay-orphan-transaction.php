<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Email_WechatPay_Orphan_Transaction extends WC_Email {

	protected $template_variables;

	public function __construct() {
		$this->id             = 'wechatpay_orphan_transaction';
		$this->title          = __( 'WeChat Pay Orphan Transaction', 'woo-wechatpay' );
		$this->description    = __( 'WeChat Pay Orphan Transaction emails are sent when an WeChat Pay transaction has no counterpart in WooCommerce and could not be refunded automatically.', 'woo-wechatpay' );
		$this->template_html  = 'emails/wechatpay-orphan-transaction.php';
		$this->template_plain = 'emails/plain/wechatpay-orphan-transaction.php';
		$this->template_base  = WOO_WECHATPAY_PLUGIN_PATH . 'inc/templates/';
		$this->recipient      = $this->get_option( 'recipient' );
		$this->placeholders   = array(
			'{transaction_number}' => '',
			'{order_number}'       => '',
		);

		// Add trigger action
		add_action( 'woowechatpay_orphan_transaction_notification', array( $this, 'trigger' ), 10, 4 );

		// Add WPML translation filters
		add_filter( 'wcml_emails_options_to_translate', array( $this, 'options_to_translate' ), 10, 1 );
		add_filter( 'wcml_emails_section_name_to_translate', array( $this, 'section_name_to_translate' ), 10, 1 );

		parent::__construct();

		if ( ! $this->recipient ) {
			$this->recipient = get_option( 'admin_email' );
		}
	}

	public function get_default_subject() {

		return __( '[{site_title}]: Order #{order_number} was not found but an WeChat Pay transaction was registered', 'woo-wechatpay' );
	}

	public function get_default_heading() {

		return __( 'Orphan order: #{order_number} - WeChat Pay transaction: #{transaction_id}', 'woo-wechatpay' );
	}

	public function trigger( $order_id, $transaction_id, $log_path, $type ) {
		$this->setup_locale();

		$this->placeholders['{order_number}']       = $order_id;
		$this->placeholders['{transaction_number}'] = $transaction_id;
		$this->template_variables                   = array(
			'order_id'       => $order_id,
			'transaction_id' => $transaction_id,
			'log_path'       => $log_path,
			'type'           => $type,
		);

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	public function get_content_html() {

		return wc_get_template_html(
			$this->template_html,
			array_merge(
				array(
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'email'              => $this,
					'site_name'          => $this->get_blogname(),
				),
				$this->template_variables
			),
			$this->template_base . $this->template_html,
			$this->template_base . $this->template_html
		);
	}

	public function get_content_plain() {

		return wc_get_template_html(
			$this->template_plain,
			array_merge(
				array(
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'site_name'          => $this->get_blogname(),
				),
				$this->template_variables
			),
			$this->template_base . $this->template_html,
			$this->template_base . $this->template_html
		);
	}


	public function get_default_additional_content() {

		return __( 'You may need to double check the WeChat Pay transactions in the merchant platform and take care of refunds by other means if necessary.', 'woo-wechatpay' );
	}

	public function init_form_fields() {
		parent::init_form_fields();

		$this->form_fields['recipient'] = array(
			'title'       => 'Recipient(s)',
			'type'        => 'text',
			'description' => sprintf( 'Enter recipients (comma separated) for this email. Defaults to <code>%s</code>.', esc_attr( get_option( 'admin_email' ) ) ),
			'placeholder' => '',
			'default'     => '',
		);

		$subject_desc = $this->form_fields['subject']['description'];
		$heading_desc = $this->form_fields['heading']['description'];

		$this->form_fields['subject']['description']  = __( 'Default value: ', 'woo-wechatpay' );
		$this->form_fields['subject']['description'] .= $this->form_fields['subject']['placeholder'] . '<br/>';
		$this->form_fields['subject']['description'] .= $subject_desc;

		$this->form_fields['heading']['description']  = __( 'Default value: ', 'woo-wechatpay' );
		$this->form_fields['heading']['description'] .= $this->form_fields['heading']['placeholder'] . '<br/>';
		$this->form_fields['heading']['description'] .= $subject_desc;
	}

	public function options_to_translate( $options ) {
		$options[] = 'woocommerce_wechatpay_orphan_transaction_settings';

		return $options;
	}

	public function section_name_to_translate( $section_name ) {

		if ( 'wechatpay_orphan_transaction' === $section_name ) {

			return 'wc_email_wechatpay_orphan_transaction';
		}

		return $section_name;
	}

}
