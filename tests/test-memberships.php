<?php
use ASN\Core\Integrations\PMPro_Integration;
use ASN\Core\Integrations\WooCommerce_Integration;
use ASN\Core\Memberships;
use PHPUnit\Framework\TestCase;

final class MembershipsTest extends TestCase {
    public function test_premium_level_is_detected(): void {
        $this->assertTrue( PMPro_Integration::is_available() );
        $this->assertSame( 2, Memberships::level_id( 7 ) );
        $this->assertTrue( Memberships::is_premium( 7 ) );
    }

    public function test_free_member_is_not_premium(): void {
        $this->assertSame( 1, Memberships::level_id( 8 ) );
        $this->assertFalse( Memberships::is_premium( 8 ) );
    }

    public function test_woocommerce_is_defensive_when_plugin_is_absent(): void {
        $this->assertFalse( WooCommerce_Integration::is_available() );
        $this->assertFalse( WooCommerce_Integration::subscriptions_available() );
    }
}
