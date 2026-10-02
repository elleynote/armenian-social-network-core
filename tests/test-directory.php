<?php
use ASN\Core\Directory\Directory_Query;
use ASN\Core\Directory\Directory_Service;
use PHPUnit\Framework\TestCase;

final class ASN_Directory_Test_WPDB extends ASN_Test_WPDB {
    public $prepared_sql = array();

    public function prepare( $sql, ...$values ) {
        $prepared = parent::prepare( $sql, ...$values );
        $this->prepared_sql[] = $prepared;
        return $prepared;
    }

    public function esc_like( $text ) {
        return addcslashes( (string) $text, '_%\\' );
    }

    public function get_var( $sql ) {
        if ( false !== stripos( $sql, 'SELECT COUNT(' ) && false !== stripos( $sql, 'asn_profiles' ) ) {
            return count( $this->filtered_rows( $sql ) );
        }
        return parent::get_var( $sql );
    }

    public function get_results( $sql, $output = null ) {
        $rows = $this->filtered_rows( $sql );
        usort( $rows, static function ( $a, $b ) use ( $sql ) {
            if ( false !== stripos( $sql, 'ORDER BY p.last_active_at DESC' ) ) {
                $active = strcmp( (string) ( $b['last_active_at'] ?? '' ), (string) ( $a['last_active_at'] ?? '' ) );
                if ( 0 !== $active ) {
                    return $active;
                }
            }
            $date = strcmp( (string) $b['registered_at'], (string) $a['registered_at'] );
            return 0 !== $date ? $date : (int) $b['user_id'] <=> (int) $a['user_id'];
        } );

        preg_match( '/LIMIT\s+(\d+)\s+OFFSET\s+(\d+)/i', $sql, $limit );
        $rows = array_slice( $rows, isset( $limit[2] ) ? (int) $limit[2] : 0, isset( $limit[1] ) ? (int) $limit[1] : 20 );
        $allowed = array_flip( array( 'user_id', 'display_name', 'country', 'age', 'gender', 'job_title', 'dialect', 'proficiency', 'here_for', 'last_active_at', 'registered_at' ) );

        return array_map( static function ( $row ) use ( $allowed ) {
            return array_intersect_key( $row, $allowed );
        }, $rows );
    }

