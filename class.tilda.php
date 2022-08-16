<?php
/*
 * User: Michael Akimov <michael@island-future.ru>
 * Date: 2016-02-05
 */

class Tilda {
	private static $initiated = false;
	public static $errors;
	public static $active_on_page = null;

	const OPTION_PROJECTS = 'tilda_projects';
	const OPTION_PAGES = 'tilda_pages';
	const OPTION_OPTIONS = 'tilda_options';
	const OPTION_KEYS = 'tilda_options_keys';
	const OPTION_MAPS = 'tilda_maps';
	const MAP_KEY_PROJECTS = 'projects';
	const MAP_PROJECT_PAGES = 'pages';
	const MAP_PAGE_POSTS = 'posts';

	public static function init() {
		if ( ! self::$initiated ) {
			self::init_consts();
			self::init_hooks();
		}

		if ( self::isUpgraded() ) {
			if ( ! class_exists( 'Tilda_Admin', false ) ) {
				require_once( TILDA_PLUGIN_DIR . 'class.tilda-admin.php' );
			}
			Tilda_Admin::migrateOptions();
		}
	}

	/**
	 * Detects if plugin was upgraded by checking stored data structures
	 *
	 * @return bool
	 */
	public static function isUpgraded() {
		$options = get_option( Tilda::OPTION_OPTIONS );

		return ( isset( $options['public_key'] ) && isset( $options['secret_key'] ) );
	}

	public static function get_upload_dir() {


		$upload     = wp_upload_dir();
		$upload_dir = $upload['basedir'];
		$upload_dir = $upload_dir . '/tilda/';
		if ( ! is_dir( $upload_dir ) ) {
			if ( ! wp_mkdir_p( $upload_dir ) ) {
				die( "Cannot create writable directory [$upload_dir]" );
			}
		}

		return $upload_dir;
	}

	public static function show_errors() {
		$errors = self::$errors->get_error_messages();
		echo '<ul class="errors">';
		foreach ( $errors as $error ) {
			echo '<li class="error silver" style="color:#9F9F9F;"><span class="red" style="color:#C60000">Ошибка:</span> ' . esc_html( $error ) . '</li>';
		}
		echo '</ul>';
	}

	public static function json_errors() {
		$errors = self::$errors->get_error_messages();
		$arErr  = [];
		foreach ( $errors as $error ) {
			$arErr[] = $error;
		}

		return json_encode( [ 'error' => implode( ' | ', $arErr ) ] );
	}

	public static function get_upload_path() {
		$upload     = wp_upload_dir();
		$upload_dir = $upload['baseurl'];

		return $upload_dir . '/tilda/';
	}

	public static function plugin_deactivation() {
	}

	public static function plugin_activation() {
		$upload_dir = self::get_upload_dir();

		if ( ! is_dir( $upload_dir ) ) {
			wp_mkdir_p( $upload_dir );
		}

		file_put_contents( $upload_dir . DIRECTORY_SEPARATOR . 'tilda.txt', 'tilda' );
	}

	private static function init_consts() {
		self::$initiated = true;
		self::$errors    = new WP_Error();

		define( 'TILDA_API_URL', 'http://api.tildacdn.info/v1/' );
		define( 'TILDA_PUBLIC_KEY', self::get_public_key() );
		define( 'TILDA_SECRET_KEY', self::get_secret_key() );

	}


	private static function init_hooks() {
		self::$initiated = true;
		self::load_textdomain();
		add_action( 'wp_enqueue_scripts', [ 'Tilda', 'enqueue_scripts' ] );

		add_filter( 'the_content', [ 'Tilda', 'the_content' ] );
		add_filter( 'body_class', [ 'Tilda', 'body_class' ] );
		//add_filter('sidebars_widgets', array('Tilda', 'sidebar_widgets'));

		// !Важно не забыть повесить эти 2 хука. Дабы wp не отправил 0 или пустой ответ
		// call /wp-admin/admin-ajax.php?action=nopriv_tilda_sync_event
		// записывает задание в очередь
		add_action( 'wp_ajax_tilda_sync_event', [ 'Tilda', 'add_sync_event' ] );
		add_action( 'wp_ajax_nopriv_tilda_sync_event', [ 'Tilda', 'add_sync_event' ] );

		// когда наступит время, начнет выполнять задание
		add_action( 'tilda_sync_single_event', [ 'Tilda', 'sync_single_event' ], 10, 3 );
		add_action( 'tilda_sync_single_export_file', [ 'Tilda', 'sync_single_export_file' ] );

	}

