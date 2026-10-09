<?php

/*
Template Name: Post Archive
*/

?>
<?php
// Sections on this page. A category listed here keeps its order, heading and anchor;
// any other category that has posts (say, one just created) gets a section of its own
// automatically, listed before the last one. [PIC+Claude, 10/10/26: was five hand-written blocks]
$pa_known = array(           // slug => array( anchor id, heading )
	'tutorial'  => array( 'tutes',    'TUTORIALS' ),
	'robotics'  => array( 'robotics', 'ROBOTICS' ),
	'control'   => array( 'control',  'CONTROL' ),
	'matlab'    => array( 'matlab',   'MATLAB STUFF' ),
	'mac-stuff' => array( 'mac',      'MAC STUFF' ),
	'general'   => array( 'gen',      'GENERAL' ),
);
$pa_sections = array();
$pa_extra    = array();
foreach ( get_categories( array( 'hide_empty' => true ) ) as $pa_cat ) {
	if ( isset( $pa_known[ $pa_cat->slug ] ) ) {
		$pa_sections[ $pa_cat->slug ] = array( $pa_cat->term_id, $pa_known[ $pa_cat->slug ][0], $pa_known[ $pa_cat->slug ][1] );
	} else {
		$pa_extra[ $pa_cat->name ] = array( $pa_cat->term_id, $pa_cat->slug, strtoupper( $pa_cat->name ) );
	}
}
$pa_ordered = array();
foreach ( array_keys( $pa_known ) as $pa_slug ) {            // known sections, in the order above
	if ( isset( $pa_sections[ $pa_slug ] ) ) {
		$pa_ordered[] = $pa_sections[ $pa_slug ];
	}
}
ksort( $pa_extra );                                          // new categories, by name,
$pa_last = ( isset( $pa_sections['general'] ) ) ? array_pop( $pa_ordered ) : null;   // ahead of General
$pa_ordered = array_merge( $pa_ordered, array_values( $pa_extra ), $pa_last ? array( $pa_last ) : array() );
?>
<?php get_header(); ?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main">

		<?php while ( have_posts() ) : the_post(); ?>

			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				
				<div class="entry-header">
					<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				</div><!-- .entry-header -->
				
				<?php if( get_field('show_anchor_menu') ): ?>
					<div class="anchor-menu">
						<ul>
						<?php foreach ( $pa_ordered as $pa ) : ?>
							<li>
								<a href="#<?php echo esc_attr( $pa[1] ); ?>"><?php echo esc_html( $pa[2] ); ?></a>
							</li>
						<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<div class="entry-content">
					<?php the_content();?>
					
					<div class="post-entries">
					<?php foreach ( $pa_ordered as $pa ) : ?>
						<hr />
						<h2 class="txt-orange" id="<?php echo esc_attr( $pa[1] ); ?>"><?php echo esc_html( $pa[2] ); ?></h2>
						<?php $recent = get_posts( array( 'post_type' => 'post', 'posts_per_page' => 200, 'category' => $pa[0] ) );
						if ( $recent ) {
						foreach ( $recent as $post ) {
						setup_postdata($post);
						$excerpt = substr( strip_tags( get_the_excerpt() ), 0, 250 ); ?>
							<div class="item">
								<div class="post-date">
									<?php the_time('d M Y'); ?>
								</div>
								<div class="news-text">
									<h3><?php the_title(); ?></h3>
									<p><?php echo $excerpt; ?>...</p>
								</div>
								<a href="<?php the_permalink(); ?>" class="btn-orange">Read</a>
							</div>
						<?php } } wp_reset_postdata(); ?>
					<?php endforeach; ?>
					
					</div>
					
				</div><!-- .entry-content -->

			</article><!-- #post-<?php the_ID(); ?> -->

		<?php endwhile; ?>
		
		</main><!-- #main -->
	</div><!-- #primary -->

<?php get_footer(); ?>
