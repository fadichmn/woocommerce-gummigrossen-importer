<?php
/**
 * Plugin Name:     WooCommerce Gummigrossen Importer
 * Description:     Imports Articles from Gummigrossen.se into WooCommerce
 * Author:          Fadi Chamoun
 * Author URI:      www.artend.se
 * Text Domain:     woocommerce-gummigrossen
 * Domain Path:     /languages
 * Version:         1.0
 *
 * @package         Artend
 */

if ( ! defined( 'WC_GUMMIGROSSEN_DIR' ) ) {
	define( 'WC_GUMMIGROSSEN_DIR', plugins_url( '', __FILE__ ) );
}

if ( ! defined( 'WC_GUMMIGROSSEN_PATH' ) ) {
	define( 'WC_GUMMIGROSSEN_PATH', plugin_dir_path( '', __FILE__ ) );
}

require_once( 'inc/httpful/bootstrap.php' );
require_once( 'classes/class-gummigrossen-api.php' );
require_once( 'classes/class-wc-gummigrossen.php' );
require_once( 'inc/init.php' );

register_activation_hook( __FILE__, 'wc_dp_install_metadata' );
