<?php

namespace BHS\Storehouse;

/**
 * Record object.
 *
 * @since 1.0.0
 */
class Record {
	protected $field_map = array(

	);

	protected $fields = array();

	public function set_up_from_raw_atts( $atts ) {
		$this->set( 'title', $this->generate_title( $atts ) );
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
}
