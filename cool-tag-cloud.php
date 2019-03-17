<?php
/*
Plugin Name: Tf Tag
Plugin URI: https://github.com/SashokNekulin/tf-tag
Description: A simple, yet very beautiful tag cloud.
Version: 1.0.2
Author: Alexandr Nikulin
Author URI: https://github.com/SashokNekulin
Text Domain: tf-tag
*/ 

class Tf_Tag_Widget extends WP_Widget {

	//defaults
	private $m_defaults = array( 
		'title'         => 'Tags',
		'font-weight'   => 'Normal',
		'font-family'   => 'arial',
		'smallest'      => 16,
		'largest'       => 16,
		'format'        => 'flat',
		'separator'     => '',
		'unit'          => 'px',
		'number'        => 20,
		'orderby'       => 'name',
		'order'         => 'ASC',
		'taxonomy'      => 'post_tag',
		'exclude'       => null,
		'include'       => null,
		'tooltip'       => 'Yes',
		'texttransform' => 'none',
		'nofollow'      => 'No',
		'imagestyle'    => 'ctcdefault',
		'imagealign'    => 'ctcleft',
        'animation'     => 'No',
        'on_single_display' => 'global',
        'show_count' => 'no',
	);

	public function __construct() {
		$l_options = array('description' => __('Tf Tag widget.', 'tf-tag'));
		parent::__construct('Tf_Tag', __('Tf Tags','tf-tag'), $l_options);
	}

    //render tagcloud
	public function widget($p_args, $p_instance) {
		extract($p_args, EXTR_PREFIX_ALL, 'l_args');
		
		if (!empty( $p_instance['title'])) {
			$l_title = $p_instance['title'];
		} else {
			$l_current_tax = get_taxonomy( 
				$this->_get_current_taxonomy($p_instance));
			$l_title = __('Tags','tf-tag');
			$l_title = $l_current_tax->labels->name;
		}
		$l_title = apply_filters('widget_title', $l_title);

		echo $l_args_before_widget;
		echo $l_args_before_title . $l_title . $l_args_after_title;

		$l_tag_params = wp_parse_args($p_instance, $this->m_defaults);
		$l_tag_params["echo"] = 0;
		if ($l_tag_params["tooltip"] == 'No') {add_filter('wp_tag_cloud', 'ctc_remove_title_attributes');};
		if ($l_tag_params["nofollow"] == 'Yes') {add_filter('wp_tag_cloud', 'ctc_nofollow_tag_cloud');};
		if ( $l_tag_params['on_single_display'] == 'local' && is_singular( array( 'post', 'page' ) ) ) {
			$tag_ids = wp_get_post_tags( get_the_ID(), array( 'fields' => 'ids' ) );
			$l_tag_params['include'] = $tag_ids;
		}
		if ( $l_tag_params['show_count'] == 'yes' ) {
			$l_tag_params['show_count'] = true;
		} else {
			$l_tag_params['show_count'] = false;
		}
		$l_tag_cloud_text = wp_tag_cloud( $l_tag_params  );
		if ($l_tag_params["tooltip"] == 'No') {remove_filter('wp_tag_cloud', 'ctc_remove_title_attributes');};
		if ($l_tag_params["nofollow"] == 'Yes') {remove_filter('wp_tag_cloud', 'ctc_nofollow_tag_cloud');};

		echo '<div class="tf-tag">';
		echo $l_tag_cloud_text;
		echo '</div>';
  

		echo $l_args_after_widget;
	}

