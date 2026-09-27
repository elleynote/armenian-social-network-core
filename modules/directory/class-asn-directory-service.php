<?php
namespace ASN\Core\Directory;

use ASN\Core\Database;

defined( 'ABSPATH' ) || exit;

final class Directory_Service {
    public static function search( array $filters ): array {
        global $wpdb;

        $filters = Directory_Query::from_request( $filters );
        $table = Database::table( 'profiles' );
        $where = array( '1=1' );
        $values = array();

        if ( '' !== $filters['q'] ) {
            $like = '%' . $wpdb->esc_like( $filters['q'] ) . '%';
            $where[] = '(display_name LIKE %s OR country LIKE %s OR job_title LIKE %s)';
            array_push( $values, $like, $like, $like );
        }

        foreach ( array( 'dialect', 'proficiency', 'country' ) as $key ) {
            if ( '' !== $filters[ $key ] ) {
                $where[] = $key . ' = %s';
                $values[] = $filters[ $key ];
            }
        }

        $where_sql = implode( ' AND ', $where );
        $count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
        if ( ! empty( $values ) ) {
            $count_sql = $wpdb->prepare( $count_sql, ...$values );
        }
        $total = (int) $wpdb->get_var( $count_sql );
        $pages = $total > 0 ? (int) ceil( $total / Directory_Query::PER_PAGE ) : 0;
        $page = $pages > 0 ? min( $filters['page'], $pages ) : 1;
        $offset = ( $page - 1 ) * Directory_Query::PER_PAGE;

        $select = 'user_id, display_name, country, age, gender, job_title, dialect, proficiency, registered_at';
        $sql = "SELECT {$select} FROM {$table} WHERE {$where_sql} ORDER BY registered_at DESC, user_id DESC LIMIT %d OFFSET %d";
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
