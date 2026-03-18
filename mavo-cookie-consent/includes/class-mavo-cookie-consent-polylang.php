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
 * Delays all third-party cookies until implied consent is given, then
 * restores them client-side after consent.
 *
 * How it works
 * ============
 * Plugins register cookies via setcookie() during `init`. WordPress only
 * physically sends HTTP headers once output begins, so all Set-Cookie headers
 * are still in PHP's pending queue when `send_headers` fires. We use that
 * window to snapshot the pending cookies via headers_list(), then call
 * header_remove('Set-Cookie') to drop them all.
 *
 * The captured cookie name/value pairs are passed to cookie-consent.js via
 * wp_localize_script. On implied consent (click / 300 px scroll), the JS
 * re-sets each captured cookie client-side — no extra page load required.
 *
 * HttpOnly cookies are excluded from the snapshot because they cannot be
 * written by JavaScript.
 *
 * Once the mavo_cookie_consent cookie is present (returning visitors), this
 * class registers no hooks and all cookies are sent normally.
 */
class Mavo_Cookie_Consent_Polylang {

	/** Cookies captured from pending Set-Cookie headers before suppression. */
	private static array $pending_cookies = [];

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
	 * Snapshots then drops all pending Set-Cookie headers before the response
	 * is sent.
	 *
	 * Runs at PHP_INT_MAX priority on `send_headers`, after every plugin has
	 * had a chance to queue its cookies via setcookie() during `init`.
	 */
	public function suppress_all_cookies(): void {
		self::$pending_cookies = self::parse_pending_cookies();
		header_remove( 'Set-Cookie' );
	}

	// -------------------------------------------------------------------------
	// Static helpers (used by Mavo_Cookie_Consent::enqueue_assets)
	// -------------------------------------------------------------------------

	/**
	 * Returns cookie name/value pairs captured before suppression.
	 * Used by Mavo_Cookie_Consent::enqueue_assets() for JS localisation.
	 *
	 * @return list<array{name: string, value: string}>
	 */
	public static function get_pending_cookies(): array {
		return self::$pending_cookies;
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Reads headers_list() and extracts Set-Cookie name/value pairs.
	 * HttpOnly cookies are excluded — they cannot be written by JavaScript.
	 *
	 * @return list<array{name: string, value: string}>
	 */
	private static function parse_pending_cookies(): array {
		$cookies = [];

		foreach ( headers_list() as $header ) {
			if ( 0 !== stripos( $header, 'Set-Cookie:' ) ) {
				continue;
			}

			$cookie_string = trim( substr( $header, strlen( 'Set-Cookie:' ) ) );

			// HttpOnly cookies cannot be written client-side — skip them.
			if ( 1 === preg_match( '/;\s*HttpOnly/i', $cookie_string ) ) {
				continue;
			}

			// Extract the name=value pair (everything before the first ';').
			$name_value_part = explode( ';', $cookie_string, 2 )[0];
			$eq_pos          = strpos( $name_value_part, '=' );

			if ( false === $eq_pos ) {
				continue; // Malformed — skip.
			}

			$name  = urldecode( substr( $name_value_part, 0, $eq_pos ) );
			$value = urldecode( substr( $name_value_part, $eq_pos + 1 ) );

			if ( '' === $name ) {
				continue;
			}

			$cookies[] = [
				'name'  => $name,
				'value' => $value,
			];
		}

		return $cookies;
	}
}
