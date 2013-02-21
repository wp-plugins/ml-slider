<?php
/*
 *
 */
class MLNivoSlider extends MLSlider {
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
        return parent::wrap_html($this->get_html()) . parent::get_javascript('nivoSlider');
    }

    /**
     *
     */
    private function enqueue_scripts() {
        if (parent::get_setting('printJs') == 'true') {
            wp_enqueue_script('ml-slider_nivo_slider', MLSLIDER_ASSETS_URL . 'nivoslider/jquery.nivo.slider.pack.js', array('jquery'), MLSLIDER_VERSION);
        }

        if (parent::get_setting('printCss') == 'true') {
            wp_enqueue_style('ml-slider_display_css', plugins_url('ml-slider/assets/ml-slider-display.css'));
            wp_enqueue_style('ml-slider_nivo_slider_css', plugins_url('ml-slider/assets/nivoslider/nivo-slider.css'));
            wp_enqueue_style('ml-slider_nivo_slider_theme_' . $parent::get_setting('theme'), plugins_url('ml-slider/assets/nivoslider/themes/' . $parent::get_setting('theme') . '/' . $parent::get_setting('theme') . '.css'));
        }
    }

    /**
     * Return coin slider markup
     *
     * @return string coin slider markup.
     */
    private function get_html() {
        $identifier = parent::get_identifier();

        $retVal  = "<div class='slider-wrapper theme-{$this->get_setting('theme')}'>";
        $retVal .= "<div class='ribbon'></div>";
        $retVal .= "<div id='" . $identifier . "' class='nivoSlider'>";
        
        foreach (parent::get_slides() as $slide) {
            if (strlen($slide['url'])) $retVal .= "<a href='{$slide['url']}'>";
            $retVal .= "<img src='{$slide['src']}' title='{$slide['caption']}' alt='{$slide['alt']}'>";
            if (strlen($slide['url'])) $retVal .= "</a>";
        }
        
        $retVal .= "</div></div>";
        
        return $retVal;
    }
}
?>