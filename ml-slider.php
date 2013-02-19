<?php
/*
 * Plugin Name: ML Slider
 * Plugin URI: http://www.ml-slider.com
 * Description: 4 sliders in 1! Choose from Nivo Slider, Flex Slider, Coin Slider or Responsive Slides.
 * Version: 1.2
 * Author: Matcha Labs
 * Author URI: http://www.matchalabs.com
 * License: GPL
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
*/

define( 'MLSLIDER_VERSION', '1.2' );
define( 'MLSLIDER_BASE_URL', plugin_dir_url( __FILE__ ) );
define( 'MLSLIDER_ASSETS_URL', MLSLIDER_BASE_URL . 'assets/' );

class MLSlider {

    var $slider = 0;
    var $slides = array();
    var $settings = array();

    /**
     * /////////////////////////////////////////////////////////////////
     *                        Plugin Registration
     * /////////////////////////////////////////////////////////////////
     */

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'register_admin_menu'), 10001);
        add_action('admin_menu', array($this, 'admin_process'), 10002);
        add_action('init', array($this, 'register_post_type' ));
        add_action('init', array($this, 'register_taxonomy' ));
        add_action('admin_print_styles', array( $this, 'register_admin_styles'));
        add_shortcode('ml-slider', array($this, 'register_shortcode'));
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
     * /////////////////////////////////////////////////////////////////
     *                        ML Slider
     * /////////////////////////////////////////////////////////////////
     */

    /**
     * Current slide ID
     */
    private function set_slider($id) {
        $this->slider = $id;
        $this->settings = $this->get_settings();
        $this->slides = $this->get_slides();
    }

    /**
     * Get slide ID
     *
     * @return int the current slider ID
     */
    private function get_slider() {
        return $this->slider;
    }

    /**
     * Get settings for the current slider
     *
     * @return array slider settings
     */
    private function get_settings() {
        return get_post_meta($this->get_slider(), 'ml-slider_settings', true);
    }

    /**
     * Return an individual setting
     *
     * @param string $name Name of the setting
     * @return string | bool setting value or fase
     */
    private function get_setting($name) {
        return isset($this->settings[$name]) && strlen($this->settings[$name]) > 0 ? $this->settings[$name] : "false";
    }

    /**
     * Handle slide uploads/changes
     */
    public function admin_process() {
        if (isset($_REQUEST['id'])) {
            $slider = $_REQUEST['id'];
        } else {
            $slider = $this->find_slider('date', 'DESC');
        }

        $this->set_slider($slider);

        $this->handle_slide_updates();
        $this->handle_delete_slider();
        $this->handle_delete_slide();
        $this->handle_update_slider_title();
        $this->handle_update_slider_settings();
        $this->handle_create_slider();
    }

    /**
     * Get sliders. Returns a nicely formatted array of currently
     * published sliders.
     *
     * @return array array of all published sliders
     */
    private function get_sliders() {
        $sliders = false;
        
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
            if (!$this->get_slider()) {
                $this->set_slider($the_query->post->ID);
            }
            
            $the_query->the_post();
            $active = $this->get_slider() == $the_query->post->ID ? true : false;
            
            $sliders[] = array(
                'active' => $active,
                'title' => get_the_title(),
                'id' => $the_query->post->ID
            );
        }
        
        return $sliders;
    }

    /**
     * Return slides for the current slider
     *
     * @return array collection of slides belonging to the current slider
     */
    public function get_slides() {
        $retVal = array();

        $args = array(
            'orderby' => $this->get_setting('random') == 'true' && !is_admin() ? 'rand' : 'menu_order',
            'order' => 'ASC',
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'tax_query' => array(
                array(
                    'taxonomy' => 'ml-slider',
                    'field' => 'slug',
                    'terms' => $this->get_slider()
                )
            )
        );

        $query = new WP_Query($args);

        while ($query->have_posts()) {
            $query->next_post();

            $image_attributes = wp_get_attachment_image_src($query->post->ID, 'ml-slider-slide'); // returns an array

            $retVal[] = array(
                'id' => $query->post->ID,
                'type' => 'image',
                'url' => get_post_meta($query->post->ID, 'ml-slider_url', true),
                'caption' => htmlentities($query->post->post_excerpt, ENT_QUOTES),
                'src' => $image_attributes[0],
                'alt' => htmlentities(get_post_meta($query->post->ID, '_wp_attachment_image_alt', true),ENT_QUOTES),
                'menu_order' => $query->post->menu_order
            );
        }

        return $retVal;
    }

    /**
     * Create a new slider
     */
    private function handle_create_slider() {
        // create a new slider
        if (isset($_GET['add'])) {
            // if possible, take a copy of the last edited slider settings in place of default settings
            if ($last_modified = $this->find_slider('modified', 'DESC')) {
                $defaults = get_post_meta($last_modified, 'ml-slider_settings', true);
            } else {
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
            }

            // insert the post
            $id = wp_insert_post(array(
                'post_title' => 'New Slider',
                'post_status' => 'publish',
                'post_type' => 'ml-slider'
            ));

            // insert the post meta
            add_post_meta($id, 'ml-slider_settings', $defaults, true);

            // create the taxonomy term, the term is the ID of the slider itself
            wp_insert_term($id, 'ml-slider');

            // set the current slider to the one we have created
            $this->set_slider($id);
        }
    }

    /**
     * Update slider settings
     */
    private function handle_update_slider_settings() {
        if (isset($_POST['settings'])) {
            $old_settings = get_post_meta($this->get_slider(), 'ml-slider_settings', true);
            $new_settings = $_POST['settings'];
            
            // convert submitted checkbox values from 'on' or 'off' to boolean values
            $checkboxes = array('hoverPause', 'links', 'navigation', 'reverse', 'random', 'printCss', 'printJs');

            foreach ($checkboxes as $checkbox) {
                if (isset($new_settings[$checkbox]) && $new_settings[$checkbox] == 'on') {
                    $new_settings[$checkbox] = true;
                } else {
                    $new_settings[$checkbox] = false;
                }
            }
            
            // update the slider settings
            update_post_meta($this->get_slider(), 'ml-slider_settings', array_merge($old_settings, $new_settings));

            // update settings
            $this->settings = get_post_meta($this->get_slider(), 'ml-slider_settings', true);
        }
    }

    /**
     * Update slider title
     */
    private function handle_update_slider_title() {
        if (isset($_POST['title'])) {
            $slide = array(
                'ID' => $this->get_slider(),
                'post_title' => $_POST['title']
            );
            
            wp_update_post($slide);
        }
    }

    /**
     * 'Delete' a slide. Note: this doesn't delete the slide itself, it just deletes
     * the relationship between the slider taxonomy term and the slide.
     * 
     * @return bool true if the slide was untagged
     */
    private function handle_delete_slide() {
        if (isset($_GET['deleteSlide'])) {
            $slideToUntagFromCurrentSlider = $_GET['deleteSlide'];

            // Get the existing terms and only keep the ones we don't want removed
            $new_terms = array();
            $current_terms = wp_get_object_terms($slideToUntagFromCurrentSlider, 'ml-slider', array('fields' => 'ids'));
            $term = get_term_by('name', $this->get_slider(), 'ml-slider');

            foreach ($current_terms as $current_term) {
                if ($current_term != $term->term_id) {
                    $new_terms[] = intval($current_term);
                }
            }
         
            return wp_set_object_terms($slideToUntagFromCurrentSlider, $new_terms, 'ml-slider');
        }
    }

    /**
     * Update the slides. Add new slides, update ordering, taxonomy tagging (associating
     * slide with slider), resize images.
     */
    private function handle_slide_updates() {
        // handle slide description, url and ordering
        if (isset($_POST['attachment'])) {
            foreach ($_POST['attachment'] as $id => $fields) {
                // get the term thats name is the same as the ID of the slider
                $term = get_term_by('name', $this->get_slider(), 'ml-slider');

                // tag this slide to the taxonomy term
                wp_set_post_terms($id, $term->term_id, 'ml-slider', true);

                // update the slide
                wp_update_post(array(
                    'ID' => $id,
                    'post_excerpt' => $fields['post_excerpt'],
                    'menu_order' => $fields['menu_order']
                ));
                
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

                // add a new image size for the current slider
                add_image_size('ml-slider-slide', $this->get_setting('width'), $this->get_setting('height'), true);
                $file = get_attached_file($id);
                // ask WordPress to resize our slides for us
                wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $file));
            }
        }
    }

    /**
     * Delete a slider
     */
    private function handle_delete_slider() {
        if (isset($_GET['delete'])) {
            $slide = array(
                'ID' => intVal($_GET['delete']),
                'post_status' => 'trash'
            );
            
            wp_update_post($slide);
            
            // set current slider to first published
            $this->set_slider($this->find_slider('date', 'DESC'));
        }
    }

    /**
     * Find a single slider ID. For example, last edited, or first published.
     *
     * @param string $orderby field to order.
     * @param string $order direction (ASC or DESC).
     * @return int slider ID.
     */
    private function find_slider($orderby, $order) {
        $args = array(
            'post_type' => 'ml-slider',
            'num_posts' => 1,
            'post_status' => 'publish',
            'orderby' => $orderby,
            'order' => $order
        );

        $the_query = new WP_Query($args);
        
        while ($the_query->have_posts()) {
            $the_query->the_post();
            return $the_query->post->ID;
        }

        return false;
    }

    

    /**
     * Get the slider libary parameters
     *
     * @return string javascript options
     */
    private function get_params() {
        $params = array(
            'width' => array(
                'map' => array(
                    'coin' => 'width'
                ),
                'default' => 565
            ),
            'height' => array(
                'map' => array(
                    'coin' => 'height'
                ),
                'default' => 290
            ),
            'spw' => array(
                'map' => array(
                    'coin' => 'spw', 
                    'nivo' => 'boxCols'
                ),
                'default' => 7
            ),
            'sph' => array(
                'map' => array(
                    'coin' => 'sph', 
                    'nivo' => 'boxRows'
                ),
                'default' => 5
            ),
            'delay' => array(
                'map' => array(
                    'coin' => 'delay', 
                    'nivo' => 'pauseTime', 
                    'flex' => 'slideshowSpeed', 
                    'responsive' => 'timeout'
                ),
                'default' => 3000
            ),
            'sDelay' => array(
                'map' => array(
                    'coin' => 'sDelay'
                ),
                'default' => 30
            ),
            'opacity' => array(
                'map' => array(
                    'coin' => 'opacity'
                ),
                'default' => 0.7
            ),
            'effect' => array(
                'map' => array(
                    'coin' => 'effect', 
                    'nivo' => 'effect', 
                    'flex' => 'animation'
                ),
                'default' => 'random'
            ),
            'navigation' => array(
                'map' => array(
                    'coin' => 'navigation', 
                    'nivo' => 'controlNav', 
                    'flex' => 'controlNav', 
                    'responsive' => 'pager'
                ),
                'default' => true
            ),
            'links' => array(
                'map' => array(
                    'nivo' => 'directionNav', 
                    'flex' => 'directionNav', 
                    'responsive' => 'nav'
                ),
                'default' => true
            ),
            'hoverPause' => array(
                'map' => array(
                    'coin' => 'hoverPause', 
                    'nivo' => 'pauseOnHover', 
                    'flex' => 'pauseOnHover', 
                    'responsive' => 'pause'
                ),
                'default' => true
            ),
            'theme' => array(
                'map' => array(
                    'nivo' => 'theme'
                ),
                'default' => 'dark'
            ),
            'direction' => array(
                'map' => array(
                    'flex' => 'direction'
                ),
                'default' => 'horizontal'
            ),
            'reverse' => array(
                'map' => array(
                    'flex' => 'reverse'
                ),
                'default' => false,
            ),
            'animationSpeed' => array(
                'map' => array(
                    'nivo' => 'animSpeed', 
                    'flex' => 'animationSpeed', 
                    'responsive' => 'speed'
                ),
                'default' => 600
            ),
            'prevText' => array(
                'map' => array(
                    'nivo' => 'prevText', 
                    'flex' => 'prevText', 
                    'responsive' => 'prevText'
                ),
                'default' => 'Previous'
            ),
            'nextText' => array(
                'map' => array(
                    'nivo' => 'nextText', 
                    'flex' => 'nextText', 
                    'responsive' => 'nextText'
                ),
                'default' => 'Next'
            ),
            'slices' => array(
                'map' => array(
                    'nivo' => 'slices'
                ),
                'default' => 15
            )
        );

        $options = array();

        foreach ($params as $setting => $map) {
            if (isset($map['map'][$this->get_setting('type')])) {
                $optionName = $map['map'][$this->get_setting('type')];

                if (!$optionVal = $this->get_setting($setting)) {
                    $optionVal = $map['default'];
                }

                if (gettype($map['default']) == 'string') {
                    $options[] = $optionName . ": '" . $optionVal . "'";
                } else {
                    $options[] = $optionName . ": " . $optionVal;
                }
            }
        }

        return implode(",\n            ", $options);;
    }

    /**
     * Return the Javascript to kick off the slider. Code is wrapped in a timer
     * to allow for themes that load jQuery at the bottom of the page.
     *
     * @return string javascript
     */
    private function get_inline_javascript($type, $identifier) {
        $retVal  = "\n<script type='text/javascript'>";
        $retVal .= "\n    var " . $identifier . " = function($) {";
        $retVal .= "\n        $('#" . $identifier . "')." . $type . "({ ";
        $retVal .= "\n            " . $this->get_params();
        $retVal .= "\n        });";
        $retVal .= "\n    };";
        $retVal .= "\n    var timer_" . $identifier . " = function() {";
        $retVal .= "\n        if (window.jQuery && jQuery.isReady) {";
        $retVal .= "\n            " . $identifier . "(window.jQuery);";
        $retVal .= "\n        } else {";
        $retVal .= "\n            window.setTimeout(timer_" . $identifier . ", 100);";
        $retVal .= "\n        }";
        $retVal .= "\n    };";
        $retVal .= "\n    timer_" . $identifier . "();";
        $retVal .= "\n</script>";

        return $retVal;
    }
    
    /**
     * Return coin slider markup
     *
     * @return string coin slider markup.
     */
    private function get_coin_slider($identifier) {
        $retVal = "<div id='" . $identifier . "' class='coin-slider'>";
        
        foreach ($this->get_slides() as $slide) {
            $url = strlen($slide['url']) ? $slide['url'] : "javascript:void(0)"; // coinslider always wants a URL
            $retVal .= "<a href='{$url}'>";
            $retVal .= "<img src='{$slide['src']}' alt='{$slide['alt']}'>";
            $retVal .= "<span>{$slide['caption']}</span>";
            $retVal .= "</a>";
        }
        
        $retVal .= "</div>";
        
        return $retVal;
    }

    /**
     * Return flexslider markup
     *
     * @return string flex slider markup.
     */
    private function get_flex_slider($identifier) {
        $retVal = "<div id='" . $identifier . "' class='flexslider'><ul class='slides'>";
        
        foreach ($this->get_slides() as $slide) {
            $retVal .= "<li>";
            if (strlen($slide['url'])) $retVal .= "<a href='{$slide['url']}'>";
            $retVal .= "<img src='{$slide['src']}' alt='{$slide['alt']}'>";
            if (strlen($slide['caption'])) $retVal .= "<p class='flex-caption'>{$slide['caption']}</p>";
            if (strlen($slide['url'])) $retVal .= "</a>";
            $retVal .= "</li>";
        }
        
        $retVal .= "</ul></div>";

        return $retVal;
    }
    
    /**
     * Return responsive slides markup
     *
     * @return string responsive slider markup.
     */
    private function get_responsive_slider($identifier) {
        $retVal = "<ul id='" . $identifier . "' class='rslides'>";
        
        foreach ($this->get_slides() as $slide) {
            $retVal .= "<li>";
            if (strlen($slide['url'])) $retVal .= "<a href='{$slide['url']}'>";
            $retVal .= "<img src='{$slide['src']}' alt='{$slide['alt']}'>";
            if (strlen($slide['url'])) $retVal .= "</a>";
            $retVal .= "</li>";
        }
        
        $retVal .= "</ul>";
        
        return $retVal;
    }
    
    /**
     * Return nivoslider markup
     *
     * @return string nivo slider markup.
     */
    private function get_nivo_slider($identifier) {
        $retVal  = "<div class='slider-wrapper theme-{$this->get_setting('theme')}'>";
        $retVal .= "<div class='ribbon'></div>";
        $retVal .= "<div id='" . $identifier . "' class='nivoSlider'>";
        
        foreach ($this->get_slides() as $slide) {
            if (strlen($slide['url'])) $retVal .= "<a href='{$slide['url']}'>";
            $retVal .= "<img src='{$slide['src']}' title='{$slide['caption']}' alt='{$slide['alt']}'>";
            if (strlen($slide['url'])) $retVal .= "</a>";
        }
        
        $retVal .= "</div></div>";
        
        return $retVal;
    }
    
    /**
     * Shortcode used to display slideshow
     *
     * @return string HTML output of the shortcode
     */
    public function register_shortcode($atts) {
        extract(shortcode_atts(array(
            'id' => null
        ), $atts));
        

        if ($id == null) {
            return;
        }

        $slider = get_post($id);

        // check the slider is published
        if ($slider->post_status != 'publish') {
            return false;
        }

        $this->set_slider($id);

        $identifier = 'ml_slider_' . rand();

        // coinslider
        if ($this->get_setting('type') == 'coin') {
            if ($this->get_setting('printJs') == 'true') {
                wp_enqueue_script('ml-slider_coin_slider', MLSLIDER_ASSETS_URL . 'coinslider/coin-slider.min.js', array('jquery'), MLSLIDER_VERSION);
            }

            if ($this->get_setting('printCss') == 'true') {
                wp_enqueue_style('ml-slider_coin_slider_css', plugins_url('ml-slider/assets/coinslider/coin-slider-styles.css'));
            }

            $retVal = $this->get_coin_slider($identifier);
            $retVal .= $this->get_inline_javascript('coinslider', $identifier);
        }

        // flex
        if ($this->get_setting('type') == 'flex') {
            if ($this->get_setting('printJs') == 'true') {
                wp_enqueue_script('ml-slider_flex_slider', MLSLIDER_ASSETS_URL . 'flexslider/jquery.flexslider-min.js', array('jquery'), MLSLIDER_VERSION);
            }

            if ($this->get_setting('printCss') == 'true') {
                wp_enqueue_style('ml-slider_flex_slider_css', plugins_url('ml-slider/assets/flexslider/flexslider.css'));
            }

            $retVal = $this->get_flex_slider($identifier);
            $retVal .= $this->get_inline_javascript('flexslider', $identifier);
        }
        
        // responsive
        if ($this->get_setting('type') == 'responsive') {
            if ($this->get_setting('printJs') == 'true') {
                wp_enqueue_script('ml-slider_responsive_slides', MLSLIDER_ASSETS_URL . 'responsiveslides/responsiveslides.min.js', array('jquery'), MLSLIDER_VERSION);
            }

            if ($this->get_setting('printCss') == 'true') {
                wp_enqueue_style('ml-slider_responsive_slides_css', plugins_url('ml-slider/assets/responsiveslides/responsiveslides.css'));
            }

            $retVal = $this->get_responsive_slider($identifier);
            $retVal .= $this->get_inline_javascript('responsiveSlides', $identifier);
        }
        
        // nivo
        if ($this->get_setting('type') == 'nivo') {
            if ($this->get_setting('printJs') == 'true') {
                wp_enqueue_script('ml-slider_nivo_slider', MLSLIDER_ASSETS_URL . 'nivoslider/jquery.nivo.slider.pack.js', array('jquery'), MLSLIDER_VERSION);
            }

            if ($this->get_setting('printCss') == 'true') {
                wp_enqueue_style('ml-slider_nivo_slider_css', plugins_url('ml-slider/assets/nivoslider/nivo-slider.css'));
                wp_enqueue_style('ml-slider_nivo_slider_theme_' . $this->get_setting('theme'), plugins_url('ml-slider/assets/nivoslider/themes/' . $this->get_setting('theme') . '/' . $this->get_setting('theme') . '.css'));
            }
            
            $retVal = $this->get_nivo_slider($identifier);
            $retVal .= $this->get_inline_javascript('nivoSlider', $identifier);
        }
        
        return "<div class='ml-slider ml-slider-{$this->get_setting('type')} {$this->get_setting('cssClass')}'>" . $retVal . "</div>";
    }

    /**
     * /////////////////////////////////////////////////////////////////
     *                        Admin Page
     * /////////////////////////////////////////////////////////////////
     */

    /**
     * Render the admin page (tabs, slides, settings)
     */
    public function render_admin_page() {
        ?>

        <div class="wrap ml-slider">
            <form enctype="multipart/form-data" action="?page=ml-slider&id=<?php echo $this->get_slider() ?>" method="post">

                <h2 class="nav-tab-wrapper" style="font-size: 13px;">
                    <?php
                        if ($tabs = $this->get_sliders()) {
                            foreach ($tabs as $tab) {
                                if ($tab['active']) {
                                    echo "<div class='nav-tab nav-tab-active' style='font-size: 13px;'><input type='text' name='title'  value='" . $tab['title'] . "' style='padding: 0; margin: 0; border: 0; width: 100px; font-size: 14px' onkeypress='this.style.width = ((this.value.length + 1) * 9) + \"px\"' /></div>";
                                } else {
                                    echo "<a href='?page=ml-slider&id={$tab['id']}' class='nav-tab' style='font-size: 13px;'>" . $tab['title'] . "</a>";
                                }
                            }                           
                        }
                    ?>
                    
                    <a href="?page=ml-slider&add=true" id="create_new_tab" class="nav-tab" style='font-size: 13px;'>+</a>
                </h2>

                <?php
                    if (!$this->get_slider()) {
                        return;
                    }
                ?>

                <div class="left" style='width: 68%; margin-top: 20px; float: left; clear: none;'>
                    <table class="widefat sortable slides">
                        <thead>
                            <tr>
                                <th style="width: 100px;">Slides</th>
                                <th><input class='upload_image_button alignright button-secondary' type='button' value='Add Slide' data-uploader_title='Select Slide' data-uploader_button_text='Add to slider' /></th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php
                                $slides = $this->get_slides();

                                foreach($slides as $slide) {
                                    $image_attributes = wp_get_attachment_image_src($slide['id']); // returns an array
                                    $url = get_post_meta($slide['id'], 'ml-slider_url', true);
                                    echo "<tr class='slide'>";
                                    echo "<td>";
                                    echo "<div style='position: absolute'><a class='delete-slide confirm' href='?page=ml-slider&id={$this->get_slider()}&deleteSlide={$slide['id']}'>x</a></div>";
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

                <div class='right' style="width: 30%; float: right; margin-top: 20px; clear: none;">
                    <table class="widefat settings">
                        <thead>
                            <tr>
                                <th colspan="2">Settings</th>
                                <th>
                                    <input type='submit' value='Save' class='alignright button-primary' />
                                    <div class='unsaved tooltip' style='display: none;' title='Unsaved Changes'>!</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan='3'>
                                    <div class='slider-lib nivo'>
                                        <label for='nivo' title='Version: 3.2<br />Responsive: Yes<br />Effects: 14<br />Size: 12kb<br />Mobile Friendly: Yes<br />Themes: 4' class='tooltiptop'>NivoSlider</label>
                                        <input class="select-slider" id='nivo' rel='nivo' type='radio' name="settings[type]" <?php if ($this->get_setting('type') == 'nivo') echo 'checked=checked' ?> value='nivo' />
                                    </div>
                                    <div class='slider-lib coin'>
                                        <label for='coin' title='Version: 1.0<br />Responsive: No<br />Effects: 4<br />Size: 8kb<br />Mobile Friendly: Yes' class='tooltiptop'>CoinSlider</label>
                                        <input class="select-slider" id='coin' rel='coin' type='radio' name="settings[type]" <?php if ($this->get_setting('type') == 'coin') echo 'checked=checked' ?> value='coin' />
                                    </div>
                                    <div class='slider-lib flex'>
                                        <label for='flex' title='Version: 2.1<br />Responsive: Yes<br />Effects: 2<br />Size: 17kb<br />Mobile Friendly: Yes' class='tooltiptop'>FlexSlider</label>
                                        <input class="select-slider" id='flex' rel='flex' type='radio' name="settings[type]" <?php if ($this->get_setting('type') == 'flex') echo 'checked=checked' ?> value='flex' />
                                    </div>
                                    <div class='slider-lib responsive'>
                                        <label for='responsive' title='Version: 1.53<br />Responsive: Yes<br />Effects: 1<br />Size: 3kb<br />Mobile Friendly: Yes' class='tooltiptop'>Responsive</label>
                                        <input class="select-slider" id='responsive' rel='responsive' type='radio' name="settings[type]" <?php if ($this->get_setting('type') == 'responsive') echo 'checked=checked' ?> value='responsive' />
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><a href="#" class="tooltip" title="Set the initial size for the slides (width x height)">?</a></td>
                                <td>Size</td>
                                <td>
                                    <input type='text' size='3' name="settings[width]" value='<?php echo $this->get_setting('width') ?>' />px X
                                    <input type='text' size='3' name="settings[height]" value='<?php echo $this->get_setting('height') ?>' />px
                                </td>
                            </tr>
                            <tr>
                                <td><a href="#" class="tooltip" title="Slide transition effect">?</a></td>
                                <td>Effect</td>
                                <td>
                                    <select name="settings[effect]" class='effect option coin nivo flex'>
                                        <option class='option coin nivo' value='random' <?php if ($this->get_setting('effect') == 'random') echo 'selected=selected' ?>>Random</option>
                                        <option class='option coin' value='swirl' <?php if ($this->get_setting('effect') == 'swirl') echo 'selected=selected' ?>>Swirl</option>
                                        <option class='option coin' value='rain' <?php if ($this->get_setting('effect') == 'rain') echo 'selected=selected' ?>>Rain</option>
                                        <option class='option coin' value='straight' <?php if ($this->get_setting('effect') == 'straight') echo 'selected=selected' ?>>Straight</option>
                                        <option class='option nivo' value='sliceDown' <?php if ($this->get_setting('effect') == 'sliceDown') echo 'selected=selected' ?>>Slice Down</option>
                                        <option class='option nivo' value='sliceUp' <?php if ($this->get_setting('effect') == 'sliceUp') echo 'selected=selected' ?>>Slice Up</option>
                                        <option class='option nivo' value='sliceUpLeft' <?php if ($this->get_setting('effect') == 'sliceUpLeft') echo 'selected=selected' ?>>Slice Up Left</option>
                                        <option class='option nivo' value='sliceUpDown' <?php if ($this->get_setting('effect') == 'sliceUpDown') echo 'selected=selected' ?>>Slice Up Down</option>
                                        <option class='option nivo' value='sliceUpDownLeft' <?php if ($this->get_setting('effect') == 'sliceUpDownLeft') echo 'selected=selected' ?>>Slice Up Down Left</option>
                                        <option class='option nivo' value='fold' <?php if ($this->get_setting('effect') == 'fold') echo 'selected=selected' ?>>Fold</option>
                                        <option class='option nivo flex' value='fade' <?php if ($this->get_setting('effect') == 'fade') echo 'selected=selected' ?>>Fade</option>
                                        <option class='option nivo' value='slideInRight' <?php if ($this->get_setting('effect') == 'slideInRight') echo 'selected=selected' ?>>Slide In Right</option>
                                        <option class='option nivo' value='slideInLeft' <?php if ($this->get_setting('effect') == 'slideInLeft') echo 'selected=selected' ?>>Slide In Left</option>
                                        <option class='option nivo' value='boxRandom' <?php if ($this->get_setting('effect') == 'boxRandom') echo 'selected=selected' ?>>Box Random</option>
                                        <option class='option nivo' value='boxRain' <?php if ($this->get_setting('effect') == 'boxRain') echo 'selected=selected' ?>>Box Rain</option>
                                        <option class='option nivo' value='boxRainReverse' <?php if ($this->get_setting('effect') == 'boxRainReverse') echo 'selected=selected' ?>>Box Rain Reverse</option>
                                        <option class='option nivo' value='boxRainGrowReverse' <?php if ($this->get_setting('effect') == 'boxRainGrowReverse') echo 'selected=selected' ?>>Box Rain Grow Reverse</option>
                                        <option class='option flex' value='slide' <?php if ($this->get_setting('effect') == 'slide') echo 'selected=selected' ?>>Slide</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><a href="#" class="tooltip" title="Change the slider style">?</a></td>
                                <td>Theme</td>
                                <td>
                                    <select class='option nivo' name="settings[theme]">
                                        <option value='default' <?php if ($this->get_setting('theme') == 'default') echo 'selected=selected' ?>>Default</option>
                                        <option value='dark' <?php if ($this->get_setting('theme') == 'dark') echo 'selected=selected' ?>>Dark</option>
                                        <option value='light' <?php if ($this->get_setting('theme') == 'light') echo 'selected=selected' ?>>Light</option>
                                        <option value='bar' <?php if ($this->get_setting('theme') == 'bar') echo 'selected=selected' ?>>Bar</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><a href="#" class="tooltip" title="Number of squares (width x height)">?</a></td>
                                <td>Number of squares</td>
                                <td>
                                    <input class='option coin nivo' type='text' size='2' name="settings[spw]" value='<?php echo $this->get_setting('spw') ?>' /> x 
                                    <input class='option coin nivo' type='text' size='2' name="settings[sph]" value='<?php echo $this->get_setting('sph') ?>' />
                                </td>
                            </tr>
                            <tr>
                                <td><a href="#" class="tooltip" title="Number of slices">?</a></td>
                                <td>Number of slices</td>
                                <td>
                                    <input class='option nivo' type='text' size='2' name="settings[slices]" value='<?php echo $this->get_setting('slices') ?>' />
                                </td>
                            </tr>
                            <tr>
                                <td><a href="#" class="tooltip" title="How long to display each slide, in milliseconds">?</a></td>
                                <td>Slide delay</td>
                                <td><input class='option coin flex responsive nivo' type='text' size='5' name="settings[delay]" value='<?php echo $this->get_setting('delay') ?>' />ms</td>
                            </tr>
                            <tr>
                                <td><a href="#" class="tooltip" title="Delay beetwen squares in ms">?</a></td>
                                <td>Square delay</td>
                                <td><input class='option coin' type='text' size='5' name="settings[sDelay]" value='<?php echo $this->get_setting('sDelay') ?>' />ms</td>
                            </tr>
                            <tr>
                                <td><a href="#" class="tooltip" title="Opacity of title and navigation">?</a></td>
                                <td>Opacity</td>
                                <td><input class='option coin' type='text' size='5' name="settings[opacity]" value='<?php echo $this->get_setting('opacity') ?>' /></td>
                            </tr>
                            <tr>
                                <td><a href="#" class="tooltip" title="Set the fade in speef of the caption">?</a></td>
                                <td>Caption speed</td>
                                <td><input class='option coin' type='text' size='5' name="settings[titleSpeed]" value='<?php echo $this->get_setting('titleSpeed') ?>' />ms</td>
                            </tr>
                            <tr>
                                <td><a href="#" class="tooltip" title="Set the speed of animations, in milliseconds">?</a></td>
                                <td>Animation speed</td>
                                <td><input class='option flex responsive nivo' type='text' size='5' name="settings[animationSpeed]" value='<?php echo $this->get_setting('animationSpeed') ?>' />ms</td>
                            </tr>
                            <tr>
                                <td><a href="#" class="tooltip" title="Show slide navigation row">?</a></td>
                                <td>Navigation</td>
                                <td>
                                    <input class='option coin responsive nivo flex' type='checkbox' name="settings[navigation]" <?php if ($this->get_setting('navigation') == 'true') echo 'checked=checked' ?> />
                                </td>
                            </tr>
                            <tr>
                                <td><a href="#" class="tooltip" title="Show previous and next links">?</a></td>
                                <td>Links</td>
                                <td>
                                    <input class='option responsive nivo flex' type='checkbox' name="settings[links]" <?php if ($this->get_setting('links') == 'true') echo 'checked=checked' ?> />
                                </td>
                            </tr>
                            <tr>
                                <td><a href="#" class="tooltip" title="Pause the slideshow when hovering over slider, then resume when no longer hovering">?</a></td>
                                <td>Hover pause</td>
                                <td>
                                    <input class='option coin flex responsive nivo' type='checkbox' name="settings[hoverPause]" <?php if ($this->get_setting('hoverPause') == 'true') echo 'checked=checked' ?> />
                                </td>
                            </tr>
                            <tr>
                                <td><a href="#" class="tooltip" title="Reverse the animation direction">?</a></td>
                                <td>Reverse</td>
                                <td>
                                    <input class='option flex' type='checkbox' name="settings[reverse]" <?php if ($this->get_setting('reverse') == 'true') echo 'checked=checked' ?> />
                                </td>
                            </tr>
                            <tr>
                                <td><a href="#" class="tooltip" title="Randomise the order of the slides">?</a></td>
                                <td>Random</td>
                                <td>
                                    <input type='checkbox' name="settings[random]" <?php if ($this->get_setting('random') == 'true') echo 'checked=checked' ?> />
                                </td>
                            </tr>
                            <tr>
                                <td><a href="#" class="tooltip" title="Uncheck this is you would like to include your own CSS">?</a></td>
                                <td>Print CSS</td>
                                <td>
                                    <input type='checkbox' name="settings[printCss]" <?php if ($this->get_setting('printCss') == 'true') echo 'checked=checked' ?> />
                                </td>
                            </tr>
                            <tr>
                                <td><a href="#" class="tooltip" title="Uncheck this is you would like to include your own Javascript">?</a></td>
                                <td>Print JS</td>
                                <td>
                                    <input type='checkbox' name="settings[printJs]" <?php if ($this->get_setting('printJs') == 'true') echo 'checked=checked' ?> />
                                </td>
                            </tr>
                            <tr>
                                <td><a href="#" class="tooltip" title="Select the sliding direction">?</a></td>
                                <td>Direction</td>
                                <td>
                                    <select class='option flex' name="settings[direction]">
                                        <option value='horizontal' <?php if ($this->get_setting('direction') == 'horizontal') echo 'selected=selected' ?>>Horizontal</option>
                                        <option value='vertical' <?php if ($this->get_setting('direction') == 'vertical') echo 'selected=selected' ?>>Vertical</option>
                                    </select>                       
                                </td>
                            </tr>
                            <tr>
                                <td><a href="#" class="tooltip" title="Set the text for the 'previous' direction item">?</a></td>
                                <td>Previous text</td>
                                <td><input class='option flex responsive nivo' type='text' name="settings[prevText]" value='<?php if ($this->get_setting('prevText') != 'false') echo $this->get_setting('prevText') ?>' /></td>
                            </tr>
                            <tr>
                                <td><a href="#" class="tooltip" title="Set the text for the 'next' direction item">?</a></td>
                                <td>Next text</td>
                                <td><input class='option flex responsive nivo' type='text' name="settings[nextText]" value='<?php if ($this->get_setting('nextText') != 'false') echo $this->get_setting('nextText') ?>' /></td>
                            </tr>
                            <tr>
                                <td><a href="#" class="tooltip" title="Specify any custom CSS Classes you would like to be added to the slider wrapper">?</a></td>
                                <td>CSS classes</td>
                                <td><input type='text' name="settings[cssClass]" value='<?php if ($this->get_setting('cssClass') != 'false') echo $this->get_setting('cssClass') ?>' /></td>
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
                                <td><textarea style="width: 100%">[ml-slider id=<?php echo $this->get_slider() ?>]</textarea></td>
                            </tr>
                        </tbody>
                    </table>

                    <br />
                    <a class='alignright button-secondary confirm' href="?page=ml-slider&delete=<?php echo $this->get_slider() ?>">Delete Slider</a>
                </div>
            </form>
        </div>
        <?php
    }
}
$mlslider = new MLSlider();
?>