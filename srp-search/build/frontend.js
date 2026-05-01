/**
 * SRP Search — Frontend JavaScript
 *
 * Handles all client-side behaviour for the SRP Search block:
 *   - Populating the Year and Major dropdowns via AJAX on page load
 *   - Binding form submit to an AJAX search request
 *   - Rendering results into a responsive table
 *   - Pagination via Load More (appends rows without re-rendering)
 *   - Pushing/reading search state to/from the URL (bookmarkable searches)
 *   - Surfacing DB unavailable state when dropdowns fail to load
 *   - Retrying dropdown loads if the cache stampede lock is active
 *
 * MULTIPLE INSTANCE SUPPORT:
 * Each block instance is scoped to its own wrapper div (.srp-search-wrap).
 * All DOM queries use $wrap.find() rather than global selectors so multiple
 * blocks on one page work independently. The bindInstance() function is
 * idempotent — it won't double-bind a block it has already processed.
 *
 * BLOCK EDITOR SUPPORT:
 * The block uses ServerSideRender which injects HTML into the editor canvas
 * after the script has already loaded. A MutationObserver watches for new
 * .srp-search-wrap elements and calls bindInstance() when they appear.
 *
 * DATA ATTRIBUTE CONTRACT:
 * Block settings are passed from PHP (render callback) to JS via data-* attributes
 * on the wrapper div. These are the attributes and their expected values:
 *   data-srp-per-page:    number (25, 50, or 100)
 *   data-srp-no-results:  string (custom no results message)
 *   data-srp-show-major2: "1" or "0"
 *   data-srp-show-advisor:"1" or "0"
 *   data-srp-order-by:    one of the SRP_ORDER_MAP keys from PHP
 *   data-srp-autorun:     "1" if URL params are present and search should auto-fire
 *
 * GLOBAL:
 *   window.srpData       — localized by PHP via wp_localize_script()
 *   window.srp_run_diagnostic() — run in browser console for connection debugging
 */
