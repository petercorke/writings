<?php
/**
 * Plugin Name: Documents in site search
 * Description: Lists documents from docs.petercorke.com that match a site search. Source: github.com/petercorke/writings, wordpress/docs-search.php.
 *
 * The documents' index (docs.json, written by the writings site build) is fetched at most
 * every 12 hours and kept in a transient, so a search costs no extra request. The theme's
 * search.php prints the result of pc_docs_search_html() at the top of the first page.
 */

const PC_DOCS_SITE = 'https://docs.petercorke.com/';

/**
 * The documents' index, from cache or freshly fetched.
 *
 * @return array list of documents (title, url, year, authors, summary, …); empty if unavailable
 */
function pc_docs_index(): array {
	$docs = get_transient( 'pc_docs_index' );
	if ( is_array( $docs ) ) {
		return $docs;
	}
	$response = wp_remote_get( PC_DOCS_SITE . 'docs.json', array( 'timeout' => 3 ) );
	$docs     = is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response )
		? null : json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $docs ) ) {
		set_transient( 'pc_docs_index', array(), HOUR_IN_SECONDS ); // try again in an hour
		return array();
	}
	set_transient( 'pc_docs_index', $docs, 12 * HOUR_IN_SECONDS );
	return $docs;
}

/**
 * Normalise text for matching: lower case, accents removed.
 */
function pc_docs_fold( string $s ): string {
	return mb_strtolower( remove_accents( $s ) );
}

/**
 * HTML listing the documents that contain every word of the query, as on the docs site's
 * own Find box.
 *
 * @param string $query the search as typed (unescaped)
 * @return string HTML, or '' when nothing matches or this isn't the first results page
 */
function pc_docs_search_html( string $query ): string {
	$words = preg_split( '/\s+/', pc_docs_fold( trim( $query ) ), -1, PREG_SPLIT_NO_EMPTY );
	if ( ! $words || is_paged() ) {
		return '';
	}
	$hits = array();
	foreach ( pc_docs_index() as $doc ) {
		$text = pc_docs_fold( implode( ' ', array( $doc['title'] ?? '', implode( ' ', $doc['authors'] ?? array() ), $doc['summary'] ?? '', $doc['year'] ?? '' ) ) );
		foreach ( $words as $w ) {
			if ( false === strpos( $text, $w ) ) {
				continue 2;
			}
		}
		$hits[] = $doc;
	}
	if ( ! $hits ) {
		return '';
	}
	usort( $hits, fn( $a, $b ) => ( $b['year'] ?? 0 ) <=> ( $a['year'] ?? 0 ) );
	$items = '';
	foreach ( array_slice( $hits, 0, 10 ) as $doc ) {
		$items .= sprintf(
			'<li><a href="%s">%s</a> (%s)<br><span class="pc-docs-summary">%s</span></li>',
			esc_url( $doc['url'] ), esc_html( $doc['title'] ), esc_html( $doc['year'] ?? '' ), esc_html( $doc['summary'] ?? '' )
		);
	}
	$more = sprintf(
		'<p><a href="%s">%s on docs.petercorke.com</a></p>',
		esc_url( PC_DOCS_SITE . '?q=' . rawurlencode( trim( $query ) ) ),
		count( $hits ) > 10 ? sprintf( 'All %d matching documents', count( $hits ) ) : 'Browse all documents'
	);
	return '<section class="pc-docs"><h2>Documents</h2><ul>' . $items . '</ul>' . $more . '</section>';
}
