<?php
/**
 * Plugin Name: Paste as plain text
 * Description: Makes the classic editor paste as plain text by default. Pasting from an email, Gemini, Word or a web page otherwise carries the source's HTML along (wrapper divs, <p> inside list items, tracking attributes, citation spans), which WordPress stores as it is and the theme then spaces badly; the editor hides it, so the damage only shows on the published page. Format with the editor's own toolbar after pasting. The toolbar's "Paste as text" button (kitchen-sink row) still switches it off for a deliberate rich paste. Source: github.com/petercorke/writings, wordpress/paste-as-text.php.
 *
 * Delete this file to restore the editor's normal paste behavior.
 */

add_filter(
	'tiny_mce_before_init',
	function ( array $init ): array {
		$init['paste_as_text'] = true;
		return $init;
	}
);
