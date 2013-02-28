<?php
/*
 *
 */
class MetaResponsiveSlider extends MetaSlider {

    public $js_function = 'responsiveSlides';

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
        return 'responsiveslides/responsiveslides.min.js';
    }

    /**
     * Return the file path to the CSS for this slider
     */
    public function get_css_path() {
        return 'responsiveslides/responsiveslides.css';
    }

    /**
     * Detect whether thie slide supports the requested setting,
     * and if so, the name to use for the setting in the Javascript parameters
     * 
     * @return false (parameter not supported) or parameter name (parameter supported)
     */
    protected function get_param($param) {
        $params = array(
            'prevText' => 'prevText',
            'nextText' => 'nextText',
            'delay' => 'timeout',
            'animationSpeed' => 'speed',
            'hoverPause' => 'pause',
            'navigation' => 'pager',
            'links' =>'nav'
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
        $return_value = "<ul id='" . $this->get_identifier() . "' class='rslides'>";
        
        foreach ($this->get_slides() as $slide) {
            $return_value .= "<li>";
            if (strlen($slide['url'])) $return_value .= "<a href='{$slide['url']}' target='{$slide['target']}'>";
            $return_value .= "<img src='{$slide['src']}' alt='{$slide['alt']}'>";
            if (strlen($slide['url'])) $return_value .= "</a>";
            $return_value .= "</li>";
        }
        
        $return_value .= "</ul>";
        
        return $return_value;
    }
}
?>