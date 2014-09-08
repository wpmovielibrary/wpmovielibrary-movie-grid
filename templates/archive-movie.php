<?php get_header(); ?>

	<div id="container">

		<div id="content" role="main">

			<div id="wpmlmg-breadcrumb">
				<?php WPMovieLibrary_Movie_Grid::breadcrumb() ?>
			</div>

			<div id="wpmlmg-movies">

				<?php WPMovieLibrary_Movie_Grid::movie_grid() ?>

			</div>

		</div>

	</div>

<?php //get_sidebar(); ?>

<?php get_footer(); ?>