<?php
namespace ASN\Core\Features;

use ASN\Core\Database;

defined( 'ABSPATH' ) || exit;

final class Member_Safety {
    public function register_hooks(): void {
        add_action( 'admin_post_asn_report_profile', array( $this, 'handle_report' ) );
        add_action( 'admin_post_asn_block_profile', array( $this, 'handle_block' ) );
        add_action( 'admin_post_asn_unblock_profile', array( $this, 'handle_unblock' ) );
    }

    public static function report_reasons(): array {
        return array(
            'fake' => 'Fake or misleading profile',
            'inappropriate' => 'Inappropriate content',
            'harassment' => 'Harassment',
            'spam' => 'Spam or promotion',
            'suspicious' => 'Suspicious activity',
            'other' => 'Other',
        );
    }

    public static function is_blocked_between( int $first_user_id, int $second_user_id ): bool {
        if ( $first_user_id <= 0 || $second_user_id <= 0 || $first_user_id === $second_user_id ) {
            return false;
        }

        global $wpdb;
        $table = Database::table( 'blocks' );
        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE (blocker_id = %d AND blocked_id = %d) OR (blocker_id = %d AND blocked_id = %d)",
            $first_user_id,
            $second_user_id,
            $second_user_id,
            $first_user_id
        );

        return (int) $wpdb->get_var( $sql ) > 0;
    }

    public static function is_blocked_by( int $blocker_id, int $blocked_id ): bool {
        if ( $blocker_id <= 0 || $blocked_id <= 0 || $blocker_id === $blocked_id ) {
            return false;
        }

        global $wpdb;
        $table = Database::table( 'blocks' );
        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE blocker_id = %d AND blocked_id = %d",
            $blocker_id,
            $blocked_id
        );

        return (int) $wpdb->get_var( $sql ) > 0;
    }

    public static function block( int $blocker_id, int $blocked_id ): bool {
        if ( $blocker_id <= 0 || $blocked_id <= 0 || $blocker_id === $blocked_id || false === get_userdata( $blocked_id ) ) {
            return false;
        }

        if ( self::is_blocked_by( $blocker_id, $blocked_id ) ) {
            return true;
        }

        global $wpdb;
        $result = $wpdb->insert(
            Database::table( 'blocks' ),
            array(
                'blocker_id' => $blocker_id,
                'blocked_id' => $blocked_id,
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s' )
        );

        return false !== $result;
    }

    public static function unblock( int $blocker_id, int $blocked_id ): bool {
        if ( $blocker_id <= 0 || $blocked_id <= 0 ) {
            return false;
        }

        global $wpdb;
        $result = $wpdb->delete(
            Database::table( 'blocks' ),
            array(
                'blocker_id' => $blocker_id,
                'blocked_id' => $blocked_id,
            ),
            array( '%d', '%d' )
        );

        return false !== $result;
    }

    public static function open_report_count(): int {
        global $wpdb;
        $table = Database::table( 'reports' );
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'open'" );
    }

    public static function latest_open_reports( int $limit = 20 ): array {
        global $wpdb;
        $table = Database::table( 'reports' );
        $limit = max( 1, min( 100, $limit ) );
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, reporter_id, target_user_id, reason, created_at FROM {$table} WHERE status = %s ORDER BY created_at DESC LIMIT %d",
                'open',
                $limit
            ),
            defined( 'ARRAY_A' ) ? ARRAY_A : 'ARRAY_A'
        );

        return is_array( $rows ) ? $rows : array();
    }

    public static function report( int $reporter_id, int $target_user_id, string $reason ): bool {
        $reason_key = sanitize_key( $reason );
        $reasons = self::report_reasons();

        if (
            $reporter_id <= 0
            || $target_user_id <= 0
            || $reporter_id === $target_user_id
            || false === get_userdata( $target_user_id )
            || ! isset( $reasons[ $reason_key ] )
        ) {
            return false;
        }

        global $wpdb;
        $now = current_time( 'mysql' );
        $result = $wpdb->insert(
            Database::table( 'reports' ),
            array(
                'reporter_id' => $reporter_id,
                'target_user_id' => $target_user_id,
                'object_type' => 'profile',
                'object_id' => $target_user_id,
                'reason' => $reason_key,
                'status' => 'open',
                'created_at' => $now,
                'updated_at' => $now,
            ),
            array( '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s' )
        );

        return false !== $result;
    }

    public function handle_report(): void {
        $this->handle_action( 'report' );
    }

    public function handle_block(): void {
        $this->handle_action( 'block' );
    }

    public function handle_unblock(): void {
        $this->handle_action( 'unblock' );
    }

    private function handle_action( string $action ): void {
        $actor_id = (int) get_current_user_id();
        $target_id = isset( $_POST['target_user_id'] ) ? absint( wp_unslash( $_POST['target_user_id'] ) ) : 0;
        $return_url = isset( $_POST['return_url'] ) ? esc_url_raw( wp_unslash( $_POST['return_url'] ) ) : '';
        if ( '' === $return_url ) {
            $return_url = add_query_arg( 'member', $target_id, site_url( '/asn-profile-test/' ) );
        }

        if ( ! is_user_logged_in() || $actor_id <= 0 || $target_id <= 0 || $actor_id === $target_id ) {
            $this->redirect( add_query_arg( 'asn_profile_notice', 'not_allowed', $return_url ) );
            return;
        }

        $nonce = isset( $_POST['asn_safety_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['asn_safety_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'asn_profile_safety_' . $target_id ) ) {
            $this->redirect( add_query_arg( 'asn_profile_notice', 'security', $return_url ) );
            return;
        }

        if ( 'report' === $action ) {
            $reason = isset( $_POST['reason'] ) ? sanitize_key( wp_unslash( $_POST['reason'] ) ) : '';
            $ok = self::report( $actor_id, $target_id, $reason );
            $notice = $ok ? 'reported' : 'report_failed';
        } elseif ( 'unblock' === $action ) {
            $ok = self::unblock( $actor_id, $target_id );
            $notice = $ok ? 'unblocked' : 'block_failed';
        } else {
            $ok = self::block( $actor_id, $target_id );
            $notice = $ok ? 'blocked' : 'block_failed';
        }

        $this->redirect( add_query_arg( 'asn_profile_notice', $notice, $return_url ) );
    }

    private function redirect( string $url ): void {
        wp_safe_redirect( $url );
        if ( ! defined( 'ASN_CORE_TESTING' ) || ! ASN_CORE_TESTING ) {
            exit;
        }
    }
}
