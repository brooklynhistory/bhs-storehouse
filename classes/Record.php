<?php

namespace BHS\Storehouse;

/**
 * Record object.
 *
 * @since 1.0.0
 */
class Record {
	protected $dc_elements = array(
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
		foreach ( $atts as $att_type => $att ) {
			if ( in_array( $att_type, $this->dc_elements ) ) {
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

	public function save() {
		// Build post data for WP.
		$post_data = array(
			'post_type' => 'bhssh_record',
			'post_status' => 'publish',
		);

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

		foreach ( $this->dc_elements as $element ) {
			$values = get_post_meta( $post_id, 'bhs_dc_' . $element );
			$this->dc_metadata[ $element ] = $values;
		}
	}
}