	/**
	 * Добавляем разовое задание на закачку обновленной страницы с тильды
	 */
	public static function add_sync_event() {
		// put this line inside a function,
		// presumably in response to something the user does
		// otherwise it will schedule a new event on every page visit
		if ( empty( $_REQUEST['page_id'] ) || empty( $_REQUEST['project_id'] ) ) {
			if ( ! empty( $_REQUEST['projectid'] ) && ! empty( $_REQUEST['pageid'] ) ) {
				$_REQUEST['page_id']    = $_REQUEST['pageid'];
				$_REQUEST['project_id'] = $_REQUEST['projectid'];
			} else {
				echo 'ERROR unknown page_id or project_id';
				wp_die();
			}
		}

		$maps = self::get_local_map( Tilda::MAP_PAGE_POSTS );
		if ( empty( $maps[ intval( $_REQUEST['page_id'] ) ] ) ) {
			echo 'ERROR unknown link between post_id and page_id';
			wp_die();
		}

		$meta = get_post_meta( $maps[ intval( $_REQUEST['page_id'] ) ], '_tilda', true );
		if ( ! $meta || empty( $meta['status'] ) || $meta['status'] != 'on' ) {
			echo 'ERROR for page_id not found Post or tilda - off';
			wp_die();
		}

		if ( empty( $_REQUEST['publickey'] ) ) {
			echo 'Access denied';
			wp_die();
		}

		$isPublicKeyValid = false;
		$arAllKeys        = self::get_local_keys();
		foreach ( $arAllKeys as $arKey ) {
			if ( $arKey['public_key'] === $_REQUEST['publickey'] ) {
				$isPublicKeyValid = true;
				break;
			}
		}
		if ( ! $isPublicKeyValid ) {
			echo 'Access denied';
			wp_die();
		}

		/* access allow for tilda.cc and api.tildacdn.com */
		if ( ! in_array( $_SERVER['REMOTE_ADDR'], [ '81.163.23.245', '194.177.22.186', '95.213.201.187' ] ) ) {
			echo 'Access denied';
			wp_die();
		}

		wp_schedule_single_event( time() + 1, 'tilda_sync_single_event', [
			intval( $_REQUEST['page_id'] ),
			intval( $_REQUEST['project_id'] ),
			$maps[ intval( $_REQUEST['page_id'] ) ]
		] );
		echo 'OK';
		wp_die();
	}

	public static function sync_single_event( $page_id, $project_id, $post_id ) {
		if ( ! class_exists( 'Tilda_Admin', false ) ) {
			require_once( TILDA_PLUGIN_DIR . 'class.tilda-admin.php' );
		}

		$meta = get_post_meta( $post_id, '_tilda', true );
		if ( ! $meta || empty( $meta['status'] ) || $meta['status'] != 'on' ) {
			echo 'ERROR for page_id not fount Post or tilda - off';
			wp_die();
		}

		$arDownload = Tilda_Admin::export_tilda_page( $page_id, $project_id, $post_id );

		wp_schedule_single_event( time() + 1, 'tilda_sync_single_export_file', [ $arDownload ] );
	}

