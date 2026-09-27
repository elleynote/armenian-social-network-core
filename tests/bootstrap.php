<?php
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( __DIR__ ) . '/tests/wordpress/' );
}

$GLOBALS['asn_test_hooks'] = array();
$GLOBALS['asn_test_options'] = array();

if ( ! function_exists( 'plugin_dir_path' ) ) {
    function plugin_dir_path( $file ) {
        return dirname( $file ) . '/';
    }
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
    function plugin_dir_url( $file ) {
        return 'https://example.test/wp-content/plugins/asn-core/';
    }
}

if ( ! function_exists( 'register_activation_hook' ) ) {
    function register_activation_hook( $file, $callback ) {
        $GLOBALS['asn_activation_hook'] = $callback;
    }
}

if ( ! function_exists( 'register_deactivation_hook' ) ) {
    function register_deactivation_hook( $file, $callback ) {
        $GLOBALS['asn_deactivation_hook'] = $callback;
    }
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $hook, $callback ) {
        $GLOBALS['asn_test_hooks'][ $hook ][] = $callback;
    }
}

require_once dirname( __DIR__ ) . '/asn-core.php';
