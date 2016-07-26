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

	public function set_up_from_raw_atts( $atts ) {
		return true;
	}

	/**
	 * Generates a title for the record.
	 *
	 * If a non-empty title value is provided, it'll be used. Otherwise, we
	 * try to get something meaningful out of the description.
	 */
	public function generate_title( $title, $description ) {
		$title = trim( $title );

		if ( $title ) {
			return $title;
		}

		$lines = explode( "\n", $description );
		$parts = explode( ". ", $lines[0] );
		$generated = $parts[0];

		return $generated;
	}
}
