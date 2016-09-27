<?php

namespace BHS\Storehouse;

/**
 * Record object.
 *
 * @since 1.0.0
 */
class Record {
	protected static $dc_elements = array(
		'contributor', 'coverage',
		'creator', 'creator_alpha',
		'date', 'description',
		'format', 'identifier', 'language', 'publisher',
		'relation_findingaid', 'relation_ohms', 'relation_image', 'relation_attachment',
		'rights', 'right_request',
		'source',
		'subject_people', 'subject_subject', 'subject_places',
		'title_collection', 'title_title', 'title_accession',
		'type',
	);

	protected static $singular_elements = array(
		'date', 'format', 'identifier', 'relation_ohms', 'rights', 'rights_request',
		'title_collection', 'title_title', 'title_accession',
	);

	protected $dc_metadata = array();

	protected $asset_base = 'http://brooklynhistory.org/wordpress-assets/';

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

		// post_title is a combination of identifier + title_title + title_collection.
		$title_parts = array( $this->get_dc_metadata( 'identifier' ) );

		if ( $title_title = $this->get_dc_metadata( 'title_title' ) ) {
			$title_parts[] = $title_title;
		}

		if ( $title_collection = $this->get_dc_metadata( 'title_collection' ) ) {
			$title_parts[] = $title_collection;
		}

		$post_data['post_title'] = implode( ' - ', $title_parts );

		// post_content is 'description'.
		$post_data['post_content'] = $this->get_dc_metadata( 'description' );

		// post_name is a URL-safe version of the identifier.
		$post_data['post_name'] = sanitize_title( $this->get_dc_metadata( 'identifier' ) );

		if ( $is_new ) {
			$post_id = wp_insert_post( $post_data );
		} else {
			$post_id = wp_update_post( $post_data );
		}

		if ( $post_id ) {
			wp_set_object_terms( $post_id, $this->get_dc_metadata( 'subject', false ), 'bhssh_subject' );

			foreach ( $this->dc_metadata as $dc_key => $_ ) {
				$meta_key = 'bhs_dc_' . $dc_key;

				// Delete existing keys, in case of update.
				delete_post_meta( $post_id, $meta_key );

				// Note: 'subject' is being added here as well as in a taxonomy.
				$f = $this->get_dc_metadata( $dc_key, false );
				if ( is_array( $f ) ) {
					foreach ( $f as $value ) {
						add_post_meta( $post_id, $meta_key, $this->addslashes_deep( $value ) );
					}
				} else {
					add_post_meta( $post_id, $meta_key, $this->addslashes_deep( $f ) );
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
			'post_status' => 'any',
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
			$get_single = in_array( $element, self::get_singular_elements(), true );
			$values = get_post_meta( $post_id, 'bhs_dc_' . $element, $get_single );

			$this->dc_metadata[ $element ] = $values;
		}
	}

	public static function get_dc_elements() {
		return self::$dc_elements;
	}

	public static function get_singular_elements() {
		return self::$singular_elements;
	}

	public function format_for_endpoint() {
		$dc_metadata = array();

		// @todo Should some of these be flattened?
		foreach ( self::get_dc_elements() as $dc_element ) {
			$value = $this->get_dc_metadata( $dc_element, false );

			if ( 'relation_image' === $dc_element ) {
				$value = array_map( array( $this, 'convert_filename_to_asset_path' ), $value );
			}

			$dc_metadata[ $dc_element ] = $value;
		}

		return $dc_metadata;
	}

	public function convert_filename_to_asset_path( $value ) {
		$value = str_replace( '\\', '/', $value );
		$value = trailingslashit( $this->asset_base ) . $value;
		return $value;
	}

	public function addslashes_deep( $value ) {
		if ( is_array( $value ) ) {
			return array_map( array( $this, 'addslashes_deep' ), $value );
		} elseif ( is_object( $value ) ) {
			$vars = get_object_vars( $value );
			foreach ( $vars as $key => $data ) {
				$value->{$key} = $this->addslashes_deep( $data );
			}
			return $value;
		}

		return addslashes( $value );
	}
}
