<?php
/**
 * 
 */
class MetaSliderImportExport {


    /**
     * Constructor
     */
    public function __construct() {

        //add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 9554 );

    }

    /**
     *
     */
    public function register_admin_menu() {

        $capability = apply_filters( 'metaslider_capability', 'edit_others_posts' );

        $export = add_submenu_page( 
            'metaslider', 
            __( 'Export', 'metaslider' ), 
            __( 'Export', 'metaslider' ), 
            $capability, 
            'metaslider-export', 
            array( $this, 'export_page' ) 
        );

        add_action( 'admin_print_styles-' . $export, array( $this, 'export_styles' ) );

        $import = add_submenu_page( 
            'metaslider', 
            __( 'Import', 'metaslider' ), 
            __( 'Import', 'metaslider' ), 
            $capability, 
            'metaslider-import', 
            array( $this, 'import_page' ) 
        );

        add_action( 'admin_print_styles-' . $import, array( $this, 'import_styles' ) );
    }

    /**
     *
     */
    public function export_styles() {

    }

    /**
     *
     */
    public function export_page() {

        $sliders = $this->all_meta_sliders();

        ?>

        <form>
            <input type='hidden' action='metaslider_export_sliders' />

            <p>Select Sliders to export</p>

            <ul>

            <?php foreach ( $sliders as $slider ): ?>

                <li><input type='checkbox' value='<?php echo $slider['id'] ?>' /><?php echo $slider['title']; ?></li>

            <?php endforeach; ?>

            </ul>

        </form>

        <?php
    }

    /**
     *
     */
    public function import_styles() {

    }

    /**
     *
     */
    public function import_page() {
        

    }

    /**
     *
     */
    public function download_and_import_image( $url ) {

        $tmp = download_url( $url );
        $post_id = 1;
        $desc = "The WordPress Logo";
        $file_array = array();

        // Set variables for storage
        // fix file filename for query strings
        preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $url, $matches);
        $file_array['name'] = basename($matches[0]);
        $file_array['tmp_name'] = $tmp;

        // If error storing temporarily, unlink
        if ( is_wp_error( $tmp ) ) {
            @unlink($file_array['tmp_name']);
            $file_array['tmp_name'] = '';
        }

        // do the validation and storage stuff
        $id = media_handle_sideload( $file_array, $post_id, $desc );

        // If error storing permanently, unlink
        if ( is_wp_error($id) ) {
            @unlink($file_array['tmp_name']);
            return $id;
        }

        $src = wp_get_attachment_url( $id );

    }


    /**
     *
     */
    private function all_meta_sliders( $sort_key = 'date' ) {

        $sliders = array();

        // list the tabs
        $args = array(
            'post_type' => 'ml-slider',
            'post_status' => 'publish',
            'orderby' => $sort_key,
            'suppress_filters' => 1, // wpml, ignore language filter
            'order' => 'ASC',
            'posts_per_page' => -1
        );

        // WP_Query causes issues with other plugins using admin_footer to insert scripts
        // use get_posts instead
        $all_sliders = get_posts( $args );

        foreach( $all_sliders as $slideshow ) {

            $sliders[] = array(
                'title' => $slideshow->post_title,
                'id' => $slideshow->ID
            );

        } 

        return $sliders;

    }

}