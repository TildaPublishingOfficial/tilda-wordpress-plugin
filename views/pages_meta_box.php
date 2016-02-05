<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 14.04.15
 * Time: 13:16
 * 
 * User: Michael Akimov
 * Date: 2016-02-05
 */

$status = isset($data["status"]) && !empty($data["status"]) ? $data["status"] : 'off';
$has_current = isset($data["current_page"]) && !empty($data["current_page"])? true : false;
if (empty($data['post_id'])) {
    global $post;
    $data['post_id'] = $post->ID;
}
if ($has_current) {
    ?>
    <div class="current_page">
        <img src="<?= plugins_url('../images/icon_tilda.png', __FILE__) ?>" class="alignleft"/>

        <div class="info">
            <div class="title"><?=__("Show page",'tilda')?>:</div>
            <div class="name"><?= $data["current_page"]->title ?></div>
            <?php if (isset($data["current_page"]->sync_time)):?>
                <div>
                    <small>
                        <?php printf(__("Sync in progress",'tilda').': %1$s %2$s', mysql2date(get_option('date_format'), $data["current_page"]->sync_time), mysql2date(get_option('time_format'), $data["current_page"]->sync_time)); ?>
                        <?=(!empty(self::$global_message) ? "<br>\n".self::$global_message : '')?>
                    </small>
                </div>
            <?php endif; ?>
            
            <?php Tilda::show_errors(); ?>
        </div>

        <div class="alignright">
            <a href=" https://tilda.cc/page/?pageid=<?= $data["page_id"] ?>" target="_blank" class="button">
                <?=__("Edit",'tilda')?>
            </a>

            <!-- a href="#" class="button sync">
                Синхронизировать
            </a -->

            <a href="#" class="button" id="ajaxsync" data-pageid="<?=$data['page_id']?>" data-projectid="<?=$data['project_id']?>" data-postid="<?=$data['post_id']?>">
                <?=__("Synchronization",'tilda')?>
            </a>

            <!-- input type="hidden" name="tilda[update_page]" id="update_page" value=""/ -->

            <a href="#" class="button tilda_edit_page">
                <?=__("Get another page",'tilda')?>
            </a>
            
        </div>
        <div class="clear"></div>
    </div>
<?php
}?>

<div id="tilda_block_sync_progress" style="display: none;">
    <span class="tilda_sync_label"><?=__("Sync in progress",'tilda')?></span>
    <div id="tilda_progress_bar">
    </div>
    <div class="clear"></div>
</div>
<div class="tilda_pages_list <?php if ($has_current) {echo 'close';}?>">
    <?php if ($projects_list) {?>
        <p><?=__("Please, select page from list",'tilda')?></p>
        <div class="tilda_projects_tabs" id="js_tilda_projects_tabs">
            <div class="form clearfix">
                <ul>
                    <?php foreach ($projects_list as $project): ?>
                        <li><a href="#project-<?= $project->id ?>"><?= $project->title ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <?php foreach ($projects_list as $project): ?>
                    <div id="project-<?= $project->id ?>" data-project-id="<?= $project->id; ?>" style="overflow: auto;">
                        <?php foreach ($project->pages as $page): ?>
                            <div class="row">
                                <div class="widget">
                                    <input type="radio"
                                           name="tilda[page_id]"
                                           id="tilda_page_<?= $page->id; ?>"
                                           value="<?= $page->id; ?>"
                                        <?php if (isset($data["page_id"]) && ($data["page_id"] == $page->id)) {
                                            echo 'checked';
                                        } ?>
                                        >
                                    <label for="tilda_page_<?= $page->id; ?>"><?= $page->title; ?></label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="clear"></div>

        <p class="desc silver">
            <?=__("Selected page will be copied to your site. Further if you change something on the page on Tilda, you will need to update the page manually by clicking “Synchronization”",'tilda')?>
        </p>
    <?php } ?>
    <?php Tilda::show_errors(); ?>
    <div class="clear">

        <a href="javascript:void(0)" id="tilda_toggle" class="alignleft remove_tilda">
            <?=__("Cancel connect",'tilda')?>
        </a>

        <p class="submit text-align-right">
            <input type="submit" class="button" value="<?=__('Save','tilda')?>" id="tilda_save_page" data-postid="<?=$data['post_id']?>">

            <button type="submit" class="button" name="tilda[update_data]" value="update_data">
                <?=__("Refresh list",'tilda')?>
            </button>
        </p>

        <input type="hidden" name="tilda[project_id]" value="<?= $data["project_id"]; ?>"/>
        <input type="hidden" name="tilda[status]" value="<?= esc_attr($status) ?>"/>
    </div>

</div>
<?php wp_nonce_field('tilda_switcher', 'tilda_nonce'); ?>