<?php

namespace BHS\Storehouse\Endpoints\V2;

use BHS\Storehouse\Record;

/**
 * REST API endpoint.
 *
 * @since 1.0.0
 */
class Endpoint {
	protected $namespace = 'bhs';
	protected $api_version = 'v2';

	/**
	 * Hook into WordPress.
	 *
	 * @since 1.0.0
	 */
	public function set_up_hooks() {
		add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
	}

	/**
	 * Register route.
	 *
	 * @since 1.0.0
	 */
	public function register_rest_route() {
		register_rest_route(
			"{$this->namespace}/{$this->api_version}",
			'/record/(?P<identifier>[^/]+)',
			array(
				'methods' => 'GET',
				'callback' => array( $this, 'get_record' ),
			)
		);
	}

	/**
	 * Handle record requests.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request
	 */
	public function get_record( \WP_REST_Request $request ) {
		$params = $request->get_params();

		if ( ! isset( $params['identifier'] ) ) {
			return new \WP_Error( 'bhs_no_identifier', 'No identifier provided.', array( 'status' => 404 ) );
		}

		$r = new Record();
		$record_id = $r->get_post_id_by_identifier( $params['identifier'] );

		if ( ! $record_id ) {
			return new \WP_Error( 'bhs_no_identifier', 'No record found matching that identifier.', array( 'status' => 404 ) );
		}

		$record = new Record( $record_id );

		$retval = $record->format_for_endpoint( 2 );

		$response = rest_ensure_response( $retval );
		$response->set_status( 200 );
		return $response;
	}
}
