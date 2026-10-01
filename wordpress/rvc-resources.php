<?php
/**
 * Plugin Name: RVC resource lists
 * Description: [rvc_resources topic="localization"] shows a topic's curated links from docs.petercorke.com/resources/. Source: github.com/petercorke/writings, wordpress/rvc-resources.php.
 *
 * The lists are kept in the writings repo (resources/<topic>.yml) and published as JSON
 * by its site build, so every edition's chapter page shows the same, link-checked list.
 * A topic's JSON is fetched at most every 12 hours (transient) and the last good copy is
 * kept (option), so the page still shows the list if docs.petercorke.com can't be reached.
 *
 * Attributes:
 *   topic    the topic id, e.g. "localization" (required; see resources/topics.yml)
 *   heading  heading element for each group: h2, h3 (default) or h4
 *   rule     "yes" (default) puts a horizontal rule before each group, as the chapter
 *            pages always have; "no" leaves it out
 *
 * To see a change before the cache expires: wp transient delete rvc_resources_<topic>
 */

const RVC_RESOURCES_SITE = 'https://docs.petercorke.com/resources/';

/**
 * A topic's list, from cache, freshly fetched, or the last good copy.
 *
 * @param string $topic topic id
 * @return array|null decoded JSON ({topic, title, chapters, groups}), or null if never fetched
 */
function rvc_resources_get( string $topic ): ?array {
	$cached = get_transient( "rvc_resources_$topic" );
	if ( is_array( $cached ) ) {
		return $cached ? $cached : null; // an empty array caches a miss
	}
	$response = wp_remote_get( RVC_RESOURCES_SITE . rawurlencode( $topic ) . '.json', array( 'timeout' => 5 ) );
	$data     = is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response )
		? null : json_decode( wp_remote_retrieve_body( $response ), true );
	if ( is_array( $data ) && isset( $data['groups'] ) ) {
		set_transient( "rvc_resources_$topic", $data, 12 * HOUR_IN_SECONDS );
		update_option( "rvc_resources_last_$topic", $data, false );
		return $data;
	}
	$last = get_option( "rvc_resources_last_$topic" );
	if ( is_array( $last ) ) {
		set_transient( "rvc_resources_$topic", $last, HOUR_IN_SECONDS ); // retry in an hour
		return $last;
	}
	set_transient( "rvc_resources_$topic", array(), HOUR_IN_SECONDS ); // unknown topic or site down
	return null;
}

/**
 * The [rvc_resources] shortcode.
 *
 * @param array|string $atts shortcode attributes
 * @return string HTML
 */
function rvc_resources_shortcode( $atts ): string {
	$atts  = shortcode_atts( array( 'topic' => '', 'heading' => 'h3', 'rule' => 'yes' ), $atts, 'rvc_resources' );
	$topic = sanitize_key( $atts['topic'] );
	$tag   = in_array( $atts['heading'], array( 'h2', 'h3', 'h4' ), true ) ? $atts['heading'] : 'h3';
	$rule  = 'no' !== $atts['rule'] ? "<hr />\n" : '';

	$data = $topic ? rvc_resources_get( $topic ) : null;
	if ( ! $data ) {
		// never fetched (unknown topic, or the site unreachable on first use): link to the index
		return sprintf( '<p><a href="%s">Resources for each chapter</a></p>', esc_url( RVC_RESOURCES_SITE ) );
	}
	$out = '';
	foreach ( $data['groups'] as $group ) {
		$out .= $rule . sprintf( "<%s>%s</%s>\n<ul>\n", $tag, esc_html( $group['heading'] ), $tag );
		foreach ( $group['items'] as $item ) {
			$out .= sprintf(
				"<li><a href=\"%s\" target=\"_blank\" rel=\"noopener\">%s</a>%s%s</li>\n",
				esc_url( $item['url'] ),
				esc_html( $item['title'] ),
				empty( $item['archived'] ) ? '' : ' (archived)',
				empty( $item['note'] ) ? '' : ', ' . esc_html( $item['note'] )
			);
		}
		$out .= "</ul>\n";
	}
	$out .= sprintf(
		'<p class="rvc-resources-source"><small>These links are kept in one place for every edition and checked monthly. <a href="%s">Resources for every chapter</a>.</small></p>',
		esc_url( RVC_RESOURCES_SITE )
	);
	return '<div class="rvc-resources" data-topic="' . esc_attr( $topic ) . "\">\n" . $out . '</div>';
}
add_shortcode( 'rvc_resources', 'rvc_resources_shortcode' );
