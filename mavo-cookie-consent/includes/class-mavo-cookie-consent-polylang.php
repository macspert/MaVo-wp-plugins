<?php
/**
 * Polylang integration: delay pll_language cookie until implied consent.
 *
 * @package MaVo_Cookie_Consent
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Mavo_Cookie_Consent_Polylang
 *
 * Delays all third-party cookies until implied consent is given, and
 * restores the Polylang language preference client-side on consent.
 *
 * How it works
 * ============
 * Plugins (e.g. Polylang) register cookies via setcookie() during `init`.
 * WordPress only physically sends HTTP headers once output begins, so all
 * Set-Cookie headers are still in PHP's pending queue when `send_headers`
 * fires.  We use that window to call header_remove('Set-Cookie'), which
 * drops every pending cookie in one step.
 *
 * On the client side, cookie-consent.js reads the pllLanguage value passed
 * through wp_localize_script and writes the pll_language cookie itself
 * immediately after the visitor triggers implied consent — so the language
 * preference is captured without needing an extra page load.
 *
 * Once the mavo_cookie_consent cookie is present (returning visitors), this
 * class registers no hooks and all cookies are sent normally.
 */
class Mavo_Cookie_Consent_Polylang {

	/** Name of the Polylang language-preference cookie. */
	const PLL_COOKIE = 'pll_language';

	/** Singleton instance. */
	private static ?self $instance = null;

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		if ( isset( $_COOKIE[ Mavo_Cookie_Consent::COOKIE_NAME ] ) ) {
			// Consent already given — send cookies normally.
			return;
		}

		// No consent yet: suppress all pending Set-Cookie headers.
		add_action( 'send_headers', [ $this, 'suppress_all_cookies' ], PHP_INT_MAX );
	}

	// -------------------------------------------------------------------------
	// Header suppression
	// -------------------------------------------------------------------------

	/**
	 * Drops all pending Set-Cookie headers before the response is sent.
	 *
	 * Runs at PHP_INT_MAX priority on `send_headers`, after every plugin has
	 * had a chance to queue its cookies via setcookie() during `init`.
	 */
	public function suppress_all_cookies(): void {
		header_remove( 'Set-Cookie' );
	}

	// -------------------------------------------------------------------------
	// Static helpers (used by Mavo_Cookie_Consent::enqueue_assets)
	// -------------------------------------------------------------------------

	/**
	 * Returns cookie name and current language slug for use in wp_localize_script.
	 *
	 * Returns empty strings when Polylang is not active so the JS config shape
	 * is always consistent and the JS simply no-ops on those fields.
	 *
	 * @return array{cookieName: string, language: string}
	 */
	public static function get_cookie_data(): array {
		if ( ! function_exists( 'pll_current_language' ) ) {
			return [
				'cookieName' => '',
				'language'   => '',
			];
		}

		$lang = pll_current_language( 'slug' );

		return [
			'cookieName' => self::PLL_COOKIE,
			'language'   => is_string( $lang ) ? $lang : '',
		];
	}
}
