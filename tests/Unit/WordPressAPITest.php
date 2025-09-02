<?php



/**
 * usage
 * npm run test:php tests/Unit/WordPressAPITest.php
 * or
 * npm run test:php:single
 */
class HelpersTest extends WP_UnitTestCase
{

	public function test_filter_options_by_bid() {

		$mock_response = json_decode( file_get_contents( __DIR__ . '/../fixtures/mock-response-stock-options-by-id.json' ), true );

		$options_data = $mock_response['options'];

		$filtered_data = $this->wordpress_api->filter_options_by_bid( $options_data );

		$this->assertCount( 1, $filtered_data );
	}

}
