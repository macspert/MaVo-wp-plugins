<?php
/**
 * Admin settings page for MaVo Cookie Consent.
 *
 * Provides a Settings > Cookie Consent page where the site owner can enter
 * tracking credentials. Values are stored as individual wp_options entries.
 *
 * @package MaVo_Cookie_Consent
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Mavo_Cookie_Consent_Settings
 */
class Mavo_Cookie_Consent_Settings {

	/** Option keys. */
	const OPT_GA4_ID     = 'mavo_cc_ga4_id';
	const OPT_SC_PROJECT = 'mavo_cc_statcounter_project';
	const OPT_SC_SECURITY = 'mavo_cc_statcounter_security';

	/** Settings page slug. */
	const PAGE_SLUG = 'mavo-cookie-consent';

	/** Option group for settings API. */
	const OPTION_GROUP = 'mavo_cookie_consent_options';

	/** Singleton instance. */
	private static ?self $instance = null;

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu',  [ $this, 'add_settings_page' ] );
		add_action( 'admin_init',  [ $this, 'register_settings' ] );
	}

	/** Registers the submenu page under Settings. */
	public function add_settings_page(): void {
		add_options_page(
			__( 'Cookie Consent', 'mavo-cookie-consent' ),
			__( 'Cookie Consent', 'mavo-cookie-consent' ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render_page' ]
		);
	}

	/** Registers settings, sections, and fields via the Settings API. */
	public function register_settings(): void {
		register_setting(
			self::OPTION_GROUP,
			self::OPT_GA4_ID,
			[
				'type'              => 'string',
				'sanitize_callback' => [ $this, 'sanitize_ga4_id' ],
				'default'           => '',
			]
		);

		register_setting(
			self::OPTION_GROUP,
			self::OPT_SC_PROJECT,
			[
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			]
		);

		register_setting(
			self::OPTION_GROUP,
			self::OPT_SC_SECURITY,
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			]
		);

		// ---- Google Analytics 4 section ---------------------------------- //
		add_settings_section(
			'mavo_cc_ga4',
			__( 'Google Analytics 4', 'mavo-cookie-consent' ),
			'__return_false',
			self::PAGE_SLUG
		);

		add_settings_field(
			self::OPT_GA4_ID,
			__( 'Measurement ID', 'mavo-cookie-consent' ),
			[ $this, 'field_ga4_id' ],
			self::PAGE_SLUG,
			'mavo_cc_ga4'
		);

		// ---- Statcounter section ----------------------------------------- //
		add_settings_section(
			'mavo_cc_statcounter',
			__( 'Statcounter', 'mavo-cookie-consent' ),
			'__return_false',
			self::PAGE_SLUG
		);

		add_settings_field(
			self::OPT_SC_PROJECT,
			__( 'Project ID', 'mavo-cookie-consent' ),
			[ $this, 'field_sc_project' ],
			self::PAGE_SLUG,
			'mavo_cc_statcounter'
		);

		add_settings_field(
			self::OPT_SC_SECURITY,
			__( 'Security Code', 'mavo-cookie-consent' ),
			[ $this, 'field_sc_security' ],
			self::PAGE_SLUG,
			'mavo_cc_statcounter'
		);
	}

	// -------------------------------------------------------------------------
	// Field renderers
	// -------------------------------------------------------------------------

	public function field_ga4_id(): void {
		$value = get_option( self::OPT_GA4_ID, '' );
		?>
		<input
			type="text"
			id="<?php echo esc_attr( self::OPT_GA4_ID ); ?>"
			name="<?php echo esc_attr( self::OPT_GA4_ID ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			placeholder="G-XXXXXXXXXX"
			class="regular-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Enter your GA4 Measurement ID, e.g. G-XXXXXXXXXX. Leave blank to disable.', 'mavo-cookie-consent' ); ?>
		</p>
		<?php
	}

	public function field_sc_project(): void {
		$value = get_option( self::OPT_SC_PROJECT, 0 );
		?>
		<input
			type="number"
			id="<?php echo esc_attr( self::OPT_SC_PROJECT ); ?>"
			name="<?php echo esc_attr( self::OPT_SC_PROJECT ); ?>"
			value="<?php echo esc_attr( $value ? (string) $value : '' ); ?>"
			placeholder="12345678"
			class="small-text"
			min="0"
		/>
		<p class="description">
			<?php esc_html_e( 'Your numeric Statcounter Project ID. Leave blank to disable.', 'mavo-cookie-consent' ); ?>
		</p>
		<?php
	}

	public function field_sc_security(): void {
		$value = get_option( self::OPT_SC_SECURITY, '' );
		?>
		<input
			type="text"
			id="<?php echo esc_attr( self::OPT_SC_SECURITY ); ?>"
			name="<?php echo esc_attr( self::OPT_SC_SECURITY ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			placeholder="abcdef01"
			class="regular-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Your Statcounter Security Code (8-character hex string).', 'mavo-cookie-consent' ); ?>
		</p>
		<?php
	}

	// -------------------------------------------------------------------------
	// Sanitization
	// -------------------------------------------------------------------------

	/**
	 * Sanitizes the GA4 Measurement ID.
	 * Accepts the standard G-XXXXXXXXXX format or an empty string.
	 *
	 * @param mixed $raw Raw input value.
	 * @return string
	 */
	public function sanitize_ga4_id( $raw ): string {
		$value = sanitize_text_field( (string) $raw );

		// Allow empty (disables tracking).
		if ( '' === $value ) {
			return '';
		}

		// Enforce G-XXXXXXXXXX pattern (letters and digits after the dash).
		if ( ! preg_match( '/^G-[A-Z0-9]+$/i', $value ) ) {
			add_settings_error(
				self::OPT_GA4_ID,
				'invalid_ga4_id',
				__( 'Invalid GA4 Measurement ID. Expected format: G-XXXXXXXXXX.', 'mavo-cookie-consent' )
			);
			return get_option( self::OPT_GA4_ID, '' );
		}

		return strtoupper( $value );
	}

	// -------------------------------------------------------------------------
	// Page renderer
	// -------------------------------------------------------------------------

	/** Outputs the settings page HTML. */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p>
				<?php esc_html_e( 'Tracking scripts are fired immediately for returning visitors (consent cookie present) and only after implied consent for first-time visitors.', 'mavo-cookie-consent' ); ?>
			</p>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Returns a config array used by both the tracking output and JS localisation.
	 *
	 * @return array{ga4_id: string, sc_project: int, sc_security: string}
	 */
	public static function get_tracking_config(): array {
		return [
			'ga4_id'      => (string) get_option( self::OPT_GA4_ID, '' ),
			'sc_project'  => (int) get_option( self::OPT_SC_PROJECT, 0 ),
			'sc_security' => (string) get_option( self::OPT_SC_SECURITY, '' ),
		];
	}
}
