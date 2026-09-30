<?php
namespace ASN\Core\Directory;

use ASN\Core\Database;
use ASN\Core\Memberships;

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
            $where[] = '(p.display_name LIKE %s OR p.country LIKE %s OR p.job_title LIKE %s)';
            array_push( $values, $like, $like, $like );
        }

        foreach ( array( 'dialect', 'proficiency', 'country' ) as $key ) {
            if ( '' !== $filters[ $key ] ) {
                $where[] = 'p.' . $key . ' = %s';
                $values[] = $filters[ $key ];
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

        $select = 'p.user_id, p.display_name, p.country, p.age, p.gender, p.job_title, p.dialect, p.proficiency, p.registered_at';
        $sql = "SELECT DISTINCT {$select} FROM {$table} AS p INNER JOIN {$membership_table} AS mu ON mu.user_id = p.user_id WHERE {$where_sql} ORDER BY p.registered_at DESC, p.user_id DESC LIMIT %d OFFSET %d";
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
}
