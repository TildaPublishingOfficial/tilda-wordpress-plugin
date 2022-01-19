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

<?php
$tilda_options    = get_option( 'tilda_options' );
$enabledposttypes = isset( $tilda_options['enabledposttypes'] ) ? $tilda_options['enabledposttypes'] : array('post','page');
$storageforfiles = isset( $tilda_options['storageforfiles'] ) ? $tilda_options['storageforfiles'] : 'cdn';
/*$locales = array( 'ru_RU' );
foreach( $locales as $tmp_locale ){
	$mo = new MO;
	$mofile = dirname(__FILE__).'/../languages/tilda-'.$tmp_locale.'.mo';
	$mo->import_from_file( $mofile );

	foreach ( $mo->entries as $entry ) {
		$msgid  = $entry->singular;
		$msgstr = $entry->translations[0];
	}
}*/
?>
<script>window.tilda_plugin_url = '<?php echo plugin_dir_url( __DIR__ );?>';</script>
<div class="tilda wrap">

    <?php

    if ( ! function_exists( 'curl_init' ) ) {
	    if ( ini_get( 'allow_url_fopen' ) != 1 ) {
		    echo "<p>" . __( 'Please, install curl library or add option allow_url_fopen=true in php.ini file', 'tilda' ) . ".</p>";
	    }
    }

    if ( ! file_exists( Tilda::get_upload_dir() . DIRECTORY_SEPARATOR . 'tilda.txt' ) ) {
	    Tilda::plugin_activation();
	    if ( ! file_exists( Tilda::get_upload_dir() . DIRECTORY_SEPARATOR . 'tilda.txt' ) ) {
		    echo '<p>' . __( 'Please, set mode Write for directory', 'tilda' ) . ': ' . Tilda::get_upload_dir() . '</p>';
	    }
    }

    Tilda::show_errors();

    ?>

    <div>
        <h1><?php echo __("Settings",'tilda')?> Tilda.cc API</h1>

        <div id="error_tab" class="error notice" style="display: none">
            <p>Errors will be shown here</p>
        </div>

        <div id="success_tab" class="success notice notice-success" style="display: none">
            <p>Success messages will be shown here</p>
        </div>

        <h2 class="mt-40"><?php echo __('Common options', 'tilda'); ?></h2>

        <div id="common-settings-fields">
            <table class="form-table" role="presentation">
                <tbody>
                <tr>
                    <th scope="row"><?php echo __( 'Types of post where show Tilda button', 'tilda' ); ?></th>
                    <td id="enabledposttypes">
                        <input type="checkbox" value="post" <?php if(in_array('post', $enabledposttypes)){ ?>checked="checked"<?php } ?>> <?php echo __( 'Posts', 'tilda' ); ?><br/>
                        <input type="checkbox" value="page" <?php if(in_array('page', $enabledposttypes)){ ?>checked="checked"<?php } ?>> <?php echo __( 'Pages', 'tilda' ); ?><br/>
                        <input type="checkbox" value="attachment" <?php if(in_array('attachment', $enabledposttypes)){ ?>checked="checked"<?php } ?>>  <?php echo __( 'Attachments', 'tilda' ); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo __( 'Storage for images', 'tilda' ); ?></th>
                    <td>
                        <select id="storageforfiles">
                            <option value="cdn" <?php if($storageforfiles==='cdn'){ ?>selected<?php } ?>><?php echo __("Leave images on CDN",'tilda'); ?></option>
                            <option value="local" <?php if($storageforfiles==='local'){ ?>selected<?php } ?>><?php echo __("Download images locally",'tilda'); ?></option>
                        </select>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <h2 class="mt-40"><?php echo __('Keys'); ?></h2>
        <table id="tilda_keys_table" class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th>
                    <?php echo __('Public key', 'tilda'); ?>
                    <span class="tilda-tooltip">
                        <span class="customize-help-toggle dashicons dashicons-editor-help"></span>
                        <span class="tilda-tooltip-text">
                            <?php echo __('You can find Public key at site configuration in section `Export->API Integration`', 'tilda'); ?>
                        </span>
                    </span>
                </th>
                <th>
                    <?php echo __('Secret key', 'tilda'); ?>
                    <span class="tilda-tooltip">
                        <span class="customize-help-toggle dashicons dashicons-editor-help"></span>
                        <span class="tilda-tooltip-text">
                            <?php echo __('You can find Secret key at site configuration in section `Export->API Integration`', 'tilda'); ?>
                        </span>
                    </span>
                </th>
                <th>
	                <?php echo __('Type storage', 'tilda'); ?>
                    <span class="tilda-tooltip">
                        <span class="customize-help-toggle dashicons dashicons-editor-help"></span>
                        <span class="tilda-tooltip-text">
                            <?php echo __('Save only HTML or additionally save text for 3rd party plugins usage (rss, yml, etc)', 'tilda'); ?>
                        </span>
                    </span>
                </th>
                <th>
	                <?php echo __('Apply css styles', 'tilda'); ?>
                    <span class="tilda-tooltip">
                        <span class="customize-help-toggle dashicons dashicons-editor-help"></span>
                        <span class="tilda-tooltip-text"><?php echo __('Enable/disable css styles in posts list', 'tilda'); ?></span>
                    </span>
                </th>
                <th></th>
            </tr>
            </thead>
            <tbody id="tilda_keys_table_body">
            <tr>
                <td colspan="5" align="center">
                    <img width="32" height="32" src="<?php echo plugin_dir_url( __DIR__ );?>images/ajax-loader.gif" alt="Loading" />
                </td>
            </tr>
            </tbody>
            <tfoot>
            <tr id="tilda_add_key_waiting" style="display: none">
                <td colspan="5" align="center">
                    <img width="32" height="32" src="<?php echo plugin_dir_url( __DIR__ );?>images/ajax-loader.gif" alt="Loading" />
                </td>
            </tr>
            <tr id="tilda_add_key_table" class="tilda-hidden">
                <td>
                    <input type="text" size="20" maxlength="100" id="public_key" class="m-0" />
                </td>
                <td>
                    <input type="text" size="20" maxlength="100" id="secret_key" class="m-0" />
                </td>
                <td>
                    <img id="store_html_only" src="https://front.tildacdn.com/feeds/img/t-icon-switcher-on.png" width="40px" data-on="1" />
                </td>
                <td>
                    <img id="apply_css_in_list" src="https://front.tildacdn.com/feeds/img/t-icon-switcher-on.png" width="40px" data-on="1" />
                </td>
                <td>
                    <button id="save_new_key" class="button button-primary"><?php echo __('Save', 'tilda') ?></button>
                </td>
            </tr>
            </tfoot>
        </table>

        <span id="tilda_add_key" class="tilda-dashed-underscore tilda-cursor-pointer"><?php echo __( 'Add API key', 'tilda' ); ?></span>

        <h2 class="mt-40"><?php echo __('Projects', 'tilda'); ?></h2>

        <table id="tilda-projects-table" class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th>
                    <?php echo __('Project', 'tilda'); ?>
                </th>
                <th>
                    <?php echo __('Enable', 'tilda'); ?>
                </th>
            </tr>
            </thead>
            <tbody id="tilda-projects-table-body">
            <tr>
                <td colspan="5" align="center">
                    <img width="32" height="32" src="<?php echo plugin_dir_url( __DIR__ );?>images/ajax-loader.gif" alt="Loading" />
                </td>
            </tr>
            </tbody>
        </table>

        <h2 class="mt-40">Webhook URL</h2>
        <p><?php echo __('Paste this URL on Tilda in Site Settings → Export → Integration API to make changes in Tilda automatically sync with WordPress.'); ?></p>

        <div class="tilda-tooltip">
            <span class="webhook_url_container">
                <input id="webhook_url" type="text" size="65" value="<?php echo get_option('siteurl') ?>/wp-admin/admin-ajax.php?action=tilda_sync_event" readonly/>
                <span class="dashicons dashicons-admin-page"> </span>
            </span>
            <span class="tilda-tooltip-text"><?php echo __('Copy', 'tilda'); ?></span>
        </div>

    </div>

</div>

<?php
wp_footer();