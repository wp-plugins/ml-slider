<?php 
/**
 * Adds Meta Slider widget.
 */
class MetaSlider_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
	 		'metaslider_widget', // Base ID
			'Meta Slider', // Name
			array( 'description' => __( 'Meta Slider', 'metaslider' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		extract($args);

		if (isset($instance['slider_id'])) {
			$slider_id = $instance['slider_id'];

			echo $before_widget;
			echo do_shortcode("[metaslider id={$slider_id}]");
			echo $after_widget;
		}
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['slider_id'] = strip_tags( $new_instance['slider_id'] );

		return $instance;
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$selected_slider = 0;
		$sliders = false;

		if (isset($instance['slider_id'])) {
			$selected_slider = $instance['slider_id'];
		}

        // list the tabs
        $args = array(
            'post_type' => 'ml-slider',
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'ASC',
            'posts_per_page' => -1
        );
        
        $the_query = new WP_Query($args);
        
        while ($the_query->have_posts()) {
            $the_query->the_post();
            $active = $selected_slider == $the_query->post->ID ? true : false;
            
            $sliders[] = array(
                'active' => $active,
                'title' => get_the_title(),
                'id' => $the_query->post->ID
            );
        }
        
		?>
		<p>
			<?php if ($sliders) { ?>
				<label for="<?php echo $this->get_field_id('slider_id'); ?>"><?php _e('Select Slider:', 'metaslider'); ?></label> 
				<select id="<?php echo $this->get_field_id('slider_id'); ?>" name="<?php echo $this->get_field_name('slider_id'); ?>">
					<?php
						foreach ($sliders as $slider) {
							$selected = $slider['active'] ? 'selected=selected' : '';
							echo "<option value='{$slider['id']}' {$selected}>{$slider['title']}</option>"; 
						}
					?>
				</select>
			<?php } else {
				_e('No slideshows found', 'metaslider');
			} ?>
		</p>
		<?php 
	}
}

add_action('widgets_init', 'register_metaslider_widget');  

function register_metaslider_widget() {  
    register_widget('MetaSlider_Widget'); 
}
?>