    private function filtered_rows( string $sql ): array {
        $rows = array_values( $GLOBALS['asn_test_profile_rows'] );

        if ( false !== stripos( $sql, 'pmpro_memberships_users' ) ) {
            $rows = array_values( array_filter( $rows, static function ( $row ) {
                $level = $GLOBALS['asn_test_pmpro_levels'][ (int) ( $row['user_id'] ?? 0 ) ] ?? null;
                return in_array( (int) $level, array( 1, 2 ), true );
            } ) );
        }

        $is_us_alias_sql = false !== stripos( $sql, "p.country LIKE '%United States%'" );

        foreach ( array( 'dialect', 'proficiency', 'country', 'gender' ) as $key ) {
            if ( 'country' === $key && $is_us_alias_sql ) {
                continue;
            }

            if ( preg_match( "/" . $key . " = '([^']*)'/i", $sql, $match ) ) {
                $expected = str_replace( "''", "'", $match[1] );
                $rows = array_values( array_filter( $rows, static function ( $row ) use ( $key, $expected ) {
                    return (string) ( $row[ $key ] ?? '' ) === $expected;
                } ) );
            }
        }

        if ( $is_us_alias_sql ) {
            $rows = array_values( array_filter( $rows, static function ( $row ) {
                $country = (string) ( $row['country'] ?? '' );
                return false !== stripos( $country, 'United States' )
                    || in_array( $country, array( 'USA', 'US' ), true );
            } ) );
        }

        if ( preg_match( "/display_name LIKE '((?:''|[^'])*)'/i", $sql, $match ) ) {
            $needle = str_replace( array( '\\%', '\\_', "''" ), array( '%', '_', "'" ), $match[1] );
            $needle = trim( $needle, '%' );
            $rows = array_values( array_filter( $rows, static function ( $row ) use ( $needle ) {
                foreach ( array( 'display_name', 'country', 'job_title', 'here_for' ) as $key ) {
                    if ( false !== stripos( (string) ( $row[ $key ] ?? '' ), $needle ) ) {
                        return true;
                    }
                }
                return false;
            } ) );
        }

        if ( preg_match( "/p\.job_title LIKE '((?:''|[^'])*)'/i", $sql, $match ) && false === stripos( $sql, 'p.display_name LIKE' ) ) {
            $needle = trim( str_replace( array( '\\%', '\\_', "''" ), array( '%', '_', "'" ), $match[1] ), '%' );
            $rows = array_values( array_filter( $rows, static function ( $row ) use ( $needle ) {
                return false !== stripos( (string) ( $row['job_title'] ?? '' ), $needle );
            } ) );
        }

        if ( preg_match( "/p\.here_for LIKE '((?:''|[^'])*)'/i", $sql, $match ) && false === stripos( $sql, 'p.display_name LIKE' ) ) {
            $needle = trim( str_replace( array( '\\%', '\\_', "''" ), array( '%', '_', "'" ), $match[1] ), '%' );
            $rows = array_values( array_filter( $rows, static function ( $row ) use ( $needle ) {
                return false !== stripos( (string) ( $row['here_for'] ?? '' ), $needle );
            } ) );
        }

        if ( preg_match( '/p\.age >= (\d+)/i', $sql, $match ) ) {
            $min = (int) $match[1];
            $rows = array_values( array_filter( $rows, static function ( $row ) use ( $min ) {
                return (int) ( $row['age'] ?? 0 ) >= $min;
            } ) );
        }

        if ( preg_match( '/p\.age <= (\d+)/i', $sql, $match ) ) {
            $max = (int) $match[1];
            $rows = array_values( array_filter( $rows, static function ( $row ) use ( $max ) {
                return (int) ( $row['age'] ?? 0 ) <= $max;
            } ) );
        }

        if ( preg_match( "/p\.last_active_at >= '([^']+)'/i", $sql, $match ) ) {
            $cutoff = $match[1];
            $rows = array_values( array_filter( $rows, static function ( $row ) use ( $cutoff ) {
                return '' !== (string) ( $row['last_active_at'] ?? '' )
                    && strcmp( (string) $row['last_active_at'], $cutoff ) >= 0;
            } ) );
        }

        if ( preg_match( '/p\.user_id IN \(([^)]+)\)/i', $sql, $match ) ) {
            $ids = array_map( 'intval', preg_split( '/\s*,\s*/', $match[1] ) );
            $rows = array_values( array_filter( $rows, static function ( $row ) use ( $ids ) {
                return in_array( (int) ( $row['user_id'] ?? 0 ), $ids, true );
            } ) );
        }

        if ( false !== stripos( $sql, '1 = 0' ) ) {
            $rows = array();
        }

        return $rows;
    }
}

final class DirectoryTest extends TestCase {
    private $wpdb;
    private $rows;
    private $levels;

