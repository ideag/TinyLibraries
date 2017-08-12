<?php
/**
 * TinyLibraries
 *
 * @package TinyLibraries
 */

/*
Plugin Name: TinyLibraries
Plugin URI: http://arunas.co
Description: Create library plugins
Version: 0.1.0
Author: Arūnas Liuiza
Author URI: http://arunas.co
Text Domain: tinylibraries
*/

/**
 * Plugin class instantioation function
 */
function TinyLibraries() {
	if ( false === TinyLibrariesClass::$instance ) {
		TinyLibrariesClass::$instance = new TinyLibrariesClass();
	}
	return TinyLibrariesClass::$instance;
}
add_action( 'plugins_loaded', 'TinyLibraries' );

/**
 * Main olugin class.
 */
class TinyLibrariesClass {
	/**
	 * Stores plugin class instance.
	 *
	 * @var object
	 */
	public static $instance = false;
	/**
	 * Stores plugin full path
	 *
	 * @var string
	 */
	public $plugin_path = '';
	/**
	 * Stores plugin filter
	 *
	 * @var string
	 */
	public $plugin_file = '';
	/**
	 * Class constuctor function
	 */
	public function __construct() {
		$this->plugin_path = plugin_dir_path( __FILE__ );
		$this->plugin_file = __FILE__;
		add_filter( 'views_plugins',					array( $this, 'views' ) );
		add_filter( 'show_advanced_plugins',	array( $this, 'plugins' ), 10, 2 );
		add_filter( 'activate_plugin',				array( $this, 'activation' ), 10, 2 );
		add_filter( 'deactivate_plugin',			array( $this, 'deactivation' ), 10, 2 );
		add_filter( 'plugin_action_links',		array( $this, 'actions' ) );
		add_action( 'wp_version_check',				array( $this, 'updates' ) );
	}
	/**
	 * Load the library if needed
	 *
	 * @param  string $library Library slug.
	 * @return bool            SUccess/fail
	 */
	public function require_library( $library ) {
		$file = WP_CONTENT_DIR . "/libraries/{$library}/{$library}.php";
		if ( ! file_exists( $file ) ) {
			return false;
		}
		require_once( $file );
		return true;
	}
	/**
	 * Display Libraries View in Plugins page.
	 *
	 * @param  array $views List of Plugin Views.
	 * @return array        List of Plugin Views.
	 */
	public function views( $views ) {
		global $status, $totals;
		$id = 'tinylibraries';
		$count = $totals[ $id ];
		if ( 0 === $count ) {
			return $views;
		}
		$current = $id === $status ? ' class="current"' : '';
		$views[ $id ]  = '<a href="plugins.php?plugin_status=' . $id . '"' . $current . '>';
		$views[ $id ] .= __( 'Libraries', 'tinylibraries' );
		$views[ $id ] .= ' <span class="count">(' . $count . ')</span></a>' . PHP_EOL;
		return $views;
	}
	/**
	 * Display a list of Libraries in Plugins page.
	 *
	 * @param  bool   $bool Should advanced plugins be displayed.
	 * @param  string $type Type of advanced plugin.
	 * @return bool         Should advanced plugins be displayed.
	 */
	public function plugins( $bool, $type ) {
		global $plugins, $status, $totals;
		if ( 'dropins' !== $type ) {
			return $bool;
		}
		$data = $this->_get_libraries();
		foreach ( $data as $library => $file ) {
			$data[ $library ] = $this->_get_library_info( $file );
		}
		// @codingStandardsIgnoreStart - I know accesing WP Globals is bad, but I need this hack, because we do not have a proper filter.
		$plugins['tinylibraries'] = $data;
		$totals['tinylibraries'] = count( $data );
		if ( isset( $_REQUEST['plugin_status'] ) && ( 'tinylibraries' === $_REQUEST['plugin_status'] ) ) {
			$status = 'tinylibraries';
		}
		// @codingStandardsIgnoreEnd
		return $bool;
	}
	/**
	 * Check if plugin requires Libraries before activation.
	 *
	 * @param  string $plugin       Plugin path.
	 * @return bool               	Success/fail.
	 */
	public function activation( $plugin ) {
		$libraries = $this->_get_required_libraries( $plugin );
		if ( ! is_array( $libraries ) ) {
			return false;
		}
		foreach ( $libraries as $library ) {
			$this->_install( $library );
		}
		return true;
	}
	/**
	 * Remove unused libraries on plugin deactivation
	 *
	 * @param  string $this_plugin  plugin that is being deactivated.
	 * @return void
	 */
	public function deactivation( $this_plugin ) {
		$plugins = get_option( 'active_plugins' );
		$counts = array();
		foreach ( $plugins as $plugin ) {
			$libraries = $this->_get_required_libraries( $plugin );
			if ( ! $libraries ) {
				continue;
			}
			foreach ( $libraries as $library ) {
				if ( ! isset( $counts[ $library ] ) ) {
					$counts[ $library ] = 0;
				}
				++$counts[ $library ];
			}
		}
		$libraries = $this->_get_required_libraries( $this_plugin );
		foreach ( $libraries as $library ) {
			if ( 1 < $counts[ $library ] ) {
				continue;
			}
			$this->_delete( $library );
		}
	}
	/**
	 * Check for updates
	 *
	 * @return void
	 */
	public function updates() {
		$libraries = $this->_get_libraries();
		foreach ( $libraries as $library => $library_file ) {
			$current_version = $this->_get_library_info( $library_file );
			$current_version = $current_version['Version'];
			$latest_version = $this->_get_library_data( $library );
			$latest_version = $latest_version['version'];
			if ( -1 === version_compare( $current_version, $latest_version ) ) {
				$this->_update( $library );
			}
		}
	}
	/**
	 * Hide action links for Libraries.
	 *
	 * @param  array $actions Action links.
	 * @return array          Action links.
	 */
	public function actions( $actions ) {
		global $status;
		if ( 'tinylibraries' !== $status ) {
			return $actions;
		}
		return array();
	}
	/**
	 * Update a library
	 *
	 * @param  string $library Library slug.
	 * @return void
	 */
	private function _update( $library ) {
		$this->_delete( $library );
		$this->_install( $library );
	}
	/**
	 * Install a Library.
	 *
	 * @param  string $library 	Library slug.
	 * @return bool							Success/fail.
	 */
	private function _install( $library ) {
		global $wp_filesystem;
		$data = $this->_get_library_data( $library );
		if ( ! $data ) {
			return false;
		}
		$dir = WP_CONTENT_DIR . "/libraries/{$library}/";
		if ( file_exists( $dir ) ) {
			return false;
		}
		$result = download_url( $data['download'] );
		if ( ! $wp_filesystem ) {
			WP_Filesystem();
		}
		$unzip = unzip_file( $result, $dir );
		if ( is_wp_error( $unzip ) ) {
			return false;
		}
		$dirlist = $wp_filesystem->dirlist( $dir );
		$dirlist = array_keys( $dirlist );
		$library_dir = array_pop( $dirlist );
		$main_file = "{$dir}/{$library}.php";
		$content  = '<?php' . PHP_EOL;
		$content .= '/*' . PHP_EOL;
		$content .= "Library Name: {$data['name']}" . PHP_EOL;
		$content .= "Description: {$data['description']}" . PHP_EOL;
		$content .= "Version: {$data['version']}" . PHP_EOL;
		$content .= "Class: {$data['main_class']}" . PHP_EOL;
		$content .= "File: {$data['main_file']}" . PHP_EOL;
		$content .= "Author: {$data['author']}" . PHP_EOL;
		$content .= "Author URI: {$data['author_uri']}" . PHP_EOL;
		$content .= "Library URI: {$data['uri']}" . PHP_EOL;
		$content .= '*/' . PHP_EOL;
		$content .= "if ( !class_exists( '{$data['main_class']}' ) ) {" . PHP_EOL;
		$content .= "  require_once( '{$dir}{$library_dir}/{$data['main_file']}');" . PHP_EOL;
		$content .= '}' . PHP_EOL;
		$wp_filesystem->put_contents( $main_file, $content );
		return true;
	}
	/**
	 * Delete library
	 *
	 * @param  string $library Library slug.
	 * @return bool						 Success/fail
	 */
	private function _delete( $library ) {
		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			WP_Filesystem();
		}
		$libdir = WP_CONTENT_DIR . '/libraries/' . $library;
		return $wp_filesystem->rmdir( $libdir, true );
	}
	/**
	 * Get list of requested Libraries
	 *
	 * @param  strng $plugin Plugin path.
	 * @return array         List of requested libraries.
	 */
	private function _get_required_libraries( $plugin ) {
		$default_headers = array(
			'Name' => 'Plugin Name',
			'Libraries'	=> 'Libraries',
		);
		$data = get_file_data( WP_PLUGIN_DIR . '/' . $plugin, $default_headers, 'plugin' );
		$libraries = $data['Libraries'];
		$libraries = explode( ',', $libraries );
		$none = array(
			0 => '',
		);
		if ( $none === $libraries ) {
			$libraries = false;
		}
		return $libraries;
	}
	/**
	 * Get list of installed Libraries.
	 *
	 * @return array 	List of installed libraries.
	 */
	private function _get_libraries() {
		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			WP_Filesystem();
		}
		$dir = WP_CONTENT_DIR . '/libraries/';
		$dirlist = $wp_filesystem->dirlist( $dir );
		$libraries = array();
		foreach ( $dirlist as $library => $library_data ) {
			$libraries[ $library ] = $dir . $library . '/' . $library . '.php';
		}
		return $libraries;
	}
	/**
	 * Get current Library information.
	 *
	 * @param  string $file Library init file.
	 * @return array 				Library info.
	 */
	private function _get_library_info( $file ) {
		$default_headers = array(
			'Name'				=> 'Library Name',
			'Description'	=> 'Description',
			'Version'			=> 'Version',
			'Author'			=> 'Author',
			'AuthorURI'		=> 'Author URI',
			'PluginURI'		=> 'Library URI',
			'TextDomain'	=> 'Text Domain',
		);
		$data = get_file_data( $file, $default_headers, 'plugin' );
		return $data;
	}
	/**
	 * Get information about available Libraries.
	 *
	 * @param  string $library Library slug.
	 * @return mixed 					 array of library info or false if library not found.
	 */
	private function _get_library_data( $library ) {
		$url = "https://api.aru.lt/json/tinylibraries/v1/library/{$library}";
		$data = wp_remote_get( $url );
		if ( is_wp_error( $data ) ) {
			return false;
		}
		$data = $data['body'];
		$data = json_decode( $data, true );
		return $data;
	}
}
