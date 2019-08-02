<?php

defined( 'ABSPATH' ) or exit;

/**
 * Class to connect with Gummigrossen's API
 */
class WC_Gummigrossen
{
    const AJAX_NONCE        = 'gummigrossen';
    const AJAX_NONCE_IMG    = 'gummigrossen-img';
    const META_KEY_IMG      = '_gummigrossen_image_id';
    const META_KEY_GUMMI_ID = '_gummigrossen_id';

    function __construct() {
        if ( is_admin() ) {
            add_action( 'plugins_loaded', array( $this, 'download_xml' ) );
        }
    }

    function init() {
        if ( is_admin() ) {
            add_action( 'admin_menu', array( $this, 'admin_menu' ) );
            add_action( 'admin_notices', array( $this, 'print_notices' ) );

            //add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts') );
        }
    }

    public function admin_scripts( $hook ) {
        if ( $hook !== 'woocommerce_page_wc-gummigrossen-importer' ) {
            return;
        }

        $jsPath = WC_GUMMIGROSSEN_DIR . '/assets/js/wc-gummigrossen.js';
        wp_enqueue_script( 'wc-gummigrossen', $jsPath, array( 'jquery' ), '1.0', true );
    }

    /**
     * Adds the plugin's settings page to the menu
     */
    public function admin_menu() {
        add_submenu_page(
            'woocommerce',
            'Gummigrossen Importer',
            'Gummigrossen Importer',
            'manage_options',
            'wc-gummigrossen-importer',
            array( $this, 'settings_form' )
        );
    }

    /**
     * Settings page's form to save options
     */
    public function settings_form() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        ?>
        <div class="wrap">
            <h1>Gummigrossen Importer</h1>
            <h2>Download XML file</h2>
            <p>Download XML file to use for <strong>WP All Import</strong> plugin.</p>
            <form method="post" action="<?php echo admin_url( '?download-xml' ) ?>">
                <p>
                    <input type="submit" class="button button-primary" name="startSync" id="btnSync"
                           value="Start import">
                </p>
                <?php wp_nonce_field( self::AJAX_NONCE ); ?>
            </form>
            <br>
            <h2>Import images</h2>
            <p>After all products have been imported, click on the next button to import images.</p>
            <form method="post"
                  action="<?php echo admin_url( 'admin.php?page=wc-gummigrossen-importer&import-images' ) ?>">
                <p>
                    <input type="submit" class="button button-primary" name="downloadImages" id="btnImages"
                           value="Download Images">
                </p>
                <?php wp_nonce_field( self::AJAX_NONCE_IMG ); ?>
            </form>
        </div>
        <?php
    }

    public function download_xml() {
        if ( isset( $_GET['download-xml'] ) ) {

            $validReferer = check_admin_referer( self::AJAX_NONCE );

            if ( isset( $_POST['startSync'] ) && $validReferer ) {
                $api = new Gummigrossen_API();

                if ( $api->loadArticles() ) {
                    $xml = $api->generateXML();

                    header( "Content-Type: application/force-download; name=\"products.xml" );
                    header( "Content-type: text/xml" );
                    header( "Content-Transfer-Encoding: binary" );
                    header( "Content-Disposition: attachment; filename=\"products.xml" );
                    header( "Expires: 0" );
                    header( "Cache-Control: no-cache, must-revalidate" );
                    header( "Pragma: no-cache" );
                    echo $xml;
                    exit();
                } else {
                    wp_send_json_error( $api->error );
                }
            }
        }

        if ( isset( $_GET['import-images'] ) ) {
            $validReferer = check_admin_referer( self::AJAX_NONCE_IMG );

            if ( isset( $_POST['downloadImages'] ) && $validReferer ) {
                $api = new Gummigrossen_API();
                $api->importImages();
            }
        }
    }

    public function print_notices() {
        if ( isset( $_GET['page'] ) && $_GET['page'] === "wc-gummigrossen-importer" ) {
            if ( isset( $_SESSION[ Gummigrossen_API::SESSION_KEY_SUCCESS ] ) && ! empty( $_SESSION[ Gummigrossen_API::SESSION_KEY_SUCCESS ] ) ) {
                echo '<div class="notice notice-info is-dismissible">
					<p>' . $_SESSION[ Gummigrossen_API::SESSION_KEY_SUCCESS ] . '</p>
					</div>';
            }

            if ( isset( $_SESSION[ Gummigrossen_API::SESSION_KEY_ERROR ] ) && ! empty( $_SESSION[ Gummigrossen_API::SESSION_KEY_ERROR ] ) ) {
                echo '<div class="notice notice-error is-dismissible">
					<p>' . $_SESSION[ Gummigrossen_API::SESSION_KEY_ERROR ] . '</p>
					</div>';
            }
        }
    }
}

$wc_gummigrossen = new WC_Gummigrossen();
