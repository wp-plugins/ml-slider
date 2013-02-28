<?php
/*
 *
 */
class MetaNivoSlider extends MetaSlider {

    protected $js_function = 'nivoSlider';

    /**
     * Constructor
     */
    public function __construct($id) {
        parent::__construct($id);
    }

    /**
     * Return the file path to the Javascript for this slider
     */
    public function get_js_path() {
        return 'nivoslider/jquery.nivo.slider.pack.js';
    }

    /**
     * Return the file path to the CSS for this slider
     */
    public function get_css_path() {
        return 'nivoslider/nivo-slider.css';
    }

    /**
     * Detect whether thie slide supports the requested setting,
     * and if so, the name to use for the setting in the Javascript parameters
     * 
     * @return false (parameter not supported) or parameter name (parameter supported)
     */
    protected function get_param($param) {
        $params = array(
            'effect' => 'effect',
            'slices' => 'slices',
            'prevText' => 'prevText',
            'nextText' => 'nextText',
            'delay' => 'pauseTime',
            'animationSpeed' => 'animSpeed',
            'hoverPause' => 'pauseOnHover',
            'spw' => 'boxCols',
            'sph' => 'boxRows',
            'navigation' => 'controlNav',
            'links' =>'directionNav'
        );

        if (isset($params[$param])) {
            return $params[$param];
        }

        return false;
    }

    /**
     * Include slider assets
     */
    public function enqueue_scripts() {
        parent::enqueue_scripts();

        // include the theme
        if ($this->get_setting('printCss') == 'true') {
            $theme = $this->get_setting('theme');
            wp_enqueue_style('ml-slider_nivo_slider_theme_' . $theme, METASLIDER_ASSETS_URL  . "nivoslider/themes/{$theme}/{$theme}.css");
        }
    }

    /**
     * Build the HTML for a slider.
     *
     * @return string slider markup.
     */
    protected function get_html() {
        $retVal  = "<div class='slider-wrapper theme-{$this->get_setting('theme')}'>";
        $retVal .= "<div class='ribbon'></div>";
        $retVal .= "<div id='" . $this->get_identifier() . "' class='nivoSlider'>";
        
        foreach ($this->get_slides() as $slide) {
            if (strlen($slide['url'])) $retVal .= "<a href='{$slide['url']}' target='{$slide['target']}'>";
            $retVal .= "<img src='{$slide['src']}' title='{$slide['caption']}' alt='{$slide['alt']}'>";
            if (strlen($slide['url'])) $retVal .= "</a>";
        }
        
        $retVal .= "</div></div>";
        
        return $retVal;
    }
}
?>