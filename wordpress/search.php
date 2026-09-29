<?php get_header(); ?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main">

		<?php
		// documents on docs.petercorke.com that match (mu-plugins/docs-search.php)
		$pc_docs = function_exists( 'pc_docs_search_html' ) ? pc_docs_search_html( get_search_query( false ) ) : '';
		if ( have_posts() || $pc_docs ) : ?>
		
			<div class="entry-header">
				<h1 class="entry-title"><?php printf( esc_html__( 'Search Results for: %s', 'zephyr_petercorke' ), '<span>' . get_search_query() . '</span>' ); ?></h1>
			</div><!-- .entry-header -->
			
			<div class="entry-content">

			<?php echo $pc_docs; ?>

			<?php if ( $pc_docs && have_posts() ) : ?><h2>Pages and posts</h2><?php endif; ?>

			<?php while ( have_posts() ) : the_post();

				get_template_part( 'template-parts/content', 'search' );

				endwhile;

				 

				else :

				get_template_part( 'template-parts/content', 'none' );

			endif; ?>
			
			<div class="pagination">
				<?php if (function_exists("pagination"))
				{pagination($additional_loop->max_num_pages);} ?>
			</div>
			
			</div>
		</main><!-- #main -->
	</div><!-- #primary -->

<?php get_footer(); ?>