    //widget setup
	public function form($p_instance) {

		$l_instance = wp_parse_args($p_instance, $this->m_defaults);
		
		echo '<p>';
		echo '<label for="' . $this->get_field_id('title') . '">' .
			__('Title:', 'tf-tag') . '</label>';
		echo '<input class="widefat" id="' . $this->get_field_id('title') .
			'" name="' . $this->get_field_name('title') . '" type="text" ' .
			'value="' . __(esc_attr($l_instance['title']), 'tf-tag') . '" />';
		echo '</p>';

		echo '<p>';
		echo '<label for="' . $this->get_field_id('number') . '">' .
			__('Maximum tags (0 for no limit):', 'tf-tag') . '</label>';
		echo '<input class="widefat" id="' . $this->get_field_id('number') .
			'" name="' . $this->get_field_name('number') . '" type="text" ' .
			'value="' . esc_attr($l_instance['number']) . '" />';
		echo '</p>';

		echo '<p>';
		echo '<label for="' . $this->get_field_id('orderby') . '">' .
			__('Order tags by:', 'tf-tag') . '</label>';
		echo '<select class="widefat" id="' . 
			$this->get_field_id('orderby') .  '" name="' . 
			$this->get_field_name('orderby') . '">';
		echo '<option ' . selected('name', $l_instance['orderby'], false) .
			' value="name">'. __('name', 'tf-tag') . '</option>';
		echo '<option ' . selected('count', $l_instance['orderby'], false) .
			' value="count">'. __('count', 'tf-tag') . '</option>';
		echo '</select>';
		echo '</p>';

		echo '<p>';
		echo '<label for="' . $this->get_field_id('order') . '">' .
			__('Tag order direction:', 'tf-tag') . '</label>';
		echo '<select class="widefat" id="' . $this->get_field_id('order') . 
			'" name="' . $this->get_field_name( 'order' ) . '">';
		echo '<option ' . selected('ASC', $l_instance['order'], false) .
			' value="ASC">'. __('ascending', 'tf-tag') . '</option>';
		echo '<option ' . selected('DESC', $l_instance['order'], false) .
			' value="DESC">'. __('descending', 'tf-tag') . '</option>';
		echo '<option ' . selected('RAND', $l_instance['order'], false) .
			' value="RAND">'. __('random', 'tf-tag') . '</option>';
		echo '</select>';
		echo '</p>';

		$l_current_tax = $this->_get_current_taxonomy($p_instance);
		echo '<p>';
		echo '<label for="' . $this->get_field_id('taxonomy') . '">' .
			__('Taxonomy:', 'tf-tag') . '</label>';
		echo '<select class="widefat" id="' . 
			$this->get_field_id('taxonomy') . '" name="' . 
			$this->get_field_name('taxonomy') . '">';
		foreach(get_taxonomies() as $l_taxonomy) {
			$l_tax = get_taxonomy($l_taxonomy);
			if (!$l_tax->show_tagcloud || empty($l_tax->labels->name)) {
				continue;
			}
			echo '<option ' . selected($l_taxonomy, $l_current_tax, false) .
				' value="' . esc_attr($l_taxonomy) . '">' .
				$l_tax->labels->name . '</option>';
		}
		echo '</select>';
		echo '</p>';
				

		
		echo '<p>';
		echo '<label for="' . $this->get_field_id('nofollow') . '">' .
			__( 'Nofollow for tag links:', 'tf-tag' ) . '</label>';
		echo '<select class="widefat" id="' . $this->get_field_id('nofollow') . 
			'" name="' . $this->get_field_name('nofollow') . '">';
		echo '<option ' . selected('Yes', $l_instance['nofollow'], false) .
			' value="Yes">' . __('Yes', 'tf-tag') .'</option>';
		echo '<option ' . selected('No', $l_instance['nofollow'], false) .
			' value="No">' . __('No', 'tf-tag') .'</option>';
		echo '</select>';
		echo '</p>';

		?>
		<p>
			<label for="<?php $this->get_field_id('show_count'); ?>"><?php esc_html_e( 'Show post counts', 'tf-tag' ); ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id('show_count'); ?>" name="<?php echo $this->get_field_name('show_count'); ?>">
				<option value="yes" <?php selected( 'yes', $l_instance['show_count'] ); ?>><?php esc_html_e( 'Yes', 'tf-tag' ); ?></option>
				<option value="no" <?php selected( 'no', $l_instance['show_count'] ); ?>><?php esc_html_e( 'No', 'tf-tag' ); ?></option>
			</select>
		</p>
		<?php
		
	}

