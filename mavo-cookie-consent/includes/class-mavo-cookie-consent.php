<?php
/**
 * Core plugin class.
 *
 * @package MaVo_Cookie_Consent
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Mavo_Cookie_Consent
 *
 * Behaviour summary:
 *
 * Returning visitor (consent cookie present):
 *   - Tracking scripts (GA4 + Statcounter) are output directly in wp_head so
 *     they fire immediately on every page load as normal.
 *   - No banner HTML or banner assets are loaded.
 *
 * First-time visitor (no consent cookie):
 *   - Banner CSS + JS are enqueued.
 *   - Tracking credentials are passed to the JS via wp_localize_script.
 *   - The JS shows the banner and, once the user interacts (click / 300 px
 *     scroll), sets the consent cookie AND dynamically injects the tracking
 *     scripts into the page — all without a page reload.
 */
class Mavo_Cookie_Consent {

	/** Cookie name written to the visitor's browser. */
	const COOKIE_NAME = 'mavo_cookie_consent';

	/** Singleton instance. */
	private static ?self $instance = null;

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		if ( $this->has_consent() ) {
			// Returning visitor — output tracking immediately, skip banner.
			add_action( 'wp_head', [ $this, 'output_tracking' ], 1 );
		} else {
			// First-time visitor — load banner assets and render the banner.
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
			add_action( 'wp_footer',          [ $this, 'render_banner' ] );
		}
	}

	// -------------------------------------------------------------------------
	// Returning visitors: immediate tracking output
	// -------------------------------------------------------------------------

	/**
	 * Outputs GA4 and Statcounter script tags directly in <head>.
	 * Only called when the consent cookie is already present.
	 */
	public function output_tracking(): void {
		$cfg = Mavo_Cookie_Consent_Settings::get_tracking_config();

		$this->print_ga4_snippet( $cfg['ga4_id'] );
		$this->print_statcounter_snippet( $cfg['sc_project'], $cfg['sc_security'] );
	}

	// -------------------------------------------------------------------------
	// First-time visitors: banner assets
	// -------------------------------------------------------------------------

	/**
	 * Enqueues the banner stylesheet and script.
	 * Tracking credentials are passed via wp_localize_script so the JS can
	 * inject the trackers after the visitor grants implied consent.
	 */
	public function enqueue_assets(): void {
		wp_enqueue_style(
			'mavo-cookie-consent',
			MAVO_CC_URL . 'assets/css/cookie-consent.css',
			[],
			MAVO_CC_VERSION
		);

		wp_enqueue_script(
			'mavo-cookie-consent',
			MAVO_CC_URL . 'assets/js/cookie-consent.js',
			[],
			MAVO_CC_VERSION,
			true
		);

		$cfg = Mavo_Cookie_Consent_Settings::get_tracking_config();

		wp_localize_script(
			'mavo-cookie-consent',
			'mavoCookieConsent',
			[
				'cookieName'      => self::COOKIE_NAME,
				'scrollThreshold' => 300,
				'ga4Id'           => $cfg['ga4_id'],
				'scProject'       => $cfg['sc_project'] ?: 0,
				'scSecurity'      => $cfg['sc_security'],
			]
		);
	}

	/**
	 * Outputs the banner HTML in wp_footer.
	 * The banner starts hidden via CSS; JS removes the hidden class after DOM
	 * ready to avoid any flash when cached HTML is served.
	 */
	public function render_banner(): void {
		?>
		<div id="mavo-cookie-banner" class="mavo-cookie-banner mavo-cookie-banner--hidden" role="region" aria-label="<?php esc_attr_e( 'Cookie notice', 'mavo-cookie-consent' ); ?>">
			<p class="mavo-cookie-banner__text">
				<?php
				esc_html_e(
					'By using this site you accept the use of cookies and anonymous analytics.',
					'mavo-cookie-consent'
				);
				?>
			</p>
		</div>
		<?php
	}

	// -------------------------------------------------------------------------
	// Snippet helpers
	// -------------------------------------------------------------------------

	/**
	 * Prints the GA4 global site tag snippet.
	 *
	 * @param string $measurement_id GA4 Measurement ID (e.g. G-XXXXXXXXXX).
	 */
	private function print_ga4_snippet( string $measurement_id ): void {
		if ( '' === $measurement_id ) {
			return;
		}
		$safe_id = esc_js( $measurement_id );
		?>
<!-- Google Analytics 4 | MaVo Cookie Consent -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $measurement_id ); ?>"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '<?php echo $safe_id; // phpcs:ignore WordPress.Security.EscapeOutput ?>');
</script>
		<?php
	}

	/**
	 * Prints the Statcounter script snippet.
	 *
	 * @param int    $project_id  Statcounter project ID.
	 * @param string $security    Statcounter security code.
	 */
	private function print_statcounter_snippet( int $project_id, string $security ): void {
		if ( 0 === $project_id || '' === $security ) {
			return;
		}
		?>
<!-- Statcounter | MaVo Cookie Consent -->
<script>
var sc_project=<?php echo absint( $project_id ); ?>, sc_invisible=1, sc_security='<?php echo esc_js( $security ); ?>';
</script>
<script async src="https://www.statcounter.com/counter/counter.js"></script>
		<?php
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Returns true when the visitor has already given implied consent.
	 */
	private function has_consent(): bool {
		return ! empty( $_COOKIE[ self::COOKIE_NAME ] );
	}
}
