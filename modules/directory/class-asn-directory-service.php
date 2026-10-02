<?php
namespace ASN\Core\Directory;

use ASN\Core\Database;
use ASN\Core\Features\Member_Discovery;
use ASN\Core\Features\Member_Safety;
use ASN\Core\Memberships;
use ASN\Core\Profiles\Profile_Index;

defined( 'ABSPATH' ) || exit;

final class Directory_Service {
    public static function search( array $filters ): array {
        global $wpdb;

        $filters = Directory_Query::from_request( $filters );
        $table = Database::table( 'profiles' );
        $membership_table = $wpdb->prefix . 'pmpro_memberships_users';
        $where = array(
            'mu.status = %s',
            'mu.membership_id IN (%d, %d)',
        );
        $values = array(
            'active',
            Memberships::FREE_LEVEL_ID,
            Memberships::PREMIUM_LEVEL_ID,
        );

        if ( '' !== $filters['q'] ) {
            $like = '%' . $wpdb->esc_like( $filters['q'] ) . '%';
            $where[] = '(p.display_name LIKE %s OR p.country LIKE %s OR p.job_title LIKE %s OR p.here_for LIKE %s)';
            array_push( $values, $like, $like, $like, $like );
        }

        foreach ( array( 'dialect', 'proficiency', 'gender' ) as $key ) {
            if ( '' !== $filters[ $key ] ) {
                $where[] = 'p.' . $key . ' = %s';
                $values[] = $filters[ $key ];
            }
        }

        if ( '' !== $filters['country'] ) {
            if ( Directory_Query::is_united_states_country( $filters['country'] ) ) {
                $where[] = '(p.country LIKE %s OR p.country = %s OR p.country = %s)';
                array_push( $values, '%United States%', 'USA', 'US' );
            } else {
                $where[] = 'p.country = %s';
                $values[] = $filters['country'];
            }
        }

        if ( '' !== $filters['job_title'] ) {
            $where[] = 'p.job_title LIKE %s';
            $values[] = '%' . $wpdb->esc_like( $filters['job_title'] ) . '%';
        }

        if ( '' !== $filters['here_for'] ) {
            $where[] = 'p.here_for LIKE %s';
            $values[] = '%' . $wpdb->esc_like( $filters['here_for'] ) . '%';
        }

        if ( $filters['age_min'] > 0 ) {
            $where[] = 'p.age >= %d';
            $values[] = $filters['age_min'];
        }

        if ( $filters['age_max'] > 0 ) {
            $where[] = 'p.age <= %d';
            $values[] = $filters['age_max'];
        }

        if ( $filters['recent'] ) {
            $where[] = 'p.last_active_at IS NOT NULL';
            $where[] = 'p.last_active_at >= %s';
            $values[] = Member_Discovery::recent_cutoff();
        }

        if ( $filters['favorites'] ) {
            $favorite_ids = Member_Discovery::favorite_ids( (int) get_current_user_id() );
            if ( empty( $favorite_ids ) ) {
                $where[] = '1 = 0';
            } else {
                $placeholders = implode( ',', array_fill( 0, count( $favorite_ids ), '%d' ) );
                $where[] = "p.user_id IN ({$placeholders})";
                foreach ( $favorite_ids as $favorite_id ) {
                    $values[] = $favorite_id;
                }
            }
        }

        $where_sql = implode( ' AND ', $where );
        $count_sql = "SELECT COUNT(DISTINCT p.user_id) FROM {$table} AS p INNER JOIN {$membership_table} AS mu ON mu.user_id = p.user_id WHERE {$where_sql}";
        if ( ! empty( $values ) ) {
            $count_sql = $wpdb->prepare( $count_sql, ...$values );
        }
        $total = (int) $wpdb->get_var( $count_sql );
        $pages = $total > 0 ? (int) ceil( $total / Directory_Query::PER_PAGE ) : 0;
        $page = $pages > 0 ? min( $filters['page'], $pages ) : 1;
        $offset = ( $page - 1 ) * Directory_Query::PER_PAGE;

        $select = self::select_columns();
        $order = $filters['recent']
            ? 'p.last_active_at DESC, p.registered_at DESC, p.user_id DESC'
            : 'p.registered_at DESC, p.user_id DESC';
        $sql = "SELECT DISTINCT {$select} FROM {$table} AS p INNER JOIN {$membership_table} AS mu ON mu.user_id = p.user_id WHERE {$where_sql} ORDER BY {$order} LIMIT %d OFFSET %d";
        $query_values = array_merge( $values, array( Directory_Query::PER_PAGE, $offset ) );
        $sql = $wpdb->prepare( $sql, ...$query_values );
        $items = $wpdb->get_results( $sql, defined( 'ARRAY_A' ) ? ARRAY_A : 'ARRAY_A' );

        return array(
            'items'    => is_array( $items ) ? $items : array(),
            'total'    => $total,
            'page'     => $page,
            'pages'    => $pages,
            'per_page' => Directory_Query::PER_PAGE,
        );
    }

