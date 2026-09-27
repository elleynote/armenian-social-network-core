<?php
namespace ASN\Core\Admin;

use ASN\Core\Profiles\Profile_Index;

defined( 'ABSPATH' ) || exit;

final class Profile_Index_Admin {
    public const OFFSET_OPTION = 'asn_core_profile_sync_offset';
    public const COMPLETE_OPTION = 'asn_core_profile_sync_complete';
    public const BATCH_SIZE = 50;

    public function register_hooks(): void {
        add_action( 'admin_post_asn_sync_profiles', array( $this, 'handle_sync' ) );
    }

    public function handle_sync(): void {
        $return_url = wp_get_referer();
        if ( ! $return_url ) {
            $return_url = admin_url( 'admin.php?page=asn-core' );
        }

        if ( ! current_user_can( Admin::CAPABILITY ) ) {
            $this->redirect( add_query_arg( 'asn_profile_sync_status', 'not_allowed', $return_url ) );
            return;
        }

        $nonce = isset( $_POST['asn_sync_profiles_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['asn_sync_profiles_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'asn_sync_profiles' ) ) {
            $this->redirect( add_query_arg( 'asn_profile_sync_status', 'security', $return_url ) );
            return;
        }

        $offset = max( 0, (int) get_option( self::OFFSET_OPTION, 0 ) );
        $result = Profile_Index::sync_batch( $offset, self::BATCH_SIZE );

        if ( $result['done'] ) {
            update_option( self::COMPLETE_OPTION, 1, false );
            update_option( self::OFFSET_OPTION, 0, false );
            $status = $result['failed'] > 0 ? 'complete_with_errors' : 'complete';
        } else {
            update_option( self::COMPLETE_OPTION, 0, false );
            update_option( self::OFFSET_OPTION, (int) $result['next_offset'], false );
            $status = $result['failed'] > 0 ? 'progress_with_errors' : 'progress';
        }

        $this->redirect( add_query_arg( 'asn_profile_sync_status', $status, $return_url ) );
    }

    private function redirect( string $url ): void {
        wp_safe_redirect( $url );
        if ( ! defined( 'ASN_CORE_TESTING' ) || ! ASN_CORE_TESTING ) {
            exit;
        }
    }
}
