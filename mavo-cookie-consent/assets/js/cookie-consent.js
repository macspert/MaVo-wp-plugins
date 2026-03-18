/* global mavoCookieConsent */
( function () {
	'use strict';

	var config   = mavoCookieConsent;
	var banner   = document.getElementById( 'mavo-cookie-banner' );
	var dismissed = false;

	// Nothing to do if the banner was not rendered (PHP already saw the cookie).
	if ( ! banner ) {
		return;
	}

	// -------------------------------------------------------------------------
	// Cookie helper
	// -------------------------------------------------------------------------

	/**
	 * Writes a cookie with a 1-year expiry on the root path.
	 *
	 * @param {string} name
	 * @param {string} value
	 */
	function setCookie( name, value ) {
		var expires = new Date();
		expires.setFullYear( expires.getFullYear() + 1 );
		document.cookie =
			encodeURIComponent( name ) + '=' + encodeURIComponent( value ) +
			'; expires=' + expires.toUTCString() +
			'; path=/; SameSite=Lax';
	}

	// -------------------------------------------------------------------------
	// Deferred tracking injection
	// -------------------------------------------------------------------------

	/**
	 * Dynamically injects GA4 and Statcounter scripts into <head>.
	 * Called once, immediately after implied consent is recorded.
	 */
	function loadTracking() {
		// Google Analytics 4 ------------------------------------------------ //
		if ( config.ga4Id ) {
			var gtagScript = document.createElement( 'script' );
			gtagScript.async = true;
			gtagScript.src   = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent( config.ga4Id );
			document.head.appendChild( gtagScript );

			window.dataLayer = window.dataLayer || [];
			function gtag() { window.dataLayer.push( arguments ); }
			window.gtag = gtag;
			gtag( 'js', new Date() );
			gtag( 'config', config.ga4Id );
		}

		// Statcounter -------------------------------------------------------- //
		if ( config.scProject && config.scSecurity ) {
			window.sc_project   = config.scProject;
			window.sc_invisible = 1;
			window.sc_security  = config.scSecurity;

			var scScript = document.createElement( 'script' );
			scScript.async = true;
			scScript.src   = 'https://www.statcounter.com/counter/counter.js';
			document.head.appendChild( scScript );
		}
	}

	// -------------------------------------------------------------------------
	// Dismiss logic
	// -------------------------------------------------------------------------

	/**
	 * Animates the banner out, sets the consent cookie, fires deferred
	 * trackers, and removes all event listeners.
	 */
	function dismiss() {
		if ( dismissed ) {
			return;
		}
		dismissed = true;

		// Record consent immediately so trackers can fire right away.
		setCookie( config.cookieName, '1' );

		// If Polylang is active, also set its language cookie now.
		// The server suppressed pll_language until consent; we restore it here
		// so the language preference is captured without needing a page reload.
		if ( config.pllCookieName && config.pllLanguage ) {
			setCookie( config.pllCookieName, config.pllLanguage );
		}

		loadTracking();

		// Animate banner out.
		banner.classList.add( 'mavo-cookie-banner--dismissing' );
		banner.addEventListener( 'transitionend', function onEnd() {
			banner.removeEventListener( 'transitionend', onEnd );
			banner.remove();
		} );

		document.removeEventListener( 'click',  onUserInteraction );
		window.removeEventListener(   'scroll', onScroll );
	}

	/** Dismiss on any click anywhere in the document. */
	function onUserInteraction() {
		dismiss();
	}

	/** Dismiss when the visitor has scrolled past the configured threshold. */
	function onScroll() {
		var scrollY = window.scrollY !== undefined
			? window.scrollY
			: document.documentElement.scrollTop;

		if ( scrollY >= config.scrollThreshold ) {
			dismiss();
		}
	}

	// -------------------------------------------------------------------------
	// Initialise
	// -------------------------------------------------------------------------

	function init() {
		// Reveal the banner (triggers the CSS slide-up transition).
		banner.classList.remove( 'mavo-cookie-banner--hidden' );

		document.addEventListener( 'click',  onUserInteraction );
		window.addEventListener(   'scroll', onScroll, { passive: true } );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
