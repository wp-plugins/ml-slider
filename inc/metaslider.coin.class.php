<?php
/*
 *
 */
class MetaCoinSlider extends MetaSlider {

    protected $js_function = 'coinslider';

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
        return 'coinslider/coin-slider.min.js';
    }

    /**
     * Return the file path to the CSS for this slider
     */
    public function get_css_path() {
        return 'coinslider/coin-slider-styles.css';
    }

    /**
     * Enable the parameters that are accepted by the slider
     * 
     * @return array enabled parameters
     */
    protected function get_param($param) {
        $params = array(
            'effect' => 'animation',
            'width' => 'width',
            'height' => 'height',
            'sph' => 'sph',
            'spw' => 'spw',
            'delay' => 'delay',
            'sDelay' => 'sDelay',
            'opacity' => 'opacity',
            'titleSpeed' => 'titleSpeed',
            'hoverPause' => 'hoverPause',
            'navigation' => 'navigation'
        );

        if (isset($params[$param])) {
            return $params[$param];
        }

        return false;
    }

    /**
     * Build the HTML for a slider.
     *
     * @return string slider markup.
     */
    protected function get_html() {
        $retVal = "<div id='" . $this->get_identifier() . "' class='coin-slider'>";
        
        foreach ($this->get_slides() as $slide) {
            $url = strlen($slide['url']) ? $slide['url'] : "javascript:void(0)"; // coinslider always wants a URL
            $retVal .= "<a href='{$url}'>";
            $retVal .= "<img src='{$slide['src']}' alt='{$slide['alt']}' target='{$slide['target']}'>";
            $retVal .= "<span>{$slide['caption']}</span>";
            $retVal .= "</a>";
        }
        
        $retVal .= "</div>";
        
        return $retVal;
    }
}
?>