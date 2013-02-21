<?php
/*
 *
 */
class MLCoinSlider extends MLSlider {
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
        return parent::wrap_html($this->get_html()) . parent::get_javascript('coinSlider');
    }

    /**
     *
     */
    private function enqueue_scripts() {
        if (parent::get_setting('printJs') == 'true') {
            wp_enqueue_script('ml-slider_coin_slider', MLSLIDER_ASSETS_URL . 'coinslider/coin-slider.min.js', array('jquery'), MLSLIDER_VERSION);
        }

        if (parent::get_setting('printCss') == 'true') {
            wp_enqueue_style('ml-slider_display_css', plugins_url('ml-slider/assets/ml-slider-display.css'));
            wp_enqueue_style('ml-slider_coin_slider_css', plugins_url('ml-slider/assets/coinslider/coin-slider-styles.css'));
        }
    }

    /**
     * Return coin slider markup
     *
     * @return string coin slider markup.
     */
    private function get_html() {
        $identifier = parent::get_identifier();

        $retVal = "<div id='" . $identifier . "' class='coin-slider'>";
        
        foreach (parent::get_slides() as $slide) {
            $url = strlen($slide['url']) ? $slide['url'] : "javascript:void(0)"; // coinslider always wants a URL
            $retVal .= "<a href='{$url}'>";
            $retVal .= "<img src='{$slide['src']}' alt='{$slide['alt']}'>";
            $retVal .= "<span>{$slide['caption']}</span>";
            $retVal .= "</a>";
        }
        
        $retVal .= "</div>";
        
        return $retVal;
    }
}
?>