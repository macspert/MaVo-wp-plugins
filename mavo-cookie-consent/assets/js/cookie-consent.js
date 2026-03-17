/* global mavoCookieConsent */
( function () {
	'use strict';

	var config  = mavoCookieConsent;
	var banner  = document.getElementById( 'mavo-cookie-banner' );
	var dismissed = false;

	// Nothing to do if the banner was not rendered (PHP already saw the cookie).
	if ( ! banner ) {
		return;
	}

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

	/**
	 * Animates the banner out, sets the consent cookie, and removes all
	 * event listeners so they cannot fire a second time.
	 */
	function dismiss() {
		if ( dismissed ) {
			return;
		}
		dismissed = true;

		banner.classList.add( 'mavo-cookie-banner--dismissing' );

		setCookie( config.cookieName, '1' );

		// Remove listeners after the transition completes.
		banner.addEventListener( 'transitionend', function onEnd() {
			banner.removeEventListener( 'transitionend', onEnd );
			banner.remove();
		} );

		document.removeEventListener( 'click',  onUserInteraction );
		window.removeEventListener( 'scroll', onScroll );
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

	// Show the banner (remove the hidden class) once the DOM is ready.
	function init() {
		banner.classList.remove( 'mavo-cookie-banner--hidden' );

		document.addEventListener( 'click',  onUserInteraction );
		window.addEventListener( 'scroll', onScroll, { passive: true } );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
