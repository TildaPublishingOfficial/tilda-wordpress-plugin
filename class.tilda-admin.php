<?php
/*
 * User: Michael Akimov <michael@island-future.ru>
 * Date: 2016-02-05
 */

class Tilda_Admin {

	private static $initiated = false;
	private static $libs = [ 'curl_init', 'timezonedb' ];
	private static $log_time = null;
	public static $ts_start_plugin = null;
	public static $global_message = '';

	const OPTION_PROJECTS = 'tilda_projects';
	const OPTION_PAGES = 'tilda_pages';
	const OPTION_OPTIONS = 'tilda_options';
	const OPTION_KEYS = 'tilda_options_keys';
	const OPTION_MAPS = 'tilda_maps';
	const MAP_KEY_PROJECTS = 'projects';
	const MAP_PROJECT_PAGES = 'pages';
	const MAP_PAGE_POSTS = 'posts';

	const MAX_ALLOWED_KEY_PAIRS = 5;

	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

	public static function init_hooks() {
		if ( ! self::$ts_start_plugin ) {
			self::$ts_start_plugin = time();
		}

		self::$initiated = true;

		add_action( 'admin_init', [ 'Tilda_Admin', 'admin_init' ] );
		add_action( 'admin_menu', [ 'Tilda_Admin', 'admin_menu' ], 5 );
		add_action( 'add_meta_boxes', [ 'Tilda_Admin', 'add_meta_box' ], 5 );
		add_action( 'admin_enqueue_scripts', [ 'Tilda_Admin', 'admin_enqueue_scripts' ] );
		add_action( 'save_post', [ 'Tilda_Admin', 'save_tilda_data' ], 10 );

		add_action( 'edit_form_after_title', [ 'Tilda_Admin', 'edit_form_after_title' ] );

		add_action( 'wp_ajax_tilda_admin_sync', [ 'Tilda_Admin', 'ajax_sync' ] );
		add_action( 'wp_ajax_tilda_admin_export_file', [ 'Tilda_Admin', 'ajax_export_file' ] );
		add_action( 'wp_ajax_tilda_admin_switcher_status', [ 'Tilda_Admin', 'ajax_switcher_status' ] );

		/* 0.2.32 */
		add_action( 'wp_ajax_tilda_admin_update_common_settings', [ 'Tilda_Admin', 'ajax_update_common_settings' ] );
		add_action( 'wp_ajax_add_new_key', [ 'Tilda_Admin', 'ajax_add_new_key' ] );
		add_action( 'wp_ajax_update_key', [ 'Tilda_Admin', 'ajax_update_key' ] );
		add_action( 'wp_ajax_delete_key', [ 'Tilda_Admin', 'ajax_delete_key' ] );
		add_action( 'wp_ajax_refresh_key', [ 'Tilda_Admin', 'ajax_refresh_key' ] );
		add_action( 'wp_ajax_get_keys', [ 'Tilda_Admin', 'ajax_get_keys' ] );
		add_action( 'wp_ajax_get_projects', [ 'Tilda_Admin', 'ajax_get_projects' ] );
		add_action( 'wp_ajax_update_project', [ 'Tilda_Admin', 'ajax_update_project' ] );
	}

	/**
	 * Run options migration process for upgrading 0.2.31 => 0.2.32
	 */
	public static function migrateOptions() {
		$current_options = get_option( Tilda_Admin::OPTION_OPTIONS );
		$old_key_id      = $current_options['public_key'] . $current_options['secret_key'];
		$keys            = Tilda::get_local_keys();

		$store_html_only = true;
		if ( isset( $current_options['type_stored'] ) ) {
			if ( $current_options['type_stored'] === 'post' ) {
				$store_html_only = false;
			}
		}

		$apply_css_in_list = true;
		if ( isset( $current_options['acceptcssinlist'] ) ) {
			if ( $current_options['acceptcssinlist'] === 'no' ) {
				$apply_css_in_list = false;
			}
		}

		$keys[ $old_key_id ] = [
			'id'                => $old_key_id,
			'public_key'        => $current_options['public_key'],
			'secret_key'        => $current_options['secret_key'],
			'store_html_only'   => $store_html_only,
			'apply_css_in_list' => $apply_css_in_list,
		];
		//keys are ready to save

		$projects         = Tilda::get_local_projects();
		$pages            = Tilda::get_local_pages();
		$key_project_map  = Tilda::get_local_map( Tilda_Admin::MAP_KEY_PROJECTS );
		$project_page_map = Tilda::get_local_map( Tilda_Admin::MAP_PROJECT_PAGES );
		$all_project_ids  = [];
		foreach ( $projects as $project_id => $project ) {
			$projects[ $project_id ]->enabled = true;
			$all_project_ids[]                = $project->id;
			$project_pages                    = $project->pages;
			$page_ids                         = [];
			foreach ( $project_pages as $page ) {
				$page_ids[]         = $page->id;
				$pages[ $page->id ] = $page;
			}
			$project_page_map[ $project->id ] = array_unique( $page_ids );
		}
		$key_project_map[ $old_key_id ] = array_unique( $all_project_ids );
		//projects, pages, map_key_project, map_project_page are ready to save

		$page_post_map = Tilda::get_local_map( Tilda_Admin::MAP_PAGE_POSTS );
		$old_maps      = get_option( 'tilda_maps' );
		if ( ! empty( $old_maps ) ) {
			foreach ( $old_maps as $map_page_id => $map_post_id ) {
				$page_post_map[ $map_page_id ] = $map_post_id;
			}
		}

		//If everything goes fine until this, then save new data structure
		Tilda_Admin::update_keys( $keys );
		Tilda_Admin::update_local_projects( $projects );
		Tilda_Admin::update_local_pages( $pages );

		$maps                                   = Tilda::get_local_maps();
		$maps[ Tilda_Admin::MAP_KEY_PROJECTS ]  = $key_project_map;
		$maps[ Tilda_Admin::MAP_PROJECT_PAGES ] = $project_page_map;
		$maps[ Tilda_Admin::MAP_PAGE_POSTS ]    = $page_post_map;
		Tilda_Admin::update_local_maps( $maps );

		//If everything goes fine until this, then delete old data
		unset(
			$current_options['type_stored'],
			$current_options['acceptcssinlist'],
			$current_options['public_key'],
			$current_options['secret_key']
		);
		update_option( Tilda_Admin::OPTION_OPTIONS, $current_options );
		delete_option( 'tilda_map_pages' );
	}

	public static function edit_form_after_title() {
		global $post, $wp_meta_boxes;
		do_meta_boxes( get_current_screen(), 'advanced', $post );
		unset( $wp_meta_boxes[ get_post_type( $post ) ]['advanced'] );
	}

	public static function admin_init() {
		register_setting(
			static::OPTION_KEYS,
			static::OPTION_KEYS,
			[ 'Tilda_Admin', 'keys_sanitize' ]
		);

		register_setting(
			static::OPTION_PAGES,
			static::OPTION_PAGES,
			[ 'Tilda_Admin', 'pages_validate' ]
		);

		register_setting(
			static::OPTION_MAPS,
			static::OPTION_MAPS,
			[ 'Tilda_Admin', 'maps_validate' ]
		);

		register_setting(
			static::OPTION_OPTIONS,
			static::OPTION_OPTIONS,
			[ 'Tilda_Admin', 'options_validate' ]
		);

		add_settings_section(
			'tilda_keys',
			'',
			[ 'Tilda_Admin', 'custom_callback' ],
			'tilda-config'
		);
	}

