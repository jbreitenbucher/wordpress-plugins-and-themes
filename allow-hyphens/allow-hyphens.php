<?php

/**
 * Plugin Name: Allow hyphens in site names
 * Plugin URI: http://apex.wooster.edu
 * Description: Expands Site Name choices during the sign up process to allow "internal" dashes "-" (hyphens).
 * Version: 1.0.0
 * Revision Date: 06/18/2013
 * Requires at least: WP 3.1
 * Tested up to: WP 3.5.1
 * License: GNU General Public License 2.0 (GPL) or later
 * Author: Jon Breitenbucher
 * Author URI: http://jon.breitenbucher.net
 * Network: True
 * Tags: blog, name, multi, site, sitename, blogname, multisite, network, multinetorks, networks, dash, hyphen
 */

if ( function_exists( 'add_filter' ) ) {
	add_filter( 'wpmu_validate_blog_signup', 'jb_wpmu_validate_blog_signup' );
}

function jb_wpmu_validate_blog_signup( $result ) {

	$olderrors = $result[ 'errors' ];

	// If Site Name ('blogname') is long enough,
	// and we have no error object, just return.
	if ( ! is_object( $olderrors ) ) {
	    return $result;
	}

	// Build a new WP_Error Object to hold new errors.
	$errors = new WP_Error();

	// Look for a 'blogname' $code error in this loop.
	foreach ( $olderrors->errors as $code => $error ) {
	if ( $code == 'blogname' ) {
	    // Sort the 'blogname' error $value with this loop.
	    foreach ( $error as $key => $value ) {
	        // Switch each action based on the $error $value
	        // and our slected options.
	        switch ( $value ) {

	            case "Only lowercase letters (a-z) and numbers are allowed.":
	                $ok_chars = '-';

	                $pattern = '/^[a-z0-9]+([' . $ok_chars . ']?[a-z0-9]+)*$/';
	                preg_match( $pattern, $result[ 'blogname' ], $match );

	                    if ( $result[ 'blogname' ] != $match[ 0 ] ) {
	                        // Build a new error to add to the $errors object
	                        // Allow Lowercase Letters
	                        $ok_chars = __( 'Only the following Characters are allowed: lowercase letters (a-z), numbers (0-9) and hyphen (-).' );

	                            // Add the new error to the $errors object
	                            $errors->add( 'blogname', $ok_chars );
	                    }

	                break;

	            case "That name is not allowed.":
	                // Do Nothing, just break
	                break;
								
	            case "Site name must be at least 4 characters.":
	                // Do Nothing, just break
	                break;

	            case "Sorry, site names may not contain the character &#8220;_&#8221;!":
	                // Do Nothing, just break
	                break;
								
	            case "Sorry, you may not use that site name.":
	                // Do Nothing, just break
	                break;

	            case "Sorry, site names must have letters too!":
	                // Do Nothing, just break
	                break;

	            case "Sorry, that site is reserved!":
	                // Do Nothing, just break
	                break;
				default:
				    $errors->add( 'blogname', $value );
			} // end switch ($value)
	    } // end foreach ($error as $key => $value)
	} else {
	    // Add other errors to $error object from the nested arrays.
	        foreach ( $error as $key => $value ) {
	            $errors->add( $code, $value );
	        }
	} // end if ($code == 'blogname')
	} // end foreach ($olderrors->errors as $code => $error)

	// Unset old errors object in $result
	    unset( $result[ 'errors' ] );
	// Set new errors object in $result
	    $result[ 'errors' ] = $errors;

	return $result;
} // end function jb_wpmu_validate_blog_signup