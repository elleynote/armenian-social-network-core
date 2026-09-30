<?php
if ( ! defined( 'ASN_CORE_TESTING' ) ) {
    define( 'ASN_CORE_TESTING', true );
}

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( __DIR__ ) . '/tests/wordpress/' );
}

$GLOBALS['asn_test_hooks'] = array();
$GLOBALS['asn_test_options'] = array();
$GLOBALS['asn_test_tables'] = array();
$GLOBALS['asn_test_fail_table'] = '';
$GLOBALS['asn_test_current_user_can'] = true;
$GLOBALS['asn_test_profile_rows'] = array();
$GLOBALS['asn_test_sync_fail_user'] = 0;
$GLOBALS['asn_test_dbdelta_sql'] = array();
$GLOBALS['asn_test_avatar_urls'] = array( 7 => 'https://example.test/avatar-7.jpg', 8 => 'https://example.test/avatar-8.jpg' );
$GLOBALS['asn_test_shortcodes'] = array();
$GLOBALS['asn_test_current_user_id'] = 7;
$GLOBALS['asn_test_styles'] = array();
$GLOBALS['asn_test_logged_in'] = true;
$GLOBALS['asn_test_valid_nonce'] = 'valid-nonce';
$GLOBALS['asn_test_redirect'] = '';
$GLOBALS['asn_test_referer'] = 'https://example.test/asn-profile-test/';
$GLOBALS['asn_test_better_messages_enabled'] = false;
$GLOBALS['asn_test_wc_products'] = array();
$GLOBALS['asn_test_scheduled_events'] = array();
$GLOBALS['asn_test_new_user_notifications'] = array();

class ASN_Test_WPDB {
    public $prefix = 'wp_';

    public function get_charset_collate() {
        return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    }

