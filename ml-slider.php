<?php
/*
 * Plugin Name: ML Slider
 * Plugin URI: http://www.ml-slider.com
 * Description: 4 sliders in 1! Choose from NivoSlider, FlexSlider, CoinSlider or Responsive Slides.
 * Version: 1.0.1
 * Author: Matcha Labs
 * Author URI: http://www.matchalabs.com
 * License: GPL
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
*/

define( 'MLSLIDER_VERSION', '1.0.1' );
define( 'MLSLIDER_BASE_URL', plugin_dir_url( __FILE__ ) );
define( 'MLSLIDER_ASSETS_URL', MLSLIDER_BASE_URL . 'assets/' );

class MLSlider {

	var $current_slide = 0;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_action('admin_menu', array($this, 'register_admin_menu'), 10001);
		add_action('admin_menu', array($this, 'process'), 10002);
		add_action('init', array($this, 'register_post_type' ));
        add_action('init', array($this, 'register_taxonomy' ));
		add_action('admin_print_styles', array( $this, 'register_admin_styles'));
		add_shortcode('ml-slider', array($this, 'register_shortcode'));
	}

	/**
	 * Current slide ID
	 */
	private function set_current_slide($id) {
		$this->current_slide = $id;
	}

	/**
	 * Return the current slide ID
	 */
	private function get_current_slide() {
		return $this->current_slide;
	}

    /**
     * Handle slide uploads/changes
     */
    public function process() {
        $current_slide = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
        $this->set_current_slide($current_slide);
        
        $settings = get_post_meta($this->get_current_slide(), 'ml-slider_settings', true);
        
        // handle slide description, url and ordering
        if (isset($_POST['attachment'])) {
            foreach ($_POST['attachment'] as $id => $fields) {
                $term = get_term_by('name', $current_slide, 'ml-slider');
                wp_set_post_terms($id, $term->term_id, 'ml-slider', false);

                $slide = array(
                    'ID' => $id,
                    'post_excerpt' => $fields['post_excerpt'],
                    'menu_order' => $fields['menu_order']
                );
                
                wp_update_post($slide);
                
                // store the URL as a meta field against the attachment
                if (get_post_meta($id, 'ml-slider_url')) {
                    if ($fields['url'] == '') {
                        delete_post_meta($id, 'ml-slider_url');
                    } else {
                        update_post_meta($id, 'ml-slider_url', $fields['url']);
                    }
                } else {
                    add_post_meta($id, 'ml-slider_url', $fields['url'], true);
                }

                // resize slide
                add_image_size('ml-slider-slide', $settings['width'], $settings['height'], true);
                $file = get_attached_file($id);
                wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $file));
            }
        }
        
        // delete slider
        if (isset($_GET['delete'])) {
            $slide = array(
                'ID' => intVal($_GET['delete']),
                'post_status' => 'trash'
            );
            
            wp_update_post($slide);
            
            $this->set_current_slide(0);
        }
        
        // delete slide
        if (isset($_GET['deleteSlide'])) {
            $slideToUntagFromCurrentSlider = $_GET['deleteSlide'];

            // Get the existing terms and only keep the ones we don't want removed
            $new_terms = array();
            $current_terms = wp_get_object_terms($slideToUntagFromCurrentSlider, 'ml-slider', array('fields' => 'ids'));
            $term = get_term_by('name', $this->get_current_slide(), 'ml-slider');

            foreach ($current_terms as $current_term) {
                if ($current_term != $term->term_id) {
                    $new_terms[] = intval($current_term);
                }
            }
         
            return wp_set_object_terms($slideToUntagFromCurrentSlider, $new_terms, 'ml-slider');
        }
        
        // update title
        if (isset($_POST['title'])) {
            $slide = array(
                'ID' => $this->get_current_slide(),
                'post_title' => $_POST['title']
            );
            
            wp_update_post($slide);
        }
        
        // update options
        if (isset($_POST['settings'])) {
            $old_settings = get_post_meta($this->get_current_slide(), 'ml-slider_settings', true);
            $new_settings = $_POST['settings'];
            
            // convert checkbox values
            $new_settings['hoverPause'] = (isset($new_settings['hoverPause']) && $new_settings['hoverPause'] == 'on') ? 'true' : 'false';
            $new_settings['links']      = (isset($new_settings['links']) && $new_settings['links'] == 'on') ? 'true' : 'false';
            $new_settings['navigation'] = (isset($new_settings['navigation']) && $new_settings['navigation'] == 'on') == 'on' ? 'true' : 'false';
            $new_settings['reverse']    = (isset($new_settings['reverse']) && $new_settings['reverse'] == 'on') == 'on' ? 'true' : 'false';
            $new_settings['random']     = (isset($new_settings['random']) && $new_settings['random'] == 'on') == 'on' ? 'true' : 'false';
            $new_settings['printCss']   = (isset($new_settings['printCss']) && $new_settings['printCss'] == 'on') == 'on' ? 'true' : 'false';
            $new_settings['printJs']    = (isset($new_settings['printJs']) && $new_settings['printJs'] == 'on') == 'on' ? 'true' : 'false';

            
            update_post_meta($this->get_current_slide(), 'ml-slider_settings', array_merge($old_settings, $new_settings));
            $settings = get_post_meta($this->get_current_slide(), 'ml-slider_settings', true);
            
            // has the slideshow been resized?
            if ($old_settings['width'] != $settings['width'] || $old_settings['height'] != $settings['height']) {
                // resize all slides - register a new image size
                add_image_size('ml-slider-slide', $settings['width'], $settings['height'], true);
                
                $args = array(
                    'post_type' => 'attachment',
                    'numberposts' => -1,
                    'post_status' => null,
                    'post_parent' => $this->get_current_slide(),
                    'post_mime_type' => 'image'
                );
                
                $attachments = get_posts($args);
                
                if ($attachments) {
                    foreach ($attachments as $post) {
                        $file = get_attached_file($post->ID);
                        wp_update_attachment_metadata($post->ID, wp_generate_attachment_metadata($post->ID, $file));
                    }
                }
            }
        }
        
        // create a new slider
        if (isset($_GET['add'])) {
            $slide = array(
                'post_title' => 'New Slider',
                'post_status' => 'publish',
                'post_type' => 'ml-slider'
            );
            
            // insert the post
            $id = wp_insert_post($slide);
            $this->set_current_slide($id);
            
            // default settings
            $defaults = array(
                'type' => 'nivo',
                'height' => 290,
                'width' => 565,
                'spw' => 7, // squares per width
                'sph' => 5, // squares per height
                'delay' => 3000,
                'sDelay' => 30, // delay between squares
                'opacity' => 0.7, // opacity of title and navigation
                'titleSpeed' => 500, // speed of title appereance in ms
                'effect' => 'random', // random, swirl, rain, straight
                'navigation' => 'true', // prev next and buttons
                'links' => 'true', // show images as links
                'hoverPause' => 'true', // pause on hover
                'theme' => 'dark',
                'direction' => 'horizontal',
                'reverse' => 'false',
                'animationSpeed' => 600,
                'prevText' => 'Previous',
                'nextText' => 'Next',
                'slices' => 15,
                'random' => 'false',
                'cssClass' => '',
                'printCss' => 'true',
                'printJs' => 'true'
            );
            
            // insert the post meta
            add_post_meta($this->get_current_slide(), 'ml-slider_settings', $defaults, true);
            $settings = get_post_meta($this->get_current_slide(), 'ml-slider_settings', true);

            // create the taxonomy term, the term is the ID of the slider itself
            wp_insert_term($this->get_current_slide(), 'ml-slider');
        }
    }
    

    /**
     * Registers and enqueues admin-specific styles.
     */
    public function register_admin_styles() {
        wp_enqueue_style('ml-slider-tipsy-styles', plugins_url('ml-slider/assets/tipsy/tipsy.css'));
        wp_enqueue_style('ml-slider-admin-styles', plugins_url('ml-slider/assets/ml-slider-admin.css'));
    }
    
    /**
     * Registers and enqueues admin-specific JavaScript.
     */
    public function register_admin_scripts() {
        wp_enqueue_media();
        wp_enqueue_script('ml-slider-tipsy', plugins_url('ml-slider/assets/tipsy/jquery.tipsy.js'), array('jquery'));
        wp_enqueue_script('jquery-tablednd', plugins_url('ml-slider/assets/jquery.tablednd.js'), array('jquery'));
        wp_enqueue_script('ml-slider-admin-script', plugins_url('ml-slider/assets/ml-slider.js'), array('jquery', 'ml-slider-tipsy', 'jquery-tablednd', 'media-upload'));
    }
    
    /**
     * Include the default CSS
     */
    public function enqueue_scripts() {
        wp_enqueue_style('ml-slider_display_css', plugins_url('ml-slider/assets/ml-slider-display.css'));
    }
    
    /**
     * Add the menu page
     */
    public function register_admin_menu() {
        $page = add_menu_page('ML Slider', 'ML Slider', 'add_users', 'ml-slider', array(
            $this,
            'render_admin_page'
        ), MLSLIDER_ASSETS_URL . 'matchalabs.png', 99999);

        add_action('admin_print_scripts-' . $page, array( $this, 'register_admin_scripts' ) );

        
    }
    
    /**
     * Create ML Slider post type
     */
    public function register_post_type() {
        $post_type_args = array(
            'singular_label' => __('Slider'),
            'public' => false,
            'show_ui' => false,
            'publicly_queryable' => false,
            'query_var' => true,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'rewrite' => false
        );
        register_post_type('ml-slider', $post_type_args);
    }

    /**
     * Create taxonomy to store slider => slides relationship
     */
    public function register_taxonomy() {
          $labels = array(
            'name' => _x( 'Slider', 'taxonomy general name' ),
            'singular_name' => _x( 'Slider', 'taxonomy singular name' ),
            'menu_name' => __( 'Slider' )
          );

          $args = array(
            'hierarchical' => true,
            'labels' => $labels,
            'show_ui' => false,
            'show_admin_column' => true,
            'query_var' => false,
            'rewrite' => array( 'slug' => 'ml-slider' )
          );

          register_taxonomy( 'ml-slider', 'attachment', $args );
    }
    
    /**
     * Return slides for the current slider
     */
    public function getSlides($random = false) {
        $retVal = array();

        $args = array(
            'orderby' => $random == 'true' ? 'rand' : 'menu_order',
            'order' => 'ASC',
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'tax_query' => array(
                array(
                    'taxonomy' => 'ml-slider',
                    'field' => 'slug',
                    'terms' => $this->get_current_slide()
                )
            )
        );


        $query = new WP_Query($args);

        while($query->have_posts()) {
            $query->next_post();

            $image_attributes = wp_get_attachment_image_src($query->post->ID, 'ml-slider-slide'); // returns an array

            $retVal[] = array(
                'id' => $query->post->ID,
                'type' => 'image',
                'url' => get_post_meta($query->post->ID, 'ml-slider_url', true),
                'caption' => $query->post->post_excerpt,
                'src' => $image_attributes[0],
                'alt' => get_post_meta($query->post->ID, '_wp_attachment_image_alt', true),
                'menu_order' => $query->post->menu_order
            );
        }

        return $retVal;
    }
    
    /**
     * Return coinslider markup
     */
    public function getCoinSlider($slides, $identifier, $settings) {
        $retVal = "<div id='" . $identifier . "' class='coin-slider'>";
        
        foreach ($slides as $slide) {
            $url = strlen($slide['url']) ? $slide['url'] : "javascript:void(0)"; // coinslider always wants a URL
            $retVal .= "<a href='{$url}'>";
            $retVal .= "<img src='{$slide['src']}' alt='{$slide['alt']}'>";
            $retVal .= "<span>{$slide['caption']}</span>";
            $retVal .= "</a>";
        }
        
        $retVal .= "</div>";
        
        $retVal .= "<script type='text/javascript'>";
        $retVal .= "\njQuery(document).ready(function($) {";
        $retVal .= "\n  $('#" . $identifier . "').coinslider({";
        $retVal .= "\n        effect: '{$settings['effect']}',";
        $retVal .= "\n        width: '{$settings['width']}',";
        $retVal .= "\n        height: '{$settings['height']}',";
        $retVal .= "\n        spw: '{$settings['spw']}',";
        $retVal .= "\n        sph: '{$settings['sph']}',";
        $retVal .= "\n        delay: '{$settings['delay']}',";
        $retVal .= "\n        sDelay: '{$settings['sDelay']}',";
        $retVal .= "\n        opacity: '{$settings['opacity']}',";
        $retVal .= "\n        titleSpeed: '{$settings['titleSpeed']}',";
        $retVal .= "\n        navigation: {$settings['navigation']},";
        $retVal .= "\n        hoverPause: {$settings['hoverPause']}";
        $retVal .= "\n  });";
        $retVal .= "\n});";
        $retVal .= "</script>";
        
        return $retVal;
    }
    
    /**
     * Return flexslider markup
     */
    public function getFlexSlider($slides, $identifier, $settings) {
        $retVal = "<div id='" . $identifier . "' class='flexslider'><ul class='slides'>";
        
        foreach ($slides as $slide) {
            $retVal .= "<li>";
            if (strlen($slide['url'])) $retVal .= "<a href='{$slide['url']}'>";
            $retVal .= "<img src='{$slide['src']}' alt='{$slide['alt']}'>";
            if (strlen($slide['caption'])) $retVal .= "<p class='flex-caption'>{$slide['caption']}</p>";
            if (strlen($slide['url'])) $retVal .= "</a>";
            $retVal .= "</li>";
        }
        
        $retVal .= "</ul></div>";
        
        $retVal .= "<script type='text/javascript'>";
        $retVal .= "\njQuery(document).ready(function($) {";
        $retVal .= "\n  $('#" . $identifier . "').flexslider({";
        $retVal .= "\n        animation: '{$settings['effect']}',";
        $retVal .= "\n        direction: '{$settings['direction']}',";
        $retVal .= "\n        reverse: {$settings['reverse']},";
        $retVal .= "\n        slideshowSpeed: {$settings['delay']},";
        $retVal .= "\n        pauseOnHover: {$settings['hoverPause']},";
        $retVal .= "\n        animationSpeed: {$settings['animationSpeed']},";
        $retVal .= "\n        controlNav: {$settings['navigation']},";
        $retVal .= "\n        directionNav: {$settings['links']},";
        $retVal .= "\n        prevText: '{$settings['prevText']}',";
        $retVal .= "\n        nextText: '{$settings['nextText']}',";
        $retVal .= "\n  });";
        $retVal .= "\n});";
        $retVal .= "</script>";
        
        return $retVal;
    }
    
    /**
     * Return responsive slides markup
     */
    public function getResponsiveSlider($slides, $identifier, $settings) {
        $retVal = "<ul id='" . $identifier . "' class='rslides'>";
        
        foreach ($slides as $slide) {
            $retVal .= "<li>";
            if (strlen($slide['url'])) $retVal .= "<a href='{$slide['url']}'>";
            $retVal .= "<img src='{$slide['src']}' alt='{$slide['alt']}'>";
            if (strlen($slide['url'])) $retVal .= "</a>";
            $retVal .= "</li>";
        }
        
        $retVal .= "</ul>";
        
        $retVal .= "<script type='text/javascript'>";
        $retVal .= "\njQuery(document).ready(function($) {";
        $retVal .= "\n  $('#" . $identifier . "').responsiveSlides({";
        $retVal .= "\n        timeout: {$settings['delay']},";
        $retVal .= "\n        pause: {$settings['hoverPause']},";
        $retVal .= "\n        pauseControls: {$settings['hoverPause']},";
        $retVal .= "\n        speed: {$settings['animationSpeed']},";
        $retVal .= "\n        pager: {$settings['navigation']},";
        $retVal .= "\n        nav: {$settings['links']},";
        $retVal .= "\n        prevText: '{$settings['prevText']}',";
        $retVal .= "\n        nextText: '{$settings['nextText']}',";
        $retVal .= "\n  });";
        $retVal .= "\n});";
        $retVal .= "</script>";
        
        return $retVal;
    }
    
    /**
     * Return nivoslider markup
     */
    public function getNivoSlider($slides, $identifier, $settings) {
        $retVal  = "<div class='slider-wrapper theme-{$settings['theme']}'>";
        $retVal .= "<div class='ribbon'></div>";
        $retVal .= "<div id='" . $identifier . "' class='nivoSlider'>";
        
        foreach ($slides as $slide) {
            if (strlen($slide['url'])) $retVal .= "<a href='{$slide['url']}'>";
            $retVal .= "<img src='{$slide['src']}' title='{$slide['caption']}' alt='{$slide['alt']}'>";
            if (strlen($slide['url'])) $retVal .= "</a>";
        }
        
        $retVal .= "</div></div>";
        
        $retVal .= "<script type='text/javascript'>";
        $retVal .= "\njQuery(document).ready(function($) {";
        $retVal .= "\n  $('#" . $identifier . "').nivoSlider({";
        $retVal .= "\n        effect: '{$settings['effect']}',";
        $retVal .= "\n        slices: {$settings['slices']},";
        $retVal .= "\n        pauseTime: {$settings['delay']},";
        $retVal .= "\n        animSpeed: {$settings['animationSpeed']},";
        $retVal .= "\n        pauseOnHover: {$settings['hoverPause']},";
        $retVal .= "\n        boxCols: {$settings['spw']},";
        $retVal .= "\n        boxRows: {$settings['sph']},";
        $retVal .= "\n        controlNav: {$settings['navigation']},";
        $retVal .= "\n        directionNav: {$settings['links']},";
        $retVal .= "\n        prevText: '{$settings['prevText']}',";
        $retVal .= "\n        nextText: '{$settings['nextText']}',";
        $retVal .= "\n  });";
        $retVal .= "\n});";
        $retVal .= "</script>";
        
        return $retVal;
    }
    
    /**
     * Shortcode used to display slideshow
     */
    public function register_shortcode($atts) {
        extract(shortcode_atts(array(
            'id' => null
        ), $atts));
        
        if ($id != null) {
            $slider = get_post($id);
            
            if ($slider->post_status != 'publish') {
                return false;
            }

            $this->set_current_slide($id);
            
            $settings   = get_post_meta($id, 'ml-slider_settings', true);
            $identifier = 'ml-slider-' . $settings['type'] . '-' . rand();
            $slides     = $this->getSlides($settings['random']);
            
            if ($settings['type'] == 'coin') {
            	if ($settings['printJs'] == 'true') {
                	wp_enqueue_script('ml-slider_coin_slider', MLSLIDER_ASSETS_URL . 'coinslider/coin-slider.min.js', array('jquery'), MLSLIDER_VERSION);
                }

            	if ($settings['printCss'] == 'true') {
 					wp_enqueue_style('ml-slider_coin_slider_css', plugins_url('ml-slider/assets/coinslider/coin-slider-styles.css'));
                }

                $retVal = $this->getCoinSlider($slides, $identifier, $settings);
            }
            
            if ($settings['type'] == 'flex') {
            	if ($settings['printJs'] == 'true') {
                	wp_enqueue_script('ml-slider_flex_slider', MLSLIDER_ASSETS_URL . 'flexslider/jquery.flexslider-min.js', array('jquery'), MLSLIDER_VERSION);
                }

            	if ($settings['printCss'] == 'true') {
 					wp_enqueue_style('ml-slider_flex_slider_css', plugins_url('ml-slider/assets/flexslider/flexslider.css'));
                }

                $retVal = $this->getFlexSlider($slides, $identifier, $settings);
            }
            
            if ($settings['type'] == 'responsive') {
            	if ($settings['printJs'] == 'true') {
                	wp_enqueue_script('ml-slider_responsive_slides', MLSLIDER_ASSETS_URL . 'responsiveslides/responsiveslides.min.js', array('jquery'), MLSLIDER_VERSION);
                }

            	if ($settings['printCss'] == 'true') {
 					wp_enqueue_style('ml-slider_responsive_slides_css', plugins_url('ml-slider/assets/responsiveslides/responsiveslides.css'));
                }

                $retVal = $this->getResponsiveSlider($slides, $identifier, $settings);
            }
            
            if ($settings['type'] == 'nivo') {
            	if ($settings['printJs'] == 'true') {
            		wp_enqueue_script('ml-slider_nivo_slider', MLSLIDER_ASSETS_URL . 'nivoslider/jquery.nivo.slider.pack.js', array('jquery'), MLSLIDER_VERSION);
            	}

            	if ($settings['printCss'] == 'true') {
 					wp_enqueue_style('ml-slider_nivo_slider_css', plugins_url('ml-slider/assets/nivoslider/nivo-slider.css'));
                	wp_enqueue_style('ml-slider_nivo_slider_theme_' . $settings['theme'], plugins_url('ml-slider/assets/nivoslider/themes/' . $settings['theme'] . '/' . $settings['theme'] . '.css'));
            	}
               	
               	$retVal = $this->getNivoSlider($slides, $identifier, $settings);
            }
            
            return "<div class='ml-slider ml-slider-{$settings['type']} {$settings['cssClass']}'>" . $retVal . "</div>";
        }
        
        return false;
    }
    
    /**
     * Returns an array of all the published slide shows
     */
    private function getTabs() {
        $tabs = false;
        
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
            if (!$this->get_current_slide()) {
                $this->set_current_slide($the_query->post->ID);
            }
            
            $the_query->the_post();
            $active = $this->get_current_slide() == $the_query->post->ID ? true : false;
            
            $tabs[] = array(
                'active' => $active,
                'title' => get_the_title(),
                'id' => $the_query->post->ID
            );
        }
        
        return $tabs;
    }

	/**
	 * Render the admin page (tabs, slides, settings)
	 */
	public function render_admin_page() {
		$tabs = $this->getTabs();
		$settings = get_post_meta($this->get_current_slide(), 'ml-slider_settings', true);

		?>

		<div class="wrap ml-slider">
			<form enctype="multipart/form-data" action="?page=ml-slider&id=<?php echo $this->get_current_slide() ?>" method="post">

				<h2 class="nav-tab-wrapper" style="font-size: 13px;">
					<?php
						if($tabs) {
							foreach ($tabs as $tab) {
								if ($tab['active']) {
									echo "<div class='nav-tab nav-tab-active' style='font-size: 13px;'><input type='text' name='title' 	value='" . $tab['title'] . "' style='padding: 0; margin: 0; border: 0; width: 100px; font-size: 14px' onkeypress='this.style.width = ((this.value.length + 1) * 9) + \"px\"' /></div>";
								} else {
									echo "<a href='?page=ml-slider&id={$tab['id']}' class='nav-tab' style='font-size: 13px;'>" . $tab['title'] . "</a>";
								}
							}							
						}
					?>
					
					<a href="?page=ml-slider&add=true" id="create_new_tab" class="nav-tab">+</a>
				</h2>

				<?php
					if (!$this->get_current_slide()) {
						return;
					}
				?>

                <div class="slider-wrap">
    				<div class="left" style='width: 67%; margin-left: 1%; margin-top: 20px; float: left; clear: none;'>
    					<table class="widefat sortable slides">
    						<thead>
    							<tr>
    								<th style="width: 100px;">Slides</th>
    								<th><input class='upload_image_button alignright button-secondary' type='submit' value='Add Slide' data-uploader_title='Select Slide' data-uploader_button_text='Add to slider' /></th>
    							</tr>
    						</thead>

    						<tbody>
    							<?php
                                    $slides = $this->getSlides();

						            foreach($slides as $slide) {
						                $image_attributes = wp_get_attachment_image_src($slide['id']); // returns an array
						                $url = get_post_meta($slide['id'], 'ml-slider_url', true);
						                echo "<tr class='slide'>";
						                echo "<td>";
						                echo "<div style='position: absolute'><a class='delete-slide confirm' href='?page=ml-slider&id={$this->get_current_slide()}&deleteSlide={$slide['id']}'>x</a></div>";
						                echo "<img src='{$image_attributes[0]}' width='150px'></td>";
										echo "<td>";
										echo "<textarea name='attachment[{$slide['id']}][post_excerpt]' placeholder='Caption'>{$slide['caption']}</textarea>";
										echo "<input type='text' name='attachment[{$slide['id']}][url]' placeholder='URL' value='{$url}' />";
										echo "<input type='hidden' class='menu_order' name='attachment[{$slide['id']}][menu_order]' value={$slide['menu_order']} />";
										echo "</td>";
						                echo "</tr>";
						            }
    					        ?>
    						</tbody>
    					</table>
    				</div>

    				<div class='right' style="width: 29%; float: right; margin-right: 1%; margin-top: 20px; clear: none;">
    					<table class="widefat settings">
    						<thead>
    							<tr>
                                    <th colspan="2">Settings</th>

    								<th><input type='submit' value='Save' class='alignright button-primary' /></th>
    							</tr>
    						</thead>
    						<tbody>
    							<tr>
    								<td colspan='3'>
                                        <div class='slider-lib nivo'>
                                            <label for='nivo' title='Version: 3.2<br />Responsive: Yes<br />Effects: 14<br />Size: 12kb<br />Mobile Friendly: Yes<br />Themes: 4' class='tooltiptop'>NivoSlider</label>
                                            <input class="select-slider" id='nivo' rel='nivo' type='radio' name="settings[type]" <?php if ($settings['type'] == 'nivo') echo 'checked=checked' ?> value='nivo' />
                                        </div>
    									<div class='slider-lib coin'>
    										<label for='coin' title='Version: 1.0<br />Responsive: No<br />Effects: 4<br />Size: 8kb<br />Mobile Friendly: Yes' class='tooltiptop'>CoinSlider</label>
    										<input class="select-slider" id='coin' rel='coin' type='radio' name="settings[type]" <?php if ($settings['type'] == 'coin') echo 'checked=checked' ?> value='coin' />
    									</div>
    									<div class='slider-lib flex'>
    										<label for='flex' title='Version: 2.1<br />Responsive: Yes<br />Effects: 2<br />Size: 17kb<br />Mobile Friendly: Yes' class='tooltiptop'>FlexSlider</label>
    										<input class="select-slider" id='flex' rel='flex' type='radio' name="settings[type]" <?php if ($settings['type'] == 'flex') echo 'checked=checked' ?> value='flex' />
    									</div>
    									<div class='slider-lib responsive'>
    										<label for='responsive' title='Version: 1.53<br />Responsive: Yes<br />Effects: 1<br />Size: 3kb<br />Mobile Friendly: Yes' class='tooltiptop'>Responsive</label>
    										<input class="select-slider" id='responsive' rel='responsive' type='radio' name="settings[type]" <?php if ($settings['type'] == 'responsive') echo 'checked=checked' ?> value='responsive' />
    									</div>
    								</td>
    							</tr>
    							<tr>
                                    <td><a href="#" class="tooltip" title="Set the initial size for the slides (width x height)">?</a></td>
    								<td>Size</td>
    								<td>
    									<input type='text' size='3' name="settings[width]" value='<?php echo $settings['width'] ?>' />px X
    									<input type='text' size='3' name="settings[height]" value='<?php echo $settings['height'] ?>' />px
    								</td>
    							</tr>
    							<tr>
                                    <td><a href="#" class="tooltip" title="Slide transition effect">?</a></td>
    								<td>Effect</td>
    								<td>
    									<select name="settings[effect]" class='effect option coin nivo flex'>
    										<option class='option coin nivo' value='random' <?php if ($settings['effect'] == 'random') echo 'selected=selected' ?>>Random</option>
    										<option class='option coin' value='swirl' <?php if ($settings['effect'] == 'swirl') echo 'selected=selected' ?>>Swirl</option>
    										<option class='option coin' value='rain' <?php if ($settings['effect'] == 'rain') echo 'selected=selected' ?>>Rain</option>
    										<option class='option coin' value='straight' <?php if ($settings['effect'] == 'straight') echo 'selected=selected' ?>>Straight</option>
    										<option class='option nivo' value='sliceDown' <?php if ($settings['effect'] == 'sliceDown') echo 'selected=selected' ?>>Slice Down</option>
    										<option class='option nivo' value='sliceUp' <?php if ($settings['effect'] == 'sliceUp') echo 'selected=selected' ?>>Slice Up</option>
    										<option class='option nivo' value='sliceUpLeft' <?php if ($settings['effect'] == 'sliceUpLeft') echo 'selected=selected' ?>>Slice Up Left</option>
    										<option class='option nivo' value='sliceUpDown' <?php if ($settings['effect'] == 'sliceUpDown') echo 'selected=selected' ?>>Slice Up Down</option>
    										<option class='option nivo' value='sliceUpDownLeft' <?php if ($settings['effect'] == 'sliceUpDownLeft') echo 'selected=selected' ?>>Slice Up Down Left</option>
    										<option class='option nivo' value='fold' <?php if ($settings['effect'] == 'fold') echo 'selected=selected' ?>>Fold</option>
    										<option class='option nivo flex' value='fade' <?php if ($settings['effect'] == 'fade') echo 'selected=selected' ?>>Fade</option>
    										<option class='option nivo' value='slideInRight' <?php if ($settings['effect'] == 'slideInRight') echo 'selected=selected' ?>>Slide In Right</option>
    										<option class='option nivo' value='slideInLeft' <?php if ($settings['effect'] == 'slideInLeft') echo 'selected=selected' ?>>Slide In Left</option>
    										<option class='option nivo' value='boxRandom' <?php if ($settings['effect'] == 'boxRandom') echo 'selected=selected' ?>>Box Random</option>
    										<option class='option nivo' value='boxRain' <?php if ($settings['effect'] == 'boxRain') echo 'selected=selected' ?>>Box Rain</option>
    										<option class='option nivo' value='boxRainReverse' <?php if ($settings['effect'] == 'boxRainReverse') echo 'selected=selected' ?>>Box Rain Reverse</option>
    										<option class='option nivo' value='boxRainGrowReverse' <?php if ($settings['effect'] == 'boxRainGrowReverse') echo 'selected=selected' ?>>Box Rain Grow Reverse</option>
    										<option class='option flex' value='slide' <?php if ($settings['effect'] == 'slide') echo 'selected=selected' ?>>Slide</option>
    									</select>
    								</td>
    							</tr>
    							<tr>
                                    <td><a href="#" class="tooltip" title="Change the slider style">?</a></td>
    								<td>Theme</td>
    								<td>
    									<select class='option nivo' name="settings[theme]">
    										<option value='default' <?php if ($settings['theme'] == 'default') echo 'selected=selected' ?>>Default</option>
    										<option value='dark' <?php if ($settings['theme'] == 'dark') echo 'selected=selected' ?>>Dark</option>
    										<option value='light' <?php if ($settings['theme'] == 'light') echo 'selected=selected' ?>>Light</option>
    										<option value='bar' <?php if ($settings['theme'] == 'bar') echo 'selected=selected' ?>>Bar</option>
    									</select>
    								</td>
    							</tr>
    							<tr>
                                    <td><a href="#" class="tooltip" title="Number of squares (width x height)">?</a></td>
    								<td>Number of squares</td>
    								<td>
    									<input class='option coin nivo' type='text' size='2' name="settings[spw]" value='<?php echo $settings['spw'] ?>' /> x 
    								    <input class='option coin nivo' type='text' size='2' name="settings[sph]" value='<?php echo $settings['sph'] ?>' />
    								</td>
    							</tr>
    							<tr>
                                    <td><a href="#" class="tooltip" title="Number of slices">?</a></td>
    								<td>Number of slices</td>
    								<td>
    									<input class='option nivo' type='text' size='2' name="settings[slices]" value='<?php echo $settings['slices'] ?>' />
    								</td>
    							</tr>
    							<tr>
                                    <td><a href="#" class="tooltip" title="How long to display each slide, in milliseconds">?</a></td>
    								<td>Slide delay</td>
    								<td><input class='option coin flex responsive nivo' type='text' size='5' name="settings[delay]" value='<?php echo $settings['delay'] ?>' />ms</td>
    							</tr>
    							<tr>
                                    <td><a href="#" class="tooltip" title="Delay beetwen squares in ms">?</a></td>
    								<td>Square delay</td>
    								<td><input class='option coin' type='text' size='5' name="settings[sDelay]" value='<?php echo $settings['sDelay'] ?>' />ms</td>
    							</tr>
    							<tr>
                                    <td><a href="#" class="tooltip" title="Opacity of title and navigation">?</a></td>
    								<td>Opacity</td>
    								<td><input class='option coin' type='text' size='5' name="settings[opacity]" value='<?php echo $settings['opacity'] ?>' /></td>
    							</tr>
    							<tr>
                                    <td><a href="#" class="tooltip" title="Set the fade in speef of the caption">?</a></td>
    								<td>Caption speed</td>
    								<td><input class='option coin' type='text' size='5' name="settings[titleSpeed]" value='<?php echo $settings['titleSpeed'] ?>' />ms</td>
    							</tr>
    							<tr>
                                    <td><a href="#" class="tooltip" title="Set the speed of animations, in milliseconds">?</a></td>
    								<td>Animation speed</td>
    								<td><input class='option flex responsive nivo' type='text' size='5' name="settings[animationSpeed]" value='<?php echo $settings['animationSpeed'] ?>' />ms</td>
    							</tr>
    							<tr>
                                    <td><a href="#" class="tooltip" title="Show slide navigation row">?</a></td>
    								<td>Navigation</td>
    								<td>
    									<input class='option coin responsive nivo flex' type='checkbox' name="settings[navigation]" <?php if ($settings['navigation'] == 'true') echo 'checked=checked' ?> />
    								</td>
    							</tr>
    							<tr>
                                    <td><a href="#" class="tooltip" title="Show previous and next links">?</a></td>
    								<td>Links</td>
    								<td>
    									<input class='option responsive nivo flex' type='checkbox' name="settings[links]" <?php if ($settings['links'] == 'true') echo 'checked=checked' ?> />
    								</td>
    							</tr>
    							<tr>
                                    <td><a href="#" class="tooltip" title="Pause the slideshow when hovering over slider, then resume when no longer hovering">?</a></td>
    								<td>Hover pause</td>
    								<td>
    									<input class='option coin flex responsive nivo' type='checkbox' name="settings[hoverPause]" <?php if ($settings['hoverPause'] == 'true') echo 'checked=checked' ?> />
    								</td>
    							</tr>
    							<tr>
                                    <td><a href="#" class="tooltip" title="Reverse the animation direction">?</a></td>
    								<td>Reverse</td>
    								<td>
    									<input class='option flex' type='checkbox' name="settings[reverse]" <?php if ($settings['reverse'] == 'true') echo 'checked=checked' ?> />
    								</td>
    							</tr>
    							<tr>
                                    <td><a href="#" class="tooltip" title="Randomise the order of the slides">?</a></td>
    								<td>Random</td>
    								<td>
    									<input type='checkbox' name="settings[random]" <?php if ($settings['random'] == 'true') echo 'checked=checked' ?> />
    								</td>
    							</tr>
    							<tr>
                                    <td><a href="#" class="tooltip" title="Uncheck this is you would like to include your own CSS">?</a></td>
    								<td>Print CSS</td>
    								<td>
    									<input type='checkbox' name="settings[printCss]" <?php if ($settings['printCss'] == 'true') echo 'checked=checked' ?> />
    								</td>
    							</tr>
    							<tr>
                                    <td><a href="#" class="tooltip" title="Uncheck this is you would like to include your own Javascript">?</a></td>
    								<td>Print JS</td>
    								<td>
    									<input type='checkbox' name="settings[printJs]" <?php if ($settings['printJs'] == 'true') echo 'checked=checked' ?> />
    								</td>
    							</tr>
    							<tr>
                                    <td><a href="#" class="tooltip" title="Select the sliding direction">?</a></td>
    								<td>Direction</td>
    								<td>
    									<select class='option flex' name="settings[direction]">
    										<option value='horizontal' <?php if ($settings['direction'] == 'horizontal') echo 'selected=selected' ?>>Horizontal</option>
    										<option value='vertical' <?php if ($settings['direction'] == 'vertical') echo 'selected=selected' ?>>Vertical</option>
    									</select>						
    								</td>
    							</tr>
    							<tr>
                                    <td><a href="#" class="tooltip" title="Set the text for the 'previous' direction item">?</a></td>
    								<td>Previous text</td>
    								<td><input class='option flex responsive nivo' type='text' name="settings[prevText]" value='<?php echo $settings['prevText'] ?>' /></td>
    							</tr>
    							<tr>
                                    <td><a href="#" class="tooltip" title="Set the text for the 'previous' direction item">?</a></td>
    								<td>Next text</td>
    								<td><input class='option flex responsive nivo' type='text' name="settings[nextText]" value='<?php echo $settings['nextText'] ?>' /></td>
    							</tr>
    							<tr>
                                    <td><a href="#" class="tooltip" title="Specify any custom CSS Classes you would like to be added to the slider wrapper">?</a></td>
    								<td>CSS classes</td>
    								<td><input type='text' name="settings[cssClass]" value='<?php echo $settings['cssClass'] ?>' /></td>
    							</tr>
    						</tbody>
    					</table>

    					<table class="widefat" style="width: 100%; margin-top: 20px;">
    						<thead>
    							<tr>
    								<th>Shortcode</th>
    							</tr>
    						</thead>

    						<tbody>
    							<tr>
    								<td><textarea style="width: 100%">[ml-slider id=<?php echo $this->get_current_slide() ?>]</textarea></td>
    							</tr>
    						</tbody>
    					</table>

    					<br />
                        <a class='alignright button-secondary confirm' href="?page=ml-slider&delete=<?php echo $this->get_current_slide() ?>">Delete Slider</a>
                    </div>
                </div>
			</form>
		</div>
		<?php
	}
}
$mlslider = new MLSlider();
?>