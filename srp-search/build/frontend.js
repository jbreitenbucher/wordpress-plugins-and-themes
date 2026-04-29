/**
 * SRP Search — Frontend JS v2.0.0
 * - URL state: bookmarkable/shareable searches via query params
 * - AJAX timeout: 15s with clear user message
 * - "Showing X of Y" count updated on Load More
 * - Suppress empty Major 2 rows on mobile cards
 * - DB unavailable message when dropdowns fail
 * - Stampede retry for dropdowns
 * - Rate limit (429) handling
 */
( function ( $ ) {
	'use strict';

	const RETRY_DELAY   = 2000; // ms — retry dropdown load if stampede lock hit
	const URL_PARAM_MAP = {
		last_name: 'srp_last_name',
		year:      'srp_year',
		title:     'srp_title',
		major:     'srp_major',
		advisor:   'srp_advisor',
	};

	// ── URL state helpers ────────────────────────────────────────────────────
	function pushUrlState( params ) {
		if ( ! window.history || ! window.history.pushState ) return;
		const url  = new URL( window.location.href );
		// Clear all srp_ params first.
		Object.values( URL_PARAM_MAP ).forEach( function ( p ) { url.searchParams.delete( p ); } );
		// Set only non-empty values.
		Object.entries( URL_PARAM_MAP ).forEach( function ( [ field, param ] ) {
			if ( params[ field ] ) url.searchParams.set( param, params[ field ] );
		} );
		window.history.pushState( {}, '', url.toString() );
	}

	function clearUrlState() {
		if ( ! window.history || ! window.history.pushState ) return;
		const url = new URL( window.location.href );
		Object.values( URL_PARAM_MAP ).forEach( function ( p ) { url.searchParams.delete( p ); } );
		window.history.pushState( {}, '', url.toString() );
	}

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
		const $showCount = $wrap.find( '.srp-showing-count' );

		const perPage     = parseInt( $wrap.data( 'srp-per-page' ) || 25, 10 );
		const noResults   = $wrap.data( 'srp-no-results' ) || 'No projects found matching your search.';
		const showMajor2  = $wrap.data( 'srp-show-major2' )  !== '0';
		const showAdvisor = $wrap.data( 'srp-show-advisor' ) !== '0';
		const orderBy     = $wrap.data( 'srp-order-by' ) || 'year_asc_name_asc';
		const autoRun     = $wrap.data( 'srp-autorun' )    === '1' || $wrap.data( 'srp-autorun' ) === 1;

		let currentOffset  = 0;
		let currentParams  = {};
		let currentShowing = 0;
		let isLoadingMore  = false;
		let dbUnavailable  = false;

		// ── Load years ────────────────────────────────────────────────────
		function loadYears( retry ) {
			$.ajax( {
				url:     srpData.ajaxUrl,
				method:  'POST',
				timeout: srpData.ajaxTimeout,
				data:    { action: 'srp_get_years', nonce: srpData.nonce },
			} ).done( function ( res ) {
				if ( res.success ) {
					if ( res.data.retry ) {
						// Cache stampede — try again after a short delay.
						setTimeout( function () { loadYears( true ); }, RETRY_DELAY );
						return;
					}
					const selected = $yearSel.data( 'srp-selected' ) || '';
					res.data.years.forEach( function ( year ) {
						const opt = $( '<option>' ).val( year ).text( year );
						if ( String( year ) === String( selected ) ) opt.prop( 'selected', true );
						$yearSel.append( opt );
					} );
					// Auto-run search if URL state was present and this is the first load.
					if ( autoRun && ! retry ) maybeAutoRun();
				} else {
					markDbUnavailable();
				}
			} ).fail( function () {
				markDbUnavailable();
			} );
		}

		// ── Load majors ───────────────────────────────────────────────────
		function loadMajors() {
			$.ajax( {
				url:     srpData.ajaxUrl,
				method:  'POST',
				timeout: srpData.ajaxTimeout,
				data:    { action: 'srp_get_majors', nonce: srpData.nonce },
			} ).done( function ( res ) {
				if ( res.success ) {
					if ( res.data.retry ) {
						setTimeout( loadMajors, RETRY_DELAY );
						return;
					}
					const selected = $majorSel.data( 'srp-selected' ) || '';
					res.data.majors.forEach( function ( major ) {
						const opt = $( '<option>' ).val( major ).text( major );
						if ( major === selected ) opt.prop( 'selected', true );
						$majorSel.append( opt );
					} );
				} else {
					markDbUnavailable();
				}
			} ).fail( function () {
				markDbUnavailable();
			} );
		}

		// ── DB unavailable state ──────────────────────────────────────────
		function markDbUnavailable() {
			if ( dbUnavailable ) return;
			dbUnavailable = true;
			$wrap.find( '.srp-submit' ).prop( 'disabled', true );
			showError( $validMsg, 'Search is temporarily unavailable — please try again later.' );
			if ( srpData.debug ) console.warn( 'SRP: DB unavailable — dropdown load failed.' );
		}

		// ── Auto-run from URL state ───────────────────────────────────────
		function maybeAutoRun() {
			const params = collectParams();
			if ( ! params ) return;
			currentOffset  = 0;
			currentParams  = params;
			currentShowing = 0;
			$results.html( '' );
			$loadWrap.hide();
			setLoading( $submit, true );
			doSearch( 0, params, false );
		}

		// ── Form submit ───────────────────────────────────────────────────
		$form.on( 'submit', function ( e ) {
			e.preventDefault();
			$validMsg.text( '' ).removeClass( 'srp-error srp-info' );

			const params = collectParams();
			if ( ! params ) return;

			pushUrlState( params );

			currentOffset  = 0;
			currentParams  = params;
			currentShowing = 0;
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
			const last_name = $.trim( $wrap.find( 'input[name="last_name"]' ).val() ).substring( 0, srpData.maxInputLen );
			const year      = $yearSel.val();
			const title     = $.trim( $wrap.find( 'input[name="title"]' ).val() ).substring( 0, srpData.maxInputLen );
			const major     = $majorSel.val();
			const advisor   = showAdvisor
				? $.trim( $wrap.find( 'input[name="advisor"]' ).val() ).substring( 0, srpData.maxInputLen )
				: '';

			if ( ! last_name && ! year && ! title && ! major && ! advisor ) {
				showError( $validMsg, 'Please enter at least one search field.' );
				return null;
			}

			return { action: 'srp_search', nonce: srpData.nonce,
				last_name, year, title, major, advisor, per_page: perPage, order_by: orderBy };
		}

		// ── Execute search ────────────────────────────────────────────────
		function doSearch( offset, params, append ) {
			$.ajax( {
				url:     srpData.ajaxUrl,
				method:  'POST',
				timeout: srpData.ajaxTimeout,
				data:    Object.assign( {}, params, { offset: offset } ),
			} )
			.done( function ( res ) {
				if ( res.success ) {
					renderResults( res.data, append );
					currentOffset   = offset + res.data.count;
					currentShowing += res.data.count;

					if ( res.data.has_more ) {
						$showCount.text( 'Showing ' + currentShowing + ' of ' + res.data.total + ' projects' );
						$loadWrap.show();
						$loadBtn.prop( 'disabled', false ).text( 'Load More' );
					} else {
						$showCount.text( '' );
						$loadWrap.hide();
					}
				} else {
					const msg = res.data && res.data.message ? res.data.message : 'An error occurred.';
					showError( $validMsg, msg );
					$loadWrap.hide();
					clearUrlState();
				}
			} )
			.fail( function ( xhr ) {
				let msg;
				if ( xhr.status === 429 ) {
					msg = 'Too many requests — please wait a moment and try again.';
				} else if ( xhr.statusCode === 0 || xhr.statusText === 'timeout' ) {
					msg = 'The search is taking too long — please try again.';
				} else {
					msg = srpData.debug
						? 'AJAX failed: HTTP ' + xhr.status + ' — ' + ( xhr.responseText || xhr.statusText )
						: 'Could not connect to the server. Please try again.';
				}
				showError( $validMsg, msg );
				$loadWrap.hide();
				clearUrlState();
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
				clearUrlState();
				return;
			}

			if ( ! append ) {
				const totalLabel = data.total === 1 ? '1 project found' : data.total + ' projects found';
				let html = '<div class="srp-results-header"><span class="srp-results-count">' + escHtml( totalLabel ) + '</span></div>';
				html += '<div class="srp-table-wrap"><table class="srp-table">';
				html += buildThead();
				html += '<tbody>' + buildRows( data.results ) + '</tbody>';
				html += '</table></div>';
				$results.html( html );
				$( 'html, body' ).animate( { scrollTop: $results.offset().top - 40 }, 400 );
			} else {
				$results.find( 'tbody' ).append( buildRows( data.results ) );
			}
		}

		function buildThead() {
			let h = '<thead><tr>';
			h += '<th scope="col">Student</th><th scope="col">Year</th><th scope="col">Title</th><th scope="col">Major 1</th>';
			if ( showMajor2  ) h += '<th scope="col">Major 2</th>';
			if ( showAdvisor ) h += '<th scope="col">Advisor</th>';
			return h + '</tr></thead>';
		}

		function buildRows( rows ) {
			let h = '';
			rows.forEach( function ( row ) {
				const student  = escHtml( ( row.STUDENT_FIRST || '' ) + ' ' + ( row.ATTENDED_AS_LAST || '' ) );
				const year     = escHtml( row.YEAR );
				const title    = escHtml( row.IS_TITLE );
				const major1   = escHtml( row.MAJOR_1_DESC || '—' );
				const major2   = row.MAJOR_2_DESC && row.MAJOR_2_DESC.trim() ? escHtml( row.MAJOR_2_DESC ) : null;
				const advisor  = ( row.ADVISOR_FIRST || row.ADVISOR_LAST )
					? escHtml( ( row.ADVISOR_FIRST || '' ) + ' ' + ( row.ADVISOR_LAST || '' ) ) : '—';

				// data-srp-empty on Major 2 cell when blank — CSS hides it on mobile.
				const major2Attr = major2 ? '' : ' data-srp-empty="1"';

				h += '<tr>';
				h += '<td data-label="Student">' + student + '</td>';
				h += '<td data-label="Year">'    + year    + '</td>';
				h += '<td data-label="Title">'   + title   + '</td>';
				h += '<td data-label="Major 1">' + major1  + '</td>';
				if ( showMajor2  ) h += '<td data-label="Major 2"' + major2Attr + '>' + ( major2 || '—' ) + '</td>';
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

		// ── Kick off dropdown loads ───────────────────────────────────────
		loadYears( false );
		loadMajors();
	}

	// ── Bind all existing instances ──────────────────────────────────────────
	function bindAll() {
		$( '.srp-search-wrap' ).each( function () { bindInstance( this ); } );
	}

	// ── MutationObserver for editor SSR ──────────────────────────────────────
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
		if ( ! window.srpData ) { console.error( 'SRP: srpData not found.' ); return; }
		console.log( 'SRP: Running diagnostic...' );
		$.ajax( {
			url: srpData.ajaxUrl, method: 'POST',
			data: { action: 'srp_check', nonce: srpData.nonce },
		} ).done( function ( res ) {
			if ( res.success ) {
				console.table( res.data );
				if ( ! res.data.pdo_sqlsrv )                         console.error( 'SRP: pdo_sqlsrv NOT loaded in SAPI: ' + res.data.php_sapi );
				if ( ! res.data.connection )                         console.error( 'SRP: DB connection failed — ' + res.data.connection_err );
				if ( res.data.connection && ! res.data.query_test )  console.error( 'SRP: Query failed — ' + res.data.query_err );
				if ( res.data.connection && res.data.query_test )    console.log( 'SRP: ✅ All clear.' );
			} else { console.error( 'SRP diagnostic error:', res.data ); }
		} ).fail( function ( xhr ) { console.error( 'SRP: Diagnostic failed — HTTP ' + xhr.status, xhr.responseText ); } );
	};

	// ── Init ─────────────────────────────────────────────────────────────────
	$( function () {
		bindAll();
		observeForBlocks();
		if ( srpData.debug ) console.log( 'SRP Search v2.0 — debug on. Run srp_run_diagnostic() for full report.' );
	} );

} )( jQuery );
