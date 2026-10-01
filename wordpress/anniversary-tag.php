<?php
/**
 * Plugin Name: Anniversary tag for Simple Calendar
 * Description: [anniversary] in a Simple Calendar event template shows a title like "1930: Jacques Denavit born" as "1930 (96 years ago): Jacques Denavit born". Used by "This day in robotics". Source: github.com/petercorke/writings, wordpress/anniversary-tag.php.
 *
 * The age is counted from the year of the event's occurrence being shown, not today, so
 * entries listed for the coming days are right too. Titles that don't start with a year
 * are shown unchanged.
 */

add_filter(
	'simcal_event_tags_add_custom',
	function ( array $tags ): array {
		$tags[] = 'anniversary';
		return $tags;
	}
);

add_filter(
	'simcal_event_tags_do_custom',
	function ( $value, $tag, $partial, $attr, $event ) {
		if ( 'anniversary' !== $tag ) {
			return $value;
		}
		$title = esc_html( $event->title );
		if ( ! preg_match( '/^\s*(\d{3,4})\s*:\s*(.*)$/s', $title, $m ) || ! $event->start_dt ) {
			return $title;
		}
		$years = (int) $event->start_dt->format( 'Y' ) - (int) $m[1];
		if ( $years <= 0 ) {
			return $title;
		}
		$ago = 1 === $years ? '1 year ago' : "$years years ago";
		return "{$m[1]} ($ago): {$m[2]}";
	},
	10,
	5
);
