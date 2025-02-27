<?php

/**
 * Plugin Name: Tailor - Portfolio extension
 * Plugin URI: http://www.gettailor.com
 * Description: Adds portfolio functionality to the Tailor plugin.
 * Version: 1.2.2
 * Author: The Tailor Team
 * Author URI:  http://www.gettailor.com
 * Text Domain: tailor-portfolio
 *
 * @package Tailor Portfolio
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Tailor_Portfolio' ) ) {

    /**
     * Tailor Portfolio class.
     *
     * @since 1.0.0
     */
    class Tailor_Portfolio {

        /**
         * Tailor Portfolio instance.
         *
         * @since 1.0.0
         * @access private
         * @var Tailor_Portfolio
         */
        private static $instance;

        /**
         * The plugin version number.
         *
         * @since 1.0.0
         * @access private
         * @var string
         */
        private static $version;

	    /**
	     * The plugin basename.
	     *
	     * @since 1.0.0
	     * @access private
	     * @var string
	     */
	    private static $plugin_basename;

        /**
         * The plugin name.
         *
         * @since 1.0.0
         * @access private
         * @var string
         */
        private static $plugin_name;

        /**
         * The plugin directory.
         *
         * @since 1.0.0
         * @access private
         * @var string
         */
        private static $plugin_dir;

        /**
         * The plugin URL.
         *
         * @since 1.0.0
         * @access private
         * @var string
         */
        private static $plugin_url;

	    /**
	     * The minimum required version of Tailor.
	     *
	     * @since 1.2.1
	     * @access private
	     * @var string
	     */
	    private static $required_tailor_version = '1.7.2';

        /**
         * Returns the Tailor Portfolio instance.
         *
         * @since 1.0.0
         *
         * @return Tailor_Portfolio
         */
        public static function instance() {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Constructor.
         *
         * @since 1.0.0
         */
	    public function __construct() {

            $plugin_data = get_file_data( __FILE__, array( 'Plugin Name', 'Version' ) );

            self::$plugin_basename = plugin_basename( __FILE__ );
            self::$plugin_name = array_shift( $plugin_data );
            self::$version = array_shift( $plugin_data );
	        self::$plugin_dir = trailingslashit( plugin_dir_path( __FILE__ ) );
	        self::$plugin_url = trailingslashit( plugin_dir_url( __FILE__ ) );

		    add_action( 'plugins_loaded', array( $this, 'init' ) );
        }
	    
	    /**
	     * Initializes the plugin.
	     *
	     * @since 1.0.0
	     */
	    public function init() {
		    if (
			    ! class_exists( 'Tailor' ) ||                                                       // Tailor is not active, or
			    ! version_compare( tailor()->version(), self::$required_tailor_version, '>=' )      // An unsupported version is being used
		    ) {
			    add_action( 'admin_notices', array( $this, 'display_version_notice' ) );
			    return;
		    }

		    load_plugin_textdomain( 'tailor-portfolio', false, $this->plugin_dir() . 'languages/' );

		    $plugins_includes_dir = $this->plugin_dir() . 'includes/';

		    require_once $plugins_includes_dir . 'portfolio/class-post-type.php';

		    $this->load_files( array( 'helpers' ) );
		    $this->load_directory( 'shortcodes' );
		    $this->add_actions();
	    }

	    /**
	     * Displays an admin notice if an unsupported version of Tailor is being used.
	     *
	     * @since 1.2.0
	     */
	    public function display_version_notice() {
		    printf(
			    '<div class="notice notice-warning is-dismissible">' .
		            '<p>%s</p>' .
			    '</div>',
			    sprintf(
				    __( 'Please ensure that Tailor %s (or newer) is active to use the Portfolio extension.', 'tailor-portfolio' ),
				    self::$required_tailor_version
			    )
		    );
	    }

        /**
         * Adds required action hooks.
         *
         * @since 1.0.0
         * @access protected
         */
        protected function add_actions() {
	        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	        add_action( 'tailor_canvas_enqueue_scripts', array( $this, 'enqueue_canvas_scripts' ), 99 );
	        add_action( 'tailor_enqueue_sidebar_styles', array( $this, 'enqueue_sidebar_styles' ) );

	        add_action( 'tailor_register_elements', array( $this, 'register_elements' ), 99 );
	        add_filter( 'tailor_plugin_partial_paths', array( $this, 'register_partial_path' ) );
        }

	    /**
	     * Enqueues frontend styles.
	     *
	     * @since 1.0.0
	     */
	    public function enqueue_styles() {
		    if ( apply_filters( 'tailor_enqueue_stylesheets', true ) ) {

			    $extension = SCRIPT_DEBUG ? '.css' : '.min.css';

			    wp_enqueue_style(
				    'tailor-portfolio-styles',
				    $this->plugin_url() . 'assets/css/frontend' . $extension,
				    array(),
				    $this->version()
			    );
		    }
	    }

	    /**
	     * Enqueues frontend scripts.
	     *
	     * @since 1.2.2
	     */
	    public function enqueue_scripts() {

		    $extension = SCRIPT_DEBUG ? '.js' : '.min.js';

		    wp_enqueue_script(
			    'tailor-portfolio',
			    tailor_portfolio()->plugin_url() . 'assets/js/dist/frontend' . $extension,
			    array( 'tailor-frontend' ),
			    tailor_portfolio()->version(),
			    true
		    );
	    }


	    /**
	     * Enqueues sidebar styles.
	     *
	     * @since 1.0.2
	     */
	    public function enqueue_sidebar_styles() {

		    $extension = SCRIPT_DEBUG ? '.css' : '.min.css';

		    wp_enqueue_style(
			    'tailor-portfolio-sidebar-styles',
			    $this->plugin_url() . 'assets/css/sidebar' . $extension,
			    array(),
			    $this->version()
		    );
	    }
	    
	    /**
	     * Enqueues Canvas scripts.
	     *
	     * @since 1.0.0
	     */
	    public function enqueue_canvas_scripts() {

		    $extension = SCRIPT_DEBUG ? '.js' : '.min.js';

		    wp_enqueue_script(
			    'tailor-portfolio-canvas',
			    tailor_portfolio()->plugin_url() . 'assets/js/dist/canvas' . $extension,
			    array( 'tailor-canvas' ),
			    tailor_portfolio()->version(),
			    true
		    );
	    }

	    /**
	     * Registers the partial directory for this extension plugin.
	     *
	     * @since 1.0.0
	     *
	     * @param $paths
	     *
	     * @return array
	     */
	    public function register_partial_path( $paths ) {
		    $paths[] = $this->plugin_dir() . 'partials/';
		    return $paths;
	    }

	    /**
	     * Loads and registers the new Tailor elements and shortcodes.
	     *
	     * @since 1.0.0
	     *
	     * @param $element_manager Tailor_Elements
	     */
	    public function register_elements( $element_manager ) {

		    $this->load_directory( 'elements' );

		    $element_manager->add_element( 'tailor_projects', array(
			    'label'             =>  __( 'Projects', 'tailor-portfolio' ),
			    'description'       =>  __( 'Your site\'s projects.', 'tailor-portfolio' ),
			    'badge'             =>  __( 'Portfolio', 'tailor-portfolio' ),
			    'dynamic'           =>  true,
		    ) );
	    }

        /**
         * Returns the version number of the plugin.
         *
         * @since 1.0.0
         *
         * @return string
         */
        public function version() {
            return self::$version;
        }

	    /**
	     * Returns the plugin basename.
	     *
	     * @since 1.0.0
	     *
	     * @return string
	     */
	    public function plugin_basename() {
		    return self::$plugin_basename;
	    }

        /**
         * Returns the plugin name.
         *
         * @since 1.0.0
         *
         * @return string
         */
        public function plugin_name() {
            return self::$plugin_name;
        }

        /**
         * Returns the plugin directory.
         *
         * @since 1.0.0
         *
         * @return string
         */
        public function plugin_dir() {
            return self::$plugin_dir;
        }

        /**
         * Returns the plugin URL.
         *
         * @since 1.0.0
         *
         * @return string
         */
        public function plugin_url() {
            return self::$plugin_url;
        }

	    /**
	     * Loads all PHP files in a given directory.
	     *
	     * @since 1.0.0
	     */
	    public function load_directory( $directory_name ) {
		    $path = trailingslashit( $this->plugin_dir() . 'includes/' . $directory_name );
		    $file_names = glob( $path . '*.php' );
		    foreach ( $file_names as $filename ) {
			    if ( file_exists( $filename ) ) {
				    require_once $filename;
			    }
		    }
	    }

	    /**
	     * Loads specified PHP files from the plugin includes directory.
	     *
	     * @since 1.0.0
	     *
	     * @param array $file_names The names of the files to be loaded in the includes directory.
	     */
	    public function load_files( $file_names = array() ) {
		    foreach ( $file_names as $file_name ) {
			    if ( file_exists( $path = $this->plugin_dir() . 'includes/' . $file_name . '.php' ) ) {
				    require_once $path;
			    }
		    }
	    }
    }
}

if ( ! function_exists( 'tailor_portfolio' ) ) {

	/**
	 * Returns the Tailor Portfolio instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Tailor_Portfolio
	 */
	function tailor_portfolio() {
		return Tailor_Portfolio::instance();
	}
}

/**
 * Initializes the Tailor Portfolio plugin.
 *
 * @since 1.0.0
 */
tailor_portfolio();