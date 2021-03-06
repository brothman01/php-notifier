<?php
/**
 * Settings Page
 *
 * @since 1.0.0
 *
 * @author Code Parrots <support@codeparrots.com>
 */
class CP_PHP_Notifier_Settings extends CP_PHP_Notifier {

	/**
	 * PHP Version Error
	 *
	 * @var string
	 */
	private $php_version_error;

	public function __construct( $php_version_error ) {

		$this->php_version_error = $php_version_error;

		add_action( 'admin_menu',            array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init',            array( $this, 'page_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'page_styles' ) );

	}

	/**
	* Add options page
	*
	* @since 1.0.0
	*/
	public function add_plugin_page() {

		add_options_page(
			__( 'PHP Notifier Settings', 'php-notifier' ),
			__( 'PHP Notifier', 'php-notifier' ),
			'manage_options',
			'php-notifier',
			array( $this, 'create_admin_page' )
		);

	}

	/**
	* Options page callback
	*
	* @since 1.0.0
	*/
	public function create_admin_page() {

		?>

			<div class="wrap">

				<h1><?php esc_html_e( 'PHP Notifier', 'php-notifier' ); ?></h1>

				<form method="post" action="options.php">

					<?php

						printf(
							'<div class="notice notice-info is-dismissible"><p>%s</p></div>',
							sprintf(
								esc_html__( 'The PHP version running on this server: %s' ),
								wp_kses_post( '<span class="php-version">' . self::$php_version . '</span>' )
							)
						);

						settings_fields( 'php_notifier_settings_group' );

						do_settings_sections( 'php-notifier' );

						submit_button();

					?>

				</form>

			</div>

		<?php
	}

	/**
	* Register and add settings
	*
	* @since 1.0.0
	*/
	public function page_init() {

		register_setting(
			'php_notifier_settings_group',
			'php_notifier_settings',
			array( $this, 'sanitize' )
		);

		add_settings_section(
			'setting_section_id',
			__( 'General Settings', 'php-notifier' ),
			array( $this, 'print_section_info' ),
			'php-notifier'
		);

		add_settings_field(
			'send_email',
			esc_html__( 'Send Email Notification?', 'php-notifier' ),
			array( $this, 'send_email_callback' ),
			'php-notifier',
			'setting_section_id'
		);

		add_settings_field(
			'email_frequency',
			esc_html__( 'Email Frequency', 'php-notifier' ),
			array( $this, 'email_frequency_callback' ),
			'php-notifier',
			'setting_section_id'
		);

	}

	/**
	 * Enqueue the admin stylesheet
	 *
	 * @since 1.0.0
	 */
	public function page_styles() {

		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( 'php-notifier-style', PHP_NOTIFIER_URL . "library/css/style{$suffix}.css", array(), PHP_NOTIFIER_VERSION, 'all' );

	}

	/**
	* Sanitize each setting field as needed
	*
	* @param array $input Contains all settings fields as array keys
	*
	* @since 1.0.0
	*/
	public function sanitize( $input ) {

		$new_input = array();

		$new_input['warning_type']     = self::$options['warning_type'];
		$new_input['send_email']       = (bool) empty( $input['send_email'] ) ? false : true;
		$new_input['email_frequency']  = isset( $input['email_frequency'] ) ? sanitize_text_field( $input['email_frequency'] ) : 'Never';

		if ( self::$options['email_frequency'] !== $input['email_frequency'] ) {

			wp_clear_scheduled_hook( 'php_notifier_email_cron' );

			if ( ! self::$options['email_frequency'] ) {

				return $new_input;

			}

			update_option( 'php_notifier_prevent_cron', true );

			wp_schedule_event( time(), $new_input['email_frequency'], 'php_notifier_email_cron' );

		}

		return $new_input;

	}

	/**
	* Print the Section text
	*
	* @since 1.0.0
	*/
	public function print_section_info() {

		esc_html_e( 'Adjust the settings below:', 'php-notifier' );

	}

	/**
	* Get the settings option array and print one of its values
	*
	* @since 1.0.0
	*/
	public function send_email_callback() {

		printf(
			'<input type="checkbox" id="send_email" name="php_notifier_settings[send_email]" value="1" %s />',
			checked( 1, self::$options['send_email'], false )
		);

	}

	/**
	* Get the settings option array and print one of its values
	*
	* @since 1.0.0
	*/
	public function email_frequency_callback() {

		$options = array(
			'never'   => __( 'Never', 'php-notifier' ),
			'daily'   => __( 'Daily', 'php-notifier' ),
			'weekly'  => __( 'Weekly', 'php-notifier' ),
			'monthly' => __( 'Monthly', 'php-notifier' ),
		);

		print( '<select name="php_notifier_settings[email_frequency]">' );

		foreach ( $options as $value => $label ) {

			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $value ),
				selected( self::$options['email_frequency'], $value ),
				esc_html( $label )
			);

		}

		print( '</select>' );

	}
}

if ( is_admin() ) {

	$cp_php_notifier_settings = new CP_PHP_Notifier_Settings( $this->get_php_error_message() );

}
