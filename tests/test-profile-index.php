<?php
use ASN\Core\Profiles\Profile_Index;
use PHPUnit\Framework\TestCase;

final class ProfileIndexTest extends TestCase {
    protected function setUp(): void {
        $GLOBALS['asn_test_profile_rows'] = array();
        $GLOBALS['asn_test_sync_fail_user'] = 0;
        $GLOBALS['wpdb']->prefix = 'custom_';
    }

    public function test_sync_user_copies_approved_searchable_values_and_registration_date(): void {
        $this->assertTrue( Profile_Index::sync_user( 7 ) );
        $row = Profile_Index::find( 7 );

        $this->assertSame( 'Test Member', $row['display_name'] );
        $this->assertSame( 'Australia', $row['country'] );
        $this->assertSame( 35, $row['age'] );
        $this->assertSame( 'Female', $row['gender'] );
        $this->assertSame( 'Teacher', $row['job_title'] );
        $this->assertSame( 'western', $row['dialect'] );
        $this->assertSame( 'fluent', $row['proficiency'] );
        $this->assertSame( '2025-01-02 03:04:05', $row['registered_at'] );
        $this->assertArrayNotHasKey( 'user_email', $row );
        $this->assertArrayNotHasKey( 'user_pass', $row );
    }

    public function test_sync_user_never_indexes_email_like_display_name(): void {
        $original_display_name = $GLOBALS['asn_test_users'][7]->display_name;
        $GLOBALS['asn_test_users'][7]->display_name = 'private@example.test';

        try {
            $this->assertTrue( Profile_Index::sync_user( 7 ) );
            $row = Profile_Index::find( 7 );
            $this->assertSame( 'Test Member', $row['display_name'] );
            $this->assertStringNotContainsString( '@', $row['display_name'] );
        } finally {
            $GLOBALS['asn_test_users'][7]->display_name = $original_display_name;
        }
    }

    public function test_syncing_same_user_twice_keeps_one_logical_row(): void {
        $this->assertTrue( Profile_Index::sync_user( 7 ) );
        $first = Profile_Index::find( 7 );
        $this->assertTrue( Profile_Index::sync_user( 7 ) );
        $second = Profile_Index::find( 7 );

        $this->assertCount( 1, $GLOBALS['asn_test_profile_rows'] );
        $this->assertSame( $first['created_at'], $second['created_at'] );
    }

    public function test_nonexistent_user_returns_false_and_missing_row_returns_null(): void {
        $this->assertFalse( Profile_Index::sync_user( 999 ) );
        $this->assertNull( Profile_Index::find( 999 ) );
    }

    public function test_batch_sync_is_idempotent_and_does_not_change_source_meta(): void {
        $before = $GLOBALS['asn_test_user_meta'];
        $first = Profile_Index::sync_batch( 0, 50 );
        $second = Profile_Index::sync_batch( 0, 50 );

        $this->assertSame( array( 'processed' => 2, 'failed' => 0, 'next_offset' => 2, 'done' => true ), $first );
        $this->assertSame( $first, $second );
        $this->assertSame( $before, $GLOBALS['asn_test_user_meta'] );
        $this->assertCount( 2, $GLOBALS['asn_test_profile_rows'] );
    }

    public function test_failed_member_is_counted_without_aborting_batch(): void {
        $GLOBALS['asn_test_sync_fail_user'] = 8;
        $result = Profile_Index::sync_batch( 0, 50 );

        $this->assertSame( 2, $result['processed'] );
        $this->assertSame( 1, $result['failed'] );
        $this->assertNotNull( Profile_Index::find( 7 ) );
        $this->assertNull( Profile_Index::find( 8 ) );
    }
}
