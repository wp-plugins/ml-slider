<?php
/*
 *
 */
class MLSlider {

    var $id = 0;
    var $identifier = 0;
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
    public function __construct($id) {
        $this->id = $id;
        $this->slides = $this->get_slides();
        $this->settings = $this->get_settings();
        $this->identifier = 'ml_slider_' . rand();
    }


    public function wrap_html($html) {
        return "<div class='ml-slider ml-slider-{$this->get_setting('type')} {$this->get_setting('cssClass')}'>" . $html . "</div>";
    }

    /**
     *
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

            $image_attributes = wp_get_attachment_image_src($query->post->ID, 'ml-slider-slide'); // returns an array

            $slides[] = array(
                'id' => $query->post->ID,
                'type' => 'image',
                'url' => get_post_meta($query->post->ID, 'ml-slider_url', true),
                'caption' => htmlentities($query->post->post_excerpt, ENT_QUOTES),
                'src' => $image_attributes[0],
                'alt' => htmlentities(get_post_meta($query->post->ID, '_wp_attachment_image_alt', true),ENT_QUOTES),
                'menu_order' => $query->post->menu_order
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
    public function get_javascript($func) {
        $identifier = $this->identifier;
        
        $retVal  = "\n<script type='text/javascript'>";
        $retVal .= "\n    var " . $identifier . " = function($) {";
        $retVal .= "\n        $('#" . $identifier . "')." . $func . "({ ";
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
}
?>