    protected function setUp(): void {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->rows = $GLOBALS['asn_test_profile_rows'];
        $this->levels = $GLOBALS['asn_test_pmpro_levels'];
        $wpdb = new ASN_Directory_Test_WPDB();
        $wpdb->prefix = 'custom_';
        $GLOBALS['asn_test_profile_rows'] = array();

        $GLOBALS['asn_test_pmpro_levels'] = array();
        for ( $id = 1; $id <= 25; ++$id ) {
            $GLOBALS['asn_test_profile_rows'][ $id ] = array(
                'user_id' => $id,
                'display_name' => 25 === $id ? 'Anna Latest' : 'Member ' . $id,
                'country' => $id >= 24 ? 'Armenia' : 'Australia',
                'age' => 30,
                'gender' => 'Female',
                'job_title' => 23 === $id ? 'Teacher Anna' : 'Developer',
                'dialect' => 0 === $id % 2 ? 'eastern' : 'western',
                'proficiency' => 0 === $id % 3 ? 'advanced' : 'beginner',
                'here_for' => 0 === $id % 2 ? 'networking,community' : 'friendship',
                'last_active_at' => $id >= 20 ? '2026-09-27 00:00:00' : '2026-08-01 00:00:00',
                'registered_at' => sprintf( '2025-01-%02d 00:00:00', $id ),
                'user_email' => 'private@example.test',
            );
            $GLOBALS['asn_test_pmpro_levels'][ $id ] = 0 === $id % 2 ? 2 : 1;
        }

        $GLOBALS['asn_test_profile_rows'][26] = array(
            'user_id' => 26,
            'display_name' => 'Legacy Nonmember',
            'country' => 'Canada',
            'age' => 40,
            'gender' => 'Male',
            'job_title' => 'Legacy',
            'dialect' => 'eastern',
            'proficiency' => 'fluent',
            'here_for' => '',
            'last_active_at' => null,
            'registered_at' => '2025-01-26 00:00:00',
            'user_email' => 'legacy@example.test',
        );
    }

    protected function tearDown(): void {
        global $wpdb;
        $wpdb = $this->wpdb;
        $GLOBALS['asn_test_profile_rows'] = $this->rows;
        $GLOBALS['asn_test_pmpro_levels'] = $this->levels;
    }

    public function test_request_normalization_is_bounded_and_allowlisted(): void {
        $filters = Directory_Query::from_request( array(
            'q' => ' Anna ', 'dialect' => 'WESTERN', 'proficiency' => 'advanced',
            'country' => '<b>Armenia</b>', 'page' => '-4',
        ) );

        $this->assertSame( 'Anna', $filters['q'] );
        $this->assertSame( '', $filters['dialect'] );
        $this->assertSame( 'advanced', $filters['proficiency'] );
        $this->assertSame( 'Armenia', $filters['country'] );
        $this->assertSame( 1, $filters['page'] );
        $this->assertSame( 20, $filters['per_page'] );
        $this->assertSame( 10000, Directory_Query::from_request( array( 'page' => '99999999' ) )['page'] );
    }

    public function test_search_filters_and_pagination_work(): void {
        $this->assertSame( 2, Directory_Service::search( array( 'q' => 'Anna' ) )['total'] );
        $this->assertSame( 13, Directory_Service::search( array( 'dialect' => 'western' ) )['total'] );
        $this->assertSame( 8, Directory_Service::search( array( 'proficiency' => 'advanced' ) )['total'] );
        $this->assertSame( 2, Directory_Service::search( array( 'country' => 'Armenia' ) )['total'] );

        $combined = Directory_Service::search( array( 'country' => 'Armenia', 'dialect' => 'western', 'proficiency' => 'beginner' ) );
        $this->assertSame( 1, $combined['total'] );
        $this->assertSame( 25, $combined['items'][0]['user_id'] );

        $page1 = Directory_Service::search( array( 'page' => 1 ) );
        $page2 = Directory_Service::search( array( 'page' => 2 ) );
        $this->assertCount( 20, $page1['items'] );
        $this->assertSame( 25, $page1['items'][0]['user_id'] );
        $this->assertCount( 5, $page2['items'] );
        $this->assertSame( 2, Directory_Service::search( array( 'page' => 9999 ) )['page'] );
    }

