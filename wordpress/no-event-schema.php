<?php
/**
 * Plugin Name: No Event schema for Simple Calendar
 * Description: Strips the schema.org Event microdata (itemscope/itemtype/itemprop) that Simple Calendar hard-codes onto every [calendar] list item. "This day in robotics" lists historical anniversaries, not events anyone can attend, and the markup only ever had startDate and description, so Search Console reported every entry as an invalid Event (missing 'location'). Source: github.com/petercorke/writings, wordpress/no-event-schema.php.
 *
 * Done on the shortcode output rather than by editing the plugin, so plugin updates can't undo it.
 * Delete this file to restore the plugin's own markup.
 */

add_filter(
	'do_shortcode_tag',
	function ( string $output, string $tag ): string {
		if ( 'calendar' !== $tag ) {
			return $output;
		}
		return preg_replace( '/\s+(?:itemscope|itemtype="[^"]*"|itemprop="[^"]*")/', '', $output ) ?? $output;
	},
	10,
	2
);
