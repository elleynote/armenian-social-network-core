<?php
namespace ASN\Core\Profiles;

defined( 'ABSPATH' ) || exit;

final class Profile_Form {
    public function register(): void {
        add_action( 'admin_post_asn_update_profile', array( $this, 'handle' ) );
    }

    public function handle(): void {
        $actor_user_id = (int) get_current_user_id();
        $return_url = wp_get_referer();
        if ( ! $return_url ) {
            $return_url = site_url( '/profile/' );
        }

        if ( ! is_user_logged_in() || $actor_user_id <= 0 ) {
            $this->redirect( add_query_arg( 'asn_profile_status', 'not_allowed', $return_url ) );
            return;
        }

        $nonce = isset( $_POST['asn_profile_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['asn_profile_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'asn_update_profile' ) ) {
            $this->redirect( add_query_arg( 'asn_profile_status', 'security', $return_url ) );
            return;
        }

        $target_user_id = isset( $_POST['target_user_id'] ) ? absint( $_POST['target_user_id'] ) : 0;
        $input = isset( $_POST['asn_profile'] ) && is_array( $_POST['asn_profile'] ) ? wp_unslash( $_POST['asn_profile'] ) : array();
        $result = Profile_Service::update_own_profile( $actor_user_id, $target_user_id, $input );

        if ( ! $result['success'] ) {
            $status = 'error';
        } elseif ( ! $result['index_synced'] ) {
            $status = 'updated_index_warning';
        } else {
            $status = 'updated';
        }

        $this->redirect( add_query_arg( 'asn_profile_status', $status, $return_url ) );
    }

    private function redirect( string $url ): void {
        wp_safe_redirect( $url );
        if ( ! defined( 'ASN_CORE_TESTING' ) || ! ASN_CORE_TESTING ) {
            exit;
        }
    }
}