	public static function custom_callback() {
		echo '';
	}

	public static function admin_menu() {
		self::load_menu();
	}

	public static function load_menu() {
		add_submenu_page(
			'options-general.php',
			'Tilda Publishing',
			'Tilda Publishing',
			'manage_options',
			'tilda-config',
			[ 'Tilda_Admin', 'display_configuration_page' ]
		);
	}

	public static function add_meta_box() {
		$post = get_post();
		$data = get_post_meta( $post->ID, '_tilda', true );

		$options = get_option( Tilda_Admin::OPTION_OPTIONS );
		$screens = ( isset( $options['enabledposttypes'] ) ) ? $options['enabledposttypes'] : [ 'post', 'page' ];

		foreach ( $screens as $screen ) {

			if ( ! isset( $data['status'] ) || $data['status'] != 'on' ) {
				add_meta_box(
					'tilda_switcher',
					'Tilda Publishing',
					[ 'Tilda_Admin', 'switcher_callback' ],
					$screen,
					'advanced',
					'high'
				);
			}
			if ( isset( $data['status'] ) && $data['status'] == 'on' ) {
				add_meta_box(
					'tilda_pages_list',
					'Tilda Publishing',
					[ 'Tilda_Admin', 'pages_list_callback' ],
					$screen,
					'advanced',
					'high'
				);
			}
		}
	}

	public static function pages_list_callback( $post ) {
		$data = get_post_meta( $post->ID, '_tilda', true );
		//$page_id = isset($data['page_id']) ? $data['page_id'] : false;
		//$project_id = isset($data['project_id']) ? $data['project_id'] : false;

		if ( isset( $data['update_data'] ) && $data['update_data'] == 'update_data' ) {
			/* обновляем список проектов и страниц */
			self::initialize();
			unset( $data['update_data'] );
			update_post_meta( $post->ID, '_tilda', $data );
		}

		$projects_list = Tilda::get_local_projects();
		if ( ! $projects_list ) {
			Tilda::$errors->add( 'refresh', __( 'Refresh pages list', 'tilda' ) );
		}

		self::view(
			'pages_meta_box',
			[ 'projects_list' => $projects_list, 'data' => $data ]
		);
	}

	public static function save_tilda_data( $postID ) {
		if ( ! isset( $_POST['tilda'] ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $postID ) ) {
			return;
		}

		check_admin_referer( 'tilda_switcher', 'tilda_nonce' );

		$data = get_post_meta( $postID, '_tilda', true );
		if ( ! is_array( $data ) ) {
			$data = [];
		}
		foreach ( $_POST['tilda'] as $key => $val ) {
			$data[ sanitize_key( $key ) ] = esc_html( $val );
		}

		update_post_meta( $postID, '_tilda', $data );
	}

	public static function admin_enqueue_scripts( $hook ) {
		wp_register_style( 'tilda_css', TILDA_PLUGIN_URL . 'css/styles.css', [], '3' );
		wp_enqueue_style( 'tilda_css' );

		//configuration.php page
		if ( 'settings_page_tilda-config' === $hook ) {
			wp_register_script( 'tilda_configuration_js', TILDA_PLUGIN_URL . 'js/configuration.js', [
				'jquery',
				'jquery-ui-tabs'
			], '8', true );
			wp_localize_script( 'tilda_configuration_js', 'tilda_localize', Tilda_Admin::get_localization_array() );
			wp_enqueue_script( 'tilda_configuration_js' );
		}

		if ( 'post.php' != $hook && 'post-new.php' != $hook ) {
			return;
		}

		wp_enqueue_style( 'jquery-ui-tabs', TILDA_PLUGIN_URL . 'css/jquery-ui-tabs.css' );

		wp_register_script( 'tilda_js', TILDA_PLUGIN_URL . 'js/plugin.js', [ 'jquery', 'jquery-ui-tabs' ], '', true );
		wp_localize_script( 'tilda_js', 'tilda_localize', Tilda_Admin::get_localization_array() );
		wp_enqueue_script( 'tilda_js' );
	}


	/**
	 * Create localization dictionary from .po file
	 * and put it to wp_localize_script()
	 * to translate html generated by js script
	 *
	 * @param null $locale
	 *
	 * @return array
	 */
	public static function get_localization_array( $locale = null ) {
		$locale = ( empty( $locale ) ) ? get_locale() : $locale;
		$mo     = new MO;
		$mofile = dirname( __FILE__ ) . '/languages/tilda-' . $locale . '.mo';
		if ( ! file_exists( $mofile ) ) {
			$mofile = dirname( __FILE__ ) . '/languages/tilda-en_US.mo';
		}
		$mo->import_from_file( $mofile );

		$localization = [];
		foreach ( $mo->entries as $entry ) {
			$localization[ $entry->singular ] = $entry->translations[0];
		}

		return $localization;
	}

	/**
	 * Refetch projects and pages from Tilda (for all available keys)
	 */
	public static function initialize() {
		$keys = Tilda::get_local_keys();

		$success_project_ids = [];

		foreach ( $keys as $key_id => $key ) {
			$projects = Tilda::get_projects( $key['public_key'], $key['secret_key'] );

			if ( is_wp_error( $projects ) ) {
				continue;
			}

			foreach ( $projects as $project ) {
				$updated_project = Tilda_Admin::update_project( $project->id, $key['public_key'], $key['secret_key'] );

				if ( is_wp_error( $updated_project ) ) {
					continue;
				}

				$updated_pages = Tilda_Admin::update_pages( $project->id, $key['public_key'], $key['secret_key'] );

				$success_project_ids[] = $project->id;
			}
		}

		if ( count( $success_project_ids ) <= 0 ) {
			Tilda::$errors->add( 'empty_project_list', __( 'Projects list is empty', 'tilda' ) );
		}
	}

	private static function scandir( $dir ) {
		$list = scandir( $dir );

		return array_values( $list );
	}

	private static function clear_dir( $dir ) {
		$list = self::scandir( $dir );

		foreach ( $list as $file ) {
			if ( $file != '.' && $file != '..' ) {
				if ( is_dir( $dir . $file ) ) {
					self::clear_dir( $dir . $file . '/' );
					rmdir( $dir . $file );
				} else {
					unlink( $dir . $file );
				}
			}
		}
	}

	/**
	 * Save tilda keys to the DB
	 * Sanitizing will be made automatically as it applied on hook at register_setting()
	 *
	 * @param $keys
	 */
	public static function update_keys( $keys ) {
		update_option( Tilda_Admin::OPTION_KEYS, $keys );
	}

