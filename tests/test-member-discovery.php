<?php
use ASN\Core\Features\Member_Discovery;
use ASN\Core\Profiles\Profile_Index;
use PHPUnit\Framework\TestCase;

final class MemberDiscoveryTest extends TestCase {
    private $meta;
    private $views;
    private $blocks;
    private $rows;

    protected function setUp(): void {
        $this->meta = $GLOBALS['asn_test_user_meta'];
        $this->views = $GLOBALS['asn_test_profile_views'];
        $this->blocks = $GLOBALS['asn_test_blocks'];
        $this->rows = $GLOBALS['asn_test_profile_rows'];
        $GLOBALS['asn_test_profile_views'] = array();
        $GLOBALS['asn_test_blocks'] = array();
        $GLOBALS['asn_test_current_user_id'] = 7;
        $GLOBALS['asn_test_logged_in'] = true;
    }

    protected function tearDown(): void {
        $GLOBALS['asn_test_user_meta'] = $this->meta;
        $GLOBALS['asn_test_profile_views'] = $this->views;
        $GLOBALS['asn_test_blocks'] = $this->blocks;
        $GLOBALS['asn_test_profile_rows'] = $this->rows;
    }

    public function test_member_can_save_and_remove_favorite_profile(): void {
        $this->assertTrue( Member_Discovery::set_favorite( 7, 8, true ) );
        $this->assertTrue( Member_Discovery::is_favorite( 7, 8 ) );
        $this->assertSame( array( 8 ), Member_Discovery::favorite_ids( 7 ) );

        $this->assertTrue( Member_Discovery::set_favorite( 7, 8, false ) );
        $this->assertFalse( Member_Discovery::is_favorite( 7, 8 ) );
    }

    public function test_blocked_profile_cannot_be_saved(): void {
        $GLOBALS['asn_test_blocks'][] = array(
            'blocker_id' => 7,
            'blocked_id' => 8,
            'created_at' => '2026-09-28 00:00:00',
        );

        $this->assertFalse( Member_Discovery::set_favorite( 7, 8, true ) );
    }

    public function test_saved_searches_are_normalized_and_deduplicated(): void {
        $request = array(
            'country' => 'United States',
            'age_min' => '25',
            'age_max' => '40',
            'recent' => '1',
        );

        $this->assertTrue( Member_Discovery::save_search( 7, $request ) );
        $this->assertTrue( Member_Discovery::save_search( 7, $request ) );

        $searches = Member_Discovery::saved_searches( 7 );
        $this->assertCount( 1, $searches );
        $this->assertSame( 'United States', $searches[0]['filters']['country'] );
        $this->assertSame( 25, $searches[0]['filters']['age_min'] );
        $this->assertTrue( $searches[0]['filters']['recent'] );

        $this->assertTrue( Member_Discovery::delete_saved_search( 7, $searches[0]['id'] ) );
        $this->assertSame( array(), Member_Discovery::saved_searches( 7 ) );
    }

    public function test_profile_views_are_recorded_and_return_recent_unique_viewers(): void {
        $this->assertTrue( Member_Discovery::record_profile_view( 7, 8 ) );
        $this->assertCount( 1, $GLOBALS['asn_test_profile_views'] );

        // A repeat within the one-hour de-duplication window should not add another row.
        $this->assertTrue( Member_Discovery::record_profile_view( 7, 8 ) );
        $this->assertCount( 1, $GLOBALS['asn_test_profile_views'] );

        $viewers = Member_Discovery::recent_profile_viewers( 8 );
        $this->assertCount( 1, $viewers );
        $this->assertSame( 7, (int) $viewers[0]['viewer_id'] );
    }

    public function test_activity_touch_updates_meta_and_profile_index(): void {
        $this->assertTrue( Profile_Index::sync_user( 7 ) );

        $discovery = new Member_Discovery();
        $discovery->touch_current_user_activity();

        $this->assertSame( '2026-09-28 00:00:00', get_user_meta( 7, Member_Discovery::LAST_ACTIVE_META, true ) );
        $this->assertTrue( Member_Discovery::is_recently_active( 7 ) );

        $row = Profile_Index::find( 7 );
        $this->assertSame( '2026-09-28 00:00:00', $row['last_active_at'] );
    }
}
