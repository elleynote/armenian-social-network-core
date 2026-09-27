<?php
use ASN\Core\Profiles\Profile_Shortcode;
use PHPUnit\Framework\TestCase;

final class ProfileShortcodeTest extends TestCase {
    protected function setUp(): void {
        $_GET = array();
        $GLOBALS['asn_test_current_user_id'] = 7;
        $GLOBALS['asn_test_shortcodes'] = array();
    }

    public function test_shortcode_registers_once(): void {
        $shortcode = new Profile_Shortcode();
        $shortcode->register();
        $shortcode->register();

        $this->assertArrayHasKey( 'asn_profile', $GLOBALS['asn_test_shortcodes'] );
        $this->assertCount( 1, $GLOBALS['asn_test_shortcodes'] );
    }

    public function test_member_parameter_renders_server_side_public_profile(): void {
        $_GET['member'] = '7';
        $html = ( new Profile_Shortcode() )->render();

        $this->assertStringContainsString( 'Test Member', $html );
        $this->assertStringContainsString( 'Australia', $html );
        $this->assertStringContainsString( 'asn-profile', $html );
        $this->assertStringNotContainsString( 'private@example.test', $html );
        $this->assertStringNotContainsString( 'secret-hash', $html );
    }

    public function test_no_member_parameter_defaults_to_current_user_and_owner_sees_edit_control(): void {
        $html = ( new Profile_Shortcode() )->render();

        $this->assertStringContainsString( 'Test Member', $html );
        $this->assertStringContainsString( 'Edit profile', $html );
    }

    public function test_profile_renders_all_legacy_cards_including_empty_prompts_and_saved_images(): void {
        $previous_image = $GLOBALS['asn_test_user_meta'][7]['a_photo_of_me_on_holiday_image'] ?? null;
        $GLOBALS['asn_test_user_meta'][7]['a_photo_of_me_on_holiday_image'] = 'https://example.test/holiday.jpg';

        try {
            $html = ( new Profile_Shortcode() )->render();

            $this->assertSame( 23, substr_count( $html, 'class="asn-profile-card"' ) );
            $this->assertStringContainsString( 'My dream holiday destination is', $html );
            $this->assertStringContainsString( 'Not updated yet', $html );
            $this->assertStringContainsString( 'Jazz', $html );
            $this->assertStringContainsString( 'A photo of me on holiday', $html );
            $this->assertStringContainsString( 'https://example.test/holiday.jpg', $html );
        } finally {
            if ( null === $previous_image ) {
                unset( $GLOBALS['asn_test_user_meta'][7]['a_photo_of_me_on_holiday_image'] );
            } else {
                $GLOBALS['asn_test_user_meta'][7]['a_photo_of_me_on_holiday_image'] = $previous_image;
            }
        }
    }

    public function test_summary_uses_requested_name_email_age_gender_job_and_proficiency_order(): void {
        $html = ( new Profile_Shortcode() )->render();

        $positions = array(
            strpos( $html, 'class="asn-profile__name">Test Member</h1>' ),
            strpos( $html, 'class="asn-profile__email">private@example.test</p>' ),
            strpos( $html, '<strong>Age:</strong>' ),
            strpos( $html, '<strong>Gender:</strong>' ),
            strpos( $html, '<strong>Job title:</strong>' ),
            strpos( $html, '<strong>Spoken proficiency:</strong>' ),
        );

        foreach ( $positions as $position ) {
            $this->assertNotFalse( $position );
        }

        $this->assertSame( $positions, array_values( array_unique( $positions ) ) );
        $sorted = $positions;
        sort( $sorted, SORT_NUMERIC );
        $this->assertSame( $sorted, $positions );
    }

    public function test_owner_can_edit_profile_cards_inline_and_other_members_cannot(): void {
        $owner_html = ( new Profile_Shortcode() )->render();
        $this->assertStringContainsString( 'name="asn_profile[my_favorite_music_is]"', $owner_html );
        $this->assertStringContainsString( '>Jazz</textarea>', $owner_html );
        $this->assertSame( 23, substr_count( $owner_html, 'class="asn-profile-card__textarea"' ) );
        $this->assertStringContainsString( 'Save profile cards', $owner_html );

        $_GET['member'] = '8';
        $other_html = ( new Profile_Shortcode() )->render();
        $this->assertStringNotContainsString( 'asn-profile-card__textarea', $other_html );
        $this->assertStringNotContainsString( 'Save profile cards', $other_html );
    }

    public function test_other_member_does_not_show_edit_control(): void {
        $_GET['member'] = '8';
        $html = ( new Profile_Shortcode() )->render();

        $this->assertStringContainsString( 'Free Member', $html );
        $this->assertStringNotContainsString( 'Edit profile', $html );
    }

    public function test_invalid_or_nonexistent_member_renders_controlled_not_found_state(): void {
        $_GET['member'] = 'not-a-user';
        $this->assertStringContainsString( 'Member not found', ( new Profile_Shortcode() )->render() );

        $_GET['member'] = '999';
        $this->assertStringContainsString( 'Member not found', ( new Profile_Shortcode() )->render() );
    }

    public function test_core_content_does_not_require_javascript_and_uses_scoped_classes(): void {
        $html = ( new Profile_Shortcode() )->render();

        $this->assertStringNotContainsString( '<script', $html );
        $this->assertStringContainsString( 'class="asn-profile', $html );
    }
}
