<?php

namespace BHS\Storehouse;

class App {
	public static function init() {
		if ( is_admin() ) {
			$admin = new Admin();
			$admin->set_up_hooks();
		}

		$schema = new Schema();
		$schema->set_up_hooks();

		$endpoint = new Endpoint();
		$endpoint->set_up_hooks();
	}
}