    public static function suggested( int $viewer_id, int $limit = 4 ): array {
        if ( $viewer_id <= 0 || ! Memberships::can_text_chat( $viewer_id ) ) {
            return array();
        }

        $viewer = Profile_Index::find( $viewer_id );
        if ( ! is_array( $viewer ) ) {
            return array();
        }

        $rows = self::active_candidate_rows( 80 );
        $scored = array();

        foreach ( $rows as $row ) {
            $target_id = (int) ( $row['user_id'] ?? 0 );
            if ( $target_id <= 0 || $target_id === $viewer_id || Member_Safety::is_blocked_between( $viewer_id, $target_id ) ) {
                continue;
            }

            $score = 0;
            if ( '' !== (string) ( $viewer['country'] ?? '' ) && (string) ( $viewer['country'] ?? '' ) === (string) ( $row['country'] ?? '' ) ) {
                $score += 4;
            }
            if ( '' !== (string) ( $viewer['dialect'] ?? '' ) && (string) ( $viewer['dialect'] ?? '' ) === (string) ( $row['dialect'] ?? '' ) ) {
                $score += 3;
            }
            if ( '' !== (string) ( $viewer['proficiency'] ?? '' ) && (string) ( $viewer['proficiency'] ?? '' ) === (string) ( $row['proficiency'] ?? '' ) ) {
                $score += 2;
            }

            $viewer_here_for = array_filter( explode( ',', (string) ( $viewer['here_for'] ?? '' ) ) );
            $target_here_for = array_filter( explode( ',', (string) ( $row['here_for'] ?? '' ) ) );
            $score += min( 4, count( array_intersect( $viewer_here_for, $target_here_for ) ) * 2 );

            if ( self::row_is_recent( $row ) ) {
                ++$score;
            }

            $row['_match_score'] = $score;
            $scored[] = $row;
        }

        usort(
            $scored,
            static function ( $a, $b ) {
                $score = (int) ( $b['_match_score'] ?? 0 ) <=> (int) ( $a['_match_score'] ?? 0 );
                if ( 0 !== $score ) {
                    return $score;
                }

                $active = strcmp( (string) ( $b['last_active_at'] ?? '' ), (string) ( $a['last_active_at'] ?? '' ) );
                if ( 0 !== $active ) {
                    return $active;
                }

                return strcmp( (string) ( $b['registered_at'] ?? '' ), (string) ( $a['registered_at'] ?? '' ) );
            }
        );

        return array_slice( $scored, 0, max( 1, min( 12, $limit ) ) );
    }

    private static function active_candidate_rows( int $limit ): array {
        global $wpdb;

        $table = Database::table( 'profiles' );
        $membership_table = $wpdb->prefix . 'pmpro_memberships_users';
        $limit = max( 4, min( 100, $limit ) );
        $sql = $wpdb->prepare(
            "SELECT DISTINCT " . self::select_columns() . " FROM {$table} AS p INNER JOIN {$membership_table} AS mu ON mu.user_id = p.user_id WHERE mu.status = %s AND mu.membership_id IN (%d, %d) ORDER BY p.last_active_at DESC, p.registered_at DESC, p.user_id DESC LIMIT %d OFFSET 0",
            'active',
            Memberships::FREE_LEVEL_ID,
            Memberships::PREMIUM_LEVEL_ID,
            $limit
        );

        $rows = $wpdb->get_results( $sql, defined( 'ARRAY_A' ) ? ARRAY_A : 'ARRAY_A' );
        return is_array( $rows ) ? $rows : array();
    }

    private static function row_is_recent( array $row ): bool {
        $last = strtotime( (string) ( $row['last_active_at'] ?? '' ) );
        $cutoff = strtotime( Member_Discovery::recent_cutoff() );
        return false !== $last && false !== $cutoff && $last >= $cutoff;
    }

    private static function select_columns(): string {
        return 'p.user_id, p.display_name, p.country, p.age, p.gender, p.job_title, p.dialect, p.proficiency, p.here_for, p.last_active_at, p.registered_at';
    }
}
