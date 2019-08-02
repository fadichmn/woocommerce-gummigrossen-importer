<?php

// Create objects.
function wc_gummigrossen_init() {
    global $wc_gummigrossen;
    $wc_gummigrossen->init();
}

add_action( 'plugins_loaded', 'wc_gummigrossen_init' );

function wc_dp_install_metadata() {
    global $wpdb;

    $sql = "SELECT * FROM " . $wpdb->prefix . "postmeta WHERE meta_key = '" . WC_Gummigrossen::META_KEY_IMG . "'";
    $result = $wpdb->get_results( $sql );

    if ( ! count( $result ) ) {
        $products = get_posts( array( 'post_type' => 'product', 'posts_per_page' => 1 ) );

        if ( is_array( $products ) ) {
            update_post_meta( $products[0]->ID, WC_Gummigrossen::META_KEY_IMG, 0 );
            update_post_meta( $products[0]->ID, WC_Gummigrossen::META_KEY_GUMMI_ID, 0 );
        }
    }
}
