<?php
use ASN\Core\Profiles\Profile_Shortcode;
use PHPUnit\Framework\TestCase;

final class ProfileShortcodeTest extends TestCase {
    protected function setUp(): void {
        $_GET = array();
        $GLOBALS['asn_test_current_user_id'] = 7;
        $GLOBALS['asn_test_shortcodes'] = array();
        $GLOBALS['asn_test_better_messages_enabled'] = false;
    }

    public function test_shortcode_registers_once(): void {
        $shortcode = new Profile_Shortcode();
        $shortcode->register();
        $shortcode->register();

        $this->assertArrayHasKey( 'asn_profile', $GLOBALS['asn_test_shortcodes'] );
        $this->assertCount( 1, $GLOBALS['asn_test_shortcodes'] );
    }

    public function test_member_parameter_renders_elly_v2_public_profile(): void {
        $_GET['member'] = '7';
        $html = ( new Profile_Shortcode() )->render();

        $this->assertStringContainsString( 'asn-profile--v2', $html );
        $this->assertStringContainsString( 'Test Member, 35', $html );
        $this->assertStringContainsString( '@testmember', $html );
        $this->assertStringContainsString( 'Teacher', $html );
        $this->assertStringContainsString( 'Australia', $html );
        $this->assertStringContainsString( 'Fluent Speaker', $html );
        $this->assertStringNotContainsString( 'private@example.test', $html );
        $this->assertStringNotContainsString( 'secret-hash', $html );
    }

    public function test_no_member_parameter_defaults_to_current_user_and_owner_sees_edit_control(): void {
        $html = ( new Profile_Shortcode() )->render();

        $this->assertStringContainsString( 'Test Member, 35', $html );
        $this->assertStringContainsString( '>Edit Profile</a>', $html );
        $this->assertStringNotContainsString( '>Message</a>', $html );
    }

    public function test_profile_groups_onboarding_answers_into_elly_sections(): void {
        $original = $GLOBALS['asn_test_user_meta'][7];
        $GLOBALS['asn_test_user_meta'][7] = array_merge(
            $original,
            array(
                'something_i_m_really_really_good_at_is' => 'Teaching',
                'my_dream_job_is' => 'Open a language school',
                'my_greatest_childhood_memory_is' => 'Summer with family',
                'one_thing_i_could_help_teach_you_about_is' => 'Armenian grammar',
            )
        );

        try {
            $html = ( new Profile_Shortcode() )->render();

            $this->assertStringContainsString( 'What makes me, me.', $html );
            $this->assertStringContainsString( 'What gets me out of bed in the morning?', $html );
            $this->assertStringContainsString( 'What I’m planning next?', $html );
            $this->assertStringContainsString( 'How I became me.', $html );
            $this->assertStringContainsString( 'What I can share.', $html );
            $this->assertStringContainsString( 'Jazz', $html );
            $this->assertStringContainsString( 'Teaching', $html );
            $this->assertStringContainsString( 'Open a language school', $html );
            $this->assertStringContainsString( 'Summer with family', $html );
            $this->assertStringContainsString( 'Armenian grammar', $html );
        } finally {
            $GLOBALS['asn_test_user_meta'][7] = $original;
        }
    }

    public function test_profile_gallery_uses_the_four_client_photo_slots(): void {
        $original = $GLOBALS['asn_test_user_meta'][7];
        $GLOBALS['asn_test_user_meta'][7]['a_photo_of_me_doing_what_i_love_most_image'] = 'https://example.test/love.jpg';

        try {
            $html = ( new Profile_Shortcode() )->render();

            $this->assertStringContainsString( 'A glimpse into my world.', $html );
            $this->assertSame( 4, substr_count( $html, 'class="asn-profile-v2__gallery-item"' ) );
            $this->assertStringContainsString( 'A photo of me doing what I love most.', $html );
            $this->assertStringContainsString( 'A photo of me being me.', $html );
            $this->assertStringContainsString( "A photo of something I've done recently.", html_entity_decode( $html, ENT_QUOTES, 'UTF-8' ) );
            $this->assertStringContainsString( 'A photo of the good old days.', $html );
            $this->assertStringContainsString( 'https://example.test/love.jpg', $html );
            $this->assertStringNotContainsString( 'A photo of me on holiday', $html );
        } finally {
            $GLOBALS['asn_test_user_meta'][7] = $original;
        }
    }

    public function test_normal_profile_view_is_read_only_and_owner_edits_through_edit_profile(): void {
        $html = ( new Profile_Shortcode() )->render();

        $this->assertStringNotContainsString( 'asn-profile-card__textarea', $html );
        $this->assertStringNotContainsString( 'Save profile cards', $html );
        $this->assertStringContainsString( '>Edit Profile</a>', $html );
    }

    public function test_other_member_does_not_show_edit_control(): void {
        $_GET['member'] = '8';
        $html = ( new Profile_Shortcode() )->render();

        $this->assertStringContainsString( 'Free Member', $html );
        $this->assertStringNotContainsString( 'Edit Profile', $html );
    }

    public function test_other_member_shows_better_messages_action_when_available(): void {
        $GLOBALS['asn_test_better_messages_enabled'] = true;
        $_GET['member'] = '8';

        $html = ( new Profile_Shortcode() )->render();

        $this->assertStringContainsString( '>Message</a>', $html );
        $this->assertStringContainsString( 'https://example.test/messages/#conversation/1008', $html );
        $this->assertStringNotContainsString( 'Test Better Messages', $html );
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
        $this->assertStringContainsString( 'class="asn-profile asn-profile--v2"', $html );
    }
}
