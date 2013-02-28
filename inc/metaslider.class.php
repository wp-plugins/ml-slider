<?php
/**
 *
 */
class MetaSlider {

    public $id = 0; // slider ID
    public $identifier = 0; // unique identifier
    public $slides = array(); //slides belonging to this slider
    public $settings = array(); // slider settings

    /**
     * Constructor
     */
    public function __construct($id) {
        $this->id = $id;
        $this->slides = $this->get_slides();
        $this->settings = $this->get_settings();
        $this->identifier = 'metaslider_' . rand();
    }

    /**
     * Return the unique identifier for the slider (used to avoid javascript conflicts)
     */
    protected function get_identifier() {
        return $this->identifier;
    }

    /**
     * Return slides for the current slider
     *
     * @return array collection of slides belonging to the current slider
     */
    protected function get_slides() {
        $slides = array();

        $args = array(
            'orderby' => $this->get_setting('random') == 'true' && !is_admin() ? 'rand' : 'menu_order',
            'order' => 'ASC',
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'ml-slider',
                    'field' => 'slug',
                    'terms' => $this->id
                )
            )
        );

        $query = new WP_Query($args);

        while ($query->have_posts()) {
            $query->next_post();

            $slide_src = wp_get_attachment_image_src($query->post->ID, 'ml-slider-slide'); // returns an array
            $slide_thumb = wp_get_attachment_image_src($query->post->ID); // returns an array

            $slides[] = array(
                'id' => $query->post->ID,
                'type' => 'image',
                'url' => get_post_meta($query->post->ID, 'ml-slider_url', true),
                'caption' => htmlentities($query->post->post_excerpt, ENT_QUOTES, 'UTF-8'),
                'src' => $slide_src[0],
                'thumb' => $slide_thumb[0],
                'menu_order' => $query->post->menu_order,
                'target' => get_post_meta($query->post->ID, 'ml-slider_new_window', true) ? '_blank' : '_self',
                'alt' => get_post_meta($query->post->ID, '_wp_attachment_image_alt', true)
            );
        }

        return $slides;
    }

    /**
     * Get settings for the current slider
     *
     * @return array slider settings
     */
    private function get_settings() {
        return get_post_meta($this->id, 'ml-slider_settings', true);
    }

    /**
     * Return an individual setting
     *
     * @param string $name Name of the setting
     * @return string | bool setting value or fase
     */
    public function get_setting($name) {
        return isset($this->settings[$name]) && strlen($this->settings[$name]) > 0 ? $this->settings[$name] : "false";
    }

    /**
     * Get the slider libary parameters. This function is overridden by sub classes.
     *
     * @return string javascript options
     */
    public function get_default_parameters() {
        $params = array(
            'type' => 'nivo',
            'random' => false,
            'cssClass' => '',
            'printCss' => true,
            'printJs' => true,
            'width' => 565,
            'height' => 290,
            'spw' => 7,
            'sph' => 5,
            'delay' => 3000,
            'sDelay' => 30,
            'opacity' => 0.7,
            'titleSpeed' => 500,
            'effect' => 'random',
            'navigation' => true,
            'links' => true,
            'hoverPause' => true,
            'theme' => 'default',
            'direction' => 'horizontal',
            'reverse' => false,
            'animationSpeed' => 600,
            'prevText' => 'Previous',
            'nextText' => 'Next',
            'slices' => 15
        );
        
        return $params;
    }

    /**
     *
     */
    private function get_javascript_parameters() {
        $options = array();

        foreach ($this->get_default_parameters() as $name => $default) {
            if ($param = $this->get_param($name)) {
                $val = $this->get_setting($name);

                if (gettype($default) == 'string') {
                    $options[] = $param . ": '" . $val . "'";
                } else {
                    $options[] = $param . ": " . $val;
                }                
            }
        }

        return implode(",\n            ", $options);
    }

    /**
     * Return the Javascript to kick off the slider. Code is wrapped in a timer
     * to allow for themes that load jQuery at the bottom of the page.
     *
     * @return string javascript
     */
    public function get_javascript() {
        $identifier = $this->identifier;
        
        $return_value  = "\n<script type='text/javascript'>";
        $return_value .= "\n    var " . $identifier . " = function($) {";
        $return_value .= "\n        $('#" . $identifier . "')." . $this->js_function . "({ ";
        $return_value .= "\n            " . $this->get_javascript_parameters();
        $return_value .= "\n        });";
        $return_value .= "\n    };";
        $return_value .= "\n    var timer_" . $identifier . " = function() {";
        $return_value .= "\n        if (window.jQuery && jQuery.isReady) {";
        $return_value .= "\n            " . $identifier . "(window.jQuery);";
        $return_value .= "\n        } else {";
        $return_value .= "\n            window.setTimeout(timer_" . $identifier . ", 100);";
        $return_value .= "\n        }";
        $return_value .= "\n    };";
        $return_value .= "\n    timer_" . $identifier . "();";
        $return_value .= "\n</script>";

        return $return_value;
    }

    /**
     * Output the HTML and Javascript for this slider
     */
    public function output() {
        $class = "metaslider metaslider-{$this->get_setting('type')} metaslider-{$this->id} ml-slider";

        if ($this->get_setting('cssClass') != 'false') {
            $class .= " " . $this->get_setting('cssClass');
        }

        return "<div style='max-width: {$this->get_setting('width')}px;' class='{$class}'>" . 
                    $this->get_html() . 
                "</div>" .
                $this->get_javascript();
    }

    /**
     * Include slider assets
     */
    public function enqueue_scripts() {
        if ($this->get_setting('printJs') == 'true') {
            wp_enqueue_script('metaslider_' . $this->get_setting('type') . '_slider', METASLIDER_ASSETS_URL . $this->get_js_path(), array('jquery'), METASLIDER_VERSION);
        }

        if ($this->get_setting('printCss') == 'true') {
            wp_enqueue_style('metaslider_display_css', METASLIDER_ASSETS_URL . 'metaslider-display.css');
            wp_enqueue_style('metaslider_' . $this->get_setting('type') . '_slider_css', METASLIDER_ASSETS_URL . $this->get_css_path());
        }
    }
}
?>