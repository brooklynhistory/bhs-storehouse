<?php

namespace BHS\Storehouse;

/**
 * Record object.
 *
 * @since 1.0.0
 */
class Record {
	protected $post_field_map = array(
		'description' => array(
			'post_field' => 'post_content',
		),
		'title' => array(
			'post_field' => 'post_title',
		),
		'subject' => array(
			'taxonomy' => 'bhssh_subject',
		),
		'sterm' => array(
			'taxonomy' => 'bhssh_sterms',
		),
	);

	protected $fields = array();

	public function set_up_from_raw_atts( $atts ) {
		$this->set( 'title', $this->generate_title( $atts ) );

		if ( isset( $atts['subjects'] ) ) {
			$this->set( 'subject', $this->generate_multiples( $atts['subjects'] ) );
		}

		if ( isset( $atts['sterms'] ) ) {
			$this->set( 'sterm', $this->generate_multiples( $atts['sterms'] ) );
		}

		return true;
	}

	public function set( $field, $value ) {
		$this->fields[ $field ] = $value;
	}

	public function get( $field ) {
		if ( isset( $this->fields[ $field ] ) ) {
			return $this->fields[ $field ];
		}

		return null;
	}

	/**
	 * Generates a title for the record.
	 *
	 * If a non-empty title value is provided, it'll be used. Otherwise, we
	 * try to get something meaningful out of the description.
	 */
	public function generate_title( $atts ) {
		$title = $description = '';

		if ( isset( $atts['title'] ) ) {
			$title = trim( $atts['title'] );
		}

		if ( isset( $atts['descrip'] ) ) {
			$description = trim( $atts['descrip'] );
		}

		if ( $title ) {
			return $title;
		}

		$lines = explode( "\n", $description );
		$parts = explode( ". ", $lines[0] );
		$generated = $parts[0];

		return $generated;
	}

	/**
	 * Generate multiples from a text blob.
	 *
	 * Used for things like 'subject'.
	 *
	 * Uses line breaks as delimiters. @todo is this reliable?
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function generate_multiples( $string ) {
		$items = explode( "\n", $string );
		$items = array_filter( $items );
		$items = array_map( 'trim', $items );
		return array_values( $items );
	}
}
