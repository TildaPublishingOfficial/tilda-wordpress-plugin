<?php
/*
 * User: Michael Akimov <michael@island-future.ru>
 * Date: 2016-02-05
 */

class Tilda
{
    private static $initiated = false;
    public static $errors;
    public static $active_on_page = null;

    public static function init()
    {
        if (!self::$initiated) {

            self::init_consts();
            self::init_hooks();
        }
    }

    public static function get_upload_dir()
    {


        $upload = wp_upload_dir();
        $upload_dir = $upload['basedir'];
        $upload_dir = $upload_dir . '/tilda/';
        if (!is_dir($upload_dir)) {
            if (! wp_mkdir_p($upload_dir)) {
                die( "Cannot create writable directory [$upload_dir]");
            }
        }
        return $upload_dir;
    }

    public static function show_errors()
    {


        $errors = self::$errors->get_error_messages();
        echo '<ul class="errors">';
        foreach ($errors as $error) {
            echo '<li class="error silver" style="color:#9F9F9F;"><span class="red" style="color:#C60000">Ошибка:</span> ' . esc_html($error) . '</li>';
        }
        echo '</ul>';
    }

    public static function json_errors()
    {
        $errors = self::$errors->get_error_messages();
        $arErr = array();
        foreach ($errors as $error) {
            $arErr[] = $error;
        }
        return json_encode( array('error' => implode(' | ', $arErr) ) );
    }

    public static function get_upload_path()
    {


        $upload = wp_upload_dir();
        $upload_dir = $upload['baseurl'];
        $upload_dir = $upload_dir . '/tilda/';
        return $upload_dir;
    }

    public static function plugin_deactivation()
    {
    }

    public static function plugin_activation()
    {
        $upload_dir = self::get_upload_dir();

        if (!is_dir($upload_dir)) {
            wp_mkdir_p($upload_dir);
        }

        file_put_contents($upload_dir.DIRECTORY_SEPARATOR.'tilda.txt','tilda');
    }

    private static function init_consts()
    {


        self::$initiated = true;
        self::$errors = new WP_Error();

        define('TILDA_API_URL', 'http://api.tildacdn.info/v1/');
        define('TILDA_PUBLIC_KEY', self::get_public_key());
        define('TILDA_SECRET_KEY', self::get_secret_key());

    }



    private static function init_hooks()
    {


        self::$initiated = true;
        self::load_textdomain();
        add_action('wp_enqueue_scripts', array('Tilda', 'enqueue_scripts'));

        add_filter('the_content', array('Tilda', 'the_content') );
        add_filter('body_class', array('Tilda', 'body_class') );
        //add_filter('sidebars_widgets', array('Tilda', 'sidebar_widgets'));

        // !Важно не забыть повесить эти 2 хука. Дабы wp не отправил 0 или пустой ответ
        // call /wp-admin/admin-ajax.php?action=nopriv_tilda_sync_event
        // записывает задание в очередь
        add_action("wp_ajax_tilda_sync_event", array("Tilda", "add_sync_event"));
        add_action("wp_ajax_nopriv_tilda_sync_event", array("Tilda", "add_sync_event"));

        // когда наступит время начнет выполнять задание
        add_action( 'tilda_sync_single_event', array('Tilda','sync_single_event'),10,3);
        add_action( 'tilda_sync_single_export_file', array('Tilda','sync_single_export_file'));

    }

    /**
     * Добавляем разовое задание на закачку обновленной страницы с тильды
     */
    public static function add_sync_event()
    {
        // put this line inside a function,
        // presumably in response to something the user does
        // otherwise it will schedule a new event on every page visit
        if(empty($_REQUEST['page_id']) || empty($_REQUEST['project_id'])) {
            echo "ERROR unknown page_id or project_id";
            wp_die();
        }

        $maps = self::get_map_pages();
        if (empty($maps[intval($_REQUEST['page_id'])])) {
            echo "ERROR unknown link between post_id and page_id";
            wp_die();
        }

        $meta = get_post_meta($maps[intval($_REQUEST['page_id'])], '_tilda', true);
        if (!$meta || empty($meta['status']) || $meta['status']!='on') {
            echo "ERROR for page_id not found Post or tilda - off";
            wp_die();
        }

        /* public key generate in Tilda.cc and insert Admin User into wordpress */
        if (empty($_REQUEST['publickey']) || $_REQUEST['publickey'] != self::get_public_key()) {
            echo "Access denied";
            wp_die();
        }

        /* access allow for tilda.cc and api.tildacdn.com */
        if (
            $_SERVER['REMOTE_ADDR']<>"194.177.22.186"
            && $_SERVER['REMOTE_ADDR']<>'95.213.201.187'
        ) {
            echo "Access denied";
            wp_die();
        }

        wp_schedule_single_event( time() + 1, 'tilda_sync_single_event', array(intval($_REQUEST['page_id']), intval($_REQUEST['project_id']), $maps[intval($_REQUEST['page_id'])]) );
        echo "OK";
        wp_die();
    }

