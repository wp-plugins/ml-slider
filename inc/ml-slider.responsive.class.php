<?php
/*
 *
 */
class MLResponsiveSlider extends MLSlider {
    /**
     * Constructor
     */
    public function __construct($id) {
        parent::__construct($id);
        $this->enqueue_scripts();
    }

    public function output() {
        return parent::wrap_html($this->get_html()) . parent::get_javascript('responsiveSlides');
    }

    /**
     *
     */
    private function enqueue_scripts() {
        if (parent::get_setting('printJs') == 'true') {
            wp_enqueue_script('ml-slider_responsive_slides', MLSLIDER_ASSETS_URL . 'responsiveslides/responsiveslides.min.js', array('jquery'), MLSLIDER_VERSION);
        }

        if (parent::get_setting('printCss') == 'true') {
            wp_enqueue_style('ml-slider_responsive_slides_css', plugins_url('ml-slider/assets/responsiveslides/responsiveslides.css'));
        }
    }

    /**
     * Return coin slider markup
     *
     * @return string coin slider markup.
     */
    private function get_html() {
        $identifier = parent::get_identifier();

        $retVal = "<ul id='" . $identifier . "' class='rslides'>";
        
        foreach (parent::get_slides() as $slide) {
            $retVal .= "<li>";
            if (strlen($slide['url'])) $retVal .= "<a href='{$slide['url']}'>";
            $retVal .= "<img src='{$slide['src']}' alt='{$slide['alt']}'>";
            if (strlen($slide['url'])) $retVal .= "</a>";
            $retVal .= "</li>";
        }
        
        $retVal .= "</ul>";
        
        return $retVal;
    }
}
?>