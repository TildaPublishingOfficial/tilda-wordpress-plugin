<?php

/**
 * Created by PhpStorm.
 * User: ALEX
 * Date: 09.04.15
 * Time: 19:21
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
            mkdir($upload_dir, 0755);
        }
        return $upload_dir;
    }

    public static function show_errors()
    {
        

        $errors = self::$errors->get_error_messages();
        echo '<ul class="errors">';
        foreach ($errors as $error) {
            echo '<li class="error silver" style="color:#9F9F9F;"><span class="red" style="color:#C60000">Ошибка:</span> ' . $error . '</li>';
        }
        echo '</ul>';
    }

    public static function get_upload_path()
    {
        

        $upload = wp_upload_dir();
        $upload_dir = $upload['baseurl'];
        $upload_dir = $upload_dir . '/tilda/';
        return $upload_dir;
    }

    public static function plugin_activation()
    {
        

        $upload_dir = self::get_upload_dir();

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755);
        }
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
        //add_filter('sidebars_widgets', array('Tilda', 'sidebar_widgets'));
    }

    
    private static function load_textdomain() {
        

        load_plugin_textdomain('tilda', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    public static function enqueue_scripts()
    {
        global $post;

        $data = get_post_meta($post->ID, '_tilda', true);

        if(isset($data['status']) && $data['status'] == 'on') { 
            Tilda::$active_on_page = true;
        } else {
            Tilda::$active_on_page = false;
        }
        
        
        if (isset($data) && isset($data["status"]) && $data["status"] == 'on') {
            $page = self::get_local_page($data["page_id"],$data["project_id"]);

            $css_links = $page->css;
            $js_links = $page->js;

            foreach ($css_links as $file) {
                $name = basename($file);
                wp_enqueue_style($name, $file);
            }

            foreach ($js_links as $file) {
                $name = basename($file);
                wp_enqueue_script($name, $file);
            }
        }

    }

    public static function  sidebars_widgets($sidebars_widgets)
    {
        var_dump($sidebars_widgets);
        return '';
    }
    
    public static function the_content($content)
    {
        //return $content;

        global $post;

        $data = get_post_meta($post->ID, '_tilda', true);

        if(isset($data['status']) && $data['status'] == 'on') { 
            Tilda::$active_on_page = true;
        } else {
            Tilda::$active_on_page = false;
        }

        if (isset($data) && isset($data["status"]) && $data["status"] == 'on') {
            $page = self::get_local_page($data["page_id"],$data["project_id"]);
            return $page->html;
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
                $type .= 'export';
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

        if ($curl = curl_init()) {
            $url = TILDA_API_URL . '/' . $type . '/?publickey=' . TILDA_PUBLIC_KEY . '&secretkey=' . TILDA_SECRET_KEY . $suffix;
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $out = curl_exec($curl);
            curl_close($curl);
        }

        $out = json_decode($out);
        if ($out->status == 'FOUND'){
            return $out->result;
        }else{
           self::$errors->add( $code, __($out->message, 'tilda').' query: '.$suffix );
           return self::$errors;
        }
    }

    public static function get_projects()
    {
        

        return self::get_from_api('projectslist');

    }

    public static function get_project($id)
    {
        

        return self::get_from_api('project', $id);

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

    public static function get_local_projects()
    {
        

        $projects = get_option('tilda_projects');

        return $projects;
    }

    public static function get_local_page($page_id, $project_id)
    {
        

        $projects = self::get_local_projects();

        $page = $projects[$project_id]->pages[$page_id];
        $upload_path = Tilda::get_upload_path() . $project_id . '/';

        $ar = array();
        foreach($projects[$project_id]->css as $css) {
            $ar[] = $upload_path . 'css/'.$css->to;
        }
        $page->css = $ar;

        $ar = array();
        foreach($projects[$project_id]->js as $js) {
            $ar[] = $upload_path . 'js/' . $js->to;
        }
        $page->js = $ar;
        return $page;
    }

}