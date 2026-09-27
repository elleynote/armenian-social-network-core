<?php
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( __DIR__ ) . '/tests/wordpress/' );
}

$GLOBALS['asn_test_hooks'] = array();
$GLOBALS['asn_test_options'] = array();
$GLOBALS['asn_test_tables'] = array();
$GLOBALS['asn_test_fail_table'] = '';
$GLOBALS['asn_test_current_user_can'] = true;

class ASN_Test_WPDB {
    public $prefix = 'wp_';

    public function get_charset_collate() {
        return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    }

    public function prepare( $sql, $value ) {
        return str_replace( '%s', "'" . $value . "'", $sql );
    }

    public function get_var( $sql ) {
        if ( preg_match( "/LIKE '([^']+)'/", $sql, $matches ) ) {
            $table = $matches[1];
            if ( $table === $GLOBALS['asn_test_fail_table'] ) {
                return null;
            }
            return isset( $GLOBALS['asn_test_tables'][ $table ] ) ? $table : null;
        }
        return null;
    }
}

$GLOBALS['wpdb'] = new ASN_Test_WPDB();

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

if ( ! function_exists( 'get_option' ) ) {
    function get_option( $key, $default = false ) {
        return $GLOBALS['asn_test_options'][ $key ] ?? $default;
    }
}

if ( ! function_exists( 'update_option' ) ) {
    function update_option( $key, $value, $autoload = null ) {
        $GLOBALS['asn_test_options'][ $key ] = $value;
        return true;
    }
}

if ( ! function_exists( 'delete_option' ) ) {
    function delete_option( $key ) {
        unset( $GLOBALS['asn_test_options'][ $key ] );
        return true;
    }
}

if ( ! function_exists( 'dbDelta' ) ) {
    function dbDelta( $sql ) {
        if ( preg_match( '/CREATE TABLE\s+([^\s(]+)/i', $sql, $matches ) ) {
            $GLOBALS['asn_test_tables'][ $matches[1] ] = true;
        }
        return array( 'created' );
    }
}

if ( ! function_exists( 'current_user_can' ) ) {
    function current_user_can( $capability ) { return (bool) $GLOBALS['asn_test_current_user_can']; }
}
if ( ! function_exists( 'add_menu_page' ) ) {
    function add_menu_page() { $GLOBALS['asn_test_menu'] = func_get_args(); }
}
if ( ! function_exists( 'count_users' ) ) {
    function count_users() { return array( 'total_users' => 567 ); }
}
if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( $value, $domain = null ) { return $value; }
}
if ( ! function_exists( 'wp_die' ) ) {
    function wp_die( $message ) { throw new RuntimeException( $message ); }
}

require_once dirname( __DIR__ ) . '/asn-core.php';

$GLOBALS['asn_test_users'] = array(
    7 => (object) array(
        'ID'           => 7,
        'display_name' => 'Test Member',
        'user_email'   => 'private@example.test',
        'user_pass'    => 'secret-hash',
    ),
    8 => (object) array(
        'ID'           => 8,
        'display_name' => 'Free Member',
        'user_email'   => 'private2@example.test',
        'user_pass'    => 'secret-hash-2',
    ),
);
$GLOBALS['asn_test_user_meta'] = array(
    7 => array(
        'first_name'         => 'Test',
        'last_name'          => 'Member',
        'country'            => 'Australia',
        'age'                => '35',
        'gender'             => 'Female',
        'job_title'          => 'Teacher',
        'spoken_proficiency' => 'Western Armenian - Fluent',
    ),
    8 => array(
        'first_name'         => 'Free',
        'last_name'          => 'Member',
        'country'            => 'United States',
        'spoken_proficiency' => 'Eastern Armenian - Beginner',
    ),
);
$GLOBALS['asn_test_pmpro_levels'] = array( 7 => 2, 8 => 1 );

if ( ! function_exists( 'get_userdata' ) ) {
    function get_userdata( $user_id ) {
        return $GLOBALS['asn_test_users'][ $user_id ] ?? false;
    }
}

if ( ! function_exists( 'metadata_exists' ) ) {
    function metadata_exists( $type, $user_id, $key ) {
        return array_key_exists( $key, $GLOBALS['asn_test_user_meta'][ $user_id ] ?? array() );
    }
}

if ( ! function_exists( 'get_user_meta' ) ) {
    function get_user_meta( $user_id, $key, $single = true ) {
        return $GLOBALS['asn_test_user_meta'][ $user_id ][ $key ] ?? '';
    }
}

if ( ! function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
    function pmpro_getMembershipLevelForUser( $user_id ) {
        return isset( $GLOBALS['asn_test_pmpro_levels'][ $user_id ] )
            ? (object) array( 'id' => $GLOBALS['asn_test_pmpro_levels'][ $user_id ] )
            : false;
    }
}

if ( ! function_exists( 'pmpro_hasMembershipLevel' ) ) {
    function pmpro_hasMembershipLevel( $level_id, $user_id ) {
        return ( $GLOBALS['asn_test_pmpro_levels'][ $user_id ] ?? null ) === (int) $level_id;
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $value ) {
        $value = strip_tags( (string) $value );
        return trim( preg_replace( '/\\s+/', ' ', $value ) );
    }
}
if ( ! function_exists( 'sanitize_textarea_field' ) ) {
    function sanitize_textarea_field( $value ) { return trim( strip_tags( (string) $value ) ); }
}
if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $value ) { return json_encode( $value ); }
}
