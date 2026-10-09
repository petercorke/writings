<?php
/**
 * Plugin Name: Blog feed
 * Description: [blog_feed] shows one chronological list, newest first and grouped by year, of petercorke.com's own posts and Peter's GitHub Discussions (the ones he started, plus announcements, from every repo that has Discussions on). Used by the Blog page. Source: github.com/petercorke/writings, wordpress/blog-feed.php.
 *
 * Posts come straight from the database. The discussions are collected daily by
 * tools/blogfeed.py (workflow blog-feed.yml) into blog/discussions.json on the writings repo's
 * `data` branch. That file is fetched at most every 6 hours (transient) and the last good copy
 * is kept (option), so the list still shows posts, and the last known discussions, if GitHub
 * can't be reached. Discussion items link out to GitHub and carry a small source line.
 *
 * Attributes:
 *   menu   "yes" (default) shows a row of year links above the list; "no" leaves it out
 *
 * To see a change before the cache expires: wp transient delete blog_feed_discussions
 */

const BLOG_FEED_JSON = 'https://raw.githubusercontent.com/petercorke/writings/data/blog/discussions.json';

/** Short names for the repos, as Peter calls them. */
const BLOG_FEED_NICKNAMES = array(
	'robotics-toolbox-python'      => 'RTB',
	'machinevision-toolbox-python' => 'MVTB',
	'spatialmath-python'           => 'SMTB',
);

/**
 * The GitHub discussions list: from cache, freshly fetched, or the last good copy.
 *
 * @return array[] items, each {date, title, url, repo, category, excerpt}; empty if never fetched
 */
function blog_feed_discussions(): array {
	$cached = get_transient( 'blog_feed_discussions' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$response = wp_remote_get( BLOG_FEED_JSON, array( 'timeout' => 5 ) );
	$data     = is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response )
		? null : json_decode( wp_remote_retrieve_body( $response ), true );
	if ( is_array( $data ) && isset( $data['items'] ) && is_array( $data['items'] ) ) {
		set_transient( 'blog_feed_discussions', $data['items'], 6 * HOUR_IN_SECONDS );
		update_option( 'blog_feed_discussions_last', $data['items'], false );
		return $data['items'];
	}
	$last = get_option( 'blog_feed_discussions_last' );
	$last = is_array( $last ) ? $last : array();
	set_transient( 'blog_feed_discussions', $last, HOUR_IN_SECONDS ); // retry in an hour
	return $last;
}

/**
 * Everything to list, newest first.
 *
 * @return array[] each {ts, title, url, excerpt, source} where source is '' for a post
 */
function blog_feed_items(): array {
	$items = array();
	foreach ( get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => -1, 'no_found_rows' => true ) ) as $post ) {
		$items[] = array(
			'ts'      => get_post_timestamp( $post ),
			'title'   => get_the_title( $post ),
			'url'     => get_permalink( $post ),
			'excerpt' => wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post ) ), 35, '…' ),
			'source'  => '',
		);
	}
	foreach ( blog_feed_discussions() as $d ) {
		$ts = strtotime( $d['date'] ?? '' );
		if ( ! $ts || empty( $d['url'] ) || empty( $d['title'] ) ) {
			continue;
		}
		$repo     = $d['repo'] ?? '';
		$items[]  = array(
			'ts'      => $ts,
			'title'   => $d['title'],
			'url'     => $d['url'],
			'excerpt' => $d['excerpt'] ?? '',
			'source'  => 'GitHub discussion · ' . ( BLOG_FEED_NICKNAMES[ $repo ] ?? $repo ),
		);
	}
	usort( $items, fn( $a, $b ) => $b['ts'] <=> $a['ts'] );
	return $items;
}

/**
 * The [blog_feed] shortcode.
 *
 * @param array|string $atts shortcode attributes
 * @return string HTML
 */
function blog_feed_shortcode( $atts ): string {
	$atts  = shortcode_atts( array( 'menu' => 'yes' ), $atts, 'blog_feed' );
	$years = array();
	foreach ( blog_feed_items() as $item ) {
		$years[ wp_date( 'Y', $item['ts'] ) ][] = $item;
	}
	if ( ! $years ) {
		return '';
	}
	$out = '';
	if ( 'no' !== $atts['menu'] ) {
		$out .= '<div class="anchor-menu"><ul>';
		foreach ( array_keys( $years ) as $year ) {
			$out .= sprintf( '<li><a href="#blog-%1$s">%1$s</a></li>', esc_attr( $year ) );
		}
		$out .= "</ul></div>\n";
	}
	$out .= '<div class="post-entries blog-feed">';
	foreach ( $years as $year => $items ) {
		$out .= sprintf( "\n<hr />\n<h2 class=\"txt-orange\" id=\"blog-%1\$s\">%1\$s</h2>\n", esc_attr( $year ) );
		foreach ( $items as $item ) {
			$external = '' !== $item['source'];
			$out     .= sprintf(
				"<div class=\"item\">\n<div class=\"post-date\">%s</div>\n<div class=\"news-text\">\n<h3>%s</h3>\n%s%s</div>\n<a href=\"%s\" class=\"btn-orange\"%s>Read</a>\n</div>\n",
				esc_html( wp_date( 'd M Y', $item['ts'] ) ),
				esc_html( $item['title'] ),
				$external ? '<p class="blog-feed-source"><small>' . esc_html( $item['source'] ) . "</small></p>\n" : '',
				'' !== $item['excerpt'] ? '<p>' . esc_html( $item['excerpt'] ) . "</p>\n" : '',
				esc_url( $item['url'] ),
				$external ? ' target="_blank" rel="noopener"' : ''
			);
		}
	}
	return $out . "</div>\n";
}
add_shortcode( 'blog_feed', 'blog_feed_shortcode' );
