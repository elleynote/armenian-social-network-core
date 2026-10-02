<?php
namespace ASN\Core\Features;

use ASN\Core\Database;
use ASN\Core\Directory\Directory_Query;
use ASN\Core\Memberships;
use ASN\Core\Profiles\Profile_Index;

defined( 'ABSPATH' ) || exit;

final class Member_Discovery {
    public const FAVORITES_META = '_asn_favorite_profiles';
    public const SAVED_SEARCHES_META = '_asn_saved_searches';
    public const LAST_ACTIVE_META = '_asn_last_active_at';
    public const RECENT_DAYS = 7;
    private const TOUCH_INTERVAL = 600;
    private const MAX_FAVORITES = 200;
    private const MAX_SAVED_SEARCHES = 10;

    public function register_hooks(): void {
        add_action( 'template_redirect', array( $this, 'touch_current_user_activity' ), 20 );
        add_action( 'admin_post_asn_toggle_favorite', array( $this, 'handle_toggle_favorite' ) );
        add_action( 'admin_post_asn_save_search', array( $this, 'handle_save_search' ) );
        add_action( 'admin_post_asn_delete_saved_search', array( $this, 'handle_delete_saved_search' ) );
    }

    public static function favorite_ids( int $user_id ): array {
        if ( $user_id <= 0 ) {
            return array();
        }

        $raw = get_user_meta( $user_id, self::FAVORITES_META, true );
        if ( ! is_array( $raw ) ) {
            $raw = array_filter( array_map( 'trim', explode( ',', (string) $raw ) ) );
        }

        $clean = array();
        foreach ( $raw as $id ) {
            $id = absint( $id );
            if ( $id > 0 && $id !== $user_id && false !== get_userdata( $id ) ) {
                $clean[ $id ] = $id;
            }
        }

        return array_values( array_slice( $clean, 0, self::MAX_FAVORITES, true ) );
    }

    public static function is_favorite( int $user_id, int $target_user_id ): bool {
        return in_array( $target_user_id, self::favorite_ids( $user_id ), true );
    }

    public static function set_favorite( int $user_id, int $target_user_id, bool $favorite ): bool {
        if (
            $user_id <= 0
            || $target_user_id <= 0
            || $user_id === $target_user_id
            || false === get_userdata( $target_user_id )
            || ! Memberships::can_text_chat( $user_id )
            || ! Memberships::can_text_chat( $target_user_id )
            || Member_Safety::is_blocked_between( $user_id, $target_user_id )
        ) {
            return false;
        }

        $ids = self::favorite_ids( $user_id );
        $index = array_search( $target_user_id, $ids, true );

        if ( $favorite && false === $index ) {
            array_unshift( $ids, $target_user_id );
        } elseif ( ! $favorite && false !== $index ) {
            unset( $ids[ $index ] );
            $ids = array_values( $ids );
        }

        update_user_meta( $user_id, self::FAVORITES_META, array_slice( $ids, 0, self::MAX_FAVORITES ) );
        return true;
    }

    public static function saved_searches( int $user_id ): array {
        if ( $user_id <= 0 ) {
            return array();
        }

        $raw = get_user_meta( $user_id, self::SAVED_SEARCHES_META, true );
        if ( ! is_array( $raw ) ) {
            return array();
        }

        $clean = array();
        foreach ( $raw as $item ) {
            if ( ! is_array( $item ) || empty( $item['id'] ) || empty( $item['filters'] ) || ! is_array( $item['filters'] ) ) {
                continue;
            }

            $filters = Directory_Query::from_request( $item['filters'] );
            unset( $filters['page'], $filters['per_page'] );

            $clean[] = array(
                'id'      => sanitize_key( (string) $item['id'] ),
                'label'   => sanitize_text_field( (string) ( $item['label'] ?? self::search_label( $filters ) ) ),
                'filters' => $filters,
            );
        }

        return array_slice( $clean, 0, self::MAX_SAVED_SEARCHES );
    }

    public static function save_search( int $user_id, array $request ): bool {
        if ( $user_id <= 0 || ! Memberships::can_text_chat( $user_id ) ) {
            return false;
        }

        $filters = Directory_Query::from_request( $request );
        unset( $filters['page'], $filters['per_page'] );

        $meaningful = array_filter(
            $filters,
            static function ( $value ) {
                return '' !== $value && false !== $value && 0 !== $value;
            }
        );

        if ( empty( $meaningful ) ) {
            return false;
        }

        $id = substr( md5( wp_json_encode( $filters ) ), 0, 12 );
        $searches = self::saved_searches( $user_id );

        foreach ( $searches as $existing ) {
            if ( $id === $existing['id'] ) {
                return true;
            }
        }

        array_unshift(
            $searches,
            array(
                'id'      => $id,
                'label'   => self::search_label( $filters ),
                'filters' => $filters,
            )
        );

        update_user_meta( $user_id, self::SAVED_SEARCHES_META, array_slice( $searches, 0, self::MAX_SAVED_SEARCHES ) );
        return true;
    }