    public function test_usa_country_alias_matches_united_states_members(): void {
        $GLOBALS['asn_test_profile_rows'][27] = array(
            'user_id' => 27,
            'display_name' => 'US Member One',
            'country' => 'United States of America',
            'age' => 31,
            'gender' => 'Female',
            'job_title' => 'Designer',
            'dialect' => 'eastern',
            'proficiency' => 'fluent',
            'here_for' => 'friendship',
            'last_active_at' => '2026-09-27 00:00:00',
            'registered_at' => '2025-02-01 00:00:00',
        );
        $GLOBALS['asn_test_profile_rows'][28] = array(
            'user_id' => 28,
            'display_name' => 'US Member Two',
            'country' => 'United States',
            'age' => 29,
            'gender' => 'Male',
            'job_title' => 'Engineer',
            'dialect' => 'western',
            'proficiency' => 'advanced',
            'here_for' => 'networking',
            'last_active_at' => '2026-09-27 00:00:00',
            'registered_at' => '2025-02-02 00:00:00',
        );
        $GLOBALS['asn_test_pmpro_levels'][27] = 1;
        $GLOBALS['asn_test_pmpro_levels'][28] = 2;

        $this->assertTrue( Directory_Query::is_united_states_country( 'usa' ) );
        $this->assertTrue( Directory_Query::is_united_states_country( 'U.S.A.' ) );
        $this->assertTrue( Directory_Query::is_united_states_country( 'United States' ) );
        $this->assertFalse( Directory_Query::is_united_states_country( 'Australia' ) );

        $result = Directory_Service::search( array( 'country' => 'usa' ) );

        $this->assertSame( 2, $result['total'] );
        $this->assertSame( array( 28, 27 ), array_map( static function ( $item ) {
            return (int) $item['user_id'];
        }, $result['items'] ) );
    }

    public function test_advanced_filters_and_recent_activity_work(): void {
        $filters = Directory_Query::from_request(
            array(
                'age_min' => '25',
                'age_max' => '35',
                'gender' => 'Female',
                'job_title' => 'Developer',
                'here_for' => 'networking',
                'recent' => '1',
            )
        );

        $this->assertSame( 25, $filters['age_min'] );
        $this->assertSame( 35, $filters['age_max'] );
        $this->assertSame( 'Female', $filters['gender'] );
        $this->assertSame( 'networking', $filters['here_for'] );
        $this->assertTrue( $filters['recent'] );

        $result = Directory_Service::search( $filters );
        $this->assertGreaterThan( 0, $result['total'] );
        foreach ( $result['items'] as $item ) {
            $this->assertSame( 'Female', $item['gender'] );
            $this->assertStringContainsString( 'networking', $item['here_for'] );
            $this->assertSame( 'Developer', $item['job_title'] );
        }
    }

    public function test_suggested_members_exclude_viewer_and_return_active_members(): void {
        $GLOBALS['asn_test_profile_rows'][7]['country'] = 'Australia';
        $GLOBALS['asn_test_profile_rows'][7]['dialect'] = 'western';
        $GLOBALS['asn_test_profile_rows'][7]['proficiency'] = 'beginner';
        $GLOBALS['asn_test_profile_rows'][7]['here_for'] = 'friendship';

        $suggested = Directory_Service::suggested( 7, 4 );

        $this->assertNotEmpty( $suggested );
        $this->assertLessThanOrEqual( 4, count( $suggested ) );
        $this->assertNotContains( 7, array_map( static function ( $row ) {
            return (int) $row['user_id'];
        }, $suggested ) );
    }

    public function test_search_excludes_profiles_without_level_one_or_level_two(): void {
        $result = Directory_Service::search( array() );

        $ids = array_map(
            static function ( $item ) {
                return (int) $item['user_id'];
            },
            $result['items']
        );

        $this->assertSame( 25, $result['total'] );
        $this->assertNotContains( 26, $ids );
    }

    public function test_private_fields_are_not_returned_and_hostile_search_stays_data(): void {
        global $wpdb;
        $result = Directory_Service::search( array() );
        $this->assertArrayNotHasKey( 'user_email', $result['items'][0] );

        $hostile = Directory_Service::search( array( 'q' => "%' OR 1=1 --" ) );
        $this->assertSame( 0, $hostile['total'] );
        $this->assertStringContainsString( "''", implode( "\n", $wpdb->prepared_sql ) );
    }
}