( function ( $ ) {
	'use strict';

	/** ms to wait before retrying a dropdown load when the stampede lock is held */
	const RETRY_DELAY = 2000;

	/**
	 * Maps JS form field names to URL query parameter names.
	 * Used when pushing search state to the URL and reading it back on load.
	 * All params are prefixed srp_ to avoid collisions with theme/plugin params.
	 */
	const URL_PARAM_MAP = {
		last_name: 'srp_last_name',
		year:      'srp_year',
		title:     'srp_title',
		major:     'srp_major',
		advisor:   'srp_advisor',
	};

	// ── URL STATE HELPERS ────────────────────────────────────────────────────

	/**
	 * Pushes the current search parameters to the browser URL without reloading.
	 * This makes the search bookmarkable and shareable. The PHP render callback
	 * reads these same params on page load to pre-fill the form and auto-run.
	 *
	 * @param {Object} params - The search parameters object from collectParams().
	 */
	function pushUrlState( params ) {
		if ( ! window.history || ! window.history.pushState ) return;
		const url = new URL( window.location.href );
		Object.values( URL_PARAM_MAP ).forEach( function ( p ) { url.searchParams.delete( p ); } );
		Object.entries( URL_PARAM_MAP ).forEach( function ( [ field, param ] ) {
			if ( params[ field ] ) url.searchParams.set( param, params[ field ] );
		} );
		window.history.pushState( {}, '', url.toString() );
	}

	/**
	 * Removes all srp_* query parameters from the URL.
	 * Called on no-results or error to avoid misleading bookmarks.
	 */
	function clearUrlState() {
		if ( ! window.history || ! window.history.pushState ) return;
		const url = new URL( window.location.href );
		Object.values( URL_PARAM_MAP ).forEach( function ( p ) { url.searchParams.delete( p ); } );
		window.history.pushState( {}, '', url.toString() );
	}

	// ── INSTANCE BINDING ────────────────────────────────────────────────────

	/**
	 * Binds all AJAX and UI behaviour to a single block instance.
	 *
	 * Reads block settings from data-* attributes on the wrapper, then:
	 *   1. Loads years and majors dropdowns via AJAX (with retry on stampede)
	 *   2. Binds form submit to doSearch()
	 *   3. Binds Load More button to doSearch() with offset
	 *   4. Auto-runs search if data-srp-autorun is set (URL state present)
	 *
	 * @param {Element} wrap - The .srp-search-wrap DOM element.
	 */
	function bindInstance( wrap ) {
		// Prevent double-binding if MutationObserver fires multiple times.
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

		// Read block settings from data attributes set by the PHP render callback.
		const perPage     = parseInt( $wrap.data( 'srp-per-page' ) || 25, 10 );
		const noResults   = $wrap.data( 'srp-no-results' ) || 'No projects found matching your search.';
		const showMajor2  = $wrap.data( 'srp-show-major2' )  !== '0';
		const showAdvisor = $wrap.data( 'srp-show-advisor' ) !== '0';
		const orderBy     = $wrap.data( 'srp-order-by' ) || 'year_asc_name_asc';
		const autoRun     = $wrap.data( 'srp-autorun' ) === '1' || $wrap.data( 'srp-autorun' ) === 1;

		// Pagination state — reset on each new search, incremented by Load More.
		let currentOffset  = 0;
		let currentParams  = {};
		let currentShowing = 0;
		let isLoadingMore  = false;

		// Set to true if dropdowns fail — disables the search button with a message.
		let dbUnavailable  = false;

		// ── DROPDOWN LOADERS ──────────────────────────────────────────────

		/**
		 * Loads the Year dropdown from the DB (or cache).
		 * Retries after RETRY_DELAY ms if the server returns retry:true
		 * (stampede lock is held by another request populating the cache).
		 * Calls maybeAutoRun() after years are loaded if autoRun is set,
		 * ensuring the year pre-selection from URL state is applied first.
		 *
		 * @param {boolean} isRetry - True if this is a retry call (skips autoRun re-check).
		 */
		function loadYears( isRetry ) {
			$.ajax( {
				url: srpData.ajaxUrl, method: 'POST', timeout: srpData.ajaxTimeout,
				data: { action: 'srp_get_years', nonce: srpData.nonce },
			} ).done( function ( res ) {
				if ( res.success ) {
					if ( res.data.retry ) {
						setTimeout( function () { loadYears( true ); }, RETRY_DELAY );
						return;
					}
					const selected = $yearSel.data( 'srp-selected' ) || '';
					res.data.years.forEach( function ( year ) {
						const opt = $( '<option>' ).val( year ).text( year );
						if ( String( year ) === String( selected ) ) opt.prop( 'selected', true );
						$yearSel.append( opt );
					} );
					// Auto-run only after years are loaded so the pre-selected year
					// is in the dropdown before the search fires.
					if ( autoRun && ! isRetry ) maybeAutoRun();
				} else {
					markDbUnavailable();
				}
			} ).fail( markDbUnavailable );
		}

		/**
		 * Loads the Major dropdown from the DB (or cache).
		 * Uses MAJOR_1_DESC and MAJOR_2_DESC — human-readable descriptions,
		 * not the 4-character department codes. See srp_ajax_get_majors() in PHP.
		 */
		function loadMajors() {
			$.ajax( {
				url: srpData.ajaxUrl, method: 'POST', timeout: srpData.ajaxTimeout,
				data: { action: 'srp_get_majors', nonce: srpData.nonce },
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
			} ).fail( markDbUnavailable );
		}

		/**
		 * Marks the block as DB-unavailable.
		 * Disables the search button and shows a message. Called when dropdown
		 * loads fail — if we can't load dropdowns, search won't work either.
		 * Idempotent — only applies the state once per instance.
		 */
		function markDbUnavailable() {
			if ( dbUnavailable ) return;
			dbUnavailable = true;
			$submit.prop( 'disabled', true );
			showError( $validMsg, 'Search is temporarily unavailable — please try again later.' );
			if ( srpData.debug ) console.warn( 'SRP: DB unavailable — dropdown load failed.' );
		}

		// ── AUTO-RUN FROM URL STATE ───────────────────────────────────────

		/**
		 * Fires a search using the current form values if they produce valid params.
		 * Called after year dropdown loads when data-srp-autorun is set,
		 * which happens when the page was loaded with srp_* URL parameters.
		 */
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

		// ── FORM SUBMIT ───────────────────────────────────────────────────

		$form.on( 'submit', function ( e ) {
			e.preventDefault();
			$validMsg.text( '' ).removeClass( 'srp-error srp-info' );

			const params = collectParams();
			if ( ! params ) return;

			// Push search to URL so it's bookmarkable/shareable.
			pushUrlState( params );

			currentOffset  = 0;
			currentParams  = params;
			currentShowing = 0;
			$results.html( '' );
			$loadWrap.hide();
			setLoading( $submit, true );
			doSearch( 0, params, false );
		} );

		// ── LOAD MORE ────────────────────────────────────────────────────

		$loadBtn.on( 'click', function () {
			if ( isLoadingMore ) return; // Prevent double-clicks
			isLoadingMore = true;
			$loadBtn.prop( 'disabled', true ).text( 'Loading…' );
			doSearch( currentOffset, currentParams, true );
		} );

		// ── COLLECT & VALIDATE PARAMS ────────────────────────────────────

		/**
		 * Reads, trims, and validates the current form values.
		 * Returns a params object ready for the AJAX call, or null if validation fails.
		 * Client-side validation mirrors the server-side checks in srp_ajax_search().
		 *
		 * @return {Object|null} Params object or null if validation failed.
		 */
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

		// ── AJAX SEARCH ───────────────────────────────────────────────────

		/**
		 * Fires the search AJAX request and handles the response.
		 *
		 * @param {number}  offset - Row offset for pagination (0 for first page).
		 * @param {Object}  params - Search parameters from collectParams().
		 * @param {boolean} append - True to append rows (Load More), false to replace.
		 */
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
				// Distinguish between rate limiting, timeout, and other failures
				// so the visitor gets an actionable message.
				let msg;
				if ( xhr.status === 429 ) {
					msg = 'Too many requests — please wait a moment and try again.';
				} else if ( xhr.statusText === 'timeout' ) {
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

		// ── RESULT RENDERING ─────────────────────────────────────────────

		/**
		 * Renders search results into the results div.
		 * On first page: builds the full table including caption and thead.
		 * On Load More: appends only new tbody rows to the existing table.
		 *
		 * @param {Object}  data   - AJAX response data (results, total, has_more, etc.)
		 * @param {boolean} append - True to append, false to replace.
		 */
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

		/**
		 * Builds the table header row.
		 * Conditionally includes Major 2 and Advisor columns based on block settings.
		 * Must stay in sync with buildRows() — same columns in same order.
		 *
		 * @return {string} HTML string for the thead element.
		 */
		function buildThead() {
			let h = '<thead><tr>';
			h += '<th scope="col">Student</th><th scope="col">Year</th><th scope="col">Title</th><th scope="col">Major 1</th>';
			if ( showMajor2  ) h += '<th scope="col">Major 2</th>';
			if ( showAdvisor ) h += '<th scope="col">Advisor</th>';
			return h + '</tr></thead>';
		}

		/**
		 * Builds tbody rows from a results array.
		 *
		 * STUDENT NAME DISPLAY:
		 * Uses ATTENDED_AS_LAST (name used while at Wooster) as the primary last name.
		 * If ATTENDED_AS_LAST differs from STUDENT_LAST (legal/current name), shows:
		 *   "First AttendedLast (StudentLast)"
		 * This handles students who changed their name (e.g. marriage) so the record
		 * is clear about both names without burying either.
		 *
		 * MAJOR 2 EMPTY STATE:
		 * Cells with no Major 2 value get data-srp-empty="1". CSS hides these on
		 * mobile card layout so cards don't show a blank "Major 2 —" row.
		 *
		 * DATA LABELS:
		 * Each td gets a data-label attribute matching its column header.
		 * CSS uses ::before { content: attr(data-label) } on mobile to show
		 * column labels in the card layout without duplicating HTML.
		 *
		 * @param  {Array}  rows - Array of result objects from the AJAX response.
		 * @return {string} HTML string for the tbody rows.
		 */
		function buildRows( rows ) {
			let h = '';
			rows.forEach( function ( row ) {
				// Build display name — show both names if they differ.
				const firstName    = ( row.STUDENT_FIRST    || '' ).trim();
				const attendedLast = ( row.ATTENDED_AS_LAST || '' ).trim();
				const studentLast  = ( row.STUDENT_LAST     || '' ).trim();
				const namesDiffer  = attendedLast.toLowerCase() !== studentLast.toLowerCase();
				const studentRaw   = namesDiffer && studentLast
					? firstName + ' ' + attendedLast + ' (' + studentLast + ')'
					: firstName + ' ' + attendedLast;
				const student = escHtml( studentRaw.trim() );

				const year    = escHtml( row.YEAR );
				const title   = escHtml( row.IS_TITLE );
				const major1  = escHtml( row.MAJOR_1_DESC || '—' );
				const major2  = row.MAJOR_2_DESC && row.MAJOR_2_DESC.trim() ? escHtml( row.MAJOR_2_DESC ) : null;
				const advisor = ( row.ADVISOR_FIRST || row.ADVISOR_LAST )
					? escHtml( ( row.ADVISOR_FIRST || '' ) + ' ' + ( row.ADVISOR_LAST || '' ) ) : '—';

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

		// ── UTILITY HELPERS ───────────────────────────────────────────────

		function setLoading( $btn, on ) {
			$btn.prop( 'disabled', on ).toggleClass( 'srp-loading', on );
		}

		function showError( $el, msg ) {
			$el.text( msg ).addClass( 'srp-error' );
		}

		/**
		 * Escapes a value for safe HTML insertion.
		 * All DB values pass through this before being written to innerHTML.
		 * Prevents XSS if a project title or name contains HTML characters.
		 *
		 * @param  {*}      str - Value to escape.
		 * @return {string}     HTML-safe string.
		 */
		function escHtml( str ) {
			if ( str === null || str === undefined ) return '';
			return String( str )
				.replace( /&/g, '&amp;' ).replace( /</g, '&lt;' )
				.replace( />/g, '&gt;' ).replace( /"/g, '&quot;' )
				.replace( /'/g, '&#039;' );
		}

		// ── KICK OFF ─────────────────────────────────────────────────────

		loadYears( false );
		loadMajors();
	}

	// ── TOP-LEVEL INIT ───────────────────────────────────────────────────────

	/**
	 * Binds all .srp-search-wrap elements currently in the DOM.
	 * Called on document ready for front-end page loads.
	 */
	function bindAll() {
		$( '.srp-search-wrap' ).each( function () { bindInstance( this ); } );
	}

	/**
	 * Watches for dynamically injected .srp-search-wrap elements.
	 *
	 * WHY MUTATIONOBSERVER:
	 * In the block editor, ServerSideRender fetches the PHP-rendered HTML via
	 * REST API and injects it into the editor canvas after the script has loaded.
	 * jQuery's document-ready fires before this injection happens, so bindAll()
	 * alone misses editor instances. MutationObserver catches them as they arrive.
	 */
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

	/**
	 * Console diagnostic tool — run srp_run_diagnostic() in browser dev tools.
	 *
	 * Calls the srp_check AJAX endpoint which tests:
	 *   - PHP version and SAPI (web server vs CLI — extensions may differ)
	 *   - Whether pdo_sqlsrv and sqlsrv are loaded in THIS SAPI
	 *   - Whether DB constants are defined in wp-config.php
	 *   - Whether a PDO connection can be established
	 *   - Whether a test query against IS_TITLES succeeds
	 *
	 * Results are printed as a console.table() for easy reading.
	 */
	window.srp_run_diagnostic = function () {
		if ( ! window.srpData ) { console.error( 'SRP: srpData not found — is the plugin active on this page?' ); return; }
		console.log( 'SRP Search: running diagnostic...' );
		$.ajax( {
			url: srpData.ajaxUrl, method: 'POST',
			data: { action: 'srp_check', nonce: srpData.nonce },
		} ).done( function ( res ) {
			if ( res.success ) {
				console.table( res.data );
				if ( ! res.data.pdo_sqlsrv )                        console.error( 'SRP: pdo_sqlsrv NOT loaded in SAPI: ' + res.data.php_sapi );
				if ( ! res.data.connection )                        console.error( 'SRP: DB connection failed — ' + res.data.connection_err );
				if ( res.data.connection && ! res.data.query_test ) console.error( 'SRP: Query failed — ' + res.data.query_err );
				if ( res.data.connection && res.data.query_test )   console.log( 'SRP: ✅ All clear.' );
			} else {
				console.error( 'SRP diagnostic error:', res.data );
			}
		} ).fail( function ( xhr ) {
			console.error( 'SRP: Diagnostic request failed — HTTP ' + xhr.status, xhr.responseText );
		} );
	};

	$( function () {
		bindAll();
		observeForBlocks();
		if ( srpData.debug ) {
			console.log( 'SRP Search — debug mode on. Run srp_run_diagnostic() for a full connection report.' );
		}
	} );

} )( jQuery );
