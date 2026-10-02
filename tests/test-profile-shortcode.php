<?php
use ASN\Core\Profiles\Profile_Shortcode;
use PHPUnit\Framework\TestCase;

final class ProfileShortcodeTest extends TestCase {
    protected function setUp(): void {
        $_GET = array();
        $GLOBALS['asn_test_current_user_id'] = 7;
        $GLOBALS['asn_test_shortcodes'] = array();
        $GLOBALS['asn_test_better_messages_enabled'] = false;
        $GLOBALS['asn_test_blocks'] = array();
        $GLOBALS['asn_test_profile_views'] = array();
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

    public function test_owner_profile_shows_completion_and_resume_setup(): void {
        $html = ( new Profile_Shortcode() )->render();

        $this->assertStringContainsString( 'Profile completion', $html );
        $this->assertStringContainsString( 'Resume Profile Setup', $html );
        $this->assertStringContainsString( 'asn_step=prompts', $html );
    }

    public function test_profile_shows_here_for_safety_and_conversation_starters(): void {
        $previous = $GLOBALS['asn_test_user_meta'][8];
        $GLOBALS['asn_test_user_meta'][8]['im_here_for'] = 'friendship,armenian-practice';
        $GLOBALS['asn_test_user_meta'][8]['my_favorite_music_is'] = 'Jazz';
        $GLOBALS['asn_test_better_messages_enabled'] = true;
        $_GET['member'] = '8';

        try {
            $html = ( new Profile_Shortcode() )->render();

            $this->assertStringContainsString( 'Friendship', $html );
            $this->assertStringContainsString( 'Armenian practice', $html );
            $this->assertStringContainsString( 'Block Profile', $html );
            $this->assertStringContainsString( 'Report Profile', $html );
            $this->assertStringContainsString( 'Message about this', $html );
        } finally {
            $GLOBALS['asn_test_user_meta'][8] = $previous;
        }
    }

    public function test_other_member_profile_records_view_and_shows_save_profile_action(): void {
        $_GET['member'] = '8';

        $html = ( new Profile_Shortcode() )->render();

        $this->assertStringContainsString( 'Save Profile', $html );
        $this->assertCount( 1, $GLOBALS['asn_test_profile_views'] );
        $this->assertSame( 7, (int) $GLOBALS['asn_test_profile_views'][0]['viewer_id'] );
        $this->assertSame( 8, (int) $GLOBALS['asn_test_profile_views'][0]['profile_user_id'] );
    }

    public function test_owner_profile_shows_recent_profile_viewers(): void {
        $GLOBALS['asn_test_profile_views'][] = array(
            'viewer_id' => 8,
            'profile_user_id' => 7,
            'viewed_at' => '2026-09-28 00:00:00',
        );
        $_GET['member'] = '7';

        $html = ( new Profile_Shortcode() )->render();

        $this->assertStringContainsString( 'Who viewed my profile', $html );
        $this->assertStringContainsString( 'Free Member', $html );
    }

    public function test_recent_activity_badge_renders_on_member_profile(): void {
        $previous = $GLOBALS['asn_test_user_meta'][8]['_asn_last_active_at'] ?? null;
        $GLOBALS['asn_test_user_meta'][8]['_asn_last_active_at'] = '2026-09-27 00:00:00';
        $_GET['member'] = '8';

        try {
            $html = ( new Profile_Shortcode() )->render();
            $this->assertStringContainsString( 'Recently active', $html );
        } finally {
            if ( null === $previous ) {
                unset( $GLOBALS['asn_test_user_meta'][8]['_asn_last_active_at'] );
            } else {
                $GLOBALS['asn_test_user_meta'][8]['_asn_last_active_at'] = $previous;
            }
        }
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
