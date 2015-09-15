<?php

class Tilda_Admin
{

    private static $initiated = false;
    private static $libs = array('curl_init','timezonedb');
    private static $log_time = null;
    private static $ts_start_plugin = null;
    public static $global_message='';
    
    public static function init()
    {
        if (!self::$initiated) {
            self::init_hooks();
        }
    }

    public static function init_hooks()
    {
        if (!self::$ts_start_plugin) {
            self::$ts_start_plugin = time();
        }

        // Tilda_Admin::log(__CLASS__."::".__FUNCTION__, __FILE__, __LINE__);

        self::$initiated = true;

        add_action('admin_init', array('Tilda_Admin', 'admin_init'));
        add_action('admin_menu', array('Tilda_Admin', 'admin_menu'), 5);
        add_action('add_meta_boxes', array('Tilda_Admin', 'add_meta_box'),5);
        add_action('admin_enqueue_scripts', array('Tilda_Admin', 'admin_enqueue_scripts'));
        add_action('save_post', array('Tilda_Admin', 'save_tilda_data'), 10);


        add_action('edit_form_after_title', function () {
            global $post, $wp_meta_boxes;
            do_meta_boxes(get_current_screen(), 'advanced', $post);
            unset($wp_meta_boxes[get_post_type($post)]['advanced']);
        });

    }

    public static function admin_init()
    {
        // Tilda_Admin::log(__CLASS__."::".__FUNCTION__, __FILE__, __LINE__);
        register_setting(
            'tilda_options',
            'tilda_options',
            array('Tilda_Admin', 'options_validate')
        );

        add_settings_section(
            'tilda_keys',
            '',
            false,
            'tilda-config'
        );

        add_settings_field(
            'tilda_public_key',
            'Public key',
            array('Tilda_Admin', 'public_key_field'),
            'tilda-config',
            'tilda_keys'
        );

        add_settings_field(
            'tilda_secret_key',
            'Secret key',
            array('Tilda_Admin', 'secret_key_field'),
            'tilda-config',
            'tilda_keys'
        );
    }

    public static function admin_menu()
    {
        // Tilda_Admin::log(__CLASS__."::".__FUNCTION__, __FILE__, __LINE__);
        self::load_menu();
    }

    public static function load_menu()
    {
        // Tilda_Admin::log(__CLASS__."::".__FUNCTION__, __FILE__, __LINE__);
        add_submenu_page(
            'options-general.php',
            'Tilda Publishing',
            'Tilda Publishing',
            'manage_options',
            'tilda-config',
            array('Tilda_Admin', 'display_configuration_page')
        );
    }

    public static function add_meta_box()
    {
        // Tilda_Admin::log(__CLASS__."::".__FUNCTION__, __FILE__, __LINE__);

        global $post;
        $data = get_post_meta($post->ID, '_tilda', true);
        $screens = array('post', 'page');

        foreach ($screens as $screen) {

            if (!isset($data["status"]) || $data["status"] != 'on') {
                add_meta_box(
                    'tilda_switcher',
                    'Tilda Publishing',
                    array('Tilda_Admin', 'switcher_callback'),
                    $screen
                );
            };
            if (isset($data["status"]) && $data["status"] == 'on') {
                add_meta_box(
                    'tilda_pages_list',
                    'Tilda Publishing',
                    array('Tilda_Admin', 'pages_list_callback'),
                    $screen,
                    'advanced',
                    'high'
                );
            };
        }
    }

    public static function pages_list_callback($post)
    {
        // Tilda_Admin::log(__CLASS__."::".__FUNCTION__, __FILE__, __LINE__);

        $data = get_post_meta($post->ID, '_tilda', true);
        $page_id = isset($data["page_id"]) ? $data["page_id"] : false;
        $project_id = isset($data["project_id"]) ? $data["project_id"] : false;

        if (isset($data['update_data']) && $data['update_data'] == 'update_data') {
            /* обновляем список проектов и страниц */
            self::initialize();
            unset($data['update_data']);
            update_post_meta($post->ID, '_tilda', $data);
        }
        
        if ($page_id && $project_id) {
            /* если известна текущая страница */
            
            if (isset($data['update_page']) && $data['update_page'] == 'update_page') {
                $data["current_page"] = self::update_page($page_id, $project_id);
                unset($data['update_page']);
                
                /**
                 * раскомментировать, если нужно сохранять данные с Тильды в содержимое поста
                 *
                $post->post_content = $tilda_page->html;
                wp_update_post( $post );
                */
                update_post_meta($post->ID, '_tilda', $data);
            } else {
                $data["current_page"] = self::get_page($data["page_id"],$data["project_id"]);
            }

        }

        $projects_list = self::get_projects();
        if (!$projects_list){
            Tilda::$errors->add( 'refresh',__('Refresh pages list','tilda'));
        }
        
        self::view(
            'pages_meta_box',
            array('projects_list' => $projects_list, 'data' => $data)
        );

    }