    public static function sync_single_event($page_id, $project_id, $post_id)
    {
        if (! class_exists('Tilda_Admin', false)) {
            require_once( TILDA_PLUGIN_DIR . 'class.tilda-admin.php' );
        }

        $meta = get_post_meta($post_id, '_tilda', true);
        if (!$meta || empty($meta['status']) || $meta['status']!='on') {
            echo "ERROR for page_id not fount Post or tilda - off";
            wp_die();
        }

        $arDownload = Tilda_Admin::export_tilda_page($page_id, $project_id, $post_id);

        wp_schedule_single_event( time() + 1, 'tilda_sync_single_export_file', array($arDownload) );
    }

    public static function sync_single_export_file($arDownload)
    {
        if (! class_exists('Tilda_Admin', false)) {
            require_once( TILDA_PLUGIN_DIR . 'class.tilda-admin.php' );
        }
        Tilda_Admin::$ts_start_plugin = time();

        $arTmp = array();
        $downloaded=0;
        foreach ($arDownload as $file) {
            if (time() - Tilda_Admin::$ts_start_plugin > 5) {
                $arTmp[] = $file;
            } else {
                if (! file_exists($file['to_dir']) || strpos($file['to_dir'],'/pages/')===false) {

                    $content = self::getRemoteFile($file['from_url']);
                    if (is_wp_error($content)) {
                        echo self::json_errors();
                        wp_die();
                    }

                    if(file_put_contents($file['to_dir'], $content) === false) {
                        self::$errors->add( 'error_download', 'Cannot save file to ['.$file['to_dir'].'].');
                        echo self::json_errors();
                        wp_die();
                    }
                }
                $downloaded++;
            }
        }

        $arDownload = $arTmp;

        if (! empty($arDownload) && sizeof($arDownload)>0) {
            wp_schedule_single_event( time() + 1, 'tilda_sync_single_export_file', array($arDownload) );
        }
    }