	public static function sync_single_export_file( $arDownload ) {
		if ( ! class_exists( 'Tilda_Admin', false ) ) {
			require_once( TILDA_PLUGIN_DIR . 'class.tilda-admin.php' );
		}
		Tilda_Admin::$ts_start_plugin = time();

		$arTmp      = [];
		$downloaded = 0;
		foreach ( $arDownload as $file ) {
			if ( time() - Tilda_Admin::$ts_start_plugin > 5 ) {
				$arTmp[] = $file;
			} else {
				if ( ! file_exists( $file['to_dir'] ) || strpos( $file['to_dir'], '/pages/' ) === false ) {

					$content = self::getRemoteFile( $file['from_url'] );
					if ( is_wp_error( $content ) ) {
						echo self::json_errors();
						wp_die();
					}

					if ( file_put_contents( $file['to_dir'], $content ) === false ) {
						self::$errors->add( 'error_download', 'Cannot save file to [' . $file['to_dir'] . '].' );
						echo self::json_errors();
						wp_die();
					}
				}
				$downloaded ++;
			}
		}

		$arDownload = $arTmp;

		if ( ! empty( $arDownload ) && sizeof( $arDownload ) > 0 ) {
			wp_schedule_single_event( time() + 1, 'tilda_sync_single_export_file', [ $arDownload ] );
		}
	}

