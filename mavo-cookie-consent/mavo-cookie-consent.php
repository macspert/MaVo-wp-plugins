<?php
/**
 * Plugin Name:       MaVo Cookie Consent
 * Plugin URI:        https://github.com/macspert/MaVo-wp-plugins
 * Description:       Displays an implicit cookie consent banner on first visit. Dismissed automatically on click or 300px scroll, then suppressed for one year.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            MaVo
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mavo-cookie-consent
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'MAVO_CC_VERSION', '1.0.0' );
define( 'MAVO_CC_FILE',    __FILE__ );
define( 'MAVO_CC_DIR',     plugin_dir_path( __FILE__ ) );
define( 'MAVO_CC_URL',     plugin_dir_url( __FILE__ ) );

require_once MAVO_CC_DIR . 'includes/class-mavo-cookie-consent-settings.php';
require_once MAVO_CC_DIR . 'includes/class-mavo-cookie-consent.php';

/**
 * Bootstraps the plugin (main class + admin settings).
 */
function mavo_cookie_consent_init(): void {
	Mavo_Cookie_Consent_Settings::get_instance();
	Mavo_Cookie_Consent::get_instance();
}

mavo_cookie_consent_init();
