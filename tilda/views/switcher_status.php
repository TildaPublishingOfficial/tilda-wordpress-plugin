<?php
/**
 * Created by PhpStorm.
 * User: ALEX
 * Date: 18.04.15
 * Time: 10:36
 */
$status = isset($data["status"]) && !empty($data["status"]) ? $data["status"] : 'off';

$toggle_class = '';
if (!Tilda::verify_access()) {
    $toggle_class = 'disabled';
}
?>
<? wp_nonce_field('tilda_switcher', 'tilda_nonce'); ?>
<input type="hidden" name="tilda[status]" value="<?= esc_attr($status) ?>"/>
<? Tilda::show_errors(); ?>
<p>
    <input type="submit"
           name="submit"
           id="tilda_toggle"
           class="button <?= $toggle_class ?>"
           value="Включить Тильду для этой страницы">
    &nbsp;&nbsp;&nbsp;
    <? if (!Tilda::verify_access()): ?>
        <a href="options-general.php?page=tilda-config">
            Привязать аккаунт Тильды
        </a>
    <? endif; ?>
</p>

<p>
    <small>Для того, чтобы включилась Тильда, необходимо указать заголовок страницы.</small>
</p>