	/**
	 * Delete tilda key from DB by id
	 * Search for key=>project=>page relations and delete it also
	 *
	 * @param $key_id
	 */
	public static function delete_key( $key_id ) {
		$keys = Tilda::get_local_keys();
		unset( $keys[ $key_id ] );
		Tilda_Admin::update_keys( $keys );

		$maps = Tilda::get_local_maps();

		if ( empty( $keys ) ) {
			$maps[ Tilda_Admin::MAP_KEY_PROJECTS ] = [];
			Tilda_Admin::update_local_projects( [] );
			$maps[ Tilda_Admin::MAP_PROJECT_PAGES ] = [];
			Tilda_Admin::update_local_pages( [] );
		} else {
			$project_ids_to_delete = [];
			if ( isset( $maps[ Tilda_Admin::MAP_KEY_PROJECTS ] ) ) {
				if ( isset( $maps[ Tilda_Admin::MAP_KEY_PROJECTS ][ $key_id ] ) ) {
					$project_ids_to_delete = $maps[ Tilda_Admin::MAP_KEY_PROJECTS ][ $key_id ];
					unset( $maps[ Tilda_Admin::MAP_KEY_PROJECTS ][ $key_id ] );
				}
			}

			$page_ids_to_delete = [];
			if ( isset( $maps[ Tilda_Admin::MAP_PROJECT_PAGES ] ) ) {
				foreach ( $project_ids_to_delete as $project_id ) {
					if ( isset( $maps[ Tilda_Admin::MAP_PROJECT_PAGES ][ $project_id ] ) ) {
						$page_ids_to_delete = array_merge(
							$page_ids_to_delete,
							$maps[ Tilda_Admin::MAP_PROJECT_PAGES ][ $project_id ]
						);
						unset( $maps[ Tilda_Admin::MAP_PROJECT_PAGES ][ $project_id ] );
					}
				}
			}

			$projects = Tilda::get_local_projects();
			$projects = array_diff_key( $projects, array_flip( $project_ids_to_delete ) );
			Tilda_Admin::update_local_projects( $projects );

			$pages = Tilda::get_local_pages();
			foreach ( $pages as $project_id => $project_pages ) {
				if ( in_array( $project_id, $project_ids_to_delete ) ) {
					unset( $pages[ $project_id ] );
				}
			}
			Tilda_Admin::update_local_pages( $pages );
		}

		update_option( Tilda_Admin::OPTION_MAPS, $maps );
	}

	/**
	 * Handle request to wp-ajax.php with action: add_new_key
	 */
	public static function ajax_add_new_key() {
		if ( ! current_user_can( 'level_7' ) ) {
			wp_die( '<p>' . __( 'You need a higher level of permission.' ) . '<p>', 403 );
		}

		if ( ! wp_verify_nonce( $_POST['t_nonce'], 't_add_new_key' ) ) {
			wp_die( '<p>' . __( 'Invalid request' ) . '<p>', 403 );
		}

		$request  = Tilda_Admin::options_sanitize( $_POST );
		$defaults = [
			'store_html_only'   => true,
			'apply_css_in_list' => true,
		];
		$request  = array_merge( $defaults, $request );

		$keys = Tilda::get_local_keys();

		if ( count( $keys ) >= Tilda_Admin::MAX_ALLOWED_KEY_PAIRS ) {
			wp_send_json_error( __( 'Maximum number of keys is' ) . ' ' . Tilda_Admin::MAX_ALLOWED_KEY_PAIRS, 403 );
		}

		$id = $request['public_key'] . $request['secret_key'];

		if ( empty( $id ) ) {
			wp_send_json_error( __( 'Keys could not be empty' ), 422 );
		}

		if ( isset( $keys[ $id ] ) ) {
			wp_send_json_error( __( 'Key already exist' ), 422 );
		}

		//Get project list from tilda to check that key is valid
		$projects = Tilda::get_projects( $request['public_key'], $request['secret_key'] );
		if ( is_wp_error( $projects ) ) {
			wp_send_json_error( $projects->get_error_message(), 422 );
		}

		$project_ids = [];
		foreach ( $projects as $project ) {
			Tilda_Admin::update_project( $project->id, $request['public_key'], $request['secret_key'] );
			Tilda_Admin::update_pages( $project->id, $request['public_key'], $request['secret_key'] );
			$project_ids[] = $project->id;
		}
		Tilda_Admin::update_local_map( Tilda_Admin::MAP_KEY_PROJECTS, $id, $project_ids );

		$keys[ $id ] = [
			'id'                => $id,
			'public_key'        => $request['public_key'],
			'secret_key'        => $request['secret_key'],
			'store_html_only'   => $request['store_html_only'],
			'apply_css_in_list' => $request['apply_css_in_list'],
		];

		Tilda_Admin::update_keys( $keys );

		wp_send_json( $keys, 200 );
	}

	/**
	 * Handle request to wp-ajax.php with action: delete_key
	 * Delete key and all assigned projects
	 */
	public static function ajax_delete_key() {
		if ( ! current_user_can( 'level_7' ) ) {
			wp_die( '<p>' . __( 'You need a higher level of permission.' ) . '<p>', 403 );
		}

		if ( ! wp_verify_nonce( $_GET['t_nonce'], 't_delete_key' ) ) {
			wp_die( '<p>' . __( 'Invalid request' ) . '<p>', 403 );
		}

		$request = Tilda_Admin::options_sanitize( $_GET );

		if ( ! isset( $request['id'] ) ) {
			wp_send_json_error( 'id not provided', 500 );
		}

		Tilda_Admin::delete_key( $request['id'] );

		$keys = Tilda::get_local_keys();

		wp_send_json( $keys, 200 );
	}

	/**
	 * Handle request to wp-ajax.php with action: update_key
	 * Update minor parameters for dedicated key
	 */
	public static function ajax_update_key() {
		if ( ! current_user_can( 'level_7' ) ) {
			wp_die( '<p>' . __( 'You need a higher level of permission.' ) . '<p>', 403 );
		}

		if ( ! wp_verify_nonce( $_GET['t_nonce'], 't_update_key' ) ) {
			wp_die( '<p>' . __( 'Invalid request' ) . '<p>', 403 );
		}

		$request = Tilda_Admin::options_sanitize( $_GET );

		if ( ! isset( $request['id'] ) ) {
			wp_send_json_error( 'id not provided', 422 );
		}

		$keys = Tilda::get_local_keys();

		//Only these params allowed to be updated
		foreach ( [ 'store_html_only', 'apply_css_in_list' ] as $param ) {
			if ( isset( $request[ $param ] ) ) {
				$keys[ $request['id'] ][ $param ] = $request[ $param ];
			}
		}

		Tilda_Admin::update_keys( $keys );

		wp_send_json( $keys, 200 );
	}

