/**
 * Admin functionality for Coco Stock Options plugin
 *
 * @package CocoStockOptions
 */

// Import admin styles
import './admin.css';

// Declare jQuery types
declare const jQuery: any;
declare const $: any;

// Extend Window interface for our plugin
interface Window {
	cocoStockOptions: {
		ajaxUrl: string;
		nonce: string;
	};
}

/**
 * Admin class for handling admin page interactions
 */
class CocoStockAdmin {
	/**
	 * Initialize the admin functionality
	 */
	constructor() {
		this.initEventListeners();
	}

	/**
	 * Initialize event listeners
	 */
	private initEventListeners(): void {
		jQuery( document ).ready( ( $ ) => {
			this.initAdminInterface( $ );
		} );
	}

	/**
	 * Initialize admin interface
	 *
	 * @param $ jQuery object
	 */
	private initAdminInterface( $: any ): void {
		this.initAddStock( $ );
		this.initForceBuffer( $ );
		this.initAutoRefresh( $ );
	}

	/**
	 * Initialize add stock functionality
	 *
	 * @param $ jQuery object
	 */
	private initAddStock( $: any ): void {
		$( '#add-stock' ).on( 'click', ( e ) => {

			e.preventDefault();

			const symbol = $( '#new-stock-symbol' ).val() as string;
			const trimmedSymbol = symbol.trim().toUpperCase();

			if ( ! trimmedSymbol ) {
				this.showMessage( $, 'Please enter a stock symbol', 'error' );
				return;
			}

			// Show loading state
			const $button = $( e.currentTarget );
			const originalText = $button.text();
			$button.prop( 'disabled', true ).text( 'Adding...' );

			// Make AJAX request
			$.ajax( {
				url: window.cocoStockOptions.ajaxUrl,
				type: 'POST',
				data: {
					action: 'cocostock_add_stock',
					nonce: window.cocoStockOptions.nonce,
					symbol: trimmedSymbol,
				},
				success: ( response: any ) => {
					if ( response.success ) {
						this.showMessage( $, response.data, 'success' );
						$( '#new-stock-symbol' ).val( '' );
						// Reload page after short delay
						setTimeout( () => {
							location.reload();
						}, 1500 );
					} else {
						this.showMessage( $, response.data, 'error' );
					}
				},
				error: () => {
					this.showMessage( $, 'An error occurred while adding the stock', 'error' );
				},
				complete: () => {
					$button.prop( 'disabled', false ).text( originalText );
				},
			} );
		} );

		// Allow Enter key to submit
		$( '#new-stock-symbol' ).on( 'keypress', ( e ) => {
			if ( e.which === 13 ) {
				$( '#add-stock' ).click();
			}
		} );
	}

	/**
	 * Initialize force buffer functionality
	 *
	 * @param $ jQuery object
	 */
	private initForceBuffer( $: any ): void {
		$( '#force-buffer' ).on( 'click', ( e ) => {
			e.preventDefault();

			const $button = $( e.currentTarget );
			const originalText = $button.text();

			$button.prop( 'disabled', true ).text( 'Processing...' );

			$.ajax( {
				url: window.cocoStockOptions.ajaxUrl,
				type: 'POST',
				data: {
					action: 'cocostock_force_buffer',
					nonce: window.cocoStockOptions.nonce,
				},
				success: ( response: any ) => {
					if ( response.success ) {
						this.showMessage( $, 'Buffer processing completed', 'success' );
						// Reload page after short delay
						setTimeout( () => {
							location.reload();
						}, 2000 );
					} else {
						this.showMessage( $, 'Failed to process buffer', 'error' );
					}
				},
				error: () => {
					this.showMessage( $, 'An error occurred while processing buffer', 'error' );
				},
				complete: () => {
					$button.prop( 'disabled', false ).text( originalText );
				},
			} );
		} );
	}

	/**
	 * Initialize auto-refresh functionality
	 *
	 * @param $ jQuery object
	 */
	private initAutoRefresh( $: any ): void {
		// Auto-refresh buffer info every 30 seconds
		setInterval( () => {
			this.refreshBufferInfo( $ );
		}, 30000 );
	}

	/**
	 * Refresh buffer information
	 *
	 * @param $ jQuery object
	 */
	private refreshBufferInfo( $: any ): void {
		$.ajax( {
			url: window.cocoStockOptions.ajaxUrl,
			type: 'POST',
			data: {
				action: 'cocostock_get_buffer_info',
				nonce: window.cocoStockOptions.nonce,
			},
			success: ( response: any ) => {
				if ( response.success ) {
					this.updateBufferDisplay( $, response.data );
				}
			},
		} );
	}

	/**
	 * Update buffer display
	 *
	 * @param $ jQuery object
	 * @param data Buffer data
	 */
	private updateBufferDisplay( $: any, data: any ): void {
		// Update buffer count
		$( '.cocostock-buffer-info' ).find( 'p:first' ).html(
			`<strong>Stocks in Buffer:</strong> ${ data.count }`
		);

		// Update last processed time
		$( '.cocostock-buffer-info' ).find( 'p:nth-child(2)' ).html(
			`<strong>Last Processing:</strong> ${ data.last_processed }`
		);

		// Update next processing time
		$( '.cocostock-buffer-info' ).find( 'p:nth-child(3)' ).html(
			`<strong>Next Processing:</strong> ${ data.next_processing }`
		);
	}

	/**
	 * Show message
	 *
	 * @param $ jQuery object
	 * @param message Message to show
	 * @param type Message type (success, error, warning, info)
	 */
	private showMessage( $: any, message: string, type: string ): void {
		const cssClass = `notice notice-${ type }`;
		const html = `<div class="${ cssClass }"><p>${ message }</p></div>`;

		$( '#add-stock-result' ).html( html );

		// Auto-hide after 5 seconds
		setTimeout( () => {
			$( '#add-stock-result' ).fadeOut();
		}, 5000 );
	}

	/**
	 * Format number with commas
	 *
	 * @param num Number to format
	 * @return Formatted number string
	 */
	private formatNumber( num: number ): string {
		return num.toString().replace( /\B(?=(\d{3})+(?!\d))/g, ',' );
	}

	/**
	 * Format date
	 *
	 * @param dateString Date string to format
	 * @return Formatted date string
	 */
	private formatDate( dateString: string ): string {
		if ( ! dateString || dateString === 'Never' ) {
			return 'Never';
		}

		const date = new Date( dateString );
		return date.toLocaleString();
	}
}

// Initialize admin functionality when DOM is ready
jQuery( document ).ready( () => {
	new CocoStockAdmin();
} );