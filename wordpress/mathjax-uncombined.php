<?php
/**
 * Plugin Name: MathJax outside combined JS
 * Description: Keeps MathJax out of SiteGround Speed Optimizer's combined JavaScript. Source: github.com/petercorke/writings, wordpress/mathjax-uncombined.php.
 *
 * The MathJax-LaTeX plugin loads MathJax 2 from cdnjs. With "Combine JavaScript" on,
 * Speed Optimizer copies it into one combined file, where MathJax can no longer find its
 * configuration and extensions (it loads them relative to its own URL), so equations show
 * as raw LaTeX. Speed Optimizer's handle-based exclusion list only applies to local
 * scripts; external ones are excluded by a fragment of their URL, through this filter.
 */

add_filter(
	'sgo_javascript_combine_excluded_external_paths',
	function ( array $paths ): array {
		$paths[] = 'mathjax';
		return $paths;
	}
);
