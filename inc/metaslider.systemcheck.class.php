<?php
/**
 * Check for common issues with the server environment and WordPress install.
 */
class MetaSliderSystemCheck {

    var $options = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->options = get_site_option('metaslider_systemcheck');
    }

    /**
     * Check the system
     */
    public function check() {
        $this->dismissMessages();
        $this->checkWordPressVersion();
        $this->checkImageLibrary();
        $this->checkRoleScoper();
        $this->checkWpFooter();
        $this->updateSystemCheck();
    }

    /**
     * Disable a message
     */
    private function dismissMessages() {
        if (isset($_REQUEST['dismissMessage']) && isset($_REQUEST['_wpnonce'])) {
            $nonce = $_REQUEST['_wpnonce'];
            $key = $_REQUEST['dismissMessage'];

            if (wp_verify_nonce($nonce, "metaslider-dismiss-{$key}")) {
                $this->options[$key] = false;
                update_site_option('metaslider_systemcheck', $this->options);
            }
        }
    }

    /**
     * Update our stored messages
     */
    private function updateSystemCheck() {
        update_site_option('metaslider_systemcheck', $this->options);
    }

    /**
     * Check the WordPress version.
     */
    private function checkWordPressVersion() {
        if (isset($this->options['wordPressVersion']) && $this->options['wordPressVersion']  === false) {
            return;
        }

        if (!function_exists('wp_enqueue_media')) {
            $error = "Meta Slider requires WordPress 3.5 or above. Please upgrade your WordPress installation.";
            $this->printMessage($error, 'wordPressVersion');
        } else {
            $this->options['wordPressVersion'] = false;
        }
    }

    /**
     * Check GD or ImageMagick library exists
     */
    private function checkImageLibrary() {
        if (isset($this->options['imageLibrary']) && $this->options['imageLibrary'] === false) {
            return;
        }

        if ((!extension_loaded('gd') || !function_exists('gd_info')) && (!extension_loaded( 'imagick' ) || !class_exists( 'Imagick' ) || !class_exists( 'ImagickPixel' ))) {
            $error = "Meta Slider requires the GD or ImageMagick PHP extension. Please contact your hosting provider";
            $this->printMessage($error, 'imageLibrary');
        } else {
            $this->options['imageLibrary'] = false;
        }
    }

    /**
     * Detect the role scoper plugin
     */
    private function checkRoleScoper() {
        if (isset($this->options['roleScoper']) && $this->options['roleScoper'] === false) {
            return;
        }

        if (is_plugin_active('role-scoper/role-scoper.php')) {

            $access_types = get_option('scoper_disabled_access_types');

            if (isset($access_types['front']) && !$access_types['front']) {
                $error = 'Role Scoper Plugin Detected. Please go to Roles > Options. Click the Realm Tab, scroll down to "Access Types" and uncheck the "Viewing content (front-end)" setting.';
                $this->printMessage($error, 'roleScoper');
            }
        } else {
            $this->options['roleScoper'] = false;
        }
    }

    /**
     * Check the theme has a call to 'wp_footer'
     */
    private function checkWpFooter() {
        $current_theme = wp_get_theme();
        $theme_name = $current_theme->Template;

        $key = 'wpFooter:' . $theme_name;

        if (isset($this->options[$key]) && $this->options[$key] === false) {
            return;
        }

        if (file_exists(TEMPLATEPATH . '/footer.php' )) {
            $footer_file = file_get_contents(TEMPLATEPATH . '/footer.php');

            if (strpos($footer_file, 'wp_footer()') == false) {
                $error = "Theme check <b>failed</b>. Current theme: {$current_theme->name}. Required call to 'wp_footer()' not found. Please check the <a href='http://codex.wordpress.org/Function_Reference/wp_footer'>wp_footer()</a> documentation and make sure your theme has a call to wp_footer().";
                $this->printMessage($error, $key);
            } else {
                $this->options[$key] = false;
            }
        } else {
            $this->options[$key] = false;
        }
    }

    /**
     * Print a warning message to the screen
     */
    private function printMessage($message, $key) {
        $nonce = wp_create_nonce( "metaslider-dismiss-{$key}" );
        echo "<div id='message' class='updated'><p><b>Warning</b> {$message} <a href='?page=metaslider&dismissMessage={$key}&_wpnonce={$nonce}'>Dismiss this message.</a></p></div>";
    }
}
?>