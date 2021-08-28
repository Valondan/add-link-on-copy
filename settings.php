<div class="wrap">
<h2>Настройки добавления ссылки на сайт при копирование</h2>
<p>Эти параметры позволяют настроить добавления ссылки на сайт при копирование текста</p>
<form action="options.php" method="post">
<input name="Submit" type="submit" class="button button-primary action" value="<?php esc_attr_e('Сохранить изменения'); ?>" />
<?php settings_fields('append_link_on_copy_options'); ?>
<?php do_settings_sections('append_link_on_copy_options'); ?>

<input name="Submit" type="submit" class="button button-primary action" value="<?php esc_attr_e('Сохранить изменения'); ?>" />
</form>
</div>