/**
 * SRP Search — Frontend JS
 * Handles: major dropdown population, form submission, AJAX search, results rendering.
 */

( function ( $ ) {
	'use strict';

	const form       = $( '#srp-search-form' );
	const results    = $( '#srp-results' );
	const validMsg   = $( '#srp-validation-msg' );
	const submitBtn  = $( '#srp-submit' );
	const majorSel   = $( '#srp-major' );

	// ── Load majors on page ready ──────────────────────────────────────────
	$.ajax( {
		url:    srpData.ajaxUrl,
		method: 'POST',
		data:   { action: 'srp_get_majors', nonce: srpData.nonce },
	} ).done( function ( res ) {
		if ( res.success && res.data.majors.length ) {
			res.data.majors.forEach( function ( major ) {
				majorSel.append(
					$( '<option>' ).val( major ).text( major )
				);
			} );
		}
	} );

	// ── Form submit ───────────────────────────────────────────────────────
	form.on( 'submit', function ( e ) {
		e.preventDefault();
		validMsg.text( '' ).removeClass( 'srp-error srp-info' );

		const last_name = $.trim( $( '#srp-last-name' ).val() );
		const year      = $.trim( $( '#srp-year' ).val() );
		const title     = $.trim( $( '#srp-title' ).val() );
		const major     = $( '#srp-major' ).val();
		const advisor   = $.trim( $( '#srp-advisor' ).val() );

		// Client-side: require at least one field
		if ( ! last_name && ! year && ! title && ! major && ! advisor ) {
			validMsg.text( 'Please enter at least one search field.' ).addClass( 'srp-error' );
			return;
		}

		// Client-side: year numeric
		if ( year && ! /^\d{4}$/.test( year ) ) {
			validMsg.text( 'Graduation year must be a 4-digit number.' ).addClass( 'srp-error' );
			return;
		}

		setLoading( true );
		results.html( '' );

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
				renderResults( res.data.results, res.data.count );
			} else {
				showError( res.data.message || 'An error occurred.' );
			}
		} )
		.fail( function () {
			showError( 'Could not connect to the server. Please try again.' );
		} )
		.always( function () {
			setLoading( false );
		} );
	} );

	// ── Render results table ──────────────────────────────────────────────
	function renderResults( rows, count ) {
		if ( ! rows || rows.length === 0 ) {
			results.html( '<p class="srp-no-results">No projects found matching your search.</p>' );
			return;
		}

		const countLabel = count === 1 ? '1 project found' : count + ' projects found';

		let html = '<div class="srp-results-header">';
		html += '<span class="srp-results-count">' + escHtml( countLabel ) + '</span>';
		html += '</div>';
		html += '<div class="srp-table-wrap">';
		html += '<table class="srp-table">';
		html += '<thead><tr>';
		html += '<th scope="col">Student</th>';
		html += '<th scope="col">Year</th>';
		html += '<th scope="col">Title</th>';
		html += '<th scope="col">Major 1</th>';
		html += '<th scope="col">Major 2</th>';
		html += '<th scope="col">Advisor</th>';
		html += '</tr></thead>';
		html += '<tbody>';

		rows.forEach( function ( row ) {
			const studentName  = escHtml( row.STUDENT_FIRST + ' ' + row.STUDENT_LAST );
			const year         = escHtml( row.YEAR );
			const title        = escHtml( row.IS_TITLE );
			const major1       = escHtml( row.MAJOR_1 || '—' );
			const major2       = escHtml( row.MAJOR_2 || '—' );
			const advisorName  = row.ADVISOR_FIRST || row.ADVISOR_LAST
				? escHtml( row.ADVISOR_FIRST + ' ' + row.ADVISOR_LAST )
				: '—';

			html += '<tr>';
			html += '<td data-label="Student">'  + studentName  + '</td>';
			html += '<td data-label="Year">'     + year         + '</td>';
			html += '<td data-label="Title">'    + title        + '</td>';
			html += '<td data-label="Major 1">'  + major1       + '</td>';
			html += '<td data-label="Major 2">'  + major2       + '</td>';
			html += '<td data-label="Advisor">'  + advisorName  + '</td>';
			html += '</tr>';
		} );

		html += '</tbody></table></div>';

		results.html( html );
		// Smooth scroll to results
		$( 'html, body' ).animate( { scrollTop: results.offset().top - 40 }, 400 );
	}

	// ── Helpers ───────────────────────────────────────────────────────────
	function setLoading( on ) {
		submitBtn.prop( 'disabled', on ).toggleClass( 'srp-loading', on );
	}

	function showError( msg ) {
		validMsg.text( msg ).addClass( 'srp-error' );
	}

	function escHtml( str ) {
		if ( str === null || str === undefined ) return '';
		return String( str )
			.replace( /&/g,  '&amp;'  )
			.replace( /</g,  '&lt;'   )
			.replace( />/g,  '&gt;'   )
			.replace( /"/g,  '&quot;' )
			.replace( /'/g,  '&#039;' );
	}

} )( jQuery );
