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
        ?>
    </form>

    <?php Tilda::show_errors(); ?>
</div>