    //update settings
	public function update($p_new_instance, $p_old_instance) {

		$l_instance['title'] = strip_tags(stripslashes($p_new_instance['title']));

		

		if (is_numeric($p_new_instance['number'])) {
			$l_instance['number'] = $p_new_instance['number'] + 0;
		} else {
			$l_instance['number'] = $p_old_instance['number'] + 0;
		}

		if ('name' == $p_new_instance['orderby']) {
			$l_instance['orderby'] = 'name';
		} else if ('count' == $p_new_instance['orderby']) {
			$l_instance['orderby'] = 'count';
		} else {
			$l_instance['orderby'] = $p_old_instance['orderby'];
		}
		
		if ('ASC' == $p_new_instance['order']) {
			$l_instance['order'] = 'ASC';
		} else if ('DESC' == $p_new_instance['order']) {
			$l_instance['order'] = 'DESC';
		} else if ('RAND' == $p_new_instance['order']) {
			$l_instance['order'] = 'RAND';
		} else {
			$l_instance['order'] = $p_old_instance['order'];
		}

		if ("" != get_taxonomy(stripslashes($p_new_instance['taxonomy']))) {
			$l_instance['taxonomy'] = 
				stripslashes($p_new_instance['taxonomy']);
		}
		
		
		if ('Yes' == $p_new_instance['nofollow']) {
			$l_instance['nofollow'] = 'Yes';
		} else if ('No' == $p_new_instance['nofollow']) {
			$l_instance['nofollow'] = 'No';
		} else {
			$l_instance['nofollow'] = $p_old_instance['nofollow'];
		}
        
		$l_instance['show_count'] = sanitize_text_field( $p_new_instance['show_count'] );
		
		return $l_instance;
	}

	//get taxonomy
	function _get_current_taxonomy($p_instance) {
		if (!empty($p_instance['taxonomy']) && 
			 taxonomy_exists($p_instance['taxonomy'])) {
			return $p_instance['taxonomy'];
		}
		return $this->m_defaults['taxonomy'];
	}
}

function ctc_remove_title_attributes($input) {
    return preg_replace('/\s*title\s*=\s*(["\']).*?\1/', '', $input);
}
function ctc_nofollow_tag_cloud($text) {
    return str_replace('<a href=', '<a rel="nofollow" href=',  $text);	
}

function Tf_Tag_register_widget() {
	register_widget( "Tf_Tag_Widget" );
} add_action('widgets_init', 'Tf_Tag_register_widget' );

function Tf_Tag_files() {
	$purl = plugins_url();
	wp_register_style('tf-tag', $purl . '/tf-tag/inc/tf-tag.css',array(), 'v1.0');
	wp_enqueue_style('tf-tag');   
}
add_action('wp_enqueue_scripts', 'Tf_Tag_files');

function Tf_Tag_setup(){
	load_plugin_textdomain( 'tf-tag', false, dirname(plugin_basename(__FILE__)) . '/languages' );
}
add_action('init', 'Tf_Tag_setup');

function Tf_Tag_sc( $atts = array(), $content = false ) {

	$defaults = array( 
		'smallest'  => 16,
		'largest'   => 16,
		'separator'     => '',
		'unit'          => 'px',
		'number'        => 20,
		'orderby'       => 'name',
		'order'         => 'ASC',
		'taxonomy'      => 'post_tag',
		'exclude'       => null,
		'include'       => null,
		'tooltip'       => 'yes',
		'text_transform' => 'none',
		'nofollow'      => 'no',
		'style'    => 'default',
		'align'    => 'left',
        'animation'     => 'no',
        'on_single_display' => 'global',
        'show_count' => 'no',
	);

	ob_start();

		$l_tag_params = wp_parse_args($atts, $defaults);
		$l_tag_params['echo'] = false;
		
		if ( $l_tag_params['show_count'] == 'yes' ) {
			$l_tag_params['show_count'] = true;
		} else {
			$l_tag_params['show_count'] = false;
		}
		$l_tag_cloud_text = wp_tag_cloud( $l_tag_params  );
		if ($l_tag_params["nofollow"] == 'yes') {remove_filter('wp_tag_cloud', 'ctc_nofollow_tag_cloud');};

		echo '<div class="tf-tag">';
			echo '<div class="ctc-tf-tag">';
					echo $l_tag_cloud_text;
			echo '</div>';
		echo '</div>';		

	$output = ob_get_contents();
	ob_end_clean();

	return $output;

} add_shortcode( 'Tf_Tag', 'Tf_Tag_sc' );

if( ! class_exists( 'Smashing_Updater' ) ){
	include_once( plugin_dir_path( __FILE__ ) . 'updater.php' );
}
$updater = new Smashing_Updater( __FILE__ );
$updater->set_username( 'SashokNekulin' );
$updater->set_repository( 'tf-tag' );
/*
	$updater->authorize( 'abcdefghijk1234567890' ); // Your auth code goes here for private repos
*/
$updater->initialize();
