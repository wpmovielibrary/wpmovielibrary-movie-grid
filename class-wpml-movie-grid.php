<?php
/**
 * WPMovieLibrary-Movie-Grid
 *
 * @package   WPMovieLibrary-Movie-Grid
 * @author    Charlie MERLAND <charlie@caercam.org>
 * @license   GPL-3.0
 * @link      http://www.caercam.org/
 * @copyright 2014 Charlie MERLAND
 */

if ( ! class_exists( 'WPMovieLibrary_Movie_Grid' ) ) :

	/**
	* Plugin class
	*
	* @package WPMovieLibrary-Movie-Grid
	* @author  Charlie MERLAND <charlie@caercam.org>
	*/
	class WPMovieLibrary_Movie_Grid extends WPMLMG_Module {

		/**
		 * Initialize the plugin by setting localization and loading public scripts
		 * and styles.
		 *
		 * @since     1.0
		 */
		public function __construct() {

			$this->init();
		}

		/**
		 * Initializes variables
		 *
		 * @since    1.0
		 */
		public function init() {

			if ( ! $this->wpml_requirements_met() ) {
				add_action( 'init', 'wpmlmg_l10n' );
				add_action( 'admin_notices', 'wpmlmg_requirements_error' );
				return false;
			}

			$this->register_hook_callbacks();

			$this->register_shortcodes();
		}

		/**
		 * Make sure WPMovieLibrary is active and compatible.
		 *
		 * @since    1.0
		 * 
		 * @return   boolean    Requirements met or not?
		 */
		private function wpml_requirements_met() {

			$wpml_active  = is_wpml_active();
			$wpml_version = ( is_wpml_active() && version_compare( WPML_VERSION, WPMLMG_REQUIRED_WPML_VERSION, '>=' ) );

			if ( ! $wpml_active || ! $wpml_version )
				return false;

			return true;
		}

		/**
		 * Register callbacks for actions and filters
		 * 
		 * @since    1.0
		 */
		public function register_hook_callbacks() {

			add_action( 'plugins_loaded', 'wpmlmg_l10n' );

			add_action( 'activated_plugin', __CLASS__ . '::require_wpml_first' );

			// Enqueue scripts and styles
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

			add_filter( 'rewrite_rules_array', array( $this, 'register_permalinks' ), 11 );
			add_filter( 'query_vars', array( $this, 'movie_grid_query_vars' ), 10, 1 );

			add_filter( 'template_include', array( $this, 'filter_movie_archive_template' ) );
		}

		/**
		 * Create a new set of permalinks for Movie Grid
		 *
		 * @since    1.0
		 *
		 * @param    object     $wp_rewrite Instance of WordPress WP_Rewrite Class
		 */
		public static function register_permalinks( $rules = null ) {

			$movies = WPML_Settings::wpml__movie_rewrite();
			$movies = ( '' != $movies ? $movies : 'movies' );

			$new_rules = array(
				$movies . '/grid/([^/]+)/?$' => 'index.php?post_type=movie&wpml_view=grid&wpml_letter=$matches[1]',
				$movies . '/grid/([^/]+)/page/([^/]+)?$' => 'index.php?post_type=movie&wpml_view=grid&wpml_letter=$matches[1]&page=$matches[2]',
				$movies . '/grid/page/([^/]+)?$' => 'index.php?post_type=movie&wpml_view=grid&page=$matches[1]',
				$movies . '/grid/?$' => 'index.php?post_type=movie&wpml_view=grid',
			);

			if ( ! is_null( $rules ) )
				return $new_rules + $rules;

			foreach ( $new_rules as $regex => $rule )
				add_rewrite_rule( $regex, $rule, 'top' );
		}

		/**
		 * Register all shortcodes.
		 *
		 * @since    1.0
		 */
		public function register_shortcodes() {

			//add_shortcode( 'movie_trailer', __CLASS__ . '::movie_trailer_shortcode' );
		}

		/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 *
		 *                     Plugin  Activate/Deactivate
		 * 
		 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

		/**
		 * Fired when the plugin is activated.
		 *
		 * @since    1.0
		 *
		 * @param    boolean    $network_wide    True if WPMU superadmin uses
		 *                                       "Network Activate" action, false if
		 *                                       WPMU is disabled or plugin is
		 *                                       activated on an individual blog.
		 */
		public function activate( $network_wide ) {

			global $wpdb;

			if ( function_exists( 'is_multisite' ) && is_multisite() ) {
				if ( $network_wide ) {
					$blogs = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

					foreach ( $blogs as $blog ) {
						switch_to_blog( $blog );
						$this->single_activate( $network_wide );
					}

					restore_current_blog();
				} else {
					$this->single_activate( $network_wide );
				}
			} else {
				$this->single_activate( $network_wide );
			}

		}

		/**
		 * Fired when the plugin is deactivated.
		 * 
		 * When deactivatin/uninstalling WPML, adopt different behaviors depending
		 * on user options. Movies and Taxonomies can be kept as they are,
		 * converted to WordPress standars or removed. Default is conserve on
		 * deactivation, convert on uninstall.
		 *
		 * @since    1.0
		 */
		public function deactivate() {

			flush_rewrite_rules();
		}

		/**
		 * Runs activation code on a new WPMS site when it's created
		 *
		 * @since    1.0
		 *
		 * @param    int    $blog_id
		 */
		public function activate_new_site( $blog_id ) {
			switch_to_blog( $blog_id );
			$this->single_activate( true );
			restore_current_blog();
		}

		/**
		 * Prepares a single blog to use the plugin
		 *
		 * @since    1.0
		 *
		 * @param    bool    $network_wide
		 */
		protected function single_activate( $network_wide ) {

			self::require_wpml_first();

			flush_rewrite_rules();
		}

		/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 *
		 *                     Scripts/Styles and Utils
		 * 
		 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

		/**
		 * Register and enqueue public-facing style sheet.
		 *
		 * @since    1.0
		 */
		public function enqueue_styles() {

			wp_enqueue_style( WPMLMG_SLUG . '-css', WPMLMG_URL . '/assets/css/public.css', array(), WPMLMG_VERSION );
		}

		/**
		 * Register and enqueue public-facing style sheet.
		 *
		 * @since    1.0
		 */
		public function admin_enqueue_styles() {

			wp_enqueue_style( WPMLMG_SLUG . '-admin-css', WPMLMG_URL . '/assets/css/admin.css', array(), WPMLMG_VERSION );
		}

		/**
		 * Register and enqueue public-facing style sheet.
		 *
		 * @since    1.0
		 */
		public function admin_enqueue_scripts() {

			//wp_enqueue_script( WPMLMG_SLUG . 'admin-js', WPMLMG_URL . '/assets/js/wpmltr-trailers.js', array( WPML_SLUG ), WPMLMG_VERSION, true );
		}

		/**
		 * Make sure the plugin is load after WPMovieLibrary and not
		 * before, which would result in errors and missing files.
		 *
		 * @since    1.0
		 */
		public static function require_wpml_first() {

			$this_plugin_path = plugin_dir_path( __FILE__ );
			$this_plugin      = basename( $this_plugin_path ) . '/wpml-movie-grid.php';
			$active_plugins   = get_option( 'active_plugins' );
			$this_plugin_key  = array_search( $this_plugin, $active_plugins );
			$wpml_plugin_key  = array_search( 'wpmovielibrary/wpmovielibrary.php', $active_plugins );

			if ( $this_plugin_key < $wpml_plugin_key ) {

				unset( $active_plugins[ $this_plugin_key ] );
				$active_plugins = array_merge(
					array_slice( $active_plugins, 0, $wpml_plugin_key ),
					array( $this_plugin ),
					array_slice( $active_plugins, $wpml_plugin_key )
				);

				update_option( 'active_plugins', $active_plugins );
			}
		}

		/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 *
		 *                              Callbacks
		 * 
		 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

		

		/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 *
		 *                              Movie Grid
		 * 
		 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

		/**
		 * Generate alphanumerical breadcrumb menu for Grid view
		 * 
		 * @since    1.0
		 */
		public static function breadcrumb() {

			global $wp_query, $wpdb, $wp_rewrite;

			if ( ! is_post_type_archive( 'movie' ) || 'grid' != get_query_var( 'wpml_view' ) )
				return false;

			$default = str_split( '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ' );
			$letters = array();
			$url = home_url( ( '' == $wp_rewrite->permalink_structure ? '?post_type=movie&grid=' : 'movies/grid/' ) ) ;
			$current = get_query_var( 'wpml_letter' );

			$result = $wpdb->get_results( "SELECT DISTINCT LEFT(post_title, 1) as letter FROM {$wpdb->posts} WHERE post_type='movie' AND post_status='publish' ORDER BY letter" );
			foreach ( $result as $r )
				$letters[] = $r->letter;

			echo self::render_template( 'breadcrumb.php', array( 'letters' => $letters, 'default' => $default, 'url' => $url, 'current' => $current ) );
		}

		/**
		 * Generate pagination menu for Grid view
		 * 
		 * @since    1.0
		 */
		public static function movie_grid_pagination( $args ) {

			$pagination = WPML_Utils::paginate_links( $args );

			return $pagination;
		}

		/**
		 * Generate Movie Grid
		 * 
		 * If a current letter is passed to the query use it to narrow
		 * the list of movies; if no letter is defined display the first
		 * 20 movies in alphabetical order.
		 * 
		 * @since    1.0
		 */
		public static function movie_grid() {

			global $wpdb;

			$letter = get_query_var( 'wpml_letter' );
			$paged  = get_query_var( 'page' );
			$total  = 0;

			$movies = array();
			$posts_per_page = 8;

			if ( '' != $letter ) {

				// like_escape deprecated since WordPress 4.0
				$where  = ( method_exists( 'wpdb', 'esc_like' ) ? $wpdb->esc_like( $letter ) : like_escape( $letter ) ) . '%';
				$result = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT ID FROM {$wpdb->posts} WHERE post_type='movie' AND post_status='publish' AND post_title LIKE '%s' ORDER BY post_title ASC",
						$where
					)
				);
				$total = count( $result );

				if ( ! empty( $result ) )
					foreach ( $result as $r )
						$movies[] = $r->ID;
			}

			$args = array(
				'posts_per_page' => $posts_per_page,
				'offset'         => max( 0, ( $paged - 1 ) * $posts_per_page ),
				'orderby'        => 'post_title',
				'order'          => 'ASC',
				'post_type'      => 'movie',
				'post_status'    => 'publish'
			);

			if ( ! empty( $movies ) )
				$args['post__in'] = $movies;

			$movies = get_posts( $args );

			$slug = WPML_Settings::wpml__movie_rewrite();
			$args = array(
				'type'    => 'list',
				'total'   => ceil( ( $total ) / $posts_per_page ),
				'current' => max( 1, $paged ),
				'format'  => home_url( "{$slug}/grid/{$letter}/page/%#%/" ),
			);

			$paginate = self::movie_grid_pagination( $args );
			$paginate = '<div id="wpmlmg-movies-pagination">' . $paginate . '</div>';

			$content  = self::render_template( 'loop-movie-grid.php', array( 'movies' => $movies ) );
			$content  = $content . $paginate;

			echo $content;
		}

		/**
		 * Get movies for the grid
		 * 
		 * If a current letter is passed to the query use it to narrow
		 * the list of movies; if no letter is defined get the first
		 * 20 movies in alphabetical order.
		 * 
		 * @since    1.0
		 * 
		 * @return   array    Movies for the grid
		 */
		private static function get_movies() {

			

			return $movies;
		}

		/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 *
		 *                              Shortcodes
		 * 
		 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

		

		/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 *
		 *                               Utils
		 * 
		 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

		/**
		 * Filter Templates to show a custom view on Movie Archives Page
		 * 
		 * @since    1.0
		 * 
		 * @param    string    $template The path of the template to include.
		 * 
		 * @return   string    Edited $template
		 */
		public function filter_movie_archive_template( $template ) {

			if ( is_post_type_archive( 'movie' ) && 'grid' == get_query_var( 'wpml_view' ) )
				$template = WPMLMG_PATH . '/templates/archive-movie.php';

			return $template;
		}

		/**
		 * Add Movie Grid slugs to queryable vars
		 * 
		 * @since    1.0
		 * 
		 * @param    array     Current WP_Query instance's queryable vars
		 * 
		 * @return   array     Updated WP_Query instance
		 */
		public function movie_grid_query_vars( $q_var ) {

			$q_var[] = 'wpml_view';
			$q_var[] = 'wpml_letter';

			return $q_var;
		}

	}
endif;