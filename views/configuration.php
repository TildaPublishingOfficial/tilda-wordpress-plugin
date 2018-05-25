<?php
/**
 * Created by Tilda-publishing (tilda.cc).
 * User: ALEX
 * Date: 09.04.15
 * Time: 19:47
 *
 * User: Michael Akimov
 * Date: 2016-02-05
 */
?>

<div class="tilda wrap">
    <h2><?php echo __("Settings",'tilda')?> Tilda.cc API</h2>

    <form id="tilda_options" action="options.php" method="post">
        <?php
        settings_fields('tilda_options');
        do_settings_sections('tilda-config');
        submit_button(__('Save','tilda'), 'primary');

        if (! function_exists('curl_init')) {
            if (ini_get('allow_url_fopen') != 1) {
                echo "<p>".__('Please, install curl library or add option allow_url_fopen=true in php.ini file','tilda').".</p>";
            }
        }

        if (! file_exists(Tilda::get_upload_dir().DIRECTORY_SEPARATOR.'tilda.txt')) {
            Tilda::plugin_activation();
            if (! file_exists(Tilda::get_upload_dir().DIRECTORY_SEPARATOR.'tilda.txt')) {
                echo '<p>'.__('Please, set mode Write for directory','tilda').': '.Tilda::get_upload_dir().'</p>';
            }
        }
        ?>
    </form>

    <?php Tilda::show_errors(); ?>
</div>