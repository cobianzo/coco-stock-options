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
						// Reload page to refresh buffer info after adding stock
						setTimeout( () => {
							window.location.reload();
						}, 1000 );
					} else {
						showMessage( response.data, 'error', '#add-stock-result' );
					}
				} )
				.catch( ( error: unknown ) => {
					console.error(error);
					showMessage( 'An error occurred while adding the stock: ' + error, 'error', '#add-stock-result' );
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






}

// Initialize admin functionality when DOM is ready
if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', () => {
		new CocoStockAdmin();
	} );
} else {
	new CocoStockAdmin();
}