    public static function save_tilda_data($postID)
    {
        // Tilda_Admin::log(__CLASS__."::".__FUNCTION__, __FILE__, __LINE__);

        if (!isset($_POST['tilda'])) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($postID)) {
            return;
        }

        check_admin_referer("tilda_switcher", "tilda_nonce");

        $data = $_POST['tilda'];

        update_post_meta($postID, '_tilda', $data);

    }

    public static function admin_enqueue_scripts($hook)
    {
        // Tilda_Admin::log(__CLASS__."::".__FUNCTION__, __FILE__, __LINE__);

        if ('post.php' != $hook && 'post-new.php' != $hook) {
            return;
        }

        wp_enqueue_script('tilda_js', TILDA_PLUGIN_URL . 'js/plugin.js', array('jquery','jquery-ui-tabs'));

        wp_enqueue_style('jquery-ui-tabs', TILDA_PLUGIN_URL . 'css/jquery-ui-tabs.css');
        wp_enqueue_style('tilda_css', TILDA_PLUGIN_URL . 'css/styles.css');
    }

    public static function initialize()
    {
        // Tilda_Admin::log(__CLASS__."::".__FUNCTION__, __FILE__, __LINE__);

        $projects = Tilda::get_projects();
        $projects_list = array();

        if (is_wp_error($projects)){
            return;
        }

        if (!$projects || count($projects) <= 0) {
            Tilda::$errors->add( 'empty_project_list',__('Projects list is empty','tilda'));
            return;
        }

        foreach ($projects as $project) {
            $project = Tilda::get_project($project->id);

            if ($project) {
                $id = $project->id;

                $projects_list[$id] = $project;

                // self::download_project_assets($project);

                $pages = Tilda::get_pageslist($id);
                if ($pages && count($pages) > 0) {
                    $projects_list[$id]->pages = array();
                    foreach ($pages as $page) {
                        $projects_list[$id]->pages[$page->id] = $page;
                    }
                }
            }
        }

        update_option('tilda_projects', $projects_list);

//        self::download_assets($projects_list);
    }

    public static function update_page($page_id, $project_id)
    {
        // Tilda_Admin::log(__CLASS__."::".__FUNCTION__, __FILE__, __LINE__);

        $project = Tilda::get_project($project_id);
        if (is_wp_error($project)){
            return;
        }

        if (
            isset($project)
            || !isset($project->css)
            || !isset($project->css[0])
            || !isset($project->css[0]->to)
        ) {
            self::initialize();
        }

        $new_page = Tilda::get_pageexport($page_id);

        if (is_wp_error($new_page)){
            return;
        }

        if($new_page){
            self::download_project_assets($project);
            $old_page = self::create_page($new_page, $project, true);
        }else{
            $old_page = self::get_page($page_id, $project_id);
            $old_page->removed = true;
        }

        self::set_page($old_page, $project_id);
        return $old_page;
    }


    public static function create_page($page, $project, $sync=false)
    {
        // Tilda_Admin::log(__CLASS__."::".__FUNCTION__, __FILE__, __LINE__);
        
        set_time_limit(0);
        $page->html = htmlspecialchars_decode($page->html);
        $page_id =  $page->id;
        $project_id = $project->id;

        if ($sync) {
            $page->sync_time = current_time('mysql');
    
            $upload_path = Tilda::get_upload_path() . $project->id . '/';
    
            $css_links = $project->css;
            $js_links = $project->js;
    
            foreach ($css_links as $file) {
                $page->css[] = $upload_path . 'css/' . $file->to;
            }
    
            foreach ($js_links as $file) {
                $page->js[] = $upload_path . 'js/' . $file->to;
            }
    
            $upload_path = Tilda::get_upload_path() . $project_id . '/pages/'.$page_id.'/';
            $upload_dir = Tilda::get_upload_dir() . $project_id . '/pages/'.$page_id.'/';

            $localimages = array();
            $images = array();
            $i=0;
            foreach ($page->images as $file) {
                if (time() - self::$ts_start_plugin > 20) {
                    self::$global_message = "Синхронизация заняла больше 30 секунд и все файлы не успелись синхронизироваться. Нажмите еще раз кнопку Синхронизировать для продолжения синхронизации.";
                    break;
                }
                $i++;
                if ($project->export_imgpath > '') {
                    $exportimages[] = '|'.$project->export_imgpath.'/'.$file->to.'|i';
                } else {
                    $exportimages[] = '|'.$file->to.'|i';
                }
                $to = Tilda_Admin::download_export_image($file, $page_id, $project_id);
                $replaceimages[] = $to;
            }
            
            $html = preg_replace($exportimages, $replaceimages, $page->html);

            if ($html ) {
                $page->html = $html;
            }
        }
        return $page;
    }

    public static function download_export_image($file, $page_id, $project_id) {
        // Tilda_Admin::log(__CLASS__."::".__FUNCTION__, __FILE__, __LINE__);

        if (empty($file->from) || empty($file->to)) {
            echo "Error: cannot export image file";
            return false;
        }
        
        $upload_dir = Tilda::get_upload_dir() . $project_id . '/pages/'.$page_id.'/';
        $upload_path = Tilda::get_upload_path() . $project_id . '/pages/'.$page_id.'/';

        if (file_exists($upload_dir . $file->to)) {
            return $upload_path . $file->to;
        }

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755);
            $error = error_get_last();
            echo $error['message'];
        }
        
        $contents = file_get_contents($file->from);
        if ($contents) file_put_contents($upload_dir . $file->to, $contents);

        return $upload_path . $file->to;
    }

    public static function download_image($src,$page_id, $project_id){
        // Tilda_Admin::log(__CLASS__."::".__FUNCTION__, __FILE__, __LINE__);

        $upload_dir = Tilda::get_upload_dir() . $project_id . '/pages/'.$page_id.'/';
        $upload_path = Tilda::get_upload_path() . $project_id . '/pages/'.$page_id.'/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755);
            $error = error_get_last();
            echo $error['message'];
        }

        $name = basename($src);
        $contents = file_get_contents($src);
        if ($contents) file_put_contents($upload_dir . $name, $contents);

        return $upload_path . $name;
    }

    public static function get_page($page_id, $project_id)
    {
        // Tilda_Admin::log(__CLASS__."::".__FUNCTION__, __FILE__, __LINE__);

        $projects = self::get_projects();
        $page = $projects[$project_id]->pages[$page_id];

        return $page;
    }

    public static function set_page($page, $project_id){
        // Tilda_Admin::log(__CLASS__."::".__FUNCTION__, __FILE__, __LINE__);

        $projects = self::get_projects();
        $projects[$project_id]->pages[$page->id] = $page;
        update_option('tilda_projects', $projects);
    }

    private static function scandir($dir)
    {
        // Tilda_Admin::log(__CLASS__."::".__FUNCTION__, __FILE__, __LINE__);

        $list = scandir($dir);
        return array_values($list);
    }

    private static function clear_dir($dir)
    {
        // Tilda_Admin::log(__CLASS__."::".__FUNCTION__, __FILE__, __LINE__);

        $list = self::scandir($dir);

        foreach ($list as $file) {
            if ($file != '.' && $file != '..') {
                if (is_dir($dir . $file)) {
                    self::clear_dir($dir . $file . '/');
                    rmdir($dir . $file);
                } else {
                    unlink($dir . $file);
                }
            }
        }
    }

    public static function download_assets($projects_list)
    {
        // Tilda_Admin::log(__CLASS__."::".__FUNCTION__, __FILE__, __LINE__);

        foreach ($projects_list as $project) {
            self::download_project_assets($project["id"]);
        }
    }

    public static function is_exist_assets($project_id)
    {
        // Tilda_Admin::log(__CLASS__."::".__FUNCTION__, __FILE__, __LINE__);

        $upload_dir = Tilda::get_upload_dir() . $project_id . '/';

        return is_dir($upload_dir);
    }

    public static function download_project_assets($project)
    {
        // Tilda_Admin::log(__CLASS__."::".__FUNCTION__, __FILE__, __LINE__);

        if (empty($project)) {
            return;
        }

        $upload_dir = Tilda::get_upload_dir() . $project->id . '/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755);
        }

        // self::clear_dir($upload_dir);

        $css_path = $upload_dir . 'css/';
        $js_path = $upload_dir . 'js/';
        $pages_path = $upload_dir . 'pages/';

        if (!is_dir($css_path)) {
            mkdir($css_path, 0755);
        }
        if (!is_dir($js_path)) {
            mkdir($js_path, 0755);
        }
        if (!is_dir($pages_path)) {
            mkdir($pages_path, 0755);
        }

        $css_links = $project->css;
        $js_links = $project->js;

        foreach ($css_links as $file) {
            if (! file_exists($css_path . $file->to)) {
                file_put_contents($css_path . $file->to, file_get_contents($file->from));
            }
        }

        foreach ($js_links as $file) {
            if (! file_exists($js_path . $file->to)) {
                file_put_contents($js_path . $file->to, file_get_contents($file->from));
            }
        }

    }

    public static function get_projects()
    {
        // Tilda_Admin::log(__CLASS__."::".__FUNCTION__, __FILE__, __LINE__);

        $projects = get_option('tilda_projects');

        return $projects;
    }

    public static function public_key_field()
    {

        $options = get_option('tilda_options');
        $key = (isset($options['public_key'])) ? $options['public_key'] : '';
        ?>
        <input type="text" id="public_key" name="tilda_options[public_key]" maxlength="100" size="50"
               value="<?= esc_attr($key); ?>"/>
<?php
    }

    public static function secret_key_field()
    {
        $options = get_option('tilda_options');
        $key = (isset($options['secret_key'])) ? $options['secret_key'] : '';
        ?>
        <input type="text" id="secret_key" name="tilda_options[secret_key]" maxlength="100" size="50"
               value="<?= esc_attr($key); ?>"/>
<?php
    }

    public static function options_validate($input)
    {
        return $input;
    }

    private static function validate_required_libs(){
        // Tilda_Admin::log(__CLASS__."::".__FUNCTION__, __FILE__, __LINE__);

        $libs = self::$libs;
        foreach ($libs as $lib_name){
            if(!extension_loaded($lib_name)){
                Tilda::$errors->add( 'no_library',__('Not found library ','tilda').$lib_name);
            }
        }


    }

    public static function display_configuration_page()
    {
//        self::validate_required_libs();

        self::view('configuration');
    }

    public static function switcher_callback($post)
    {
        // Tilda_Admin::log(__CLASS__."::".__FUNCTION__, __FILE__, __LINE__);

        $data = get_post_meta($post->ID, '_tilda', true);

        if (!Tilda::verify_access()){
            Tilda::$errors->add( 'empty_keys',__('The security keys is not set','tilda'));
        }

        self::view('switcher_status', array('data' => $data));
    }

    public static function view($name, array $args = array())
    {
        // Tilda_Admin::log(__CLASS__."::".__FUNCTION__, __FILE__, __LINE__);

        $args = apply_filters('tilda_view_arguments', $args, $name);

        foreach ($args AS $key => $val) {
            $$key = $val;
        }

        $file = TILDA_PLUGIN_DIR . 'views/' . $name . '.php';
        include($file);
    }
    
    static public function log($message, $file=__FILE__, $line=__LINE__)
    {
        if (self::$log_time === null) {
            self::$log_time = date('Y-m-d H:i:s');
        }
        if (!self::$ts_start_plugin) {
            self::$ts_start_plugin = time();
        }
       $sec = time() - self::$ts_start_plugin;
        $f = fopen(Tilda::get_upload_dir() . '/log.txt','a');
        fwrite($f, "[".self::$log_time." - $sec s] ".$message." in [file: $file, line: $line]\n");
        fclose($f);
    }

}