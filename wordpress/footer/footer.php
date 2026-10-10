</div><!-- #content -->

	<footer>
		<div class="footer-logo-box">
			<a href="<?php bloginfo('url'); ?>">
				<?php $image = get_field('site_logo','option');
				if( !empty($image) ): ?>
					<img src="<?php echo $image['url']; ?>" alt="<?php echo $image['alt']; ?>" />
					<h3><?php the_field('site_title','option'); ?></h3>
				<?php endif; ?>
			</a>
		</div>
		<div class="social-media">
		<ul>
		<?php
		// The ACF rows have only an image and a link, no label, so the hovertip and the accessible
		// name are taken from the link's host.  Unknown hosts fall back to the bare host name.
		$labels = array(
			'github.com'       => 'GitHub',
			'linkedin.com'     => 'LinkedIn',
			'scholar.google.'  => 'Google Scholar',
			'orcid.org'        => 'ORCID',
			'dblp.org'         => 'DBLP',
			'researchgate.net' => 'ResearchGate',
		);
		$rows = get_field('social_media','option');
		if ( $rows ) {
		foreach ( $rows as $row ) {
			$label = '';
			foreach ( $labels as $needle => $name ) {
				if ( false !== strpos( $row['link'], $needle ) ) { $label = $name; break; }
			}
			if ( '' === $label ) { $label = (string) wp_parse_url( $row['link'], PHP_URL_HOST ); }
			?>
			<li><a href="<?php echo esc_url( $row['link'] ); ?>" target="_blank" rel="noopener" title="<?php echo esc_attr( $label ); ?>" aria-label="<?php echo esc_attr( $label ); ?>"><img src="<?php echo esc_url( $row['logo'] ); ?>" alt=""></a></li>
		<?php } } ?>
		</ul>
		</div>
		<div class="logo-bar-footer"></div>
		<div class="orange-bar-footer"></div>
	</footer>
	<div class="copyright">
	<p>&copy; COPYRIGHT <?php echo date('Y'); ?>, Peter Corke. All rights reserved. &nbsp;&nbsp;&nbsp;
	<?php $post_objects = get_field('footer_links','option');
	if( $post_objects ): ?>
		<?php foreach( $post_objects as $post_object): ?>
			<a href="<?php echo get_permalink($post_object->ID); ?>"><?php echo get_the_title($post_object->ID); ?></a>&nbsp;&nbsp;&nbsp;
			<?php endforeach; ?>
	<?php endif; ?>
	<a href="https://www.zephyrmedia.com.au" target="_blank">WEBSITE BY ZEPHYRMEDIA</a></p>
	</div>
</div><!-- #page -->

<div style="display: none;" id="search-modal">
	<?php get_search_form(); ?>
</div>

<script type='text/javascript' src="<?php bloginfo('template_url'); ?>/js/jquery.fancybox.min.js"></script>

<script>
var setCookie = function(cname, cvalue, exdays) {
  var d = new Date();
  d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
  var expires = "expires=" + d.toUTCString();
  document.cookie = cname + "=" + cvalue + "; " + expires;
}

var getCookie = function(cname) {
  var name = cname + "=";
  var ca = document.cookie.split(';');
  for (var i = 0; i < ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) == ' ') c = c.substring(1);
    if (c.indexOf(name) == 0) return c.substring(name.length, c.length);
  }
  return "";
}

jQuery(document).ready(function($) {
  console.log(getCookie("closed"));
  if (getCookie("closed") == "closed") {
    $("#close-me").hide();
  }

  jQuery(".close-div").click(function() {
    jQuery("#close-me").remove();
    setCookie("closed", "closed", 1)
  });
});
</script>


<?php wp_footer(); ?>

</body>
</html>
