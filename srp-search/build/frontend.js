/**
 * SRP Search — Frontend JS v1.2.0
 * - Uses data-srp-uid scoping so multiple instances never clash.
 * - MutationObserver binds to SSR-rendered forms in the block editor.
 * - Debug mode surfaces raw error messages from the server.
 * - srp_run_diagnostic() available in browser console when debug is on.
 */

( function ( $ ) {
	'use strict';

	// ── Bind a single block instance ────────────────────────────────────────
	function bindInstance( wrap ) {
		// Prevent double-binding.
		if ( $( wrap ).data( 'srp-bound' ) ) return;
		$( wrap ).data( 'srp-bound', true );

		const $wrap     = $( wrap );
		const $form     = $wrap.find( '.srp-form' );
		const $results  = $wrap.find( '.srp-results' );
		const $validMsg = $wrap.find( '.srp-validation-msg' );
		const $submit   = $wrap.find( '.srp-submit' );
		const $major    = $wrap.find( 'select[name="major"]' );

		// Load majors.
		$.ajax( {
			url:    srpData.ajaxUrl,
			method: 'POST',
			data:   { action: 'srp_get_majors', nonce: srpData.nonce },
		} ).done( function ( res ) {
			if ( res.success && res.data.majors.length ) {
				res.data.majors.forEach( function ( major ) {
					$major.append( $( '<option>' ).val( major ).text( major ) );
				} );
			} else if ( ! res.success ) {
				showError( $validMsg, res.data.message || 'Could not load majors.' );
			}
		} ).fail( function ( xhr ) {
			if ( srpData.debug ) {
				showError( $validMsg, 'Major load XHR failed: ' + xhr.status + ' ' + xhr.statusText );
			}
		} );

		// Form submit.
		$form.on( 'submit', function ( e ) {
			e.preventDefault();
			$validMsg.text( '' ).removeClass( 'srp-error srp-info' );

			const last_name = $.trim( $wrap.find( 'input[name="last_name"]' ).val() );
			const year      = $.trim( $wrap.find( 'input[name="year"]' ).val() );
			const title     = $.trim( $wrap.find( 'input[name="title"]' ).val() );
			const major     = $major.val();
			const advisor   = $.trim( $wrap.find( 'input[name="advisor"]' ).val() );

			if ( ! last_name && ! year && ! title && ! major && ! advisor ) {
				showError( $validMsg, 'Please enter at least one search field.' );
				return;
			}

			if ( year && ! /^\d{4}$/.test( year ) ) {
				showError( $validMsg, 'Graduation year must be a 4-digit number.' );
				return;
			}

			setLoading( $submit, true );
			$results.html( '' );

			$.ajax( {
				url:    srpData.ajaxUrl,
				method: 'POST',
				data:   {
					action:    'srp_search',
					nonce:     srpData.nonce,
					last_name: last_name,
					year:      year,
					title:     title,
					major:     major,
					advisor:   advisor,
				},
			} )
			.done( function ( res ) {
				if ( res.success ) {
					renderResults( $results, res.data.results, res.data.count );
				} else {
					showError( $validMsg, res.data.message || 'An error occurred.' );
				}
			} )
			.fail( function ( xhr ) {
				const msg = srpData.debug
					? 'AJAX failed: HTTP ' + xhr.status + ' — ' + ( xhr.responseText || xhr.statusText )
					: 'Could not connect to the server. Please try again.';
				showError( $validMsg, msg );
			} )
			.always( function () {
				setLoading( $submit, false );
			} );
		} );
	}

	// ── Bind all existing instances on load ──────────────────────────────────
	function bindAll() {
		$( '.srp-search-wrap' ).each( function () {
			bindInstance( this );
		} );
	}

	// ── MutationObserver: catches SSR forms rendered after script load ────────
	// This is what makes the block editor work — SSR output arrives after JS runs.
	function observeForBlocks() {
		if ( ! window.MutationObserver ) return;

		const observer = new MutationObserver( function ( mutations ) {
			mutations.forEach( function ( mutation ) {
				mutation.addedNodes.forEach( function ( node ) {
					if ( node.nodeType !== 1 ) return;

					if ( $( node ).hasClass( 'srp-search-wrap' ) ) {
						bindInstance( node );
					}

					$( node ).find( '.srp-search-wrap' ).each( function () {
						bindInstance( this );
					} );
				} );
			} );
		} );

		observer.observe( document.body, { childList: true, subtree: true } );
	}

	// ── Diagnostic: run from browser console as srp_run_diagnostic() ─────────
	window.srp_run_diagnostic = function () {
		if ( ! window.srpData ) {
			console.error( 'SRP: srpData not found — is the plugin active on this page?' );
			return;
		}
		console.log( 'SRP: Running diagnostic...' );
		$.ajax( {
			url:    srpData.ajaxUrl,
			method: 'POST',
			data:   { action: 'srp_check', nonce: srpData.nonce },
		} ).done( function ( res ) {
			if ( res.success ) {
				console.table( res.data );
				if ( ! res.data.pdo_sqlsrv ) {
					console.error( 'SRP: pdo_sqlsrv extension is NOT loaded in this PHP SAPI (' + res.data.php_sapi + '). The extension may be installed for CLI but not for the web server.' );
				}
				if ( ! res.data.connection ) {
					console.error( 'SRP: DB connection failed — ' + res.data.connection_err );
				}
				if ( res.data.connection && ! res.data.query_test ) {
					console.error( 'SRP: Connected but query failed — ' + res.data.query_err );
				}
				if ( res.data.connection && res.data.query_test ) {
					console.log( 'SRP: ✅ Everything looks good.' );
				}
			} else {
				console.error( 'SRP diagnostic AJAX error:', res.data );
			}
		} ).fail( function ( xhr ) {
			console.error( 'SRP: Diagnostic AJAX request failed — HTTP ' + xhr.status, xhr.responseText );
		} );
	};

	// ── Helpers ──────────────────────────────────────────────────────────────
	function setLoading( $btn, on ) {
		$btn.prop( 'disabled', on ).toggleClass( 'srp-loading', on );
	}

	function showError( $el, msg ) {
		$el.text( msg ).addClass( 'srp-error' );
	}

	function renderResults( $results, rows, count ) {
		if ( ! rows || rows.length === 0 ) {
			$results.html( '<p class="srp-no-results">No projects found matching your search.</p>' );
			return;
		}

		const countLabel = count === 1 ? '1 project found' : count + ' projects found';
		let html = '<div class="srp-results-header"><span class="srp-results-count">' + escHtml( countLabel ) + '</span></div>';
		html += '<div class="srp-table-wrap"><table class="srp-table">';
		html += '<thead><tr><th scope="col">Student</th><th scope="col">Year</th><th scope="col">Title</th><th scope="col">Major 1</th><th scope="col">Major 2</th><th scope="col">Advisor</th></tr></thead>';
		html += '<tbody>';

		rows.forEach( function ( row ) {
			const student  = escHtml( ( row.STUDENT_FIRST || '' ) + ' ' + ( row.STUDENT_LAST || '' ) );
			const year     = escHtml( row.YEAR );
			const title    = escHtml( row.IS_TITLE );
			const major1   = escHtml( row.MAJOR_1 || '—' );
			const major2   = escHtml( row.MAJOR_2 || '—' );
			const advisor  = ( row.ADVISOR_FIRST || row.ADVISOR_LAST )
				? escHtml( ( row.ADVISOR_FIRST || '' ) + ' ' + ( row.ADVISOR_LAST || '' ) )
				: '—';

			html += '<tr>';
			html += '<td data-label="Student">' + student + '</td>';
			html += '<td data-label="Year">'    + year    + '</td>';
			html += '<td data-label="Title">'   + title   + '</td>';
			html += '<td data-label="Major 1">' + major1  + '</td>';
			html += '<td data-label="Major 2">' + major2  + '</td>';
			html += '<td data-label="Advisor">' + advisor + '</td>';
			html += '</tr>';
		} );

		html += '</tbody></table></div>';
		$results.html( html );
		$( 'html, body' ).animate( { scrollTop: $results.offset().top - 40 }, 400 );
	}

	function escHtml( str ) {
		if ( str === null || str === undefined ) return '';
		return String( str )
			.replace( /&/g, '&amp;' ).replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' ).replace( /"/g, '&quot;' )
			.replace( /'/g, '&#039;' );
	}

	// ── Init ─────────────────────────────────────────────────────────────────
	$( function () {
		bindAll();
		observeForBlocks();

		if ( srpData.debug ) {
			console.log( 'SRP Search: debug mode on. Run srp_run_diagnostic() in the console for a full report.' );
		}
	} );

} )( jQuery );
