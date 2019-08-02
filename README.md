# woocommerce-gummigrossen-importer
Imports Articles from Gummigrossen.se into WooCommerce

This Import Plugin creates XML File what you can later import to WP All Import plugin or any other import plugin that works with WooCommerce.

### 1. Change API User and Password 
[The user and password for your api calls can be changed inside __classes/class-gummigrossen-api.php__](classes/class-gummigrossen-api.php)

    const API_USER             = 'Enter your API user here';
    const API_PASS             = 'Enter your API password here';

### 2. Upload and activate
2.1 The files should be uploded to __/wp-content/plugins/__
2.2 Activate the plugin in the WP Admin Panel.

### 3. Go to the Plugin settings and click Download XML
A file will be downloaded and further can be imported through different WP Import Plugins.

__Recommended: [WP All Import: WordPress XML & CSV Importer Plugin](http://www.wpallimport.com/)__


## About the plugin

 * Plugin Name:     WooCommerce Gummigrossen Importer
 * Description:     Imports Articles from Gummigrossen.se into WooCommerce
 * Author:          Fadi Chamoun
 * Author URI:      www.artend.se
 * Text Domain:     woocommerce-gummigrossen
 * Domain Path:     /languages
 * Version:         1.0
 * @package         Artend
