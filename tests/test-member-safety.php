<?php
use ASN\Core\Features\Member_Safety;
use PHPUnit\Framework\TestCase;

final class MemberSafetyTest extends TestCase {
    private $wpdb;

    protected function setUp(): void {
        global $wpdb;
        $this->wpdb = $wpdb;
        $wpdb = new ASN_Test_WPDB();
        $GLOBALS['asn_test_blocks'] = array();
        $GLOBALS['asn_test_reports'] = array();
    }

    protected function tearDown(): void {
        global $wpdb;
        $wpdb = $this->wpdb;
    }

    public function test_member_can_block_and_unblock_another_profile(): void {
        $this->assertFalse( Member_Safety::is_blocked_between( 7, 8 ) );
        $this->assertTrue( Member_Safety::block( 7, 8 ) );
        $this->assertTrue( Member_Safety::is_blocked_by( 7, 8 ) );
        $this->assertTrue( Member_Safety::is_blocked_between( 7, 8 ) );

        $this->assertTrue( Member_Safety::unblock( 7, 8 ) );
        $this->assertFalse( Member_Safety::is_blocked_between( 7, 8 ) );
    }

    public function test_blocked_user_ids_are_loaded_in_one_batch(): void {
        $GLOBALS['asn_test_blocks'][] = array(
            'blocker_id' => 7,
            'blocked_id' => 8,
            'created_at' => '2026-10-03 00:00:00',
        );
        $GLOBALS['asn_test_blocks'][] = array(
            'blocker_id' => 9,
            'blocked_id' => 7,
            'created_at' => '2026-10-03 00:00:00',
        );

        $this->assertSame( array( 8, 9 ), Member_Safety::blocked_user_ids( 7 ) );
    }

    public function test_member_can_report_profile_with_allowed_reason(): void {
        $this->assertTrue( Member_Safety::report( 7, 8, 'suspicious' ) );
        $this->assertCount( 1, $GLOBALS['asn_test_reports'] );
        $this->assertSame( 7, $GLOBALS['asn_test_reports'][0]['reporter_id'] );
        $this->assertSame( 8, $GLOBALS['asn_test_reports'][0]['target_user_id'] );
        $this->assertSame( 'suspicious', $GLOBALS['asn_test_reports'][0]['reason'] );
        $this->assertSame( 'open', $GLOBALS['asn_test_reports'][0]['status'] );
    }

    public function test_invalid_or_self_safety_actions_fail_closed(): void {
        $this->assertFalse( Member_Safety::block( 7, 7 ) );
        $this->assertFalse( Member_Safety::block( 7, 999 ) );
        $this->assertFalse( Member_Safety::report( 7, 7, 'spam' ) );
        $this->assertFalse( Member_Safety::report( 7, 8, 'made-up-reason' ) );
    }

    public function test_profile_safety_handlers_register(): void {
        $safety = new Member_Safety();
        $safety->register_hooks();

        $this->assertNotEmpty( $GLOBALS['asn_test_hooks']['admin_post_asn_report_profile'] ?? array() );
        $this->assertNotEmpty( $GLOBALS['asn_test_hooks']['admin_post_asn_block_profile'] ?? array() );
        $this->assertNotEmpty( $GLOBALS['asn_test_hooks']['admin_post_asn_unblock_profile'] ?? array() );
    }
}
