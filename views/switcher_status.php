<?php
/**
 * User: ALEX
 * Date: 18.04.15
 * Time: 10:36
 *
 * User: Michael Akimov
 * Date: 2016-02-05
 */

global $post;

$status = isset($data["status"]) && !empty($data["status"]) ? $data["status"] : 'off';

$toggle_class = '';
if (!Tilda::verify_access()) {
    $toggle_class = 'disabled';
}
$is_new_post = empty($post->post_title);
if (empty($data['post_id'])) {
    global $post;
    $data['post_id'] = $post->ID;
}

wp_nonce_field('tilda_switcher', 'tilda_nonce');
?>
<div class="tilda wrap">
    <input type="hidden" name="tilda[status]" value="<?php echo  esc_attr($status) ?>"/>

    <?php Tilda::show_errors(); ?>

    <p>
        <?php if ($is_new_post) { ?>
            <small><?php echo __("Input title and click Save for activate Tilda for this page",'tilda')?>
            </small>
        <?php } else { ?>
            <input type="submit"
                   id="tilda_toggle"
                   class="button <?php echo  esc_attr($toggle_class) ?>"
                   value="<?php echo __("Activate Tilda for this page?",'tilda')?>">
            &nbsp;&nbsp;&nbsp;
            <?php if (!Tilda::verify_access()): ?>
                <a href="options-general.php?page=tilda-config">
                    <?php echo __("Tilda accounts assign",'tilda')?>
                </a>
            <?php endif; ?>
        <?php } ?>
    </p>
</div>