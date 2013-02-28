<?php
/*
 *
 */
class MetaFlexSlider extends MetaSlider {

    protected $js_function = 'flexslider';

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
        return 'flexslider/jquery.flexslider-min.js';
    }

    /**
     * Return the css path to the Javascript for this slider
     */
    public function get_css_path() {
        return 'flexslider/flexslider.css';
    }

    /**
     * Enable the parameters that are accepted by the slider
     * 
     * @return array enabled parameters
     */
    protected function get_param($param) {
        $params = array(
            'effect' => 'animation',
            'direction' => 'direction',
            'prevText' => 'prevText',
            'nextText' => 'nextText',
            'delay' => 'slideshowSpeed',
            'animationSpeed' => 'animationSpeed',
            'hoverPause' => 'pauseOnHover',
            'reverse' => 'reverse',
            'navigation' => 'controlNav',
            'links' =>'directionNav'
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
        $return_value = "<div id='" . $this->get_identifier() . "' class='flexslider'><ul class='slides'>";
        
        foreach ($this->get_slides() as $slide) {
            $return_value .= "<li>";
            if (strlen($slide['url'])) $return_value .= "<a href='{$slide['url']}' target='{$slide['target']}'>";
            $return_value .= "<img src='{$slide['src']}' alt='{$slide['alt']}'>";
            if (strlen($slide['caption'])) $return_value .= "<p class='flex-caption'>{$slide['caption']}</p>";
            if (strlen($slide['url'])) $return_value .= "</a>";
            $return_value .= "</li>";
        }
        
        $return_value .= "</ul></div>";

        return $return_value;
    }
}
?>