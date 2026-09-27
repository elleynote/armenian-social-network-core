<?php
use ASN\Core\Profiles\Profile_Form;
use ASN\Core\Profiles\Profile_Service;
use PHPUnit\Framework\TestCase;

final class ProfileFormTest extends TestCase {
    private $meta;

    protected function setUp(): void {
        $this->meta = $GLOBALS['asn_test_user_meta'];
        $GLOBALS['asn_test_current_user_id'] = 7;
        $GLOBALS['asn_test_logged_in'] = true;
        $GLOBALS['asn_test_valid_nonce'] = 'valid-nonce';
        $GLOBALS['asn_test_redirect'] = '';
        $GLOBALS['asn_test_sync_fail_user'] = 0;
        $_POST = array();
    }

    protected function tearDown(): void {
        $GLOBALS['asn_test_user_meta'] = $this->meta;
        $GLOBALS['asn_test_logged_in'] = true;
        $GLOBALS['asn_test_sync_fail_user'] = 0;
        $_POST = array();
    }

    public function test_actor_cannot_edit_another_member(): void {
        $result = Profile_Service::update_own_profile( 7, 8, array( 'country' => 'Armenia' ) );
        $this->assertFalse( $result['success'] );
        $this->assertContains( 'not_allowed', $result['errors'] );
    }

    public function test_arbitrary_meta_key_and_invalid_values_are_rejected_before_writes(): void {
        $before = $GLOBALS['asn_test_user_meta'][7]['country'];
        $result = Profile_Service::update_own_profile( 7, 7, array(
            'country'    => 'Armenia',
            'user_email' => 'attacker@example.test',
        ) );

        $this->assertFalse( $result['success'] );
        $this->assertSame( $before, $GLOBALS['asn_test_user_meta'][7]['country'] );
        $this->assertArrayNotHasKey( 'user_email', $GLOBALS['asn_test_user_meta'][7] );

        foreach ( array(
            array( 'gender' => '__invalid__' ),
            array( 'spoken_proficiency' => '__invalid__' ),
            array( 'age' => '121' ),
        ) as $input ) {
            $this->assertFalse( Profile_Service::update_own_profile( 7, 7, $input )['success'] );
        }
    }


    public function test_array_field_value_is_rejected_without_source_write(): void {
        $before = $GLOBALS['asn_test_user_meta'][7]['country'];
        $result = Profile_Service::update_own_profile( 7, 7, array(
            'country' => array( 'Armenia' ),
        ) );

        $this->assertFalse( $result['success'] );
        $this->assertSame( $before, $GLOBALS['asn_test_user_meta'][7]['country'] );
        $this->assertContains( 'invalid_value:country', $result['errors'] );
    }

    public function test_valid_own_profile_update_writes_only_approved_source_fields_and_refreshes_index(): void {
        $result = Profile_Service::update_own_profile( 7, 7, array(
            'country'   => ' Armenia ',
            'job_title' => '<b>Developer</b>',
            'age'       => '36',
        ) );

        $this->assertTrue( $result['success'] );
        $this->assertTrue( $result['index_synced'] );
        $this->assertSame( 'Armenia', $GLOBALS['asn_test_user_meta'][7]['country'] );
        $this->assertSame( 'Developer', $GLOBALS['asn_test_user_meta'][7]['job_title'] );
        $this->assertSame( 36, $GLOBALS['asn_test_user_meta'][7]['age'] );
        $this->assertContains( 'country', $result['updated'] );
    }

    public function test_index_failure_preserves_saved_wordpress_source_data_and_returns_warning(): void {
        $GLOBALS['asn_test_sync_fail_user'] = 7;
        $result = Profile_Service::update_own_profile( 7, 7, array( 'country' => 'Canada' ) );

        $this->assertTrue( $result['success'] );
        $this->assertFalse( $result['index_synced'] );
        $this->assertSame( 'Canada', $GLOBALS['asn_test_user_meta'][7]['country'] );
        $this->assertContains( 'index_sync_failed', $result['errors'] );
    }

    public function test_handler_rejects_logged_out_and_invalid_nonce_requests_with_controlled_redirect(): void {
        $form = new Profile_Form();

        $GLOBALS['asn_test_logged_in'] = false;
        $form->handle();
        $this->assertStringContainsString( 'asn_profile_status=not_allowed', $GLOBALS['asn_test_redirect'] );

        $GLOBALS['asn_test_logged_in'] = true;
        $_POST = array(
            'asn_profile_nonce' => 'wrong',
            'target_user_id'    => '7',
            'asn_profile'       => array( 'country' => 'Armenia' ),
        );
        $form->handle();
        $this->assertStringContainsString( 'asn_profile_status=security', $GLOBALS['asn_test_redirect'] );
    }

    public function test_valid_handler_redirects_with_status_and_never_prints_raw_errors(): void {
        $_POST = array(
            'asn_profile_nonce' => 'valid-nonce',
            'target_user_id'    => '7',
            'asn_profile'       => array( 'country' => 'Armenia' ),
        );

        ob_start();
        ( new Profile_Form() )->handle();
        $output = ob_get_clean();

        $this->assertSame( '', $output );
        $this->assertStringContainsString( 'asn_profile_status=updated', $GLOBALS['asn_test_redirect'] );
        $this->assertSame( 'Armenia', $GLOBALS['asn_test_user_meta'][7]['country'] );
    }
}
