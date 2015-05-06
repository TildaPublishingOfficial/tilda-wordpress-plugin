<?php
/**
 * Created by PhpStorm.
 * User: ALEX
 * Date: 09.04.15
 * Time: 19:47
 */
?>

<div class="wrap">
    <h2>Настройка Tilda.cc API</h2>

    <form id="tilda_options" action="options.php" method="post">
        <?php
        settings_fields('tilda_options');
        do_settings_sections('tilda-config');
        submit_button('Сохранить', 'primary');
        ?>
    </form>
</div>