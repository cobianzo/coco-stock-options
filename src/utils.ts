/**
 * Utility functions for Coco Stock Options plugin
 *
 * @package CocoStockOptions
 */

// Import WordPress API fetch
import apiFetch from '@wordpress/api-fetch';

// Extend Window interface for our plugin
declare global {
	interface Window {
		cocoStockOptions: {
			ajaxUrl: string;
			nonce: string;
		};
	}
}

/**
 * Sends an AJAX request to the WordPress backend using the api-fetch package.
 * automatically including the action and nonce fields, and returning the parsed JSON response.
 *
 * Usage: `makeAjaxRequest({ action: 'cocostock_add_stock', symbol: 'LMT' }).then(response => .. `
 *
 * @param data An object containing the request data. Must include an 'action' property.
 * @return Promise<any> Resolves with the parsed JSON response from the server.
 */
export async function makeAjaxRequest( data: Record<string, string> ): Promise<any> {
	const formData = new window.FormData();
	formData.append( 'action', data.action );
	formData.append( 'nonce', window.cocoStockOptions.nonce );

	Object.keys( data ).forEach( ( key ) => {
		if ( key !== 'action' ) {
			formData.append( key, data[ key ] );
		}
	} );

	try {
		const response = await apiFetch( {
			url: window.cocoStockOptions.ajaxUrl,
			method: 'POST',
			body: formData,
			parse: false, // We'll parse JSON manually to match previous behavior
		} );
		const json = JSON.parse( response as unknown as string );
		return json;
	} catch ( error: any ) {
		if ( error && error.message ) {
			throw new Error( error.message );
		}
		throw new Error( 'Network or server error' );
	}
}

/**
 * Show message in admin interface
 *
 * @param message Message to show
 * @param type Message type (success, error, warning, info)
 * @param containerSelector Optional container selector.
 */
export function showMessage( message: string, type: string, containerSelector: string ): void {
	const resultElement = document.querySelector( containerSelector );

	if ( ! resultElement ) {
		return;
	}

	const cssClass = `notice notice-${ type }`;
	const randomId = 'msg-' + Math.random().toString(36).substr(2, 9);
	const html = `<div id="${ randomId }" class="${ cssClass }"><p>${ message }</p></div>`;


	resultElement.innerHTML = html;

	// Auto-hide after 5 seconds
	setTimeout( () => {
		fadeOutAndRemove( document.getElementById( randomId ) as HTMLElement );
	}, 5000 );
}

/**
 * Fade out element, once faded, deleted.
 *
 * @param element Element to fade out
 */
export function fadeOutAndRemove( element?: HTMLElement ): void {
	if ( ! element ) {
		return;
	}
	element.style.transition = 'opacity 0.5s ease-out';
	element.style.opacity = '0';
	setTimeout( () => {
		element.remove();
	}, 500 );
}

/**
 * Format number with commas
 *
 * @param num Number to format
 * @return Formatted number string
 */
export function formatNumber( num: number ): string {
	return num.toString().replace( /\B(?=(\d{3})+(?!\d))/g, ',' );
}

/**
 * Format date
 *
 * @param dateString Date string to format
 * @return Formatted date string
 */
export function formatDate( dateString: string ): string {
	if ( ! dateString || dateString === 'Never' ) {
		return 'Never';
	}

	const date = new Date( dateString );
	return date.toLocaleString();
}