    private static function load_textdomain() {


        load_plugin_textdomain('tilda', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    public static function enqueue_scripts()
    {
        $options = get_option('tilda_options');
        if (
            isset($options['acceptcssinlist'])
            && 'no' == $options['acceptcssinlist']
            && !is_singular()
        ) {
            return false;
        }

        $post = get_post();
        if ($post) {
            $data = get_post_meta($post->ID, '_tilda', true);

            if(isset($data['status']) && $data['status'] == 'on') {
                Tilda::$active_on_page = true;
            } else {
                Tilda::$active_on_page = false;
            }

            if (isset($data) && isset($data["status"]) && $data["status"] == 'on') {
                $page = self::get_local_page($data["page_id"],$data["project_id"], $post->ID);

                $css_links = $page->css;
                $js_links = $page->js;

                $upload_dir = Tilda::get_upload_dir() . $data["project_id"] . '/';

                if (isset($page->sync_time) && $page->sync_time > '') {
                    $ver = strtotime($page->sync_time);
                } else {
                    $ver = date('Ymd');
                }

                if (is_array($css_links)) {
                    $css_path = $upload_dir . 'css/';

                    foreach ($css_links as $file) {
                        $name = basename($file);
                        wp_enqueue_style($name, $file, false, $ver);
                    }
                }

                if (is_array($js_links)) {
                    foreach ($js_links as $file) {
                        $name = basename($file);
                        wp_enqueue_script($name, $file, false, $ver);
                    }
                }
            }
        }

    }

    public static function  sidebars_widgets($sidebars_widgets)
    {
        var_dump($sidebars_widgets);
        return '';
    }

    public static function body_class($classes)
    {
        global $post;
        if (! $post || !is_object($post)) {
            return $classes;
        }
        $data = get_post_meta($post->ID, '_tilda', true);
        $tildaoptions = get_option('tilda_options');

        if (
            isset($tildaoptions['acceptcssinlist'])
            && 'no' == $tildaoptions['acceptcssinlist']
            && !is_singular()
        ) {
            return $classes;
        }

        if(isset($data['status']) && $data['status'] == 'on') {
            $classes[] = 'tilda-publishing';
        }

        return $classes;
    }


    public static function the_content($content)
    {
        $post = get_post();

        if (! $post || !is_object($post)) {
            return $content;
        }

        /* если на странице установлен пароль, то проверим, может нужно вывести форму ввода пароля.*/
        if ($post->post_password > '' && strpos($content,'action=postpass') > 0) {
            return $content;
        }

        $data = get_post_meta($post->ID, '_tilda', true);
        $tildaoptions = get_option('tilda_options');

        if(isset($data['status']) && $data['status'] == 'on') {
            Tilda::$active_on_page = true;
        } else {
            Tilda::$active_on_page = false;
        }
        if (isset($data) && isset($data["status"]) && $data["status"] == 'on') {
//            if (!empty($tildaoptions['type_stored']) && $tildaoptions['type_stored']=='post') {
//                return $content;//$post->post_content;
//            } else {
                if(isset($data['current_page'])) {
                    $page = $data['current_page'];
                } else if (!empty($data["page_id"]) && !empty($data["project_id"])) {
                    $page = self::get_local_page($data["page_id"], $data["project_id"], $post->ID);
                }
//            }

            if (! empty($page->html)) {
                remove_filter( 'the_content', 'wpautop' );
                remove_filter( 'the_excerpt', 'wpautop' );
                return $page->html;
            }
        }


        return $content;
    }

    public static function verify_access()
    {
        $public = self::get_public_key();
        $secret = self::get_secret_key();
        return !empty($public) && !empty($secret);
    }

    public static function get_public_key()
    {
        $options = get_option('tilda_options');

        return isset($options['public_key']) ? $options['public_key'] : '';
    }

    public static function get_secret_key()
    {
        $options = get_option('tilda_options');

        return isset($options['secret_key']) ? $options['secret_key'] : '';
    }

    public static function get_from_api($type, $id = false)
    {


        $suffix = '';
        $code = $type;
        switch ($type) {
            case 'projectslist':
                break;
            case 'project':
                $suffix = 'projectid=' . $id;
                break;
            case 'projectexport':
                $suffix = 'projectid=' . $id;
                break;
            case 'pageslist':
                $suffix = 'projectid=' . $id;
                break;
            case 'page':
                $suffix = 'pageid=' . $id;
                break;
            case 'pageexport':
                $suffix = 'pageid=' . $id;
                break;
        }
        $type = 'get' . $type;
        $suffix = empty($suffix) ? $suffix : '&' . $suffix;

        $url = TILDA_API_URL . '/' . $type . '/?publickey=' . TILDA_PUBLIC_KEY . '&secretkey=' . TILDA_SECRET_KEY . $suffix;

        if (function_exists('curl_init')) {
            if ($curl = curl_init()) {
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $out = curl_exec($curl);
                curl_close($curl);
            } else {
               self::$errors->add( $code, 'Cannot run query: '.$type );
               return self::$errors;
            }
        } else {
            $out = file_get_contents($url);
        }

        if ($out && substr($out,0,1) == '{') {
            $out = json_decode($out);

            if ($out && $out->status == 'FOUND'){
                return $out->result;
            } else {
               self::$errors->add( $code, __($out->message, 'tilda').' query: '.$type );
               return self::$errors;
            }
        } else {
            self::$errors->add( $code, __($out, 'tilda').' in query: '.$type );
            return self::$errors;
        }


    }

    public static function get_projects()
    {


        return self::get_from_api('projectslist');

    }

    public static function get_projectexport($id)
    {


        return self::get_from_api('projectexport', $id);

    }

    public static function get_pageslist($id)
    {


        return self::get_from_api('pageslist', $id);

    }

    public static function get_page($id)
    {


        return self::get_from_api('page', $id);
    }

    public static function get_pageexport($id)
    {


        return self::get_from_api('pageexport', $id);
    }

    /**
     * возвращает массив связи tildapage_id => post_id
     */
    public static function get_map_pages()
    {
        $maps = get_option('tilda_map_pages');
        return $maps;
    }

    public static function get_local_projects()
    {
        $projects = get_option('tilda_projects');
        return $projects;
    }

    public static function get_local_project($project_id)
    {
        $projects = get_option('tilda_projects');
        return isset($projects[$project_id]) ? $projects[$project_id] : null;
    }

    public static function get_local_page($page_id, $project_id, $post_id=0)
    {
        $projects = self::get_local_projects();
        $page = null;

        if ($post_id == 0) {
            $page = $projects[$project_id]->pages[$page_id];
            if( isset($page->post_id)) {
                $post_id = $page->post_id;
            }
        }

        if ($post_id > 0) {
            $data = get_post_meta($post_id, '_tilda', true);
            if (! empty($data['current_page'])) {
                $page = $data['current_page'];
            }
        }

        if (! $page || ! is_object($page)) {
            return (object) array('css'=>null,'js'=>null,'html'=>null);
        }
        $upload_path = Tilda::get_upload_path() . $project_id . '/';

        $ar = array();
        if (sizeof($page->css) == 0) {
            if (is_array($projects[$project_id]->css)) {
                foreach($projects[$project_id]->css as $css) {
                    $ar[] = $upload_path . 'css/'.$css->to;
                }
            }
            $page->css = $ar;
        }

        if (sizeof($page->js) == 0) {
            $ar = array();
            if (is_array($projects[$project_id]->js)) {
                foreach($projects[$project_id]->js as $js) {
                    $ar[] = $upload_path . 'js/' . $js->to;
                }
            }
            $page->js = $ar;
        }
        return $page;
    }

    public static function getRemoteFile($url) {
        if (function_exists('curl_init')) {
            if ($curl = curl_init()) {
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $out = curl_exec($curl);
                curl_close($curl);
            } else {
               self::$errors->add( 'download_error', 'Cannot get file: '.$url );
               return self::$errors;
            }
        } else {
            $out = file_get_contents($url);
        }
        return $out;
    }
}