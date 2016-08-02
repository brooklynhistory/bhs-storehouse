( function( $ ) {
	var $errorDiv, $progressbar, $successDiv;

	handleError = function( message ) {
		$successDiv.hide();
		$errorDiv.html( message ).show();
	}

	beginImport = function( data ) {
		$errorDiv.hide();
		$successDiv.show();

		$progressbar = $( '#bhs-import-progressbar' );
		$progressbar.progressbar({
			value: data.pct
		});

		// Next: kick off first step. Should happen in a separate method.
		importChunk( data.run );
	}

	importChunk = function( run ) {
		$.ajax( {
			url: ajaxurl + '?action=bhssh_import_chunk',
			data: {
				run: run
			},
			type: 'POST',
			success: function( response ) {
				if ( response.success ) {
					$progressbar.progressbar( 'value', response.data.pct );
					printLog( response.data.results );

					if ( response.data.pct < 100 ) {
						importChunk( response.data.run );
					} else {
						$successDiv.append( '<p>Complete!</p>' );
					}
				} else {
					// todo
				}
			}
		} );
	}

	printLog = function( results ) {
		var html = '';
		var r;

		for ( var i = 0; i < results.length; i++ ) {
			r = results[i];
			switch ( r.status ) {
				// todo localization
				case 'created' :
					html += '<span class="bhs-import-record-status bhs-import-record-success">Success</span>: Created record ' + r.identifer;
				break;

				case 'updated' :
					html += '<span class="bhs-import-record-status bhs-import-record-success">Success</span>: Updated record ' + r.identifer;
				break;

				case 'failed' :
					html += '<span class="bhs-import-record-status bhs-import-record-failure">Failure</span>: Could not create or update record ' + r.identifer;
				break;

				default:
				break;
			}

			html += '<br />';
		}

		$successDiv.append( html );
	}

	$(document).ready( function() {
		$errorDiv = $( '#bhs-error' );
		$successDiv = $( '#bhs-success' );

		$('#bhs-import-submit').click( function(e) {
			e.preventDefault();

			var data = new FormData();
			$.each( $( '#bhs-xml' )[0].files, function( i, file ) {
				data.append( 'file-' + i, file );
			} );

			data.append( 'bhs-import-nonce', $( '#bhs-import-nonce' ).val() );

			$.ajax( {
				url: ajaxurl + '?action=bhssh_import_upload',
				data: data,
				cache: false,
				contentType: false,
				processData: false,
				type: 'POST',
				success: function( response ) {
					if ( response.success ) {
						beginImport( response.data );
					} else {
						handleError( response.data );
					}
				}
			} );
		} );
	} );
} )( jQuery );
