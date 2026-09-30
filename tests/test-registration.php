<?php
use ASN\Core\Integrations\PMPro_Integration;
use ASN\Core\Registration\Registration;
use PHPUnit\Framework\TestCase;

final class RegistrationTest extends TestCase {
    public function test_registration_shortcode_and_handlers_are_registered(): void {
        $this->assertArrayHasKey( 'asn_register', $GLOBALS['asn_test_shortcodes'] );
        $this->assertNotEmpty( $GLOBALS['asn_test_hooks']['admin_post_nopriv_asn_register_account'] ?? array() );
        $this->assertNotEmpty( $GLOBALS['asn_test_hooks']['admin_post_asn_register_profile'] ?? array() );
        $this->assertNotEmpty( $GLOBALS['asn_test_hooks']['admin_post_asn_choose_free_plan'] ?? array() );
    }

    public function test_birth_date_validation_returns_age_or_null(): void {
        $age = Registration::age_from_birth_date( '2010-01-01' );
        $this->assertNotNull( $age );
        $this->assertGreaterThanOrEqual( 15, $age );
        $this->assertLessThanOrEqual( 17, $age );

        $this->assertNull( Registration::age_from_birth_date( 'not-a-date' ) );
        $this->assertNull( Registration::age_from_birth_date( '2999-01-01' ) );
    }

    public function test_pmpro_helper_assigns_free_level(): void {
        $previous = $GLOBALS['asn_test_pmpro_levels'][8] ?? null;
        unset( $GLOBALS['asn_test_pmpro_levels'][8] );

        try {
            $this->assertTrue( PMPro_Integration::assign_level( 1, 8 ) );
            $this->assertSame( 1, $GLOBALS['asn_test_pmpro_levels'][8] );
        } finally {
            if ( null === $previous ) {
                unset( $GLOBALS['asn_test_pmpro_levels'][8] );
            } else {
                $GLOBALS['asn_test_pmpro_levels'][8] = $previous;
            }
        }
    }
}
