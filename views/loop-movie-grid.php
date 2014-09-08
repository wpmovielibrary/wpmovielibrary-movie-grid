
				<div id="wpmlmg-movie-grid" class="wpmlmg-movie-grid">

<?php
global $post;
foreach ( $movies as $post ) :
	setup_postdata( $post );
?>
					<div id="<?php the_ID() ?>" <?php post_class() ?>>
						<?php if ( has_post_thumbnail() ) the_post_thumbnail( 'medium' ); ?>
					</div>

<?php
endforeach;
wp_reset_postdata();
?>
				</ul>
