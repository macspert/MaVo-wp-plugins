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
 * Registers front-end assets and renders the consent banner via wp_footer.
 * All dismissal logic (click / scroll threshold) and cookie-setting is handled
 * client-side so that no server round-trip is needed.
 */
class Mavo_Cookie_Consent {

	/** Cookie name written to the visitor's browser. */
	const COOKIE_NAME = 'mavo_cookie_consent';

	/** Singleton instance. */
	private static ?self $instance = null;

	/**
	 * Returns (and lazily creates) the singleton instance.
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/** Private constructor — use get_instance(). */
	private function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_footer',          [ $this, 'render_banner' ] );
	}

	/**
	 * Enqueues the banner stylesheet and script on the front end.
	 * Assets are only registered/enqueued when the consent cookie is absent,
	 * avoiding any overhead for returning visitors.
	 */
	public function enqueue_assets(): void {
		if ( $this->has_consent() ) {
			return;
		}

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
			true // Load in footer.
		);

		wp_localize_script(
			'mavo-cookie-consent',
			'mavoCookieConsent',
			[
				'cookieName'   => self::COOKIE_NAME,
				'scrollThreshold' => 300,
			]
		);
	}

	/**
	 * Outputs the banner HTML in wp_footer.
	 * Hidden via CSS by default; the JS removes the hidden class after the
	 * DOM is ready, preventing a flash on returning visits even if cached HTML
	 * is served before the cookie check runs.
	 */
	public function render_banner(): void {
		if ( $this->has_consent() ) {
			return;
		}
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

	/**
	 * Returns true when the visitor has already given (implicit) consent,
	 * i.e. the consent cookie is present in the current request.
	 */
	private function has_consent(): bool {
		return ! empty( $_COOKIE[ self::COOKIE_NAME ] );
	}
}
