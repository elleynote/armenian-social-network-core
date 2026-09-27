<?php
use ASN\Core\Members;
use PHPUnit\Framework\TestCase;

final class MembersTest extends TestCase {
    public function test_existing_user_returns_approved_profile_fields_only(): void {
        $member = Members::find( 7 );

        $this->assertSame( 7, $member['id'] );
        $this->assertSame( 'Test Member', $member['display_name'] );
        $this->assertSame( 'Australia', $member['country'] );
        $this->assertArrayNotHasKey( 'user_email', $member );
        $this->assertArrayNotHasKey( 'user_pass', $member );
    }

    public function test_nonexistent_user_returns_null(): void {
        $this->assertNull( Members::find( 999 ) );
    }

    public function test_missing_profile_meta_returns_supplied_default(): void {
        $this->assertSame( 'fallback', Members::profile_meta( 7, 'missing_field', 'fallback' ) );
    }
}
