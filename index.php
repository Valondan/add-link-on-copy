<?php
	/*
	Plugin Name: Added Link on Copy
	Plugin URI: https://github.com/Vlandon/add-link-on-copy
	Description: Этот плагин позволяет автоматически добавлять ссылку на сайт при копирование текста
	Version: 0.1
	Author: Vlandon
	Author URI: https://luntik-mir.ml/
	License: GPLv3
	*/

if ( ! class_exists( 'Appendlink' ) ){
class Appendlink {

	private $plugin_url;
	private $plugin_dir;
	private $options;

	function __construct() {
		$this->plugin_url = plugins_url( basename( dirname( __FILE__ ) ) );
		$this->plugin_dir = dirname( __FILE__ );

		$this->options = get_option('append_link_on_copy_options');

		add_action( 'init', array( &$this, 'init') );
		add_action( 'wp', array( &$this, 'load_script' ) );

		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
	}

	function init(){
		$options = get_option( 'append_link_on_copy_options' );
		if( !isset($options['readmore']) ) $options['readmore'] = 'Источник: %link%';
		if( !isset($options['prepend_break']) ) $options['prepend_break'] = 2;
		if( !isset($options['use_title']) ) $options['use_title'] = 'false';
		if( !isset($options['add_site_name']) ) $options['add_site_name'] = 'true';
		if( !isset($options['always_link_site']) ) $options['always_link_site'] = 'false';
		$this->options = $options;
	}

	function load_script() {
		wp_register_script( 'append_link', $this->plugin_url . '/js/append_link.js');
		wp_enqueue_script( 'append_link' );

		global $post;

		/* debugging
		echo '<pre>';
		var_dump( $post );
		echo '</pre>';
		*/

		$options = $this->options;

		$params = 	array(
			  'read_more'			=> $options['readmore']
			, 'prepend_break'		=> $options['prepend_break']
			, 'use_title'			=> $options['use_title']
			, 'add_site_name'		=> $options['add_site_name']
			, 'site_name'			=> get_bloginfo('name')
			, 'site_url'			=> get_bloginfo('url')
			, 'always_link_site'	=> $options['always_link_site']
		);

		if ($options['use_title'] === 'true') {
			if (is_singular()){
				$params['page_title'] = get_the_title($post->ID);
			}
			if (is_home() || is_front_page()){
				$params['page_title'] = get_bloginfo('name');
				$params['add_site_name'] = 'false';
			}
		}


		wp_localize_script( 'append_link', 'append_link', $params );
	}

	function admin_menu() {
		// add_options_page( $page_title, $menu_title, $capability, $menu_slug, $function);
		add_options_page(
			__( 'Настройки добавления ссылки на сайт при копирование', 'append_link_on_copy' )
			, __( 'Настроить добавления ссылки при копирование', 'append_link_on_copy' )
			, 'manage_options'
			, 'append_link_on_copy_options'
			, array(&$this, 'settings_page')
		);
	}

	function admin_init() {

		// register_setting( $option_group, $option_name, $sanitize_callback );
		register_setting(
			  'append_link_on_copy_options'
			, 'append_link_on_copy_options'
			, array( &$this, 'settings_validate' )
		);

		// add_settings_section( $id, $title, $callback, $page );
		add_settings_section(
			'main'
			, 'Основные настройки'
			, array( &$this, 'section_main' )
			, 'append_link_on_copy_options'
		);

		add_settings_section(
			'preview'
			, 'Предварительный просмотр'
			, array( &$this, 'section_preview' )
			, 'append_link_on_copy_options'
		);

		// add_settings_field( $id, $title, $callback, $page, $section, $args );
		add_settings_field(
			'readmore'
			, "Подробнее читайте по ссылке: (например: Текст скопированный с %link% )"
			, array( &$this, 'field_readmore' )
			, 'append_link_on_copy_options'
			, 'main'
		);

		add_settings_field(
			'add_site_name'
			, "Добавить название сайта после ссылки"
			, array( &$this, 'field_add_site_name' )
			, 'append_link_on_copy_options'
			, 'main'
		);

		add_settings_field(
			'use_title'
			, "Добавить заголовок поста при вставке"
			, array( &$this, 'field_use_title' )
			, 'append_link_on_copy_options'
			, 'main'
		);

		add_settings_field(
			'always_link_site'
			, "Всегда указывайте ссылку на основной сайт, а не на страницу/публикацию"
			, array( &$this, 'field_always_link_site' )
			, 'append_link_on_copy_options'
			, 'main'
		);

		add_settings_field(
			'prepend_break'
			, "Сколько тегов &lt;br /&gt; должно быть вставлено перед ссылкой? (По умолчанию стоит: 2)"
			, array( &$this, 'field_prepend_break' )
			, 'append_link_on_copy_options'
			, 'main'
		);
	}

	function section_main() {
		echo __('Измение внешниго вида и содержимого добавляемой ссылки.');
	}

	function section_preview() {
		echo '<b>Примечание:</b> Несмотря на то, что предварительный просмотр текста может не показывать ссылку, многие веб-системы автоматически связывают всё, начиная с https://, а также всё, что скопировано с главной страницы, не будет добавлять заголовок сайта';
		$sample_quote = "Привет, я <a href=\"https://luntik-mir.ml/\">Vlandon</a> и я очень рад что вы используете этот плагин.";
		$sample_page_link = 'https://luntik-mir.ml/?page_id=15/';
		$sample_site_link = 'https://luntik-mir.ml/';
		$sample_site_name = 'Luntik-Mir.ml';


		if ($this->options['always_link_site'] == true) {
			$link = '<a href="' . $sample_site_link . '">';
		}
		else {
			$link = '<a href="' . $sample_page_link . '">';
		}

		if ($this->options['use_title'] == 'true'){
			$link .= 'Append Link on Copy';
		}
		else {
			if ($this->options['always_link_site'] == true){
				$link .= $sample_site_link;
			}
			else {
				$link .= $sample_page_link;
			}
		}

		if ($this->options['add_site_name'] == 'true'){
			$link .= ' | ' . $sample_site_name;
		}

		$link .= '</a>';

		echo '<h4>' . 'Цитируемый текст: </h4>';
		echo "<blockquote>";
		echo $sample_quote;
		echo "</blockquote>";
		echo '<p>ссылка на пример страницы: <b>' . $sample_page_link . '</b></p>';
		echo '<p>ссылка на пример сайта: <b>' . $sample_site_link . '</b></p>';
		echo '<p>ссылка на прмер названия сайта: <b>' . $sample_site_name . '</b></p>';
		echo '<h4>' . 'Предварительный просмотр HTML:' . '</h4>';
		echo "<blockquote>";
		echo $sample_quote;
		for ($i = 0; $i < $this->options['prepend_break']; $i++){
			echo '<br />';
		}

		echo $this->options['readmore'] . ' ' . $link;
		echo "</blockquote>";
		echo '<h4>' . 'Предварительный просмотр текста:' . '</h4>';
		echo "<blockquote>";
		echo strip_tags($sample_quote);
		for ($i = 0; $i < $this->options['prepend_break']; $i++){
			echo '<br />';
		}

		echo $this->options['readmore'] . ' ' . strip_tags($link);
		echo "</blockquote>";
	}

	function field_readmore() {
		echo
			'<input id='
			. 'append_link_on_copy_options[readmore]'
			. '" name="'
			. 'append_link_on_copy_options[readmore]'
			. '" size="40" type="text" value="'
			. $this->options['readmore']
			. '" />';
	}

	function field_prepend_break() {
		echo
			'<input id='
			. 'append_link_on_copy_options[prepend_break]'
			. '" name="'
			. 'append_link_on_copy_options[prepend_break]'
			. '" size="40" type="text" value="'
			. $this->options['prepend_break']
			. '" />';
	}

	function field_add_site_name() {
	echo  '<input type="hidden" name="append_link_on_copy_options[add_site_name]" value="false" />'
		. '<label><input type="checkbox" name="append_link_on_copy_options[add_site_name]" value="true"'
		. ($this->options['add_site_name'] != 'false' ? ' checked="checked"' : '')
		.' />';
	}

	function field_use_title() {
	echo  '<input type="hidden" name="append_link_on_copy_options[use_title]" value="false" />'
		. '<label><input type="checkbox" name="append_link_on_copy_options[use_title]" value="true"'
		. ($this->options['use_title'] != 'false' ? ' checked="checked"' : '')
		.' />';
	}

	function field_always_link_site() {
		echo  '<input type="hidden" name="append_link_on_copy_options[always_link_site]" value="false" />'
		. '<label><input type="checkbox" name="append_link_on_copy_options[always_link_site]" value="true"'
		. ($this->options['always_link_site'] != 'false' ? ' checked="checked"' : '')
		.' />';
	}

    function settings_page()
    {
        require( $this->plugin_dir . '/settings.php' );
    }

    function settings_validate( $input ) {
		$newinput = $input;
		$newinput['readmore'] = strip_tags($input['readmore']);
		$newinput['prepend_break'] = (integer) $input['prepend_break'];
		//$newinput['prepend_break'] = trim($input['prepend_break']);

		return $newinput;
	}


}

$append_link = new Appendlink();

}
