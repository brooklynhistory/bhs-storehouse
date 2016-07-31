<?php

namespace BHS\Storehouse;

/**
 * Entrance class for admin functionality.
 *
 * @since 1.0.0
 */
class Admin {
	/**
	 * Hook into WP.
	 *
	 * @since 1.0.0
	 */
	public function set_up_hooks() {
		add_action( 'admin_menu', array( $this, 'route_admin_load' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
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
					<input accept="xml" type="file" name="bhs-xml" />

					<p class="submit">
						<input type="submit" class="button button-secondary" value="<?php esc_attr_e( 'Begin Import', 'bhs-storehouse' ); ?>" />
					</p>

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
		foreach ( Record::get_dc_elements() as $element ) {
			$values = get_post_meta( $post->ID, 'bhs_dc_' . $element );
			$values_formatted = array();
			foreach ( $values as $value ) {
				$values_formatted[] = '<p>' . esc_html( $value ) . '</p>';
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
}