	/**
	 * Handle request to wp-ajax.php with action: refresh_key
	 * Refetch projects and pages from API and save it to the DB
	 */
	public static function ajax_refresh_key() {
		if ( ! current_user_can( 'level_7' ) ) {
			wp_die( '<p>' . __( 'You need a higher level of permission.' ) . '<p>', 403 );
		}

		if ( ! wp_verify_nonce( $_GET['t_nonce'], 't_refresh_key' ) ) {
			wp_die( '<p>' . __( 'Invalid request' ) . '<p>', 403 );
		}

		$request = Tilda_Admin::options_sanitize( $_GET );

		if ( empty( $request['id'] ) ) {
			wp_send_json_error( __( 'Id not specified' ), 422 );
		}

		$keys = Tilda::get_local_keys();
		if ( ! isset( $keys[ $request['id'] ] ) ) {
			wp_send_json_error( __( 'Wrong key specified' ), 422 );
		}
		$key = $keys[ $request['id'] ];

		//Get project list from tilda to check that key is valid
		$projects = Tilda::get_projects( $key['public_key'], $key['secret_key'] );
		if ( is_wp_error( $projects ) ) {
			wp_send_json_error( $projects->get_error_message(), 422 );
		}

		$project_ids = [];
		foreach ( $projects as $project ) {
			Tilda_Admin::update_project( $project->id, $key['public_key'], $key['secret_key'] );
			Tilda_Admin::update_pages( $project->id, $key['public_key'], $key['secret_key'] );
			$project_ids[] = $project->id;
		}
		Tilda_Admin::update_local_map( Tilda_Admin::MAP_KEY_PROJECTS, $request['id'], $project_ids );

		wp_send_json( $keys, 200 );
	}

	/**
	 * Handle request to wp-ajax.php with action: get_projects
	 */
	public static function ajax_get_projects() {
		if ( ! current_user_can( 'level_7' ) ) {
			wp_die( '<p>' . __( 'You need a higher level of permission.' ) . '<p>', 403 );
		}

		if ( ! wp_verify_nonce( $_GET['t_nonce'], 't_get_projects' ) ) {
			wp_die( '<p>' . __( 'Invalid request' ) . '<p>', 403 );
		}

		$projects = Tilda::get_local_projects();
		if ( empty( $projects ) ) {
			$projects = [];
		}
		wp_send_json( $projects, 200 );
	}

	/**
	 * Handle request to wp-ajax.php with action: update_project
	 */
	public static function ajax_update_project() {
		if ( ! current_user_can( 'level_7' ) ) {
			wp_die( '<p>' . __( 'You need a higher level of permission.' ) . '<p>', 403 );
		}

		if ( ! wp_verify_nonce( $_POST['t_nonce'], 't_update_project' ) ) {
			wp_die( '<p>' . __( 'Invalid request' ) . '<p>', 403 );
		}

		$request = Tilda_Admin::project_sanitize( $_POST );

		if ( ! isset( $request['id'] ) ) {
			wp_send_json_error( __( 'Id not specified' ), 422 );
		}

		if ( ! isset( $request['enabled'] ) ) {
			wp_send_json_error( __( 'Enable status not specified' ), 422 );
		}

		$project          = Tilda::get_local_project( $request['id'] );
		$project->enabled = $request['enabled'];

		Tilda_Admin::update_local_project( $project );

		wp_send_json( $project, 200 );
	}

	/**
	 * Handle request to wp-ajax.php with action: get_keys
	 */
	public static function ajax_get_keys() {
		if ( ! current_user_can( 'level_7' ) ) {
			wp_die( '<p>' . __( 'You need a higher level of permission.' ) . '<p>', 403 );
		}

		if ( ! wp_verify_nonce( $_POST['t_nonce'], 't_get_keys' ) ) {
			wp_die( '<p>' . __( 'Invalid request' ) . '<p>', 403 );
		}

		wp_send_json( Tilda::get_local_keys(), 200 );
	}

	/**
	 * Handle request to wp-ajax.php with action: update_common_settings
	 */
	public static function ajax_update_common_settings() {
		if ( ! current_user_can( 'level_7' ) ) {
			wp_die( '<p>' . __( 'You need a higher level of permission.' ) . '<p>', 403 );
		}

		if ( ! wp_verify_nonce( $_POST['t_nonce'], 't_update_common_settings' ) ) {
			wp_die( '<p>' . __( 'Invalid request' ) . '<p>', 403 );
		}

		$options = get_option( Tilda_Admin::OPTION_OPTIONS );
		$request = Tilda_Admin::options_sanitize( $_POST );

		foreach ( $request as $option_name => $option_value ) {
			if ( ! isset( $options[ $option_name ] ) ) {
				continue;
			}

			$options[ $option_name ] = $option_value;
		}

		update_option( Tilda_Admin::OPTION_OPTIONS, $options );

		wp_send_json( $options, 200 );
	}

	public static function options_validate( $input ) {
		if ( empty( $input['storageforfiles'] ) || $input['storageforfiles'] != 'cdn' ) {
			$input['storageforfiles'] = 'local';
		} else {
			$input['storageforfiles'] = 'cdn';
		}

		if ( empty( $input['enabledposttypes'] ) || ! is_array( $input['enabledposttypes'] ) ) {
			$input['enabledposttypes'] = [ 'post', 'page' ];
		}

		foreach ( $input['enabledposttypes'] as $key => $type ) {
			$input['enabledposttypes'][ $key ] = preg_replace( '/[^a-zA-Z0-9\-_]+/iu', '', $type );
			if ( empty( $input['enabledposttypes'][ $key ] ) ) {
				unset( $input['enabledposttypes'][ $key ] );
			}
		}

		if ( isset( $input['secret_key'] ) ) {
			$input['secret_key'] = preg_replace( '/[^a-zA-Z0-9]+/iu', '', $input['secret_key'] );
		}

		if ( isset( $input['public_key'] ) ) {
			$input['public_key'] = preg_replace( '/[^a-zA-Z0-9]+/iu', '', $input['public_key'] );
		}

		return $input;
	}

	public static function pages_validate( $array ) {
		//TODO validate $pages array before saving to DB
		return $array;
	}

	public static function maps_validate( $array ) {
		//TODO validate $maps array before saving to DB
		return $array;
	}

	/**
	 * Sanitize tilda_options_keys array before saving it to DB
	 * Used by register_setting() in admin_init()
	 *
	 * @param $pairs
	 *
	 * @return mixed
	 */
	public static function keys_sanitize( $pairs ) {
		foreach ( $pairs as $key => $value ) {
			$pairs[ $key ] = static::options_sanitize( $value );
		}

		return $pairs;
	}


	/**
	 * Remove unwanted symbols/values from project's data
	 *
	 * @param $input
	 *
	 * @return array
	 */
	public static function project_sanitize( $input ) {
		//Booleans
		foreach ( [ 'enabled' ] as $key ) {
			if ( isset( $input[ $key ] ) ) {
				switch ( $input[ $key ] ) {
					case 'true':
						$input[ $key ] = true;
						break;
					case 'false':
						$input[ $key ] = false;
						break;
					default:
						$input[ $key ] = boolval( $input[ $key ] );
				}
			}
		}

		//Alfanumerics
		foreach ( [ 'id' ] as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$input[ $key ] = preg_replace( '/[^a-zA-Z0-9]+/iu', '', $input[ $key ] );
			}
		}