    public static function delete_saved_search( int $user_id, string $id ): bool {
        $id = sanitize_key( $id );
        if ( $user_id <= 0 || '' === $id ) {
            return false;
        }

        $searches = array_values(
            array_filter(
                self::saved_searches( $user_id ),
                static function ( $item ) use ( $id ) {
                    return $id !== (string) $item['id'];
                }
            )
        );

        update_user_meta( $user_id, self::SAVED_SEARCHES_META, $searches );
        return true;
    }

    public static function record_profile_view( int $viewer_id, int $profile_user_id ): bool {
        if (
            $viewer_id <= 0
            || $profile_user_id <= 0
            || $viewer_id === $profile_user_id
            || ! Memberships::can_text_chat( $viewer_id )
            || ! Memberships::can_text_chat( $profile_user_id )
            || Member_Safety::is_blocked_between( $viewer_id, $profile_user_id )
        ) {
            return false;
        }

        global $wpdb;
        $table = Database::table( 'profile_views' );
        $last = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT viewed_at FROM {$table} WHERE viewer_id = %d AND profile_user_id = %d ORDER BY viewed_at DESC LIMIT 1",
                $viewer_id,
                $profile_user_id
            )
        );

        if ( is_string( $last ) && '' !== $last ) {
            $last_time = strtotime( $last );
            $now_time = strtotime( current_time( 'mysql' ) );
            if ( false !== $last_time && false !== $now_time && ( $now_time - $last_time ) < 3600 ) {
                return true;
            }
        }

        return false !== $wpdb->insert(
            $table,
            array(
                'viewer_id'       => $viewer_id,
                'profile_user_id' => $profile_user_id,
                'viewed_at'       => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s' )
        );
    }

    public static function recent_profile_viewers( int $profile_user_id, int $limit = 8 ): array {
        if ( $profile_user_id <= 0 || ! Memberships::can_text_chat( $profile_user_id ) ) {
            return array();
        }

        global $wpdb;
        $table = Database::table( 'profile_views' );
        $limit = max( 1, min( 20, $limit ) );
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT viewer_id, MAX(viewed_at) AS viewed_at FROM {$table} WHERE profile_user_id = %d GROUP BY viewer_id ORDER BY viewed_at DESC LIMIT %d",
                $profile_user_id,
                $limit
            ),
            defined( 'ARRAY_A' ) ? ARRAY_A : 'ARRAY_A'
        );

        if ( ! is_array( $rows ) ) {
            return array();
        }

        return array_values(
            array_filter(
                $rows,
                static function ( $row ) use ( $profile_user_id ) {
                    $viewer_id = (int) ( $row['viewer_id'] ?? 0 );
                    return $viewer_id > 0
                        && false !== get_userdata( $viewer_id )
                        && Memberships::can_text_chat( $viewer_id )
                        && ! Member_Safety::is_blocked_between( $profile_user_id, $viewer_id );
                }
            )
        );
    }

    public static function is_recently_active( int $user_id, int $days = self::RECENT_DAYS ): bool {
        $value = trim( (string) get_user_meta( $user_id, self::LAST_ACTIVE_META, true ) );
        if ( '' === $value ) {
            return false;
        }

        $last = strtotime( $value );
        $now = strtotime( current_time( 'mysql' ) );
        if ( false === $last || false === $now ) {
            return false;
        }

        return $last <= $now && ( $now - $last ) <= ( max( 1, $days ) * DAY_IN_SECONDS );
    }

    public static function recent_cutoff(): string {
        $now = strtotime( current_time( 'mysql' ) );
        if ( false === $now ) {
            $now = time();
        }

        return gmdate( 'Y-m-d H:i:s', $now - ( self::RECENT_DAYS * DAY_IN_SECONDS ) );
    }

    public function touch_current_user_activity(): void {
        $user_id = (int) get_current_user_id();
        if ( ! is_user_logged_in() || $user_id <= 0 || ! Memberships::can_text_chat( $user_id ) ) {
            return;
        }

        $last = trim( (string) get_user_meta( $user_id, self::LAST_ACTIVE_META, true ) );
        $last_time = '' !== $last ? strtotime( $last ) : false;
        $now_time = strtotime( current_time( 'mysql' ) );

        if ( false !== $last_time && false !== $now_time && ( $now_time - $last_time ) < self::TOUCH_INTERVAL ) {
            return;
        }

        Profile_Index::touch_activity( $user_id, current_time( 'mysql' ) );
    }

    public function handle_toggle_favorite(): void {
        $user_id = (int) get_current_user_id();
        $target_id = isset( $_POST['target_user_id'] ) ? absint( wp_unslash( $_POST['target_user_id'] ) ) : 0;
        $return_url = $this->return_url( $target_id );

        if ( ! is_user_logged_in() || $user_id <= 0 || $target_id <= 0 || $user_id === $target_id ) {
            $this->redirect( add_query_arg( 'asn_feature_notice', 'not_allowed', $return_url ) );
            return;
        }

        $nonce = isset( $_POST['asn_discovery_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['asn_discovery_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'asn_member_discovery_' . $target_id ) ) {
            $this->redirect( add_query_arg( 'asn_feature_notice', 'security', $return_url ) );
            return;
        }

        $favorite = isset( $_POST['favorite'] ) && '1' === (string) wp_unslash( $_POST['favorite'] );
        $ok = self::set_favorite( $user_id, $target_id, $favorite );
        $notice = $ok ? ( $favorite ? 'favorite_saved' : 'favorite_removed' ) : 'favorite_failed';
        $this->redirect( add_query_arg( 'asn_feature_notice', $notice, $return_url ) );
    }

    public function handle_save_search(): void {
        $user_id = (int) get_current_user_id();
        $return_url = isset( $_POST['return_url'] ) ? esc_url_raw( wp_unslash( $_POST['return_url'] ) ) : site_url( '/asn-explore-test/' );

        if ( ! is_user_logged_in() || $user_id <= 0 ) {
            $this->redirect( add_query_arg( 'asn_feature_notice', 'not_allowed', $return_url ) );
            return;
        }

        $nonce = isset( $_POST['asn_discovery_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['asn_discovery_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'asn_save_search' ) ) {
            $this->redirect( add_query_arg( 'asn_feature_notice', 'security', $return_url ) );
            return;
        }

        $filters = isset( $_POST['filters'] ) && is_array( $_POST['filters'] ) ? wp_unslash( $_POST['filters'] ) : array();
        $ok = self::save_search( $user_id, $filters );
        $this->redirect( add_query_arg( 'asn_feature_notice', $ok ? 'search_saved' : 'search_empty', $return_url ) );
    }

    public function handle_delete_saved_search(): void {
        $user_id = (int) get_current_user_id();
        $return_url = isset( $_POST['return_url'] ) ? esc_url_raw( wp_unslash( $_POST['return_url'] ) ) : site_url( '/asn-explore-test/' );
        $id = isset( $_POST['search_id'] ) ? sanitize_key( wp_unslash( $_POST['search_id'] ) ) : '';

        if ( ! is_user_logged_in() || $user_id <= 0 ) {
            $this->redirect( add_query_arg( 'asn_feature_notice', 'not_allowed', $return_url ) );
            return;
        }

        $nonce = isset( $_POST['asn_discovery_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['asn_discovery_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'asn_delete_search_' . $id ) ) {
            $this->redirect( add_query_arg( 'asn_feature_notice', 'security', $return_url ) );
            return;
        }

        $ok = self::delete_saved_search( $user_id, $id );
        $this->redirect( add_query_arg( 'asn_feature_notice', $ok ? 'search_deleted' : 'search_delete_failed', $return_url ) );
    }

    private static function search_label( array $filters ): string {
        $parts = array();

        if ( ! empty( $filters['q'] ) ) {
            $parts[] = '“' . $filters['q'] . '”';
        }
        if ( ! empty( $filters['country'] ) ) {
            $parts[] = $filters['country'];
        }
        if ( ! empty( $filters['job_title'] ) ) {
            $parts[] = $filters['job_title'];
        }
        if ( ! empty( $filters['gender'] ) ) {
            $parts[] = $filters['gender'];
        }
        if ( ! empty( $filters['here_for'] ) ) {
            $parts[] = $filters['here_for'];
        }
        if ( ! empty( $filters['recent'] ) ) {
            $parts[] = 'Recently active';
        }
        if ( ! empty( $filters['favorites'] ) ) {
            $parts[] = 'Favorites';
        }

        $age_min = isset( $filters['age_min'] ) ? (int) $filters['age_min'] : 0;
        $age_max = isset( $filters['age_max'] ) ? (int) $filters['age_max'] : 0;
        if ( $age_min || $age_max ) {
            $parts[] = 'Age ' . ( $age_min ?: 'any' ) . '-' . ( $age_max ?: 'any' );
        }

        return empty( $parts ) ? 'Saved member search' : implode( ' · ', array_slice( $parts, 0, 4 ) );
    }

    private function return_url( int $target_id ): string {
        $return_url = isset( $_POST['return_url'] ) ? esc_url_raw( wp_unslash( $_POST['return_url'] ) ) : '';
        if ( '' === $return_url ) {
            $return_url = add_query_arg( 'member', $target_id, site_url( '/asn-profile-test/' ) );
        }
        return $return_url;
    }

    private function redirect( string $url ): void {
        wp_safe_redirect( $url );
        if ( ! defined( 'ASN_CORE_TESTING' ) || ! ASN_CORE_TESTING ) {
            exit;
        }
    }
}
