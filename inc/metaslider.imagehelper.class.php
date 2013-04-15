<?php
/**
 * Helper class for resizing images, returning the correct URL to the image etc
 */
class MetaSliderImageHelper {

    private $smart_crop = false; // crop mode (default or smart)
    private $container_width; // slideshow width
    private $container_height; // slideshow height
    private $url;

    /**
     * Constructor
     */
    public function __construct($width, $height, $smart_crop) {
        $this->container_width = $width;
        $this->container_height = $height;
        $this->smart_crop = $smart_crop;
    }

    /**
     * Set image URL by specifying the attachment ID
     * 
     * @var $slide_id attachment ID
     */
    public function set_url_by_attachment_id($slide_id) {
        $source_slide = wp_get_attachment_image_src($slide_id, 'full');
        $this->url = $source_slide[0];
    }

    /**
     * Set image URL
     * 
     * @var $url image url
     */
    public function set_url($url) {
        $this->url = $url;
    }

    /**
     * Return the crop dimensions.
     * 
     * Smart Crop: If the image is smaller than the container width or height, then return
     * dimensions that respect the container size ratio. This ensures image displays in a 
     * sane manner in responsive sliders
     * 
     * @return array image dimensions
     */
    private function get_crop_dimensions($image_width, $image_height) {
        if ($this->smart_crop == 'false') {
            return array('width' => (int)$this->container_width, 'height' => (int)$this->container_height);
        }

        $container_width = $this->container_width;
        $container_height = $this->container_height;
        
        /**
         * Slideshow Width == Slide Width
         */
        if ($image_width == $container_width && $image_height == $container_height) {
            $new_slide_width = $container_width;
            $new_slide_height = $container_height;
        }

        if ($image_width == $container_width && $image_height < $container_height) {
            $new_slide_height = $image_height;
            $new_slide_width = $container_width / ($container_height / $image_height);
        }

        if ($image_width == $container_width && $image_height > $container_height) {
            $new_slide_width = $container_width;
            $new_slide_height = $container_height;
        }

        /**
         * Slideshow Width < Slide Width
         */
        if ($image_width < $container_width && $image_height == $container_height) {
            $new_slide_width = $image_width;
            $new_slide_height = $image_height / ($container_width / $image_width);
        }

        if ($image_width < $container_width && $image_height < $container_height) {
            $new_slide_width = $image_width;
            $new_slide_height = $container_height / ($container_width / $image_width);
        }

        if ($image_width < $container_width && $image_height > $container_height) {
            $new_slide_width = $image_width;
            $new_slide_height = $container_height / ($container_width / $image_width);
        }

        /**
         * Slideshow Width > Slide Width
         */
        if ($image_width > $container_width && $image_height == $container_height) {
            $new_slide_width = $container_width;
            $new_slide_height = $container_height;
        }

        if ($image_width > $container_width && $image_height < $container_height) {
            $new_slide_height = $image_height;
            $new_slide_width = $container_width / ($container_height / $image_height);
        }

        if ($image_width > $container_width && $image_height > $container_height) {
            $new_slide_width = $container_width;
            $new_slide_height = $container_height;
        }

        return array('width' => (int)$new_slide_width, 'height' => (int)$new_slide_height);
    }

    /**
     * Return the image URL, crop the image to the correct dimensions if required
     * 
     * @return string resized image URL
     */
    function get_image_url() {
        // Get the image file path
        $file_path = parse_url( $this->url );
        $file_path = $_SERVER['DOCUMENT_ROOT'] . $file_path['path'];

        // load image
        $image = wp_get_image_editor($file_path);

        // editor will return an error if the path is invalid
        if (is_wp_error($image)) {
            return $image;
        }

        // get the original image size
        $size = $image->get_size();
        $orig_width = $size['width'];
        $orig_height = $size['height'];

        // get the crop size
        $size = $this->get_crop_dimensions($orig_width, $orig_height);
        $dest_width = $size['width'];
        $dest_height = $size['height'];

        // Some additional info about the image
        $info = pathinfo( $file_path );
        $dir = $info['dirname'];
        $ext = $info['extension'];
        $name = wp_basename($file_path, ".$ext");

        // Get the destination file name
        $dest_file_name = "{$dir}/{$name}-{$dest_width}x{$dest_height}.{$ext}";

        $url = str_replace(basename($this->url), basename($dest_file_name), $this->url);

        if (!file_exists($dest_file_name)) {
            $dims = image_resize_dimensions($orig_width, $orig_height, $dest_width, $dest_height, true);
            list($dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) = $dims;
            $image->crop($src_x, $src_y, $src_w, $src_h, $dst_w, $dst_h);
            $saved = $image->save($dest_file_name);
            $url = str_replace(basename($this->url), basename($saved['path']), $this->url);
        }

        return $url;
    }
}
?>