    public function prepare( $sql, ...$values ) {
        foreach ( $values as $value ) {
            if ( preg_match( '/%[sd]/', $sql, $match ) ) {
                $replacement = '%d' === $match[0] ? (string) (int) $value : "'" . str_replace( "'", "''", (string) $value ) . "'";
                $sql = preg_replace( '/%[sd]/', $replacement, $sql, 1 );
            }
        }
        return $sql;
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

    public function replace( $table, $data, $formats = null ) {
        if ( (int) ( $GLOBALS['asn_test_sync_fail_user'] ?? 0 ) === (int) $data['user_id'] ) {
            return false;
        }
        $GLOBALS['asn_test_profile_rows'][ (int) $data['user_id'] ] = $data;
        return 1;
    }

    public function get_row( $sql, $output = null ) {
        if ( preg_match( '/user_id\\s*=\\s*(\\d+)/', $sql, $matches ) ) {
            return $GLOBALS['asn_test_profile_rows'][ (int) $matches[1] ] ?? null;
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
if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
        $GLOBALS['asn_test_filters'][ $hook ][] = array(
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        );
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
        $GLOBALS['asn_test_dbdelta_sql'][] = $sql;
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

if ( ! function_exists( 'add_shortcode' ) ) {
    function add_shortcode( $tag, $callback ) { $GLOBALS['asn_test_shortcodes'][ $tag ] = $callback; }
}
if ( ! function_exists( 'get_current_user_id' ) ) {
    function get_current_user_id() { return (int) $GLOBALS['asn_test_current_user_id']; }
}
if ( ! function_exists( 'absint' ) ) {
    function absint( $value ) { return abs( (int) $value ); }
}
if ( ! function_exists( 'wp_enqueue_style' ) ) {
    function wp_enqueue_style() { $GLOBALS['asn_test_styles'][] = func_get_args(); }
}
if ( ! function_exists( 'esc_attr' ) ) {
    function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_url' ) ) {
    function esc_url( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
}

if ( ! class_exists( 'ASN_Test_Better_Messages_Functions' ) ) {
    final class ASN_Test_Better_Messages_Functions {
        public function get_private_conversation_id( $to, $from = null, $create = true, $subject = '' ) {
            return array(
                'result'    => 'thread_found',
                'thread_id' => 1000 + (int) $to,
            );
        }

        public function get_user_messages_url( $user_id, $thread_id = null ) {
            return 'https://example.test/messages/#conversation/' . (int) $thread_id;
        }

        public function get_recipients_ids( $thread_id ) {
            return array( 7, 8 );
        }
    }
}

if ( ! class_exists( 'ASN_Test_Better_Messages' ) ) {
    final class ASN_Test_Better_Messages {
        public $functions;

        public function __construct() {
            $this->functions = new ASN_Test_Better_Messages_Functions();
        }
    }
}

if ( ! function_exists( 'Better_Messages' ) ) {
    function Better_Messages() {
        if ( empty( $GLOBALS['asn_test_better_messages_enabled'] ) ) {
            return null;
        }

        static $instance = null;
        if ( null === $instance ) {
            $instance = new ASN_Test_Better_Messages();
        }
        return $instance;
    }
}

if ( ! function_exists( 'wc_get_product' ) ) {
    function wc_get_product( $product_id ) {
        return $GLOBALS['asn_test_wc_products'][ (int) $product_id ] ?? null;
    }
}

if ( ! function_exists( 'wc_get_checkout_url' ) ) {
    function wc_get_checkout_url() {
        return 'https://example.test/checkout/';
    }
}

if ( ! function_exists( 'wp_schedule_single_event' ) ) {
    function wp_schedule_single_event( $timestamp, $hook, $args = array(), $wp_error = false ) {
        $GLOBALS['asn_test_scheduled_events'][] = array(
            'timestamp' => (int) $timestamp,
            'hook'      => (string) $hook,
            'args'      => (array) $args,
        );
        return true;
    }
}

if ( ! function_exists( 'wp_next_scheduled' ) ) {
    function wp_next_scheduled( $hook, $args = array() ) {
        foreach ( $GLOBALS['asn_test_scheduled_events'] as $event ) {
            if ( $event['hook'] === (string) $hook && $event['args'] === (array) $args ) {
                return (int) $event['timestamp'];
            }
        }
        return false;
    }
}

if ( ! function_exists( 'wp_new_user_notification' ) ) {
    function wp_new_user_notification( $user_id, $deprecated = null, $notify = '' ) {
        $GLOBALS['asn_test_new_user_notifications'][] = array(
            'user_id' => (int) $user_id,
            'notify'  => (string) $notify,
        );
    }
}

require_once dirname( __DIR__ ) . '/asn-core.php';

$GLOBALS['asn_test_users'] = array(
    7 => (object) array(
        'ID'           => 7,
        'display_name' => 'Test Member',
        'user_email'   => 'private@example.test',
        'user_pass'    => 'secret-hash',
        'user_registered' => '2025-01-02 03:04:05',
    ),
    8 => (object) array(
        'ID'           => 8,
        'display_name' => 'Free Member',
        'user_email'   => 'private2@example.test',
        'user_pass'    => 'secret-hash-2',
        'user_registered' => '2025-02-03 04:05:06',
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
        'profile_pic'         => 'https://example.test/profile-7.jpg',
        'my_favorite_music_is' => 'Jazz',
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

if ( ! function_exists( 'get_users' ) ) {
    function get_users( $args = array() ) {
        $ids = array_keys( $GLOBALS['asn_test_users'] );
        sort( $ids, SORT_NUMERIC );
        $offset = isset( $args['offset'] ) ? max( 0, (int) $args['offset'] ) : 0;
        $number = isset( $args['number'] ) ? max( 0, (int) $args['number'] ) : count( $ids );
        return array_slice( $ids, $offset, $number );
    }
}
if ( ! function_exists( 'current_time' ) ) {
    function current_time( $type ) { return '2026-09-28 00:00:00'; }
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

if ( ! function_exists( 'pmpro_changeMembershipLevel' ) ) {
    function pmpro_changeMembershipLevel( $level_id, $user_id ) {
        if ( (int) $level_id <= 0 || (int) $user_id <= 0 ) {
            return false;
        }
        $GLOBALS['asn_test_pmpro_levels'][ (int) $user_id ] = (int) $level_id;
        return true;
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
if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( $value ) {
        return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
    }
}
if ( ! function_exists( 'sanitize_email' ) ) {
    function sanitize_email( $value ) { return filter_var( (string) $value, FILTER_SANITIZE_EMAIL ); }
}
if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $value ) { return json_encode( $value ); }
}

if ( ! function_exists( 'get_avatar_url' ) ) {
    function get_avatar_url( $user_id, $args = array() ) {
        return $GLOBALS['asn_test_avatar_urls'][ $user_id ] ?? '';
    }
}
if ( ! function_exists( 'esc_url_raw' ) ) {
    function esc_url_raw( $value ) { return filter_var( (string) $value, FILTER_SANITIZE_URL ); }
}


if ( ! function_exists( 'update_user_meta' ) ) {
    function update_user_meta( $user_id, $key, $value ) {
        $GLOBALS['asn_test_user_meta'][ $user_id ][ $key ] = $value;
        return true;
    }
}
if ( ! function_exists( 'delete_user_meta' ) ) {
    function delete_user_meta( $user_id, $key ) {
        unset( $GLOBALS['asn_test_user_meta'][ $user_id ][ $key ] );
        return true;
    }
}
if ( ! function_exists( 'trailingslashit' ) ) {
    function trailingslashit( $value ) {
        return rtrim( (string) $value, '/\\\\' ) . '/';
    }
}
if ( ! function_exists( 'delete_user_meta' ) ) {
    function delete_user_meta( $user_id, $key ) {
        unset( $GLOBALS['asn_test_user_meta'][ $user_id ][ $key ] );
        return true;
    }
}
if ( ! function_exists( 'trailingslashit' ) ) {
    function trailingslashit( $value ) {
        return rtrim( (string) $value, '/\\' ) . '/';
    }
}
if ( ! function_exists( 'is_user_logged_in' ) ) {
    function is_user_logged_in() { return (bool) $GLOBALS['asn_test_logged_in']; }
}
if ( ! function_exists( 'wp_verify_nonce' ) ) {
    function wp_verify_nonce( $nonce, $action ) { return in_array( $action, array( 'asn_update_profile', 'asn_sync_profiles' ), true ) && $nonce === $GLOBALS['asn_test_valid_nonce']; }
}
if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $value ) {
        if ( is_array( $value ) ) {
            return array_map( 'wp_unslash', $value );
        }
        return stripslashes( (string) $value );
    }
}
if ( ! function_exists( 'wp_safe_redirect' ) ) {
    function wp_safe_redirect( $url ) { $GLOBALS['asn_test_redirect'] = $url; return true; }
}
if ( ! function_exists( 'wp_get_referer' ) ) {
    function wp_get_referer() { return $GLOBALS['asn_test_referer']; }
}
if ( ! function_exists( 'site_url' ) ) {
    function site_url( $path = '' ) { return 'https://example.test' . $path; }
}
if ( ! function_exists( 'admin_url' ) ) {
    function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . ltrim( $path, '/' ); }
}
if ( ! function_exists( 'add_query_arg' ) ) {
    function add_query_arg( $key, $value, $url ) {
        $separator = false === strpos( $url, '?' ) ? '?' : '&';
        return $url . $separator . rawurlencode( (string) $key ) . '=' . rawurlencode( (string) $value );
    }
}
if ( ! function_exists( 'wp_nonce_field' ) ) {
    function wp_nonce_field( $action, $name ) {
        echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $GLOBALS['asn_test_valid_nonce'] ) . '">';
    }
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
    function wp_enqueue_script() { $GLOBALS['asn_test_scripts'][] = func_get_args(); }
}
