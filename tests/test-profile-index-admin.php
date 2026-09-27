<?php
use ASN\Core\Admin\Profile_Index_Admin;
use PHPUnit\Framework\TestCase;

final class ProfileIndexAdminTest extends TestCase {
    private $users;
    private $meta;
    private $options;

    protected function setUp(): void {
        $this->users = $GLOBALS['asn_test_users'];
        $this->meta = $GLOBALS['asn_test_user_meta'];
        $this->options = $GLOBALS['asn_test_options'];
        $GLOBALS['asn_test_current_user_can'] = true;
        $GLOBALS['asn_test_valid_nonce'] = 'valid-nonce';
        $GLOBALS['asn_test_redirect'] = '';
        $GLOBALS['asn_test_profile_rows'] = array();
        $GLOBALS['asn_test_sync_fail_user'] = 0;
        unset( $GLOBALS['asn_test_options'][ Profile_Index_Admin::OFFSET_OPTION ], $GLOBALS['asn_test_options'][ Profile_Index_Admin::COMPLETE_OPTION ] );
        $_POST = array( 'asn_sync_profiles_nonce' => 'valid-nonce' );
    }

    protected function tearDown(): void {
        $GLOBALS['asn_test_users'] = $this->users;
        $GLOBALS['asn_test_user_meta'] = $this->meta;
        $GLOBALS['asn_test_options'] = $this->options;
        $GLOBALS['asn_test_current_user_can'] = true;
        $_POST = array();
    }

    public function test_sync_requires_administrator_and_valid_nonce(): void {
        $admin = new Profile_Index_Admin();

        $GLOBALS['asn_test_current_user_can'] = false;
        $admin->handle_sync();
        $this->assertStringContainsString( 'asn_profile_sync_status=not_allowed', $GLOBALS['asn_test_redirect'] );

        $GLOBALS['asn_test_current_user_can'] = true;
        $_POST['asn_sync_profiles_nonce'] = 'wrong';
        $admin->handle_sync();
        $this->assertStringContainsString( 'asn_profile_sync_status=security', $GLOBALS['asn_test_redirect'] );
    }

    public function test_batch_size_is_exactly_fifty(): void {
        $this->assertSame( 50, Profile_Index_Admin::BATCH_SIZE );
    }

    public function test_progress_persists_next_offset_and_final_batch_marks_complete(): void {
        $GLOBALS['asn_test_users'] = array();
        $GLOBALS['asn_test_user_meta'] = array();
        for ( $id = 1; $id <= 55; ++$id ) {
            $GLOBALS['asn_test_users'][ $id ] = (object) array(
                'ID'              => $id,
                'display_name'    => 'Member ' . $id,
                'user_registered' => '2025-01-01 00:00:00',
            );
            $GLOBALS['asn_test_user_meta'][ $id ] = array(
                'country'            => 'Armenia',
                'gender'             => 'Female',
                'spoken_proficiency' => 'Eastern Armenian - Beginner',
            );
        }

        $admin = new Profile_Index_Admin();
        $admin->handle_sync();

        $this->assertSame( 50, get_option( Profile_Index_Admin::OFFSET_OPTION ) );
        $this->assertSame( 0, get_option( Profile_Index_Admin::COMPLETE_OPTION ) );
        $this->assertStringContainsString( 'asn_profile_sync_status=progress', $GLOBALS['asn_test_redirect'] );

        $admin->handle_sync();
        $this->assertSame( 0, get_option( Profile_Index_Admin::OFFSET_OPTION ) );
        $this->assertSame( 1, get_option( Profile_Index_Admin::COMPLETE_OPTION ) );
        $this->assertStringContainsString( 'asn_profile_sync_status=complete', $GLOBALS['asn_test_redirect'] );
        $this->assertCount( 55, $GLOBALS['asn_test_profile_rows'] );
    }

    public function test_failed_member_is_summarized_without_raw_error_output(): void {
        $GLOBALS['asn_test_sync_fail_user'] = 8;
        ob_start();
        ( new Profile_Index_Admin() )->handle_sync();
        $output = ob_get_clean();

        $this->assertSame( '', $output );
        $this->assertStringContainsString( 'complete_with_errors', $GLOBALS['asn_test_redirect'] );
        $this->assertStringNotContainsString( 'SELECT', $GLOBALS['asn_test_redirect'] );
        $this->assertStringNotContainsString( '/home/', $GLOBALS['asn_test_redirect'] );
    }
}
