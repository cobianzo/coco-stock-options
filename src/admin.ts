/**
 * Coco Stock Options Admin Script
 * Handles all admin page interactions for the Coco Stock Options plugin.
 *
 * Main functionalities of this script:
 * - Manages the "Add Stock" form, including AJAX submission and validation.
 * - Handles the "Force Buffer Processing" button to trigger buffer updates via AJAX.
 * - Periodically auto-refreshes buffer and cron job information on the admin page.
 * - Updates UI elements with the latest processing, buffer, and cron status.
 * - Displays success, error, and informational messages to the admin user.
 * - Provides utility methods for AJAX requests, number formatting, and UI effects.
 *
 * @package CocoStockOptions
 */

// Import utility functions
import { makeAjaxRequest, showMessage, formatNumber, formatDate } from './utils';

// Import admin styles
import './admin.css';

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
		// Wait for DOM to be ready
		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', () => {
				this.initAdminInterface();
			} );
		} else {
			this.initAdminInterface();
		}
	}

	/**
	 * Initialize admin interface
	 */
	private initAdminInterface(): void {
		this.initAddStock();
		this.initForceBuffer();
		this.initAutoRefresh();
	}

	/**
	 * Initialize add stock functionality
	 */
	private initAddStock(): void {
		const addStockButton = document.getElementById( 'add-stock' );
		const newStockSymbolInput = document.getElementById( 'new-stock-symbol' ) as HTMLInputElement;

		if ( ! addStockButton || ! newStockSymbolInput ) {
			return;
		}

		addStockButton.addEventListener( 'click', ( e ) => {
			e.preventDefault();

			const symbol = newStockSymbolInput.value;
			const trimmedSymbol = symbol.trim().toUpperCase();

			if ( ! trimmedSymbol ) {
				showMessage( 'Please enter a stock symbol', 'error', '#add-stock-result' );
				return;
			}

			// Show loading state
			const button = e.currentTarget as HTMLButtonElement;
			const originalText = button.textContent || '';
			button.disabled = true;
			button.textContent = 'Adding...';

			// Make AJAX request
			makeAjaxRequest( {
				action: 'cocostock_add_stock',
				symbol: trimmedSymbol,
			} )
				.then( ( response ) => {
					if ( response.success ) {
						showMessage( response.data, 'success', '#add-stock-result' );
						newStockSymbolInput.value = '';
						// Reload page after short delay
						setTimeout( () => {
							location.reload();
						}, 1500 );
					} else {
						showMessage( response.data, 'error', '#add-stock-result' );
					}
				} )
				.catch( () => {
					showMessage( 'An error occurred while adding the stock', 'error', '#add-stock-result' );
				} )
				.finally( () => {
					button.disabled = false;
					button.textContent = originalText;
				} );
		} );

		// Allow Enter key to submit
		newStockSymbolInput.addEventListener( 'keypress', ( e ) => {
			if ( e.key === 'Enter' ) {
				addStockButton.click();
			}
		} );
	}

	/**
	 * Initialize force buffer functionality
	 */
	private initForceBuffer(): void {
		const forceBufferButton = document.getElementById( 'force-buffer' );

		if ( ! forceBufferButton ) {
			return;
		}

		forceBufferButton.addEventListener( 'click', ( e ) => {
			e.preventDefault();

			const button = e.currentTarget as HTMLButtonElement;
			const originalText = button.textContent || '';

			button.disabled = true;
			button.textContent = 'Processing...';

			makeAjaxRequest( {
				action: 'cocostock_force_buffer',
			} )
				.then( ( response ) => {
					if ( response.success ) {
						showMessage( 'Buffer processing completed', 'success', '#buffer-result' );
						// Reload page after short delay
						setTimeout( () => {
							location.reload();
						}, 2000 );
					} else {
						showMessage( 'Failed to process buffer', 'error', '#buffer-result' );
					}
				} )
				.catch( () => {
					showMessage( 'An error occurred while processing buffer', 'error', '#buffer-result' );
				} )
				.finally( () => {
					button.disabled = false;
					button.textContent = originalText;
				} );
		} );
	}

	/**
	 * Initialize auto-refresh functionality
	 */
	private initAutoRefresh(): void {
		// Auto-refresh buffer info every 30 seconds
		setInterval( () => {
			this.refreshBufferInfo();
		}, 30000 );
	}

	/**
	 * Refresh buffer information
	 */
	private refreshBufferInfo(): void {
		makeAjaxRequest( {
			action: 'cocostock_get_buffer_info',
		} )
			.then( ( response ) => {
				if ( response.success ) {
					this.updateBufferDisplay( response.data );
				}
			} )
			.catch( () => {
				// Silently fail for auto-refresh
			} );
	}

	/**
	 * Update buffer display
	 *
	 * @param data Buffer data
	 */
	private updateBufferDisplay( data: any ): void {
		const bufferInfoElement = document.querySelector( '.cocostock-buffer-info' );

		if ( ! bufferInfoElement ) {
			return;
		}

		const paragraphs = bufferInfoElement.querySelectorAll( 'p' );

		// Update buffer count
		if ( paragraphs[ 0 ] ) {
			paragraphs[ 0 ].innerHTML = `<strong>Stocks in Buffer:</strong> ${ data.count }`;
		}

		// Update last processed time
		if ( paragraphs[ 1 ] ) {
			paragraphs[ 1 ].innerHTML = `<strong>Last Processing:</strong> ${ data.last_processed }`;
		}

		// Update next processing time
		if ( paragraphs[ 2 ] ) {
			paragraphs[ 2 ].innerHTML = `<strong>Next Processing:</strong> ${ data.next_processing }`;
		}
	}
}

// Initialize admin functionality when DOM is ready
if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', () => {
		new CocoStockAdmin();
	} );
} else {
	new CocoStockAdmin();
}