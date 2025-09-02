<?php
/**
 * Stock UI class
 *
 * @package CocoStockOptions
 */

namespace CocoStockOptions\Blocks;

/**
 * Stock UI class for handling UI elements
 */
class Stock_Block_UI {

	/**
	 * Helper function to get stock options data
	 * This integrates with your existing API system
	 *
	 * @param string $symbol Stock symbol
	 * @param int    $limit  Maximum number of results
	 * @return array|\WP_Error Stock data or error
	 */
	public static function get_stock_options_data( $symbol, $limit = 10 ) {
		// This would integrate with your existing stock options API
		// For now, return sample data

		if ( empty( $symbol ) ) {
			return new \WP_Error( 'invalid_symbol', __( 'Invalid stock symbol provided.', 'coco-stock-options' ) );
		}

		// Sample data structure - replace with actual API call
		return [
			'symbol'        => strtoupper( $symbol ),
			'options'       => [
				[
					'strike'  => '150.00',
					'type'    => 'call',
					'premium' => '2.50',
					'expiry'  => '2024-12-20',
					'volume'  => 1250,
				],
				[
					'strike'  => '145.00',
					'type'    => 'put',
					'premium' => '1.75',
					'expiry'  => '2024-12-20',
					'volume'  => 890,
				],
				// More sample data...
			],
			'last_updated'  => current_time( 'timestamp' ),
			'total_results' => 25,
		];
	}

	/**
	 * Render stock data as a table
	 *
	 * @param array $data Stock options data
	 * @param int   $limit Maximum rows to display
	 */
	public static function render_stock_table( $data, $limit ) {
		if ( empty( $data['options'] ) ) {
			return;
		}

		$options = array_slice( $data['options'], 0, $limit );
		?>
		<table class="stock-options-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Strike', 'coco-stock-options' ); ?></th>
					<th><?php esc_html_e( 'Type', 'coco-stock-options' ); ?></th>
					<th><?php esc_html_e( 'Premium', 'coco-stock-options' ); ?></th>
					<th><?php esc_html_e( 'Expiry', 'coco-stock-options' ); ?></th>
					<th><?php esc_html_e( 'Volume', 'coco-stock-options' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $options as $option ) : ?>
					<tr class="option-row option-type-<?php echo esc_attr( $option['type'] ); ?>">
						<td class="strike-price">$<?php echo esc_html( $option['strike'] ); ?></td>
						<td class="option-type"><?php echo esc_html( ucfirst( $option['type'] ) ); ?></td>
						<td class="premium">$<?php echo esc_html( $option['premium'] ); ?></td>
						<td class="expiry-date"><?php echo esc_html( date( 'M j, Y', strtotime( $option['expiry'] ) ) ); ?></td>
						<td class="volume"><?php echo esc_html( number_format( $option['volume'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render stock data as a chart placeholder
	 *
	 * @param array  $data Stock options data
	 * @param string $container_id Unique container ID
	 */
	public static function render_stock_chart( $data, $container_id ) {
		?>
		<div class="stock-chart-container">
			<div class="chart-placeholder" data-chart-data="<?php echo esc_attr( wp_json_encode( $data ) ); ?>">
				<p><?php esc_html_e( 'Chart visualization would be rendered here using JavaScript.', 'coco-stock-options' ); ?></p>
				<p><em><?php esc_html_e( 'This requires frontend JavaScript implementation.', 'coco-stock-options' ); ?></em></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render stock data summary
	 *
	 * @param array $data Stock options data
	 */
	public static function render_stock_summary( $data ) {
		if ( empty( $data['options'] ) ) {
			return;
		}

		$total_calls  = count( array_filter( $data['options'], fn( $opt ) => $opt['type'] === 'call' ) );
		$total_puts   = count( array_filter( $data['options'], fn( $opt ) => $opt['type'] === 'put' ) );
		$total_volume = array_sum( array_column( $data['options'], 'volume' ) );
		?>
		<div class="stock-summary">
			<div class="summary-stats">
				<div class="stat-item">
					<span class="stat-label"><?php esc_html_e( 'Total Calls:', 'coco-stock-options' ); ?></span>
					<span class="stat-value"><?php echo esc_html( $total_calls ); ?></span>
				</div>
				<div class="stat-item">
					<span class="stat-label"><?php esc_html_e( 'Total Puts:', 'coco-stock-options' ); ?></span>
					<span class="stat-value"><?php echo esc_html( $total_puts ); ?></span>
				</div>
				<div class="stat-item">
					<span class="stat-label"><?php esc_html_e( 'Total Volume:', 'coco-stock-options' ); ?></span>
					<span class="stat-value"><?php echo esc_html( number_format( $total_volume ) ); ?></span>
				</div>
			</div>
		</div>
		<?php
	}
}