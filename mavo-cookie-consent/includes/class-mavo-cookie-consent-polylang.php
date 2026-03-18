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
 * When Polylang is active, this class prevents the pll_language cookie from
 * being sent to the browser until the visitor has given implied consent.
 *
 * How it works
 * ============
 * Polylang registers pll_language via setcookie() during the `init` action.
 * WordPress only physically sends HTTP headers once output begins, so the
 * Set-Cookie header is still in PHP's pending-header queue when the
 * `send_headers` action fires.  We use that window to:
 *
 *   1. Collect all queued Set-Cookie headers via headers_list().
 *   2. Clear them all with header_remove('Set-Cookie').
 *   3. Re-register every one EXCEPT pll_language.
 *
 * On the client side, cookie-consent.js reads the pllLanguage value passed
 * through wp_localize_script and writes the pll_language cookie itself
 * immediately after the visitor triggers implied consent — so the language
 * preference is captured without needing an extra page load.
 *
 * Once the mavo_cookie_consent cookie is present (returning visitors), this
 * class registers no hooks and Polylang operates completely normally.
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
		if ( ! $this->is_polylang_active() ) {
			return;
		}

		if ( isset( $_COOKIE[ Mavo_Cookie_Consent::COOKIE_NAME ] ) ) {
			// Consent already given — let Polylang run normally.
			return;
		}

		// No consent yet: strip pll_language from outgoing headers.
		add_action( 'send_headers', [ $this, 'suppress_pll_cookie' ], PHP_INT_MAX );
	}

	// -------------------------------------------------------------------------
	// Header suppression
	// -------------------------------------------------------------------------

	/**
	 * Removes the pll_language Set-Cookie header from the pending response.
	 *
	 * Uses PHP_INT_MAX priority so it runs after Polylang (and any other
	 * plugin) has finished queuing cookies during `send_headers`.
	 */
	public function suppress_pll_cookie(): void {
		$pending = headers_list();

		// Drop every Set-Cookie header PHP has queued so far.
		header_remove( 'Set-Cookie' );

		// Re-register each one except the pll_language cookie.
		foreach ( $pending as $header ) {
			// headers_list() includes all header types; we only touch Set-Cookie.
			// stripos returns 0 (falsy as int but !== false) when the string
			// starts with 'Set-Cookie:', so use strict comparison.
			if ( 0 !== stripos( $header, 'Set-Cookie:' ) ) {
				continue; // Not a Set-Cookie header — was not removed, skip.
			}

			// Extract the cookie name: the token before the first '=' after
			// the "Set-Cookie: " prefix.
			$value_part  = ltrim( substr( $header, strlen( 'Set-Cookie:' ) ) );
			$cookie_name = urldecode( trim( (string) strtok( $value_part, '=' ) ) );

			if ( self::PLL_COOKIE === $cookie_name ) {
				continue; // Suppress — do not re-add.
			}

			header( $header, false );
		}
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

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Returns true when Polylang (or Polylang Pro) is active and initialised.
	 */
	private function is_polylang_active(): bool {
		return function_exists( 'pll_current_language' );
	}
}
