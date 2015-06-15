<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 14.04.15
 * Time: 13:16
 */
$status = isset($data["status"]) && !empty($data["status"]) ? $data["status"] : 'off';
$has_current = isset($data["current_page"]) && !empty($data["current_page"])? true : false;

if ($has_current) {
    ?>
    <div class="current_page">
        <img src="<?= plugins_url('../images/icon_tilda.png', __FILE__) ?>" class="alignleft"/>

        <div class="info">
            <div class="title">Выводится страница:</div>
            <div class="name"><?= $data["current_page"]->title ?></div>
            <? if (isset($data["current_page"]->sync_time)):?>
                <div>
                    <small>
                        <? printf('Синхронизация: %1$s %2$s', mysql2date(get_option('date_format'), $data["current_page"]->sync_time), mysql2date(get_option('time_format'), $data["current_page"]->sync_time)); ?>
                    </small>
                </div>
            <? endif; ?>
        </div>

        <div class="alignright">
            <a href=" https://tilda.cc/page/?pageid=<?= $data["page_id"] ?>" target="_blank" class="button">
                Редактировать
            </a>

            <a href="#" class="button sync">
                Синхронизировать
            </a>

            <input type="hidden" name="tilda[update_page]" id="update_page" value=""/>

            <a href="#" class="button tilda_edit_page">
                Подключить другую
            </a>
        </div>
        <div class="clear"></div>
    </div>
<?
}?>

<div class="tilda_pages_list <? if ($has_current) {echo 'close';}?>">
    <? if ($projects_list) {?>
        <p>Пожалуйста выберите страницу из списка</p>
        <div class="tilda_projects_tabs">
            <div class="form clearfix">
                <ul>
                    <? foreach ($projects_list as $project): ?>
                        <li><a href="#project-<?= $project->id ?>"><?= $project->title ?></a></li>
                    <? endforeach; ?>
                </ul>
                <? foreach ($projects_list as $project): ?>
                    <div id="project-<?= $project->id ?>" data-project-id="<?= $project->id; ?>">
                        <? foreach ($project->pages as $page): ?>
                            <div class="row">
                                <div class="widget">
                                    <input type="radio"
                                           name="tilda[page_id]"
                                           id="tilda_page_<?= $page->id; ?>"
                                           value="<?= $page->id; ?>"
                                        <? if (isset($data["page_id"]) && ($data["page_id"] == $page->id)) {
                                            echo 'checked';
                                        } ?>
                                        >
                                    <label for="tilda_page_<?= $page->id; ?>"><?= $page->title; ?></label>
                                </div>
                            </div>
                        <? endforeach; ?>
                    </div>
                <? endforeach; ?>
            </div>
        </div>
        <div class="clear"></div>

        <p class="desc silver">
            Выбранная страница будет полностью скопирована на ваш сайт. В дальнейшем, если будут внесены какие-то
            изменения в страницу на Тильде, то необходимо будет обновить страницу вручную, нажав кнопку “Обновить
            страницу”.
        </p>
    <? } ?>
    <? Tilda::show_errors(); ?>
    <div class="clear">

        <a href="javascript:void(0)" id="tilda_toggle" class="alignleft remove_tilda">
            Отменить привязку
        </a>

        <p class="submit text-align-right">
            <input type="submit" class="button" value="Сохранить">

            <button type="submit" class="button" name="tilda[update_data]" value="update_data">
                Обновить список
            </button>
        </p>

        <input type="hidden" name="tilda[project_id]" value="<?= $data["project_id"]; ?>"/>
        <input type="hidden" name="tilda[status]" value="<?= esc_attr($status) ?>"/>
    </div>

</div>
<? wp_nonce_field('tilda_switcher', 'tilda_nonce'); ?>