	private static function load_textdomain() {
		load_plugin_textdomain( 'tilda', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	public static function enqueue_scripts() {
		//$options = get_option('tilda_options');

		$post = get_post();
		if ( $post ) {
			$data = get_post_meta( $post->ID, '_tilda', true );

			if ( ! is_array( $data ) ) {
				return false;
			}

			if ( isset( $data['status'] ) && $data['status'] === 'off' ) {
				return false;
			}

			if ( empty( $data['project_id'] ) ) {
				$data['project_id'] = ( ! empty( $data['current_page']->projectid ) ) ? $data['current_page']->projectid : null;
			}

			$key_id = Tilda::get_key_for_project_id( $data['project_id'] );
			$key    = Tilda::get_local_keys( $key_id );
			$key    = $key[ $key_id ];

			if (
				false == $key['apply_css_in_list']
				&& ! is_singular()
			) {
				return false;
			}

			if ( isset( $data['status'] ) && $data['status'] == 'on' ) {
				Tilda::$active_on_page = true;

				$page = self::get_page_prepared( $data['page_id'], $data['project_id'], $post->ID );

				$css_links = $page->css;
				$js_links  = $page->js;

				$upload_dir = Tilda::get_upload_dir() . $data['project_id'] . '/';

				if ( isset( $page->sync_time ) && $page->sync_time > '' ) {
					$ver = strtotime( $page->sync_time );
				} else {
					$ver = date( 'Ymd' );
				}

				if ( is_array( $css_links ) ) {
					$css_path = $upload_dir . 'css/';

					foreach ( $css_links as $file ) {
						$name = basename( $file );
						wp_enqueue_style( $name, $file, false, $ver );
					}
				}

				if ( is_array( $js_links ) ) {
					foreach ( $js_links as $file ) {
						$name = basename( $file );
						wp_enqueue_script( $name, $file, false, $ver );
					}
				}
			} else {
				Tilda::$active_on_page = false;
			}
		}

	}

	public static function sidebars_widgets( $sidebars_widgets ) {
		var_dump( $sidebars_widgets );

		return '';
	}

	public static function body_class( $classes ) {
		global $post;
		if ( ! $post || ! is_object( $post ) ) {
			return $classes;
		}
		$data = get_post_meta( $post->ID, '_tilda', true );

		if ( ! is_array( $data ) ) {
			return $classes;
		}

		if ( isset( $data['status'] ) && $data['status'] === 'off' ) {
			return $classes;
		}

		if ( empty( $data['project_id'] ) ) {
			$data['project_id'] = ( ! empty( $data['current_page']->projectid ) ) ? $data['current_page']->projectid : null;
		}

		$key_id = Tilda::get_key_for_project_id( $data['project_id'] );
		$key    = Tilda::get_local_keys( $key_id );
		if ( ! isset( $key[ $key_id ] ) ) {
			return $classes;
		}
		$key = $key[ $key_id ];

		if (
			false == $key['apply_css_in_list']
			&& ! is_singular()
		) {
			return $classes;
		}

		if ( isset( $data['status'] ) && $data['status'] == 'on' ) {
			$classes[] = 'tilda-publishing';
		}

		return $classes;
	}


	public static function the_content( $content ) {
		$post = get_post();

		if ( ! $post || ! is_object( $post ) ) {
			return $content;
		}

		/* если на странице установлен пароль, то проверим, может нужно вывести форму ввода пароля.*/
		if ( $post->post_password > '' && strpos( $content, 'action=postpass' ) > 0 ) {
			return $content;
		}

		$data         = get_post_meta( $post->ID, '_tilda', true );
		$tildaoptions = get_option( 'tilda_options' );

		if ( isset( $data['status'] ) && $data['status'] == 'on' ) {
			Tilda::$active_on_page = true;
		} else {
			Tilda::$active_on_page = false;
		}
		if ( isset( $data ) && isset( $data['status'] ) && $data['status'] == 'on' ) {
//            if (!empty($tildaoptions['type_stored']) && $tildaoptions['type_stored']=='post') {
//                return $content;//$post->post_content;
//            } else {
			if ( isset( $data['current_page'] ) ) {
				$page = $data['current_page'];
			} else {
				if ( ! empty( $data['page_id'] ) && ! empty( $data['project_id'] ) ) {
					$page = self::get_page_prepared( $data['page_id'], $data['project_id'], $post->ID );
				}
			}
//            }

			if ( ! empty( $page->html ) ) {
				remove_filter( 'the_content', 'wpautop' );
				remove_filter( 'the_excerpt', 'wpautop' );
				// ||s|| is custom escaping symbol used for escape '<\/script>' text to bypass WordPress engine processing
				$page->html = str_replace( '<||s||script>', '<\/script>', $page->html );

				// ||n|| is custom escaping symbol for \n to bypass serialization/deserialization process
				return str_replace( '||n||', '\n', $page->html );
			}
		}

		return $content;
	}

	public static function verify_access() {
		$keys = self::get_local_keys();

		return ! empty( $keys );
	}

	public static function get_public_key() {
		$options = get_option( 'tilda_options' );

		return isset( $options['public_key'] ) ? $options['public_key'] : '';
	}

	public static function get_secret_key() {
		$options = get_option( 'tilda_options' );

		return isset( $options['secret_key'] ) ? $options['secret_key'] : '';
	}

	public static function get_from_api( $type, $id = false, $public_key = null, $secret_key = null ) {
		$public_key = ( empty( $public_key ) ) ? TILDA_PUBLIC_KEY : $public_key;
		$secret_key = ( empty( $secret_key ) ) ? TILDA_SECRET_KEY : $secret_key;

		$suffix = '';
		$code   = $type;
		switch ( $type ) {
			case 'projectslist':
				break;
//			case 'project':
//			case 'projectexport':
			case 'projectinfo':
			case 'pageslist':
				$suffix = 'projectid=' . $id;
				break;
			case 'pageexport':
			case 'page':
				$suffix = 'pageid=' . $id;
				break;
		}
		$type   = 'get' . $type;
		$suffix = empty( $suffix ) ? $suffix : '&' . $suffix;

		$url = TILDA_API_URL . '/' . $type . '/?publickey=' . $public_key . '&secretkey=' . $secret_key . $suffix;

		if ( function_exists( 'curl_init' ) ) {
			if ( $curl = curl_init() ) {
				curl_setopt( $curl, CURLOPT_URL, $url );
				curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
				curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
				curl_setopt( $curl, CURLOPT_ENCODING, '' );
				$out = curl_exec( $curl );
				curl_close( $curl );
			} else {
				self::$errors->add( $code, 'Cannot run query: ' . $type );

				return self::$errors;
			}
		} else {
			$out = file_get_contents( $url );
		}

		if ( $out && substr( $out, 0, 1 ) == '{' ) {
			$out = json_decode( $out );

			if ( $out && $out->status == 'FOUND' ) {
				return $out->result;
			} else {
				self::$errors->add( $code, __( $out->message, 'tilda' ) . ' query: ' . $type );

				return self::$errors;
			}
		} else {
			self::$errors->add( $code, __( $out, 'tilda' ) . ' in query: ' . $type );

			return self::$errors;
		}
	}

	public static function get_projects( $public_key = null, $secret_key = null ) {
		return self::get_from_api( 'projectslist', false, $public_key, $secret_key );
	}

	/**
	 * DEPRECATED
	 */
	/*public static function get_projectexport( $project_id, $public_key = null, $secret_key = null ) {
		if ( empty( $public_key ) && empty( $secret_key ) ) {
			$key_id     = Tilda::get_key_for_project_id( $project_id );
			$keys       = Tilda::get_local_keys();
			$key        = $keys[ $key_id ];
			$public_key = $key['public_key'];
			$secret_key = $key['secret_key'];
		}

		return self::get_from_api( 'projectexport', $project_id, $public_key, $secret_key );
	}*/

	public static function get_projectinfo( $project_id, $public_key = null, $secret_key = null ) {
		if ( empty( $public_key ) && empty( $secret_key ) ) {
			$key_id     = Tilda::get_key_for_project_id( $project_id );
			$keys       = Tilda::get_local_keys();
			$key        = $keys[ $key_id ];
			$public_key = $key['public_key'];
			$secret_key = $key['secret_key'];
		}

		return self::get_from_api( 'projectinfo', $project_id, $public_key, $secret_key );
	}

	public static function get_pageslist( $project_id, $public_key = null, $secret_key = null ) {
		if ( empty( $public_key ) && empty( $secret_key ) ) {
			$key_id     = Tilda::get_key_for_project_id( $project_id );
			$keys       = Tilda::get_local_keys();
			$key        = $keys[ $key_id ];
			$public_key = $key['public_key'];
			$secret_key = $key['secret_key'];
		}

		return self::get_from_api( 'pageslist', $project_id, $public_key, $secret_key );
	}

	public static function get_page( $page_id, $public_key = null, $secret_key = null ) {
		if ( empty( $public_key ) && empty( $secret_key ) ) {
			$key_id     = Tilda::get_key_for_page_id( $page_id );
			$keys       = Tilda::get_local_keys();
			$key        = $keys[ $key_id ];
			$public_key = $key['public_key'];
			$secret_key = $key['secret_key'];
		}

		return self::get_from_api( 'page', $page_id, $public_key, $secret_key );
	}

	public static function get_pageexport( $page_id, $public_key = null, $secret_key = null ) {
		if ( empty( $public_key ) && empty( $secret_key ) ) {
			$key_id     = Tilda::get_key_for_page_id( $page_id );
			$keys       = Tilda::get_local_keys();
			$key        = $keys[ $key_id ];
			$public_key = $key['public_key'];
			$secret_key = $key['secret_key'];
		}

		return self::get_from_api( 'pageexport', $page_id, $public_key, $secret_key );
	}

	/**
	 * DEPRECATED should be refactored and removed
	 * возвращает массив связи tildapage_id => post_id
	 */
	/*public static function get_map_pages()
	{
		$maps = get_option('tilda_map_pages');
		return $maps;
	}*/

	/**
	 * Return subarray of tilda_maps option or empty array
	 *
	 * @param $type
	 *
	 * @return array|mixed
	 */
	public static function get_local_map( $type ) {
		$maps = get_option( 'tilda_maps' );

		if ( ! isset( $maps[ $type ] ) ) {
			return [];
		}

		return $maps[ $type ];
	}

	/**
	 * Return tilda_maps option or empty array
	 *
	 * @return false|mixed|void
	 */
	public static function get_local_maps() {
		$maps = get_option( 'tilda_maps' );

		return $maps;
	}

	public static function get_local_page( $page_id, $project_id ) {
		// Tilda_Admin::log(__CLASS__.'::'.__FUNCTION__, __FILE__, __LINE__);

		$pages = Tilda::get_local_pages();

		return ( isset( $pages[ $page_id ] ) ) ? $pages[ $page_id ] : null;
	}

	/**
	 * Search at tilda_map option for key that has mapped project_id
	 *
	 * @param $project_id
	 *
	 * @return false|string
	 */
	public static function get_key_for_project_id( $project_id ) {
		$key_map = Tilda::get_local_map( Tilda::MAP_KEY_PROJECTS );
		foreach ( $key_map as $key => $project_ids ) {
			if ( in_array( $project_id, $project_ids ) ) {
				return $key;
			}
		}

		return false;
	}

	/**
	 * Search at tilda_map option for project_id that has mapped page_id and use it to find key
	 *
	 * @param $page_id
	 *
	 * @return false|string
	 */
	public static function get_key_for_page_id( $page_id ) {
		$project_map = Tilda::get_local_map( Tilda::MAP_PROJECT_PAGES );
		$id          = false;

		foreach ( $project_map as $project_id => $page_ids ) {
			if ( in_array( $page_id, $page_ids ) ) {
				$id = $project_id;
				break;
			}
		}

		return Tilda::get_key_for_project_id( $id );
	}

	public static function get_local_projects() {
		$projects = get_option( 'tilda_projects' );

		return $projects;
	}

	/**
	 * Return tilda_pages option filtered by $filter_project_id
	 *
	 * @return array
	 */
	public static function get_local_pages( $filter_project_id = null ) {
		$pages = get_option( 'tilda_pages' );

		if ( empty( $filter_project_id ) ) {
			return $pages;
		}

		if ( ! isset( $pages[ $filter_project_id ] ) ) {
			return [];
		}

		return [ $filter_project_id => $pages[ $filter_project_id ] ];
	}

	public static function get_local_project( $project_id ) {
		$projects = get_option( 'tilda_projects' );

		return isset( $projects[ $project_id ] ) ? $projects[ $project_id ] : null;
	}

	/**
	 * Get tilda_options_keys filtered by $filter_key_id or empty array
	 * array ( key_id => array( 'public_key' => x, 'secret_key' => y ) )
	 *
	 * @return array
	 */
	public static function get_local_keys( $filter_key_id = null ) {
		$keys = get_option( Tilda::OPTION_KEYS );

		$keys = ( empty( $keys ) ) ? [] : $keys;

		//Make $keys associative. Replace numeric indexes with 'id' value
		$keys = array_column( $keys, null, 'id' );

		if ( empty( $filter_key_id ) ) {
			return $keys;
		}

		if ( ! isset( $keys[ $filter_key_id ] ) ) {
			return [];
		}

		return [ $filter_key_id => $keys[ $filter_key_id ] ];
	}

	/**
	 * Get local page and make preparations
	 *
	 * @param     $page_id
	 * @param     $project_id
	 * @param int $post_id
	 *
	 * @return mixed|object
	 */
	public static function get_page_prepared( $page_id, $project_id, $post_id = 0 ) {
		$projects = self::get_local_projects();
		$page     = null;

		if ( $post_id == 0 ) {
			$page = Tilda::get_local_page( $page_id );
			if ( isset( $page->post_id ) ) {
				$post_id = $page->post_id;
			}
		}

		if ( $post_id > 0 ) {
			$data = get_post_meta( $post_id, '_tilda', true );
			if ( ! empty( $data['current_page'] ) ) {
				$page = $data['current_page'];
			}
		}

		if ( ! $page || ! is_object( $page ) ) {
			return (object) [ 'css' => null, 'js' => null, 'html' => null ];
		}
		$upload_path = Tilda::get_upload_path() . $project_id . '/';

		$ar = [];
		if ( sizeof( $page->css ) == 0 ) {
			if ( is_array( $projects[ $project_id ]->css ) ) {
				foreach ( $projects[ $project_id ]->css as $css ) {
					$ar[] = $upload_path . 'css/' . $css->to;
				}
			}
			$page->css = $ar;
		}

		if ( sizeof( $page->js ) == 0 ) {
			$ar = [];
			if ( is_array( $projects[ $project_id ]->js ) ) {
				foreach ( $projects[ $project_id ]->js as $js ) {
					$ar[] = $upload_path . 'js/' . $js->to;
				}
			}
			$page->js = $ar;
		}

		return $page;
	}

	public static function getRemoteFile( $url ) {
		if ( function_exists( 'curl_init' ) ) {
			if ( $curl = curl_init() ) {
				curl_setopt( $curl, CURLOPT_URL, $url );
				curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
				$out = curl_exec( $curl );
				curl_close( $curl );
			} else {
				self::$errors->add( 'download_error', 'Cannot get file: ' . $url );

				return self::$errors;
			}
		} else {
			$out = file_get_contents( $url );
		}

		return $out;
	}
}
