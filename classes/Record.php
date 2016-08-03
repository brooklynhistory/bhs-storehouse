<?php

namespace BHS\Storehouse;

/**
 * Record object.
 *
 * @since 1.0.0
 */
class Record {
	protected static $dc_elements = array(
		'contributor', 'coverage', 'creator', 'date', 'description',
		'format', 'identifier', 'language', 'publisher', 'relation',
		'rights', 'source', 'subject', 'title', 'type',
	);

	protected $dc_metadata = array();

	protected $post;

	public function __construct( $post_id = null ) {
		if ( $post_id ) {
			$this->populate( $post_id );
		}
	}

	public function set_up_from_raw_atts( $atts ) {
		$dc_elements = self::get_dc_elements();
		foreach ( $atts as $att_type => $att ) {
			if ( in_array( $att_type, $dc_elements ) ) {
				$this->dc_metadata[ $att_type ] = $att;
			}
		}

		return true;
	}

	public function get_dc_metadata( $field, $single = true ) {
		if ( isset( $this->dc_metadata[ $field ] ) ) {
			$value = $this->dc_metadata[ $field ];
		} else {
			$value = $single ? '' : array();
		}

		if ( $single && is_array( $value ) ) {
			$value = reset( $value );
		}

		return $value;
	}

	public function set_dc_metadata( $field, $value ) {
		$this->dc_metadata[ $field ] = $value;
	}

	public function save() {
		// Determine whether this is a new or existing record.
		$identifier = $this->get_dc_metadata( 'identifier' );
		$post_id = null;
		$is_new = true;
		if ( $identifier ) {
			$post_id = $this->get_post_id_by_identifier( $identifier );
		}

		if ( $post_id ) {
			$post_data = array(
				'ID' => $post_id,
			);
			$is_new = false;
		} else {
			// Build post data for WP.
			$post_data = array(
				'post_type' => 'bhssh_record',
				'post_status' => 'publish',
			);
		}

		// post_title is a combination of identifier + title.
		if ( $this->get_dc_metadata( 'title' ) ) {
			$post_data['post_title'] = sprintf(
				'%s - %s',
				$this->get_dc_metadata( 'identifier' ),
				$this->get_dc_metadata( 'title' )
			);
		} else {
			$post_data['post_title'] = $this->get_dc_metadata( 'identifier' );
		}

		// post_content is 'description'.
		$post_data['post_content'] = $this->get_dc_metadata( 'description' );

		// post_name is a URL-safe version of the identifier.
		$post_data['post_name'] = sanitize_title( $this->get_dc_metadata( 'identifier' ) );

		$post_id = wp_insert_post( $post_data );

		if ( $post_id ) {
			wp_set_object_terms( $post_id, $this->get_dc_metadata( 'subject', false ), 'bhssh_subject' );

			foreach ( $this->dc_metadata as $dc_key => $_ ) {
				$meta_key = 'bhs_dc_' . $dc_key;

				// Delete existing keys, in case of update.
				delete_post_meta( $post_id, $meta_key );

				// Note: 'subject' is being added here as well as in a taxonomy.
				foreach ( $this->get_dc_metadata( $dc_key, false ) as $value ) {
					add_post_meta( $post_id, $meta_key, $value );
				}
			}

			$this->populate( $post_id );
		}

		return $post_id;
	}

	public function get_post_id_by_identifier( $identifier ) {
		$found = get_posts( array(
			'posts_per_page' => 1,
			'post_type' => 'bhssh_record',
			'meta_query' => array(
				array(
					'key' => 'bhs_dc_identifier',
					'value' => $identifier,
				),
			),
			'fields' => 'ids',
		) );

		$post_id = null;
		if ( $found ) {
			$post_id = reset( $found );
		}

		return $post_id;
	}

	/**
	 * Populate object from database ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id
	 */
	protected function populate( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || 'bhssh_record' !== $post->post_type ) {
			return;
		}

		$this->post = $post;

		foreach ( self::get_dc_elements() as $element ) {
			$values = get_post_meta( $post_id, 'bhs_dc_' . $element );
			$this->dc_metadata[ $element ] = $values;
		}
	}

	public static function get_dc_elements() {
		return self::$dc_elements;
	}

	public function format_for_endpoint() {
		$dc_metadata = array();
		foreach ( self::get_dc_elements() as $dc_element ) {
			$dc_metadata[ $dc_element ] = $this->get_dc_metadata( $dc_element, false );
		}

		return $dc_metadata;
	}
}
