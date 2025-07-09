<?php
/**
 * Various Slim SEO customizations
 */

 if (!defined('ABSPATH')) {
    exit;
}

// Make the {sep} separator a pipe ("|")
add_filter( 'document_title_separator', function() {
    return '|';
} );


// Remove category from blog breadcrumb
add_filter( 'slim_seo_breadcrumbs_links', function( $links ) {
    // Only process single posts.
    if ( ! is_single() ) {
        return $links;
    }

    // Blog has the index 1.
    unset( $links[2] );

    return $links;
} ); 