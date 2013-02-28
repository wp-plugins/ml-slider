<?php
/*
 * Plugin Name: Meta Slider
 * Plugin URI: http://www.metaslider.com
 * Description: 4 sliders in 1! Choose from Nivo Slider, Flex Slider, Coin Slider or Responsive Slides.
 * Version: 1.3
 * Author: Matcha Labs
 * Author URI: http://www.matchalabs.com
 * License: GPLv2 or later
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

define('METASLIDER_VERSION', '1.3');
define('METASLIDER_BASE_URL', plugin_dir_url(__FILE__));
define('METASLIDER_ASSETS_URL', METASLIDER_BASE_URL . 'assets/');
define('METASLIDER_BASE_DIR_LONG', dirname(__FILE__));
define('METASLIDER_INC_DIR', METASLIDER_BASE_DIR_LONG . '/inc/');

require_once( METASLIDER_INC_DIR . 'metaslider.class.php' );
require_once( METASLIDER_INC_DIR . 'metaslider.coin.class.php' );
require_once( METASLIDER_INC_DIR . 'metaslider.flex.class.php' );
require_once( METASLIDER_INC_DIR . 'metaslider.nivo.class.php' );
require_once( METASLIDER_INC_DIR . 'metaslider.responsive.class.php' );

class MetaSliderPlugin {

    var $slider = null;

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'register_admin_menu'), 10001);
        add_action('init', array($this, 'register_post_type' ));
        add_action('init', array($this, 'register_taxonomy' ));
        add_action('admin_print_styles', array( $this, 'register_admin_styles'));
        add_shortcode('metaslider', array($this, 'register_shortcode'));
        add_shortcode('ml-slider', array($this, 'register_shortcode')); // backwards compatibility
        load_plugin_textdomain( 'metaslider', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    /**
     * Registers and enqueues admin-specific styles.
     */
    public function register_admin_styles() {
        wp_enqueue_style('metaslider-tipsy-styles', METASLIDER_ASSETS_URL . 'tipsy/tipsy.css');
        wp_enqueue_style('metaslider-admin-styles', METASLIDER_ASSETS_URL . 'metaslider-admin.css');
    }
    
    /**
     * Registers and enqueues admin-specific JavaScript.
     */
    public function register_admin_scripts() {
        wp_enqueue_media();
        wp_enqueue_script('jquery-ui-core', array('jquery'));
        wp_enqueue_script('jquery-ui-sortable', array('jquery', 'jquery-ui-core'));
        wp_enqueue_script('metaslider-tipsy', METASLIDER_ASSETS_URL . 'tipsy/jquery.tipsy.js', array('jquery'));
        wp_enqueue_script('metaslider-admin-script', METASLIDER_ASSETS_URL . 'metaslider.js', array('jquery', 'metaslider-tipsy', 'media-upload'));
        wp_localize_script( 'metaslider-admin-script', 'metaslider', array( 
            'url' => __("URL", 'metaslider'), 
            'caption' => __("Caption", 'metaslider'),
            'new_window' => __("New Window", 'metaslider'),
            'confirm' => __("Are you sure?", 'metaslider')
        ));
    }
    
    /**
     * Include the default CSS
     */
    public function enqueue_scripts() {
        wp_enqueue_style('metaslider_display_css', METASLIDER_ASSETS_URL . 'metaslider-display.css');
    }
    
    /**
     * Add the menu page
     */
    public function register_admin_menu() {
        $page = add_menu_page('MetaSlider', 'MetaSlider', 'edit_others_posts', 'metaslider', array(
            $this, 'render_admin_page'
        ), METASLIDER_ASSETS_URL . 'matchalabs.png', 9501);

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
            'rewrite' => array('slug' => 'ml-slider')
        );

        register_taxonomy( 'ml-slider', 'attachment', $args );
    }

    /**
     * Current slide ID
     */
    private function set_slider($id) {
        $this->slider = new MetaSlider($id);
    }

    /**
     * Handle slide uploads/changes
     */
    public function admin_process() {
        if (isset($_REQUEST['id'])) {
            $slider_id = $_REQUEST['id'];
        } else {
            $slider_id = $this->find_slider('date', 'DESC');
        }

        $this->set_slider($slider_id);

        $this->handle_slide_updates();
        $this->handle_delete_slider();
        $this->handle_delete_slide();
        $this->handle_update_slider_title();
        $this->handle_update_slider_settings();
        $this->handle_create_slider();

        $this->set_slider($this->slider->id); // refresh
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
            if (!$this->slider->id) {
                $this->set_slider($the_query->post->ID);
            }
            
            $the_query->the_post();
            $active = $this->slider->id == $the_query->post->ID ? true : false;
            
            $sliders[] = array(
                'active' => $active,
                'title' => get_the_title(),
                'id' => $the_query->post->ID
            );
        }
        
        return $sliders;
    }

    /**
     * Create a new slider
     */
    private function handle_create_slider() {
        // create a new slider
        if (isset($_GET['add'])) {
            $defaults = array();

            // if possible, take a copy of the last edited slider settings in place of default settings
            if ($last_modified = $this->find_slider('modified', 'DESC')) {
                $defaults = get_post_meta($last_modified, 'ml-slider_settings', true);
            }

            // insert the post
            $id = wp_insert_post(array(
                'post_title' => 'New Slider',
                'post_status' => 'publish',
                'post_type' => 'ml-slider'
            ));

            // use the default settings if we can't find anything more suitable.
            if (empty($defaults)) {
                $slider = new MetaSlider($id);
                $defaults = $slider->get_default_parameters();
            }

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
            $old_settings = $this->slider->settings;

            $new_settings = $_POST['settings'];
            
            // convert submitted checkbox values from 'on' or 'off' to boolean values
            $checkboxes = array('hoverPause', 'links', 'navigation', 'reverse', 'random', 'printCss', 'printJs');

            foreach ($checkboxes as $checkbox) {
                if (isset($new_settings[$checkbox]) && $new_settings[$checkbox] == 'on') {
                    $new_settings[$checkbox] = "true";
                } else {
                    $new_settings[$checkbox] = "false";
                }
            }

            // update the slider settings
            update_post_meta($this->slider->id, 'ml-slider_settings', array_merge($old_settings, $new_settings));
        }
    }

    /**
     * Update slider title
     */
    private function handle_update_slider_title() {
        if (isset($_POST['title'])) {
            $slide = array(
                'ID' => $this->slider->id,
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
            $term = get_term_by('name', $this->slider->id, 'ml-slider');

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
                $term = get_term_by('name', $this->slider->id, 'ml-slider');

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

                // store the new window setting as a meta field against the attachment
                if (isset($fields['new_window']) && $fields['new_window'] == 'on') {
                    if (get_post_meta($id, 'ml-slider_new_window')) {
                        update_post_meta($id, 'ml-slider_new_window', 'true');
                    } else {
                        add_post_meta($id, 'ml-slider_new_window', 'true', true);
                    }
                } else {
                    if (get_post_meta($id, 'ml-slider_new_window')) {
                        delete_post_meta($id, 'ml-slider_new_window');
                    } 
                }

                // add a new image size for the current slider
                add_image_size('ml-slider-slide', $this->slider->get_setting('width'), $this->slider->get_setting('height'), true);
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

        // good to go
        $this->set_slider($id);

        switch ($this->slider->get_setting('type')) {
            case('coin'):
                $slider = new MetaCoinSlider($id);
                break;
            case('flex'):
                $slider = new MetaFlexSlider($id);
                break;
            case('nivo'):
                $slider = new MetaNivoSlider($id);
                break;
            case('responsive'):
                $slider = new MetaResponsiveSlider($id);
                break;
        }

        $slider->enqueue_scripts();

        return $slider->output();
        
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
        $this->admin_process();
        ?>

        <div class="wrap metaslider">
            <form accept-charset="UTF-8" action="?page=metaslider&id=<?php echo $this->slider->id ?>" method="post">

                <h2 class="nav-tab-wrapper">
                    <?php
                        if ($tabs = $this->get_sliders()) {
                            foreach ($tabs as $tab) {
                                if ($tab['active']) {
                                    echo "<div class='nav-tab nav-tab-active'><input type='text' name='title'  value='" . $tab['title'] . "' onkeypress='this.style.width = ((this.value.length + 1) * 9) + \"px\"' /></div>";
                                } else {
                                    echo "<a href='?page=metaslider&id={$tab['id']}' class='nav-tab'>" . $tab['title'] . "</a>";
                                }
                            }                           
                        }
                    ?>
                    
                    <a href="?page=metaslider&add=true" id="create_new_tab" class="nav-tab">+</a>
                </h2>

                <?php
                    if (!$this->slider->id) {
                        return;
                    }
                ?>

                <div class="left">
                    <table class="widefat sortable slides">
                        <thead>
                            <tr>
                                <th style="width: 100px;"><?php _e("Slides", 'metaslider') ?></th>
                                <th><input class='upload_image_button alignright button-secondary' type='button' value='<?php _e("Add Slide", 'metaslider') ?>' data-uploader_title='<?php _e("Select Slide", 'metaslider') ?>' data-uploader_button_text='<?php _e("Add to slider", 'metaslider') ?>' /></th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php
                                foreach($this->slider->slides as $slide) {
                                    $new_window_checked = $slide['target'] == '_blank' ? 'checked=checked' : '';
                                    $str_caption = __("Caption", 'metaslider');
                                    $str_new_window = __("New Window", 'metaslider');
                                    $str_url = __("URL", 'metaslider');

                                    echo "<tr class='slide'>";
                                    echo "<td class='col-1'>";
                                    echo "<div style='position: absolute'><a class='delete-slide confirm' href='?page=metaslider&id={$this->slider->id}&deleteSlide={$slide['id']}'>x</a></div>";
                                    echo "<img src='{$slide['thumb']}' width='150px'></td>";
                                    echo "<td class='col-2'>";
                                    echo "<textarea name='attachment[{$slide['id']}][post_excerpt]' placeholder='{$str_caption}'>{$slide['caption']}</textarea>";
                                    echo "<input class='url' type='text' name='attachment[{$slide['id']}][url]' placeholder='{$str_url}' value='{$slide['url']}' />";
                                    echo "<div class='new_window'><label>{$str_new_window}";
                                    echo "<input type='checkbox' name='attachment[{$slide['id']}][new_window]' {$new_window_checked} />";
                                    echo "</label></div>";
                                    echo "<input type='hidden' class='menu_order' name='attachment[{$slide['id']}][menu_order]' value={$slide['menu_order']} />";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class='right'>
                    <table class="widefat settings">
                        <thead>
                            <tr>
                                <th><?php _e("Configuration", 'metaslider') ?></th>
                                <th>
                                    <input type='submit' value='<?php _e("Save", 'metaslider') ?>' class='alignright button-primary' />
                                    <div class='unsaved tooltip' style='display: none;' title='<?php _e("Unsaved Changes", 'metaslider') ?>'>!</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan='2'>
                                    <div class='slider-lib nivo'>
                                        <label for='nivo' title='Version: 3.2<br />Responsive: Yes<br />Effects: 14<br />Size: 12kb<br />Mobile Friendly: Yes<br />Themes: 4' class='tooltiptop'>NivoSlider</label>
                                        <input class="select-slider" id='nivo' rel='nivo' type='radio' name="settings[type]" <?php if ($this->slider->get_setting('type') == 'nivo') echo 'checked=checked' ?> value='nivo' />
                                    </div>
                                    <div class='slider-lib coin'>
                                        <label for='coin' title='Version: 1.0<br />Responsive: No<br />Effects: 4<br />Size: 8kb<br />Mobile Friendly: Yes' class='tooltiptop'>CoinSlider</label>
                                        <input class="select-slider" id='coin' rel='coin' type='radio' name="settings[type]" <?php if ($this->slider->get_setting('type') == 'coin') echo 'checked=checked' ?> value='coin' />
                                    </div>
                                    <div class='slider-lib flex'>
                                        <label for='flex' title='Version: 2.1<br />Responsive: Yes<br />Effects: 2<br />Size: 17kb<br />Mobile Friendly: Yes' class='tooltiptop'>FlexSlider</label>
                                        <input class="select-slider" id='flex' rel='flex' type='radio' name="settings[type]" <?php if ($this->slider->get_setting('type') == 'flex') echo 'checked=checked' ?> value='flex' />
                                    </div>
                                    <div class='slider-lib responsive'>
                                        <label for='responsive' title='Version: 1.53<br />Responsive: Yes<br />Effects: 1<br />Size: 3kb<br />Mobile Friendly: Yes' class='tooltiptop'>Responsive</label>
                                        <input class="select-slider" id='responsive' rel='responsive' type='radio' name="settings[type]" <?php if ($this->slider->get_setting('type') == 'responsive') echo 'checked=checked' ?> value='responsive' />
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class='tooltip' title="<?php _e("Set the initial size for the slides (width x height)", 'metaslider') ?>">
                                    <?php _e("Size", 'metaslider') ?>
                                </td>
                                <td>
                                    <input type='text' size='3' name="settings[width]" value='<?php echo $this->slider->get_setting('width') ?>' />px X
                                    <input type='text' size='3' name="settings[height]" value='<?php echo $this->slider->get_setting('height') ?>' />px
                                </td>
                            </tr>
                            <tr>
                                <td class='tooltip' title="<?php _e("Slide transition effect", 'metaslider') ?>">
                                    <?php _e("Effect", 'metaslider') ?>
                                </td>
                                <td>
                                    <select name="settings[effect]" class='effect option coin nivo flex'>
                                        <option class='option coin nivo' value='random' <?php if ($this->slider->get_setting('effect') == 'random') echo 'selected=selected' ?>>Random</option>
                                        <option class='option coin' value='swirl' <?php if ($this->slider->get_setting('effect') == 'swirl') echo 'selected=selected' ?>>Swirl</option>
                                        <option class='option coin' value='rain' <?php if ($this->slider->get_setting('effect') == 'rain') echo 'selected=selected' ?>>Rain</option>
                                        <option class='option coin' value='straight' <?php if ($this->slider->get_setting('effect') == 'straight') echo 'selected=selected' ?>>Straight</option>
                                        <option class='option nivo' value='sliceDown' <?php if ($this->slider->get_setting('effect') == 'sliceDown') echo 'selected=selected' ?>>Slice Down</option>
                                        <option class='option nivo' value='sliceUp' <?php if ($this->slider->get_setting('effect') == 'sliceUp') echo 'selected=selected' ?>>Slice Up</option>
                                        <option class='option nivo' value='sliceUpLeft' <?php if ($this->slider->get_setting('effect') == 'sliceUpLeft') echo 'selected=selected' ?>>Slice Up Left</option>
                                        <option class='option nivo' value='sliceUpDown' <?php if ($this->slider->get_setting('effect') == 'sliceUpDown') echo 'selected=selected' ?>>Slice Up Down</option>
                                        <option class='option nivo' value='sliceUpDownLeft' <?php if ($this->slider->get_setting('effect') == 'sliceUpDownLeft') echo 'selected=selected' ?>>Slice Up Down Left</option>
                                        <option class='option nivo' value='fold' <?php if ($this->slider->get_setting('effect') == 'fold') echo 'selected=selected' ?>>Fold</option>
                                        <option class='option nivo flex' value='fade' <?php if ($this->slider->get_setting('effect') == 'fade') echo 'selected=selected' ?>>Fade</option>
                                        <option class='option nivo' value='slideInRight' <?php if ($this->slider->get_setting('effect') == 'slideInRight') echo 'selected=selected' ?>>Slide In Right</option>
                                        <option class='option nivo' value='slideInLeft' <?php if ($this->slider->get_setting('effect') == 'slideInLeft') echo 'selected=selected' ?>>Slide In Left</option>
                                        <option class='option nivo' value='boxRandom' <?php if ($this->slider->get_setting('effect') == 'boxRandom') echo 'selected=selected' ?>>Box Random</option>
                                        <option class='option nivo' value='boxRain' <?php if ($this->slider->get_setting('effect') == 'boxRain') echo 'selected=selected' ?>>Box Rain</option>
                                        <option class='option nivo' value='boxRainReverse' <?php if ($this->slider->get_setting('effect') == 'boxRainReverse') echo 'selected=selected' ?>>Box Rain Reverse</option>
                                        <option class='option nivo' value='boxRainGrowReverse' <?php if ($this->slider->get_setting('effect') == 'boxRainGrowReverse') echo 'selected=selected' ?>>Box Rain Grow Reverse</option>
                                        <option class='option flex' value='slide' <?php if ($this->slider->get_setting('effect') == 'slide') echo 'selected=selected' ?>>Slide</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class='tooltip' title="<?php _e("Change the slider style", 'metaslider') ?>">
                                    <?php _e("Theme", 'metaslider') ?>
                                </td>
                                <td>
                                    <select class='option nivo' name="settings[theme]">
                                        <option value='default' <?php if ($this->slider->get_setting('theme') == 'default') echo 'selected=selected' ?>>Default</option>
                                        <option value='dark' <?php if ($this->slider->get_setting('theme') == 'dark') echo 'selected=selected' ?>>Dark</option>
                                        <option value='light' <?php if ($this->slider->get_setting('theme') == 'light') echo 'selected=selected' ?>>Light</option>
                                        <option value='bar' <?php if ($this->slider->get_setting('theme') == 'bar') echo 'selected=selected' ?>>Bar</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class='tooltip' title="<?php _e("Show slide navigation row", 'metaslider') ?>">
                                    <?php _e("Show Navigation", 'metaslider') ?>
                                </td>
                                <td>
                                    <input class='option coin responsive nivo flex' type='checkbox' name="settings[navigation]" <?php if ($this->slider->get_setting('navigation') == 'true') echo 'checked=checked' ?> />
                                </td>
                            </tr>
                            <tr>
                                <td class='tooltip' title="<?php _e("Show previous and next links", 'metaslider') ?>">
                                    <?php _e("Show Links", 'metaslider') ?>
                                </td>
                                <td>
                                    <input class='option responsive nivo flex' type='checkbox' name="settings[links]" <?php if ($this->slider->get_setting('links') == 'true') echo 'checked=checked' ?> />
                                </td>
                            </tr>
                            <tr>
                                <td class='tooltip' title="<?php _e("Pause the slideshow when hovering over slider, then resume when no longer hovering", 'metaslider') ?>">
                                    <?php _e("Hover pause", 'metaslider') ?>
                                </td>
                                <td>
                                    <input class='option coin flex responsive nivo' type='checkbox' name="settings[hoverPause]" <?php if ($this->slider->get_setting('hoverPause') == 'true') echo 'checked=checked' ?> />
                                </td>
                            </tr>
                            <tr>
                                <td class='tooltip' title="<?php _e("How long to display each slide, in milliseconds", 'metaslider') ?>">
                                    <?php _e("Slide delay", 'metaslider') ?>
                                </td>
                                <td>
                                    <input class='option coin flex responsive nivo' type='text' size='5' name="settings[delay]" value='<?php echo $this->slider->get_setting('delay') ?>' />ms
                                </td>
                            </tr>
                            <tr>
                                <td class='tooltip' title="<?php _e("Randomise the order of the slides", 'metaslider') ?>">
                                    <?php _e("Random", 'metaslider') ?>
                                </td>
                                <td>
                                    <input type='checkbox' name="settings[random]" <?php if ($this->slider->get_setting('random') == 'true') echo 'checked=checked' ?> />
                                </td>
                            </tr>
                            <tr>
                                <td class='tooltip' title="<?php _e("Select the sliding direction", 'metaslider') ?>"><?php _e("Direction", 'metaslider') ?></td>
                                <td>
                                    <select class='option flex' name="settings[direction]">
                                        <option value='horizontal' <?php if ($this->slider->get_setting('direction') == 'horizontal') echo 'selected=selected' ?>>Horizontal</option>
                                        <option value='vertical' <?php if ($this->slider->get_setting('direction') == 'vertical') echo 'selected=selected' ?>>Vertical</option>
                                    </select>                       
                                </td>
                            </tr>
                            <tr>
                                <td class='tooltip' title="<?php _e("Set the text for the 'previous' direction item", 'metaslider') ?>">
                                    <?php _e("Previous text", 'metaslider') ?>
                                </td>
                                <td>
                                    <input class='option flex responsive nivo' type='text' name="settings[prevText]" value='<?php if ($this->slider->get_setting('prevText') != 'false') echo $this->slider->get_setting('prevText') ?>' />
                                </td>
                            </tr>
                            <tr>
                                <td class='tooltip' title="<?php _e("Set the text for the 'next' direction item", 'metaslider') ?>">
                                    <?php _e("Next text", 'metaslider') ?>
                                </td>
                                <td>
                                    <input class='option flex responsive nivo' type='text' name="settings[nextText]" value='<?php if ($this->slider->get_setting('nextText') != 'false') echo $this->slider->get_setting('nextText') ?>' />
                                </td>
                            </tr>
                            <tr>
                                <td colspan='2' class='highlight'><?php _e("Advanced Settings", 'metaslider') ?></td>
                            </tr>
                            <tr>
                                <td class='tooltip' title="<?php _e("Reverse the animation direction", 'metaslider') ?>">
                                    <?php _e("Reverse", 'metaslider') ?>
                                </td>
                                <td>
                                    <input class='option flex' type='checkbox' name="settings[reverse]" <?php if ($this->slider->get_setting('reverse') == 'true') echo 'checked=checked' ?> />
                                </td>
                            </tr>
                            <tr>
                                <td class='tooltip' title="<?php _e("Number of squares (width x height)", 'metaslider') ?>">
                                    <?php _e("Number of squares", 'metaslider') ?>
                                </td>
                                <td>
                                    <input class='option coin nivo' type='text' size='2' name="settings[spw]" value='<?php echo $this->slider->get_setting('spw') ?>' /> x 
                                    <input class='option coin nivo' type='text' size='2' name="settings[sph]" value='<?php echo $this->slider->get_setting('sph') ?>' />
                                </td>
                            </tr>
                            <tr>
                                <td class='tooltip' title="<?php _e("Number of slices", 'metaslider') ?>">
                                    <?php _e("Number of slices", 'metaslider') ?>
                                </td>
                                <td>
                                    <input class='option nivo' type='text' size='2' name="settings[slices]" value='<?php echo $this->slider->get_setting('slices') ?>' />
                                </td>
                            </tr>
                            <tr>
                                <td class='tooltip' title="<?php _e("Delay beetwen squares in ms", 'metaslider') ?>">
                                    <?php _e("Square delay", 'metaslider') ?>
                                </td>
                                <td>
                                    <input class='option coin' type='text' size='5' name="settings[sDelay]" value='<?php echo $this->slider->get_setting('sDelay') ?>' />ms
                                </td>
                            </tr>
                            <tr>
                                <td class='tooltip' title="<?php _e("Opacity of title and navigation", 'metaslider') ?>">
                                    <?php _e("Opacity", 'metaslider') ?>
                                </td>
                                <td>
                                    <input class='option coin' type='text' size='5' name="settings[opacity]" value='<?php echo $this->slider->get_setting('opacity') ?>' />
                                </td>
                            </tr>
                            <tr>
                                <td class='tooltip' title="<?php _e("Set the fade in speef of the caption", 'metaslider') ?>">
                                    <?php _e("Caption speed", 'metaslider') ?>
                                </td>
                                <td>
                                    <input class='option coin' type='text' size='5' name="settings[titleSpeed]" value='<?php echo $this->slider->get_setting('titleSpeed') ?>' />ms
                                </td>
                            </tr>
                            <tr>
                                <td class='tooltip' title="<?php _e("Set the speed of animations, in milliseconds", 'metaslider') ?>">
                                    <?php _e("Animation speed", 'metaslider') ?>
                                </td>
                                <td>
                                    <input class='option flex responsive nivo' type='text' size='5' name="settings[animationSpeed]" value='<?php echo $this->slider->get_setting('animationSpeed') ?>' />ms
                                </td>
                            </tr>
                            <tr>
                                <td colspan='2' class='highlight'><?php _e("Developer Options", 'metaslider') ?></td>
                            </tr>
                            <tr>
                                <td class='tooltip' title="<?php _e("Specify any custom CSS Classes you would like to be added to the slider wrapper", 'metaslider') ?>">
                                    <?php _e("CSS classes", 'metaslider') ?>
                                </td>
                                <td>
                                    <input type='text' name="settings[cssClass]" value='<?php if ($this->slider->get_setting('cssClass') != 'false') echo $this->slider->get_setting('cssClass') ?>' />
                                </td>
                            </tr>
                            <tr>
                                <td class='tooltip' title="<?php _e("Uncheck this is you would like to include your own CSS", 'metaslider') ?>">
                                    <?php _e("Print CSS", 'metaslider') ?>
                                </td>
                                <td>
                                    <input type='checkbox' name="settings[printCss]" <?php if ($this->slider->get_setting('printCss') == 'true') echo 'checked=checked' ?> />
                                </td>
                            </tr>
                            <tr>
                                <td class='tooltip' title="<?php _e("Uncheck this is you would like to include your own Javascript", 'metaslider') ?>">
                                    <?php _e("Print JS", 'metaslider') ?>
                                </td>
                                <td>
                                    <input type='checkbox' name="settings[printJs]" <?php if ($this->slider->get_setting('printJs') == 'true') echo 'checked=checked' ?> />
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <table class="widefat shortcode">
                        <thead>
                            <tr>
                                <th><?php _e("Usage", 'metaslider') ?></th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td class='highlight'><?php _e("Shortcode", 'metaslider') ?></td>
                            </tr>
                            <tr>
                                <td><input readonly='readonly' type='text' value='[metaslider id=<?php echo $this->slider->id ?>]' /></td>
                            </tr>
                            <tr>
                                <td class='highlight'><?php _e("Template Include", 'metaslider') ?></td>
                            </tr>
                            <tr>
                                <td><input readonly='readonly' type='text' value='&lt;?php echo do_shortcode("[metaslider id=<?php echo $this->slider->id ?>]"); ?>' /></td>
                            </tr>
                        </tbody>

                    </table>

                    <br />
                    <a class='alignright button-secondary confirm' href="?page=metaslider&delete=<?php echo $this->slider->id ?>"><?php _e("Delete Slider", 'metaslider') ?></a>
                </div>
            </form>
        </div>
        <?php
    }
}

$metaslider = new MetaSliderPlugin();
?>