<?php
/*
 *
 */
class MLFlexSlider extends MLSlider {
    /**
     * Constructor
     */
    public function __construct($id) {
        parent::__construct($id);
        $this->enqueue_scripts();
    }

    /**
     *
     */
    public function output() {
        return parent::wrap_html($this->get_html()) . parent::get_javascript('flexSlider');
    }

    /**
     *
     */
    private function enqueue_scripts() {
        if (parent::get_setting('printJs') == 'true') {
            wp_enqueue_script('ml-slider_flex_slider', MLSLIDER_ASSETS_URL . 'flexslider/jquery.flexslider-min.js', array('jquery'), MLSLIDER_VERSION);
        }

        if (parent::get_setting('printCss') == 'true') {
            wp_enqueue_style('ml-slider_display_css', plugins_url('ml-slider/assets/ml-slider-display.css'));
            wp_enqueue_style('ml-slider_flex_slider_css', plugins_url('ml-slider/assets/flexslider/flexslider.css'));
        }
    }

    /**
     * Return coin slider markup
     *
     * @return string coin slider markup.
     */
    private function get_html() {
        $identifier = parent::get_identifier();

        $retVal = "<div id='" . $identifier . "' class='flexslider'><ul class='slides'>";
        
        foreach (parent::get_slides() as $slide) {
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
}
?>