/**
 * SRP Search — Frontend JS v1.3.0
 * Features: year dropdown, majors dropdown, pagination (Load More),
 *           column visibility, configurable ordering, debug mode.
 */
( function ( $ ) {
	'use strict';

	// ── Bind a single block instance ────────────────────────────────────────
	function bindInstance( wrap ) {
		if ( $( wrap ).data( 'srp-bound' ) ) return;
		$( wrap ).data( 'srp-bound', true );

		const $wrap      = $( wrap );
		const $form      = $wrap.find( '.srp-form' );
		const $results   = $wrap.find( '.srp-results' );
		const $validMsg  = $wrap.find( '.srp-validation-msg' );
		const $submit    = $wrap.find( '.srp-submit' );
		const $majorSel  = $wrap.find( 'select[name="major"]' );
		const $yearSel   = $wrap.find( 'select[name="year"]' );
		const $loadWrap  = $wrap.find( '.srp-load-more-wrap' );
		const $loadBtn   = $wrap.find( '.srp-load-more' );

		// Read block-level settings from data attributes.
		const perPage     = parseInt( $wrap.data( 'srp-per-page' ) || 25, 10 );
		const noResults   = $wrap.data( 'srp-no-results' ) || 'No projects found matching your search.';
		const showMajor2  = $wrap.data( 'srp-show-major2' )  !== '0';
		const showAdvisor = $wrap.data( 'srp-show-advisor' ) !== '0';
		const orderBy     = $wrap.data( 'srp-order-by' ) || 'year_asc_name_asc';

		// Pagination state — reset on each new search.
		let currentOffset  = 0;
		let currentParams  = {};
		let isLoadingMore  = false;

		// ── Load years ────────────────────────────────────────────────────
		$.ajax( {
			url:    srpData.ajaxUrl,
			method: 'POST',
			data:   { action: 'srp_get_years', nonce: srpData.nonce },
		} ).done( function ( res ) {
			if ( res.success && res.data.years.length ) {
				res.data.years.forEach( function ( year ) {
					$yearSel.append( $( '<option>' ).val( year ).text( year ) );
				} );
			} else if ( ! res.success && srpData.debug ) {
				console.warn( 'SRP: year load failed —', res.data.message );
			}
		} ).fail( function ( xhr ) {
			if ( srpData.debug ) console.warn( 'SRP: year XHR failed', xhr.status );
		} );

		// ── Load majors ───────────────────────────────────────────────────
		$.ajax( {
			url:    srpData.ajaxUrl,
			method: 'POST',
			data:   { action: 'srp_get_majors', nonce: srpData.nonce },
		} ).done( function ( res ) {
			if ( res.success && res.data.majors.length ) {
				res.data.majors.forEach( function ( major ) {
					$majorSel.append( $( '<option>' ).val( major ).text( major ) );
				} );
			} else if ( ! res.success ) {
				showError( $validMsg, res.data.message || 'Could not load majors.' );
			}
		} ).fail( function ( xhr ) {
			if ( srpData.debug ) {
				showError( $validMsg, 'Major load XHR failed: ' + xhr.status + ' ' + xhr.statusText );
			}
		} );

		// ── Form submit ───────────────────────────────────────────────────
		$form.on( 'submit', function ( e ) {
			e.preventDefault();
			$validMsg.text( '' ).removeClass( 'srp-error srp-info' );

			const params = collectParams();
			if ( ! params ) return; // validation failed

			// Reset pagination state for a fresh search.
			currentOffset = 0;
			currentParams = params;
			$results.html( '' );
			$loadWrap.hide();

			setLoading( $submit, true );
			doSearch( 0, params, false );
		} );

		// ── Load More ─────────────────────────────────────────────────────
		$loadBtn.on( 'click', function () {
			if ( isLoadingMore ) return;
			isLoadingMore = true;
			$loadBtn.prop( 'disabled', true ).text( 'Loading…' );
			doSearch( currentOffset, currentParams, true );
		} );

		// ── Collect & validate form values ────────────────────────────────
		function collectParams() {
			const last_name = $.trim( $wrap.find( 'input[name="last_name"]' ).val() );
			const year      = $yearSel.val();
			const title     = $.trim( $wrap.find( 'input[name="title"]' ).val() );
			const major     = $majorSel.val();
			const advisor   = showAdvisor
				? $.trim( $wrap.find( 'input[name="advisor"]' ).val() )
				: '';

			if ( ! last_name && ! year && ! title && ! major && ! advisor ) {
				showError( $validMsg, 'Please enter at least one search field.' );
				return null;
			}

			return {
				action:    'srp_search',
				nonce:     srpData.nonce,
				last_name: last_name,
				year:      year,
				title:     title,
				major:     major,
				advisor:   advisor,
				per_page:  perPage,
				order_by:  orderBy,
			};
		}

		// ── Execute search AJAX ───────────────────────────────────────────
		function doSearch( offset, params, append ) {
			$.ajax( {
				url:    srpData.ajaxUrl,
				method: 'POST',
				data:   Object.assign( {}, params, { offset: offset } ),
			} )
			.done( function ( res ) {
				if ( res.success ) {
					renderResults( res.data, append );
					currentOffset = offset + res.data.count;

					if ( res.data.has_more ) {
						$loadWrap.show();
						$loadBtn.prop( 'disabled', false ).text( 'Load More' );
					} else {
						$loadWrap.hide();
					}
				} else {
					showError( $validMsg, res.data.message || 'An error occurred.' );
					$loadWrap.hide();
				}
			} )
			.fail( function ( xhr ) {
				const msg = srpData.debug
					? 'AJAX failed: HTTP ' + xhr.status + ' — ' + ( xhr.responseText || xhr.statusText )
					: 'Could not connect to the server. Please try again.';
				showError( $validMsg, msg );
				$loadWrap.hide();
			} )
			.always( function () {
				setLoading( $submit, false );
				isLoadingMore = false;
			} );
		}

		// ── Render results ────────────────────────────────────────────────
		function renderResults( data, append ) {
			if ( ! append && ( ! data.results || data.results.length === 0 ) ) {
				$results.html( '<p class="srp-no-results">' + escHtml( noResults ) + '</p>' );
				return;
			}

			if ( ! append ) {
				// First page — build table with header.
				const totalLabel = data.total === 1 ? '1 project found' : data.total + ' projects found';
				let html = '<div class="srp-results-header">';
				html += '<span class="srp-results-count">' + escHtml( totalLabel ) + '</span>';
				html += '</div>';
				html += '<div class="srp-table-wrap"><table class="srp-table">';
				html += buildthead();
				html += '<tbody id="srp-tbody-' + escHtml( $wrap.attr( 'id' ) ) + '">';
				html += buildRows( data.results );
				html += '</tbody></table></div>';
				$results.html( html );
				$( 'html, body' ).animate( { scrollTop: $results.offset().top - 40 }, 400 );
			} else {
				// Subsequent pages — append rows to existing tbody.
				$results.find( 'tbody' ).append( buildRows( data.results ) );
			}
		}

		function buildthead() {
			let h = '<thead><tr>';
			h += '<th scope="col">Student</th>';
			h += '<th scope="col">Year</th>';
			h += '<th scope="col">Title</th>';
			h += '<th scope="col">Major 1</th>';
			if ( showMajor2  ) h += '<th scope="col">Major 2</th>';
			if ( showAdvisor ) h += '<th scope="col">Advisor</th>';
			h += '</tr></thead>';
			return h;
		}

		function buildRows( rows ) {
			let h = '';
			rows.forEach( function ( row ) {
				const student  = escHtml( ( row.STUDENT_FIRST || '' ) + ' ' + ( row.STUDENT_LAST || '' ) );
				const year     = escHtml( row.YEAR );
				const title    = escHtml( row.IS_TITLE );
				const major1   = escHtml( row.MAJOR_1 || '—' );
				const major2   = escHtml( row.MAJOR_2 || '—' );
				const advisor  = ( row.ADVISOR_FIRST || row.ADVISOR_LAST )
					? escHtml( ( row.ADVISOR_FIRST || '' ) + ' ' + ( row.ADVISOR_LAST || '' ) )
					: '—';

				h += '<tr>';
				h += '<td data-label="Student">' + student + '</td>';
				h += '<td data-label="Year">'    + year    + '</td>';
				h += '<td data-label="Title">'   + title   + '</td>';
				h += '<td data-label="Major 1">' + major1  + '</td>';
				if ( showMajor2  ) h += '<td data-label="Major 2">' + major2  + '</td>';
				if ( showAdvisor ) h += '<td data-label="Advisor">' + advisor + '</td>';
				h += '</tr>';
			} );
			return h;
		}

		// ── Helpers ───────────────────────────────────────────────────────
		function setLoading( $btn, on ) {
			$btn.prop( 'disabled', on ).toggleClass( 'srp-loading', on );
		}

		function showError( $el, msg ) {
			$el.text( msg ).addClass( 'srp-error' );
		}

		function escHtml( str ) {
			if ( str === null || str === undefined ) return '';
			return String( str )
				.replace( /&/g, '&amp;' ).replace( /</g, '&lt;' )
				.replace( />/g, '&gt;' ).replace( /"/g, '&quot;' )
				.replace( /'/g, '&#039;' );
		}
	}

	// ── Bind all existing instances ──────────────────────────────────────────
	function bindAll() {
		$( '.srp-search-wrap' ).each( function () { bindInstance( this ); } );
	}

	// ── MutationObserver: catches SSR-rendered forms in block editor ──────────
	function observeForBlocks() {
		if ( ! window.MutationObserver ) return;
		const observer = new MutationObserver( function ( mutations ) {
			mutations.forEach( function ( mutation ) {
				mutation.addedNodes.forEach( function ( node ) {
					if ( node.nodeType !== 1 ) return;
					if ( $( node ).hasClass( 'srp-search-wrap' ) ) bindInstance( node );
					$( node ).find( '.srp-search-wrap' ).each( function () { bindInstance( this ); } );
				} );
			} );
		} );
		observer.observe( document.body, { childList: true, subtree: true } );
	}

	// ── Console diagnostic ───────────────────────────────────────────────────
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
				if ( ! res.data.pdo_sqlsrv )  console.error( 'SRP: pdo_sqlsrv NOT loaded in SAPI: ' + res.data.php_sapi );
				if ( ! res.data.connection )   console.error( 'SRP: DB connection failed — ' + res.data.connection_err );
				if ( res.data.connection && ! res.data.query_test ) console.error( 'SRP: Connected but query failed — ' + res.data.query_err );
				if ( res.data.connection && res.data.query_test )   console.log( 'SRP: ✅ All clear.' );
			} else {
				console.error( 'SRP diagnostic error:', res.data );
			}
		} ).fail( function ( xhr ) {
			console.error( 'SRP: Diagnostic failed — HTTP ' + xhr.status, xhr.responseText );
		} );
	};

	// ── Init ─────────────────────────────────────────────────────────────────
	$( function () {
		bindAll();
		observeForBlocks();
		if ( srpData.debug ) {
			console.log( 'SRP Search: debug on. Run srp_run_diagnostic() for full report.' );
		}
	} );

} )( jQuery );