		return $input;
	}

	/**
	 * Remove unwanted symbols/values from array of projects
	 *
	 * @param $array
	 *
	 * @return array
	 */
	public static function projects_sanitize( $array ) {
		return array_map( function ( $element ) {
			return Tilda_Admin::project_sanitize( $element );
		}, $array );
	}

	/**
	 * Remove unwanted symbols/values from options array
	 *
	 * @param $input
	 *
	 * @return mixed
	 */
	public static function options_sanitize( $input ) {
		//Enums
		if ( isset( $input['storageforfiles'] ) ) {
			if ( ! in_array( $input['storageforfiles'], [ 'cdn', 'local' ] ) ) {
				$input['storageforfiles'] = 'cdn';
			}
		}

		//Booleans
		foreach ( [ 'store_html_only', 'apply_css_in_list' ] as $key ) {
			if ( isset( $input[ $key ] ) ) {
				switch ( $input[ $key ] ) {
					case 'true':
						$input[ $key ] = true;
						break;
					case 'false':
						$input[ $key ] = false;
						break;
					default:
						$input[ $key ] = boolval( $input[ $key ] );
				}
			}
		}

		//Alfanumerics
		foreach ( [ 'id', 'public_key', 'secret_key' ] as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$input[ $key ] = preg_replace( '/[^a-zA-Z0-9]+/iu', '', $input[ $key ] );
			}
		}

		return $input;
	}

	private static function validate_required_libs() {
		$libs = self::$libs;
		foreach ( $libs as $lib_name ) {
			if ( ! extension_loaded( $lib_name ) ) {
				Tilda::$errors->add( 'no_library', __( 'Not found library ', 'tilda' ) . $lib_name );
			}
		}
	}

	public static function display_configuration_page() {
		self::view( 'configuration' );
	}

	public static function switcher_callback( $post ) {
		$data = get_post_meta( $post->ID, '_tilda', true );
		if ( ! is_array( $data ) ) {
			$data = [];
		}
		if ( ! Tilda::verify_access() ) {
			Tilda::$errors->add( 'empty_keys', __( 'The security keys is not set', 'tilda' ) );
		}

		self::view( 'switcher_status', [ 'data' => $data ] );
	}

	public static function view( $name, array $args = [] ) {
		$args = apply_filters( 'tilda_view_arguments', $args, $name );

		foreach ( $args as $key => $val ) {
			$$key = $val;
		}

		$file = TILDA_PLUGIN_DIR . 'views/' . $name . '.php';
		include( $file );
	}

	static public function log( $message, $file = __FILE__, $line = __LINE__ ) {
		if ( self::$log_time === null ) {
			self::$log_time = date( 'Y-m-d H:i:s' );
		}
		if ( ! self::$ts_start_plugin ) {
			self::$ts_start_plugin = time();
		}
		$sec = time() - self::$ts_start_plugin;
		$f   = fopen( Tilda::get_upload_dir() . '/log.txt', 'a' );
		fwrite( $f, "[" . self::$log_time . " - $sec s] " . $message . " in [file: $file, line: $line]\n" );
		fclose( $f );
	}

	/**
	 * Метод запрашивает данные указанного проекта с Тильды и сохраняет эти данные в опции tilda_projects
	 *
	 * @param int $project_id - код проекта в Тильде
	 *
	 * @return stdClass $project обновленные данные по проекту
	 */
	public static function update_project( $project_id, $public_key = null, $secret_key = null ) {
		$project  = Tilda::get_projectinfo( $project_id, $public_key, $secret_key );
		$projects = Tilda::get_local_projects();

		if ( isset( $projects[ $project_id ] ) && $projects[ $project_id ]->enabled === false ) {
			$project->enabled = false;
		} else {
			$project->enabled = true;
		}

		$projects[ $project_id ] = $project;

		$upload_dir = Tilda::get_upload_dir() . $project->id . '/';

		if ( ! is_dir( $upload_dir ) ) {
			wp_mkdir_p( $upload_dir );
		}

		$css_path   = $upload_dir . 'css/';
		$js_path    = $upload_dir . 'js/';
		$pages_path = $upload_dir . 'pages/';

		if ( ! is_dir( $css_path ) ) {
			wp_mkdir_p( $css_path );
		}
		if ( ! is_dir( $js_path ) ) {
			wp_mkdir_p( $js_path );
		}
		if ( ! is_dir( $pages_path ) ) {
			wp_mkdir_p( $pages_path );
		}

		Tilda_Admin::update_local_projects( $projects );

		return $project;
	}

	/**
	 * Метод запрашивает данные страница для указанного проекта с Тильды и сохраняет эти данные в опции tilda_pages
	 *
	 * @param int $project_id - код проекта в Тильде
	 *
	 * @return array $local_pages
	 */
	public static function update_pages( $project_id, $public_key, $secret_key ) {
		$local_pages  = Tilda::get_local_pages();
		$server_pages = Tilda::get_pageslist( $project_id, $public_key, $secret_key );
		$new_pages    = [];
		$page_ids     = [];
		if ( $server_pages && count( $server_pages ) > 0 ) {
			foreach ( $server_pages as $page ) {
				$new_pages[ $page->id ] = $page;
				$page_ids[]             = $page->id;
			}
		}
		$local_pages[ $project_id ] = $new_pages;
		update_option( Tilda_Admin::OPTION_PAGES, $local_pages );
		Tilda_Admin::update_local_map( Tilda_Admin::MAP_PROJECT_PAGES, $project_id, $page_ids );

		return $local_pages;
	}


	/**
	 * Update page in db from array and return result array of all pages
	 *
	 * @param $page
	 *
	 * @return array
	 */
	public static function update_local_page( $page ) {
		$pages              = Tilda::get_local_pages();
		$pages[ $page->id ] = $page;
		Tilda_Admin::update_local_pages( $pages );

		return $pages;
	}

	/**
	 * @param $pages
	 */
	public static function update_local_pages( $pages ) {
		update_option( Tilda_Admin::OPTION_PAGES, $pages );
	}

	/**
	 * Update project in db from array and return result array of all projects
	 *
	 * @param $project
	 *
	 * @return false|mixed|void
	 */
	public static function update_local_project( $project ) {
		$projects                 = Tilda::get_local_projects();
		$projects[ $project->id ] = $project;
		Tilda_Admin::update_local_projects( $projects );

		return $projects;
	}

	/**
	 * @param $projects
	 */
	public static function update_local_projects( $projects ) {
		update_option( Tilda_Admin::OPTION_PROJECTS, $projects );
	}

	/**
	 * Update one map in tilda_map structure
	 * Example $type = 'projects', $map_id = $key_id, $mapped_ids = array($project1, $project2)
	 *
	 * @param $type
	 * @param $map_id
	 * @param $mapped_ids
	 *
	 * @return false|mixed|void
	 */
	public static function update_local_map( $type, $map_id, $mapped_ids ) {
		$maps = Tilda::get_local_maps();
		if ( ! isset( $maps[ $type ] ) ) {
			$maps[ $type ] = [];
		}
		$maps[ $type ][ $map_id ] = $mapped_ids;
		Tilda_Admin::update_local_maps( $maps );

		return $maps;
	}

	/**
	 * @param $maps
	 */
	public static function update_local_maps( $maps ) {
		update_option( Tilda_Admin::OPTION_MAPS, $maps );
	}

	public static function replace_outer_image_to_local( $tildapage, $export_imgpath = '' ) {
		if ( $export_imgpath > '' && substr( $export_imgpath, - 1 ) !== '/' ) {
			$export_imgpath .= '/';
		}

		$options = get_option( Tilda_Admin::OPTION_OPTIONS );

		$exportimages  = [];
		$replaceimages = [];
		$upload_path   = Tilda::get_upload_path() . $tildapage->projectid . '/pages/' . $tildapage->id . '/';

		$uniq = [];

		if ( is_array( $tildapage->images ) ) {

			foreach ( $tildapage->images as $image ) {
				if ( isset( $uniq[ $image->from ] ) ) {
					continue;
				}
				$uniq[ $image->from ] = 1;

				if ( $export_imgpath > '' ) {
					$exportimages[] = '|' . $export_imgpath . $image->to . '|i';
				} else {
					$exportimages[] = '|' . $image->to . '|i';
				}
				if ( isset( $options['storageforfiles'] ) && $options['storageforfiles'] == 'cdn' ) {
					$replaceimages[] = $image->from;
				} else {
					$replaceimages[] = $upload_path . $image->to;
				}
			}
		}
		$html = preg_replace( $exportimages, $replaceimages, $tildapage->html );
		if ( $html ) {
			$tildapage->html = $html;
		}

		return $tildapage;
	}

	/**
	 * Экспортирует HTML и список используемых файлов (картинок, стилей и скриптов) из Тильды
	 *
	 * @param integer $page_id Код страницы в Тильде
	 * @param integer $project_id Код проекта в Тильде
	 * @param integer $post_id Код страницы или поста в Wordpress
	 *
	 * @return mixed Список файлов для закачки (откуда и куда сохранить, в случае ошибки возвращает WP_Error)
	 */
	public static function export_tilda_page( $page_id, $project_id, $post_id ) {
		$key_id = Tilda::get_key_for_project_id( $project_id );
		$key    = Tilda::get_local_keys( $key_id );

		if ( ! isset( $key[ $key_id ] ) ) {
			Tilda::$errors->add( 'key_not_found', 'Cannot find key: ' . $key_id );

			return Tilda::$errors;
		}

		$key = $key[ $key_id ];

		// так как при изменении страницы мог измениться css или js, поэтому всегда запрашиваем данные проекта с Тильды
		$project = self::update_project( $project_id, $key['public_key'], $key['secret_key'] );

		if ( is_wp_error( $project ) ) {
			return $project;
		}
		$tildaoptions = get_option( Tilda_Admin::OPTION_OPTIONS );

		$tildapage = Tilda::get_pageexport( $page_id, $key['public_key'], $key['secret_key'] );

		if ( is_wp_error( $tildapage ) ) {
			return $tildapage;
		}

		$upload_path = Tilda::get_upload_path() . $project->id . '/';
		$upload_dir  = Tilda::get_upload_dir() . $project->id . '/';
		if ( ! is_dir( $upload_dir ) && ! wp_mkdir_p( $upload_dir ) ) {
			Tilda::$errors->add( 'no_directory', 'Cannot create directory: ' . $upload_dir );

			return Tilda::$errors;
		}
		if ( ! is_dir( $upload_dir . 'pages/' ) && ! wp_mkdir_p( $upload_dir . 'pages/' ) ) {
			Tilda::$errors->add( 'no_directory', 'Cannot create directory: ' . $upload_dir . 'pages/' );

			return Tilda::$errors;
		}
		if ( ! is_dir( $upload_dir . 'css/' ) && ! wp_mkdir_p( $upload_dir . 'css/' ) ) {
			Tilda::$errors->add( 'no_directory', 'Cannot create directory: ' . $upload_dir . 'css/' );

			return Tilda::$errors;
		}
		if ( ! is_dir( $upload_dir . 'js/' ) && ! wp_mkdir_p( $upload_dir . 'js/' ) ) {
			Tilda::$errors->add( 'no_directory', 'Cannot create directory: ' . $upload_dir . 'js/' );

			return Tilda::$errors;
		}

		$arDownload = [];

		// ||s|| is custom escaping symbol used to bypass '<\/script>' text from WordPress engine processing
		$tildapage->html = str_replace( '<\/script>', '<||s||script>', $tildapage->html );
		// ||n|| is custom escaping symbol for \n to bypass serialization/deserialization process
		$tildapage->html = str_replace( '\n', '||n||', $tildapage->html );

		// content of data-field-imgs-value should not be html decoded
		$isZeroGalleryFound = preg_match_all( '/data-field-imgs-value=\"([^"]*)\"/i', $tildapage->html, $matches );
		if ( $isZeroGalleryFound && ! empty( $matches[1] ) ) {
			foreach ( $matches[1] as $key => $match ) {
				$tildapage->html = str_replace( $match, "||imgsvalue-{$key}||", $tildapage->html );
			}
		}

		// find async loading js scripts and add them to the download queue
		$isAsyncJsFound = preg_match_all( '/s\.src=\"\/js\/([^"]+)/i', $tildapage->html, $asyncJsMatches );
		if ( $isAsyncJsFound && ! empty( $asyncJsMatches[1] ) ) {
			foreach ( $asyncJsMatches[1] as $key => $match ) {
				if ( substr( $match, - 3 ) === '.js' ) {
					$oDownload       = new stdClass();
					$oDownload->from = 'https://static.tildacdn.com/js/' . $match;
					$oDownload->to   = $match;
					$tildapage->js[] = $oDownload;
					$tildapage->html = str_replace(
						's.src="/js/' . $match . '"',
						's.src="' . $upload_path . 'js/' . $match . '"',
						$tildapage->html
					);
				}
			}
		}

		$tildapage->html = htmlspecialchars_decode( $tildapage->html );

		// find zero form fields and decode unicode
		if (
			preg_match_all(
				'/<textarea class="tn-atom__inputs-textarea">(.*)<\/textarea>/',
				$tildapage->html,
				$fieldsMatches
			)
			&& isset( $fieldsMatches[1] )
			&& is_array( $fieldsMatches[1] )
		) {
			foreach ( $fieldsMatches[1] as $fieldsMatch ) {
				$tildapage->html = str_replace(
					$fieldsMatch,
					json_encode( json_decode( $fieldsMatch ), JSON_UNESCAPED_UNICODE ),
					$tildapage->html
				);
			}
		}

		if ( $isZeroGalleryFound && ! empty( $matches[1] ) ) {
			foreach ( $matches[1] as $key => $match ) {
				$tildapage->html = str_replace( "||imgsvalue-{$key}||", $match, $tildapage->html );
			}
		}

		// remove all css <link> occurrences that was already added to the <header> tag
		foreach ( $tildapage->css as $css ) {
			$tildapage->html = str_replace( '<link rel="stylesheet" href="' . $css->to . '">', '', $tildapage->html );
		}

		Tilda_Admin::update_local_map( Tilda_Admin::MAP_PAGE_POSTS, $page_id, $post_id );

		$tildapage = Tilda_Admin::replace_outer_image_to_local( $tildapage, $project->export_imgpath );

		$meta = get_post_meta( $post_id, '_tilda', true );
		if ( ! is_array( $meta ) ) {
			$meta = [];
		}
		$meta['export_imgpath'] = $project->export_imgpath;
		$meta['export_csspath'] = $project->export_csspath;
		$meta['export_jspath']  = $project->export_jspath;

		$meta['page_id']    = $tildapage->id;
		$meta['project_id'] = $tildapage->projectid;
		$meta['post_id']    = $post_id;


		if ( isset( $tildapage->css ) && is_array( $tildapage->css ) ) {
			$arCSS = $tildapage->css;
		} else {
			$arCSS = $project->css;
		}
		$tildapage->css = [];
		foreach ( $arCSS as $file ) {
			$tildapage->css[] = $upload_path . 'css/' . $file->to;
			$arDownload[]     = [
				'from_url' => $file->from . '?t=' . time(),
				'to_dir'   => $upload_dir . 'css/' . $file->to,
			];
		}

		if ( isset( $tildapage->js ) && is_array( $tildapage->js ) ) {
			$arJS = $tildapage->js;
		} else {
			$arJS = $project->js;
		}
		$tildapage->js = [];
		foreach ( $arJS as $file ) {
			$tildapage->js[] = $upload_path . 'js/' . $file->to;

			$arDownload[] = [
				'from_url' => $file->from,
				'to_dir'   => $upload_dir . 'js/' . $file->to,
			];
		}

		$tildapage->html = str_replace( '$(', 'jQuery(', $tildapage->html );
		$tildapage->html = str_replace( '$.', 'jQuery.', $tildapage->html );
		$tildapage->html = str_replace( 'jQuery.cachedScript("tilda', 'jQuery.cachedScript("' . $upload_path . 'js/tilda', $tildapage->html );

		$matches = [];
		if ( preg_match_all( '/s\.src="([a-z0-9\-.]+\.min\.js)";/i', $tildapage->html, $matches ) ) {
			$checked_matches = isset( $matches[0] ) ? $matches[0] : [];
			foreach ( $checked_matches as $key => $match ) {
				if ( ! empty( $matches[1][ $key ] ) ) {
					$tildapage->html = str_replace( $match, 's.src="' . $upload_path . 'js/' . $matches[1][ $key ] . '";', $tildapage->html );
				}
			}
		}

		$matches = [];
		if ( preg_match_all( '/<script src=[\'"]([a-z0-9\-.]+\.min\.js)[\'"]/i', $tildapage->html, $matches ) ) {
			$checked_matches = isset( $matches[0] ) ? $matches[0] : [];
			foreach ( $checked_matches as $key => $match ) {
				if ( ! empty( $matches[1][ $key ] ) ) {
					$tildapage->html = str_replace( $match, '<script src="' . $upload_path . 'js/' . $matches[1][ $key ] . '"', $tildapage->html );
				}
			}
		}

		$post = get_post( $post_id );

		if ( isset( $key['store_html_only'] ) && $key['store_html_only'] === false ) {
			$post->post_content = strip_tags( $tildapage->html, '<style><script><p><br><span><img><b><i><strike><strong><em><u><h1><h2><h3><a><ul><li>' );


			while ( ( $pos = mb_strpos( $post->post_content, '<style', 0, 'UTF-8' ) ) !== false ) {
				$substring = mb_substr( $post->post_content, $pos, mb_strpos( $post->post_content, '</style>', 0, 'UTF-8' ) - $pos + 8, 'UTF-8' );
				if ( $substring > '' ) {
					$post->post_content = str_replace( $substring, '', $post->post_content );
				} else {
					break;
				}
			}

			while ( ( $pos = mb_strpos( $post->post_content, '<script', 0, 'UTF-8' ) ) !== false ) {
				$substring = mb_substr( $post->post_content, $pos, mb_strpos( $post->post_content, '</script>', 0, 'UTF-8' ) - $pos + 9, 'UTF-8' );
				if ( $substring > '' ) {
					$post->post_content = str_replace( $substring, '', $post->post_content );
				} else {
					break;
				}
			}

			$post->post_content = str_replace( "\r\n", "\n", $post->post_content );

			$tmp = str_replace( "\n\n\n\n", "\n", $post->post_content );
			if ( $tmp > '' ) {
				$tmp = str_replace( "\n\n\n", "\n", $tmp );
				if ( $tmp > '' ) {
					$tmp = str_replace( "\n\n", "\n", $tmp );
					if ( $tmp > '' ) {
						$tmp = str_replace( "\n\n", "\n", $tmp );
					}
				}
			}
			if ( $tmp > '' ) {
				$post->post_content = nl2br( $tmp );
			} else {
				$post->post_content = nl2br( $post->post_content );
			}
		} else {
			$post->post_content = __( 'Page synchronized. Edit page only on site Tilda.cc', 'tilda' ); //$tildapage->html;
		}
		wp_update_post( $post );

		$tildapage->html = str_replace('\\', '\\\\', $tildapage->html);

		$tildapage->sync_time = current_time( 'mysql' );

		$meta['current_page'] = $tildapage;
		update_post_meta( $post_id, '_tilda', $meta );

		if ( empty( $tildaoptions['storageforfiles'] ) || $tildaoptions['storageforfiles'] == 'local' ) {
			$upload_dir = Tilda::get_upload_dir() . $project->id . '/pages/' . $tildapage->id . '/';
			if ( ! is_dir( $upload_dir ) && ! mkdir( $upload_dir, 0755 ) ) {
				Tilda::$errors->add( 'no_directory', 'Cannot create directory: ' . $upload_dir );

				return Tilda::$errors;
			}

			foreach ( $tildapage->images as $file ) {
				$arDownload[] = [
					'from_url' => $file->from,
					'to_dir'   => $upload_dir . $file->to,
				];
			}

			/* скачиваем спец картинки */
			if ( isset( $tildapage->img ) && substr( $tildapage->img, 0, 4 ) == 'http' ) {
				$path  = parse_url( $tildapage->img, PHP_URL_PATH );
				$path  = explode( '/', $path );
				$fname = array_pop( $path );
				if ( $fname && ( $pos = strrpos( $fname, '.' ) ) > 0 ) {
					$ext          = substr( $fname, $pos );
					$arDownload[] = [
						'from_url' => $tildapage->img,
						'to_dir'   => $upload_dir . 'cover' . $ext,
					];
				}
			}

			if ( isset( $tildapage->featureimg ) && substr( $tildapage->featureimg, 0, 4 ) == 'http' ) {
				$path  = parse_url( $tildapage->featureimg, PHP_URL_PATH );
				$path  = explode( '/', $path );
				$fname = array_pop( $path );
				if ( $fname && ( $pos = strrpos( $fname, '.' ) ) > 0 ) {
					$ext          = substr( $fname, $pos );
					$arDownload[] = [
						'from_url' => $tildapage->featureimg,
						'to_dir'   => $upload_dir . 'feature' . $ext,
					];
				}
			}

			if ( isset( $tildapage->fb_img ) && substr( $tildapage->fb_img, 0, 4 ) == 'http' ) {
				$path  = parse_url( $tildapage->fb_img, PHP_URL_PATH );
				$path  = explode( '/', $path );
				$fname = array_pop( $path );
				if ( $fname && ( $pos = strrpos( $fname, '.' ) ) > 0 ) {
					$ext          = substr( $fname, $pos );
					$arDownload[] = [
						'from_url' => $tildapage->fb_img,
						'to_dir'   => $upload_dir . 'socnet' . $ext,
					];
				}
			}
		}

		return $arDownload;
	}

	/**
	 * Метод вызывается ajax-запросом из админки (hook)
	 *  http://example.com/wp-admin/admin-ajax.php?action=tilda_admin_sync
	 *
	 */
	public static function ajax_sync() {
		if ( ! current_user_can( 'level_7' ) ) {
			wp_die( '<p>' . __( 'You need a higher level of permission.' ) . '<p>', 403 );
		}

		if ( ! wp_verify_nonce( $_POST['t_nonce'], 't_admin_sync' ) ) {
			wp_die( '<p>' . __( 'Invalid request' ) . '<p>', 403 );
		}

		$arResult = [];
		if ( empty( $_REQUEST['page_id'] ) || empty( $_REQUEST['project_id'] ) || empty( $_REQUEST['post_id'] ) ) {
			$arResult['error'] = __( 'Bad request line. Missing parameter: projectid', 'tilda' );
			echo json_encode( $arResult );
			wp_die();
		}

		$project_id = intval( $_REQUEST['project_id'] );
		$page_id    = intval( $_REQUEST['page_id'] );
		$post_id    = intval( $_REQUEST['post_id'] );

		// запускаем экспорт
		$arDownload = self::export_tilda_page( $page_id, $project_id, $post_id );

		if ( is_wp_error( $arDownload ) ) {
			echo Tilda::json_errors();
			wp_die();
		}

		if ( ! session_id() ) {
			session_start();
		}

		$_SESSION['tildaexport'] = [
			'arDownload' => $arDownload,
			'downloaded' => 0,
			'total'      => sizeof( $arDownload ),
		];

		$arResult['total_download']   = $_SESSION['tildaexport']['total'];
		$arResult['need_download']    = $arResult['total_download'];
		$arResult['count_downloaded'] = 0;

		$arResult['page_id']    = $page_id;
		$arResult['project_id'] = $project_id;
		$arResult['post_id']    = $post_id;

		echo json_encode( $arResult );
		wp_die();
	}

	/**
	 * Метод вызывается ajax-запросом из админки
	 *  http://example.com/wp-admin/admin-ajax.php?action=tilda_admin_export_file
	 *  закачивает файлы порциями
	 *
	 */
	public static function ajax_export_file() {
		if ( ! current_user_can( 'level_7' ) ) {
			wp_die( '<p>' . __( 'You need a higher level of permission.' ) . '<p>', 403 );
		}

		if ( ! wp_verify_nonce( $_POST['t_nonce'], 't_admin_export_file' ) ) {
			wp_die( '<p>' . __( 'Invalid request' ) . '<p>', 403 );
		}

		if ( empty( self::$ts_start_plugin ) ) {
			self::$ts_start_plugin = time();
		}

		if ( ! session_id() ) {
			session_start();
		}

		$arResult = [];

		if ( empty( $_SESSION['tildaexport']['arDownload'] ) ) {
			$arResult['error'] = 'Error! cannot run session.';
			$arResult['dump']  = $_SESSION['tildaexport'];
			echo json_encode( $arResult );
			die( 0 );
		}

		$arDownload = $_SESSION['tildaexport']['arDownload'];
		$arTmp      = [];
		$downloaded = 0;
		foreach ( $arDownload as $file ) {

			if ( time() - self::$ts_start_plugin > 20 ) {
				$arTmp[] = $file;
			} else {
				if ( ! file_exists( $file['to_dir'] ) || strpos( $file['to_dir'], '/pages/' ) === false ) {
					$content = Tilda::getRemoteFile( $file['from_url'] );
					if ( is_wp_error( $content ) ) {
						echo Tilda::json_errors();
						wp_die();
					}

					/* replace  short jQuery function $(...) to jQuery(...) */
					if (
						strpos( $file['to_dir'], 'tilda-blocks-' ) !== false
						&& strpos( $file['to_dir'], '.js' ) !== false
					) {
						$content = str_replace( '$(', 'jQuery(', $content );
						$content = str_replace( '$.', 'jQuery.', $content );
					}

					$parts     = explode( '.', strtolower( $file['from_url'] ) );
					$extension = array_pop( $parts );

					if (
						in_array( $extension, [ 'jpg', 'jpeg', 'png', 'gif', 'svg', 'ico' ] )
						&& (
							strpos( $content, 'The resource could not be found.' ) !== false
							|| strpos( strtolower( $content ), 'not found.' ) !== false
						)
					) {
						$downloaded ++;
						continue;
					} elseif ( file_put_contents( $file['to_dir'], $content ) === false ) {
						Tilda::$errors->add( 'error_download', 'Cannot save file to [' . $file['to_dir'] . '].' );
						echo Tilda::json_errors();
						wp_die();
					}
				}
				$downloaded ++;
			}
		}

		$arDownload = $arTmp;

		$_SESSION['tildaexport']['arDownload'] = $arDownload;
		$_SESSION['tildaexport']['downloaded'] += $downloaded;

		$arResult['total_download']   = $_SESSION['tildaexport']['total'];
		$arResult['need_download']    = sizeof( $arDownload ); //$arResult['total_download'] - $_SESSION['tildaexport']['downloaded'];
		$arResult['count_downloaded'] = $_SESSION['tildaexport']['downloaded'];

		if ( $arResult['need_download'] > 0 ) {
			$arResult['message'] = __( 'Sync worked more 30 sec and not all files download. Please, click button Synchronization for continue download files from Tilda.cc', 'tilda' );
		}
		echo json_encode( $arResult );
		wp_die();
	}

	public static function ajax_switcher_status() {
		if ( ! current_user_can( 'level_7' ) ) {
			wp_die( '<p>' . __( 'You need a higher level of permission.' ) . '<p>', 403 );
		}

		if ( ! wp_verify_nonce( $_POST['t_nonce'], 't_admin_switcher_status' ) ) {
			wp_die( '<p>' . __( 'Invalid request' ) . '<p>', 403 );
		}

		if (
			empty( $_REQUEST['post_id'] )
			|| empty( $_REQUEST['tilda_status'] )
			|| ! in_array( $_REQUEST['tilda_status'], [ 'on', 'off' ] )
		) {
			echo json_encode( [ 'error' => __( "Error. Can't find post with this 'post_id' parameter", 'tilda' ) ] );
			wp_die();
		}

		$post_id = intval( $_REQUEST['post_id'] );
		$meta    = get_post_meta( $post_id, '_tilda', true );
		if ( empty( $meta ) || ! is_array( $meta ) ) {
			$meta = [];
		}
		$meta['status'] = $_REQUEST['tilda_status'];
		if ( ! update_post_meta( $post_id, '_tilda', $meta ) ) {
			wp_die( 'Cannot save info for Tilda plugin.' );
		}

		echo json_encode( [ 'result' => 'ok' ] );
		wp_die();
	}
}
