<?php
use ASN\Core\Database;
use PHPUnit\Framework\TestCase;

final class DatabaseTest extends TestCase {
    protected function setUp(): void {
        $GLOBALS['asn_test_options'] = array();
        $GLOBALS['asn_test_tables'] = array();
        $GLOBALS['asn_test_fail_table'] = '';
        $GLOBALS['wpdb']->prefix = 'custom_';
    }

    public function test_table_uses_runtime_wordpress_prefix(): void {
        $this->assertSame( 'custom_asn_profiles', Database::table( 'profiles' ) );
    }

    public function test_install_creates_all_tables_and_stores_version(): void {
        $this->assertTrue( Database::install() );

        foreach ( array( 'profiles', 'connections', 'profile_views', 'blocks', 'reports', 'notifications' ) as $name ) {
            $this->assertArrayHasKey( 'custom_asn_' . $name, $GLOBALS['asn_test_tables'] );
        }

        $this->assertSame( '1.0.0', get_option( Database::VERSION_OPTION ) );
    }

    public function test_install_is_idempotent(): void {
        $this->assertTrue( Database::install() );
        $first = array_keys( $GLOBALS['asn_test_tables'] );
        $this->assertTrue( Database::install() );
        $this->assertSame( $first, array_keys( $GLOBALS['asn_test_tables'] ) );
    }

    public function test_failed_verification_does_not_advance_version(): void {
        $GLOBALS['asn_test_fail_table'] = 'custom_asn_reports';

        $this->assertFalse( Database::install() );
        $this->assertSame( '', Database::current_version() );
    }
}
