<?php

namespace BHS\Storehouse;

/**
 * Entrance class for admin functionality.
 *
 * @since 1.0.0
 */
class Admin {
	/**
	 * Register CSS and JS assets.
	 *
	 * @since 1.0.0
	 */
	public function register_assets() {
		wp_register_script(
			'bhssh_admin',
			BHSSH_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'jquery-ui-progressbar' ),
			BHSSH_VERSION,
			true
		);

		wp_register_style(
			'bhs-jquery-ui-progressbar',
			BHSSH_PLUGIN_URL . 'assets/css/jquery-ui.min.css',
			array(),
			BHSSH_VERSION
		);

		wp_register_style(
			'bhssh_admin',
			BHSSH_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			BHSSH_VERSION
		);
	}

	/**
	 * Hook into WP.
	 *
	 * @since 1.0.0
	 */
	public function set_up_hooks() {
		add_action( 'admin_menu', array( $this, 'route_admin_load' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'wp_ajax_bhssh_import_upload', array( $this, 'process_ajax_submit' ) );
		add_action( 'wp_ajax_bhssh_import_chunk', array( $this, 'process_ajax_chunk' ) );
	}

	/**
	 * Route the admin page.
	 *
	 * @since 1.0.0
	 */
	public function route_admin_load() {
		if ( $this->is_import_page() && current_user_can( 'manage_options' ) && ! empty( $_FILES['bhs-xml'] ) ) {
			check_admin_referer( 'bhs-import', 'bhs-import-nonce' );

			$success = $this->process_import( $_FILES['bhs-xml'] );

			$redirect_to = admin_url( 'edit.php?post_type=bhssh_record&page=bhs-import-records&results_key=' . urlencode( $success ) );
			wp_safe_redirect( $redirect_to );
			die();
		}

		$this->register_admin_menu();
	}

	/**
	 * Register admin menus.
	 *
	 * @since 1.0.0
	 */
	public function register_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=bhssh_record',
			__( 'Import PastPerfect Records', 'bhs-storehouse' ),
			__( 'Import', 'bhs-storehouse' ),
			'manage_options',
			'bhs-import-records',
			array( $this, 'render_import_page' )
		);
	}

	/**
	 * Render Import page.
	 *
	 * @since 1.0.0
	 */
	public function render_import_page() {
		wp_enqueue_script( 'bhssh_admin' );
		wp_enqueue_style( 'bhs-jquery-ui-progressbar' );
		wp_enqueue_style( 'bhssh_admin' );

		$results_key = isset( $_GET['results_key'] ) ? urldecode( $_GET['results_key'] ) : null;
		$results = null;
		if ( $results_key ) {
			$results = get_option( 'bhs_import_results_' . $results_key );
			// delete_option( 'bhs_import_results_' . $results_key );
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import PastPerfect Records', 'bhs-storehouse' ); ?></h1>

			<?php if ( $results ) : ?>
				<h2>Results</h2>

				<?php if ( $results['created'] ) : ?>
					<p><?php esc_html_e( 'The following records were created:', 'bhs-storehouse' ); ?></p>

					<pre class="bhs-import-results"><?php
						foreach ( $results['created'] as $created ) {
							echo esc_html( $created ) . "\n";
						}
					?></pre>
				<?php endif; ?>

				<?php if ( $results['updated'] ) : ?>
					<p><?php esc_html_e( 'The following records were updated:', 'bhs-storehouse' ); ?></p>

					<pre class="bhs-import-results"><?php
						foreach ( $results['updated'] as $updated ) {
							echo esc_html( $updated ) . "\n";
						}
					?></pre>
				<?php endif; ?>

				<?php if ( $results['failed'] ) : ?>
					<p><?php esc_html_e( 'The following records could not be processed:', 'bhs-storehouse' ); ?></p>

					<pre class="bhs-import-results"><?php
						foreach ( $results['failed'] as $failed ) {
							echo esc_html( $failed ) . "\n";
						}
					?></pre>
				<?php endif; ?>

				<style type="text/css">
					pre.bhs-import-results {
						width: 400px;
						height: 100px;
						overflow: scroll;
						background: #fff;
						padding: 5px;
					}
				</style>

				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=bhssh_record&page=bhs-import-records' ) ); ?>"><?php esc_html_e( '<<< Import another set of records', 'bhs-storehouse' ); ?></a>

			<?php else : ?>
				<form action="" method="post" enctype="multipart/form-data">
					<p><?php esc_html_e( 'Upload a PastPerfect-generated XML file to begin the import process.', 'bhs-storehouse' ); ?></p>
					<input type="file" name="bhs-xml" id="bhs-xml" />

					<p class="submit">
						<input type="submit" id="bhs-import-submit" class="button button-secondary" value="<?php esc_attr_e( 'Begin Import', 'bhs-storehouse' ); ?>" />
					</p>

					<div id="bhs-error" style="display: none;"></div>
					<div id="bhs-success" style="display: none;">
						<div id="bhs-import-progressbar"></div>
						<div id="bhs-import-message"></div>
					</div>

					<?php wp_nonce_field( 'bhs-import', 'bhs-import-nonce', false ); ?>
				</form>
			<?php endif; ?>

		</div><!-- .wrap -->
		<?php
	}

	protected function is_import_page() {
		global $pagenow;

		return 'edit.php' === $pagenow
			&& isset( $_GET['post_type'] )
			&& 'bhssh_record' === $_GET['post_type']
			&& isset( $_GET['page'] )
			&& 'bhs-import-records' === $_GET['page'];
	}

	protected function process_import( $file ) {
		$time = time();

		// Move the file to a permanent location.
		$x = new \XMLReader();
		$x->open( $file['tmp_name'] );

		$doc = new \DOMDocument;

		$results = array(
			'created' => array(),
			'updated' => array(),
			'failed' => array(),
		);

		// Move to the first dc-record node.
		while ( $x->read() && 'dc-record' !== $x->name );

		while ( 'dc-record' === $x->name ) {
			$node = simplexml_import_dom( $doc->importNode( $x->expand(), true ) );
			$atts = array();
			$id = '';
			foreach ( $node->children() as $a => $b ) {
				if ( 'identifier' === $a && ! $id ) {
					$id = (string) $b;
				}

				$atts[ $a ][] = (string) $b;
			}

			$record = new Record();

			$exists = (bool) $record->get_post_id_by_identifier( $id );

			$record->set_up_from_raw_atts( $atts );

			$saved = $record->save();

			if ( $saved ) {
				if ( $exists ) {
					$results['updated'][] = $id;
				} else {
					$results['created'][] = $id;
				}
			} else {
				$results['failed'][] = $id;
			}

			$x->next( 'dc-record' );
		}

		update_option( 'bhs_import_results_' . $time, $results );

		return $time;
	}

	public function add_meta_boxes() {
		add_meta_box(
			'bhs-dc-metadata',
			__( 'Dublin Core Metadata', 'bhs-storehouse' ),
			array( $this, 'render_meta_box' ),
			'bhssh_record'
		);
	}

	public function render_meta_box( $post ) {
		echo '<table class="form-table">';
		$record = new Record( $post->ID );
		foreach ( Record::get_dc_elements() as $element ) {
			$all_values = $record->get_dc_metadata( $element, false );
			$values_formatted = array();
			foreach ( $all_values as $key => $value ) {
				if ( is_array( $value ) ) {
					$this_item = '<dl>';
					$this_item .= sprintf(
						'<dt>%s</dt><dd>%s</dd>',
						esc_html( $key ),
						implode( "\n", array_map( esc_html( $value ) ) )
					);

					$this_item .= '</dl>';

					$values_formatted[] = '<p>' . $this_item . '</p>';
				} else {
					if ( 'relation_image' === $element ) {
						$value = $record->convert_filename_to_asset_path( $value );
						$value = sprintf(
							'<img class="bhs-image-preview" src="%s" /><p>%s</p>',
							esc_url( $value ),
							esc_url( $value )
						);
					} else {
						$value = esc_html( $value );
					}

					$values_formatted[] = '<p>' . $value . '</p>';
				}
			}

			printf(
				'<tr>
				  <th scope="row">%s</th>
				  <td>%s</td>
				</tr>',
				esc_html( $element ),
				implode( "\n", $values_formatted )
			);
		}
		echo '</table>';
	}

	public function process_ajax_submit() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			die( '-1' );
		}

		$nonce = isset( $_POST['bhs-import-nonce'] ) ? wp_unslash( $_POST['bhs-import-nonce'] ) : '';

		// @todo ?
		if ( ! wp_verify_nonce( $nonce, 'bhs-import' ) ) {
		//	die( '-1' );
		}

		if ( empty( $_FILES ) ) {
			wp_send_json_error( __( 'File could not be uploaded. Check the "post_max_size" directive in php.ini.', 'bhs-storehouse' ) );
		}

		if ( empty( $_FILES['file-0']['tmp_name'] ) ) {
			wp_send_json_error( __( 'File could not be uploaded. Check the "upload_max_filesize" directive in php.ini.', 'bhs-storehouse' ) );
		}

		// @todo filetypes?

		$uploads = wp_upload_dir();
		$timestamp = time();
		$dest = $uploads['basedir'] . '/bhs-import-' . $timestamp . '.xml';

		$moved = move_uploaded_file( $_FILES['file-0']['tmp_name'], $dest );
		if ( ! $moved ) {
			wp_send_json_error( __( 'File could not be uploaded.', 'bhs-storehouse' ) );
		}

		$x = new \XMLReader();
		$x->open( $dest );
		$doc = new \DOMDocument;

		// Move to the first dc-record node.
		while ( $x->read() && 'dc-record' !== $x->name );

		$count = 0;
		while ( 'dc-record' === $x->name ) {
			$count++;
			$x->next( 'dc-record' );
		}

		$run_key = 'bhs_import_run_' . $timestamp;
		$run_data = array(
			'xml' => $dest,
			'last' => 0,
			'count' => $count,
		);
		update_option( $run_key, $run_data );

		$retval = array(
			'run' => $timestamp,
			'pct' => 0,
		);

		wp_send_json_success( $retval );
	}

	public function process_ajax_chunk() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			die( '-1' );
		}

		// @todo nonce

		$run = isset( $_POST['run'] ) ? wp_unslash( $_POST['run'] ) : '';
		$run_key = 'bhs_import_run_' . $run;
		$run_data = get_option( $run_key );
		if ( ! $run || ! $run_data ) {
			wp_send_json_error( __( 'Could not find uploaded XML file. Please upload again.', 'bhs-storehouse' ) );
		}

		$last = $run_data['last'];

		$x = new \XMLReader();
		$x->open( $run_data['xml'] );

		$doc = new \DOMDocument;

		// Move to the first dc-record node.
		while ( $x->read() && 'dc-record' !== $x->name );

		$results = array();
		$current = 0;
		$increment = 5;

		while ( 'dc-record' === $x->name ) {
			if ( $current >= ( $last + $increment ) ) {
				break;
			}

			$current++;

			if ( $current <= $last ) {
				$x->next( 'dc-record' );
				continue;
			}

			$node = simplexml_import_dom( $doc->importNode( $x->expand(), true ) );
			$atts = array();
			$id = '';
			foreach ( $node->children() as $a => $b ) {
				if ( 'identifier' === $a && ! $id ) {
					$id = (string) $b;
				}

				$children = $b->children();
				if ( $children ) {
					$value = array();
					foreach ( $children as $ck => $cv ) {
						$atts[ $a ][ $ck ][] = (string) $cv;
					}
				} else {
					$atts[ $a ][] = (string) $b;
				}
			}

			$record = new Record();

			$exists = (bool) $record->get_post_id_by_identifier( $id );

			$record->set_up_from_raw_atts( $atts );

			$saved = $record->save();

			$result = array(
				'identifer' => $id,
				'status' => '',
			);

			if ( $saved ) {
				if ( $exists ) {
					$result['status'] = 'updated';
				} else {
					$result['status'] = 'created';
				}
			} else {
				$result['status'] = 'failed';
			}

			$results[] = $result;

			$x->next( 'dc-record' );
		}

		$run_data['last'] = $current;
		update_option( $run_key, $run_data );

		$retval = array(
			'run' => $run,
			'pct' => floor( 100 * ( $current / $run_data['count'] ) ),
			'results' => $results,
		);

		wp_send_json_success( $retval );
	}
}
