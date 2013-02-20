=== Plugin Name ===
Contributors: matchalabs
Tags: wordpress slider,slider,slides,slideshow,wordpress slideshow,image slider,flexslider,flex,flex slider,nivoslider,nivo,nivo slider,responsiveslides,responsive,responsive slides,coinslider,coin,coin slider,slideshow,carousel,responsive slider,slider plugin,vertical slides,ml slider,image rotator
Requires at least: 3.5
Tested up to: 3.5
Stable tag: 1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

4 image sliders in 1! Choose from Nivo Slider, Flex Slider, Coin Slider or Responsive Slides.

== Description ==
ML Slider is a clean slideshow management plugin that leaves the heavy lifting to a selection of popular jQuery slider plugins - the choice is yours. ML Slider builds upon standard WordPress functionality wherever possible; slideshows are stored as a custom post type, slides are stored as media files and the relation between the two is stored as taxonomy data.

http://www.youtube.com/watch?v=SdPKN7rTfM8

ML Slider Features:
* Intuitive administration panel
* Create unlimited slideshows with unlimited number of slides
* Choose slider library from Nivo Slider, Flex Slider 2, Coin Slider or Responsive Slides (per slideshow)
* Add captions to slides
* Add URLs to slides
* Reorder slides with drag and drop
* Create new slides from your Media Library, or upload new images
* Mix & Match! Include as many slideshows on the same page as you want
* Option to include your own CSS
* Lightweight, only the bare minimum in terms of JavaScript and CSS is outputted to your website
* Built in shortcode

Slider Features:
* 18 transition effects
* 4 themes (Nivo Slider)
* Responsive (Nivo Slider, Flex Slider 2, Responsive Slides)
* Adjust slider libary options such as: speed, theme, hover pause, width, height

Read more and thanks to:
* [http://flexslider.woothemes.com/](http://flexslider.woothemes.com/)
* [http://responsive-slides.viljamis.com/](http://responsive-slides.viljamis.com/)
* [http://workshop.rs/projects/coin-slider/](http://workshop.rs/projects/coin-slider/)
* [http://dev7studios.com/nivo-slider/](http://dev7studios.com/nivo-slider/)

== Installation ==

1. Upload the `ml-slider` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Manage your slideshows using the 'ML Slider' menu option

== Frequently Asked Questions ==

= How do I include a slideshow directly in my templates? =

`<?php echo do_shortcode("[ml-slider id=#]") ?>`

= Why are some effects/options greyed out? =

The effects are enabled/disabled depending on which library you have selected.

For example, flex slider supports the 'Fade' and 'Slide' effect whereas coin slider supports 'Random', 'Swirl', 'Straight' and 'Rain'. Unavailable options are greyed out.

== Screenshots ==

1. Administration panel - overview
2. Nivo Slider example
3. Coin Slider example
4. Flex Slider example
5. Responsive Slides example
6. Administration panel - selecting slides

== Changelog ==

= 1.2.1 =
* Fix: Number of slides per slideshow limited to WordPress 'blog pages show at most' setting (reported by and thanks to: Kenny)
* Fix: Add warning when BMP file is added to slider (reported by and thanks to MadBong)
* Fix: Allow images smaller than default thumbnail size to be added to slider (reported by and thanks to: MadBong)

= 1.2 =
* Improvement: Code refactored
* Fix: Unable to assign the same image to more than one slider
* Fix: JavaScript error when jQuery is loaded in page footer
* Improvement: Warning notice when the slider has unsaved changes
* Fix: Captions not being escaped (reported by and thanks to: papabeers)
* Improvement: Add multiple files to slider from Media Browser

= 1.1 =
* Improvement: Code refactored
* Fix: hitting [enter] brings up Media Library
* Improvement: Settings for new sliders now based on the last edited slider
* Improvement: More screenshots added

= 1.0.1 =
* Fix: min version incorrect (should be 3.5)

= 1.0 =
* Initial version