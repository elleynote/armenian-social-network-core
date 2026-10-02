<?php
namespace ASN\Core\Admin;

use ASN\Core\Database;
use ASN\Core\Integrations\WooCommerce_Integration;
use ASN\Core\Memberships;
use ASN\Core\Features\Member_Safety;

defined( 'ABSPATH' ) || exit;

final class Admin {
    public const CAPABILITY = 'manage_options';
    public const MENU_SLUG = 'asn-core';

    public function register_hooks(): void {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
    }

    public function register_menu(): void {
        add_menu_page(
            'ASN Core',
            'ASN Core',
            self::CAPABILITY,
            self::MENU_SLUG,
            array( $this, 'render_status_page' ),
            'dashicons-groups',
            58
        );
    }

    public function render_status_page(): void {
        if ( ! current_user_can( self::CAPABILITY ) ) {
            wp_die( esc_html__( 'You do not have permission to view ASN Core diagnostics.', 'asn-core' ) );
        }

        $counts = count_users();
        $data = array(
            'version'                     => ASN_CORE_VERSION,
            'database_version'            => Database::current_version(),
            'wordpress_users'             => isset( $counts['total_users'] ) ? (int) $counts['total_users'] : 0,
            'pmpro_available'              => Memberships::is_available(),
            'premium_access_available'     => Memberships::is_available(),
            'woocommerce_available'        => WooCommerce_Integration::is_available(),
            'subscriptions_available'      => WooCommerce_Integration::subscriptions_available(),
            'atomchat_active'              => $this->plugin_is_active( 'atomchat' ),
            'miniorange_active'            => $this->plugin_is_active( 'miniorange' ),
            'profile_sync_offset'           => (int) get_option( Profile_Index_Admin::OFFSET_OPTION, 0 ),
            'profile_sync_complete'         => (bool) get_option( Profile_Index_Admin::COMPLETE_OPTION, false ),
            'open_profile_reports'           => Member_Safety::open_report_count(),
            'latest_profile_reports'         => Member_Safety::latest_open_reports( 20 ),
        );

        require __DIR__ . '/views/status.php';
    }

    private function plugin_is_active( string $needle ): bool {
        $active_plugins = (array) get_option( 'active_plugins', array() );
        $needle = strtolower( $needle );

        foreach ( $active_plugins as $plugin ) {
            if ( false !== strpos( strtolower( (string) $plugin ), $needle ) ) {
                return true;
            }
        }

        return false;
    }
}
