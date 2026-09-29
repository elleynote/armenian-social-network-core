<?php
use ASN\Core\Messaging;
use PHPUnit\Framework\TestCase;

final class MessagingTest extends TestCase {
    private $active_plugins;
    private $current_user_id;
    private $better_messages_enabled;

    protected function setUp(): void {
        $this->active_plugins = get_option( 'active_plugins', array() );
        $this->current_user_id = $GLOBALS['asn_test_current_user_id'];
        $this->better_messages_enabled = $GLOBALS['asn_test_better_messages_enabled'];
        $GLOBALS['asn_test_options']['active_plugins'] = array( 'atomchat/atomchat.php' );
        $GLOBALS['asn_test_current_user_id'] = 8;
        $GLOBALS['asn_test_better_messages_enabled'] = false;
    }

    protected function tearDown(): void {
        $GLOBALS['asn_test_options']['active_plugins'] = $this->active_plugins;
        $GLOBALS['asn_test_current_user_id'] = $this->current_user_id;
        $GLOBALS['asn_test_better_messages_enabled'] = $this->better_messages_enabled;
    }

    public function test_available_atomchat_returns_transport_neutral_action(): void {
        $action = Messaging::action( 7 );
        $this->assertTrue( $action['available'] );
        $this->assertSame( 7, $action['target_user_id'] );
        $this->assertSame( 'atomchat', $action['transport'] );
    }

    public function test_missing_transport_or_invalid_user_fails_closed(): void {
        $GLOBALS['asn_test_options']['active_plugins'] = array();
        $this->assertFalse( Messaging::action( 7 )['available'] );

        $invalid = Messaging::action( 999 );
        $this->assertFalse( $invalid['available'] );
        $this->assertSame( 0, $invalid['target_user_id'] );
        $this->assertSame( '', $invalid['transport'] );
    }

    public function test_better_messages_parallel_action_uses_wordpress_user_id_and_public_api_link(): void {
        $GLOBALS['asn_test_better_messages_enabled'] = true;

        $action = Messaging::better_messages_action( 7 );

        $this->assertTrue( $action['available'] );
        $this->assertSame( 7, $action['target_user_id'] );
        $this->assertSame( 'better-messages', $action['transport'] );
        $this->assertSame( 'https://example.test/messages/#conversation/1007', $action['url'] );
    }

    public function test_better_messages_parallel_action_requires_active_chat_membership_for_both_members(): void {
        $GLOBALS['asn_test_better_messages_enabled'] = true;

        $previous = $GLOBALS['asn_test_pmpro_levels'][8] ?? null;
        unset( $GLOBALS['asn_test_pmpro_levels'][8] );

        try {
            $action = Messaging::better_messages_action( 7 );
            $this->assertFalse( $action['available'] );
            $this->assertFalse( $action['entitled'] );
            $this->assertSame( '', $action['url'] );
        } finally {
            if ( null === $previous ) {
                unset( $GLOBALS['asn_test_pmpro_levels'][8] );
            } else {
                $GLOBALS['asn_test_pmpro_levels'][8] = $previous;
            }
        }
    }

    public function test_better_messages_send_filter_blocks_non_members_with_clear_error(): void {
        global $bp_better_messages_restrict_send_message;

        $previous = $GLOBALS['asn_test_pmpro_levels'][8] ?? null;
        $previous_errors = $bp_better_messages_restrict_send_message ?? null;
        unset( $GLOBALS['asn_test_pmpro_levels'][8] );
        $bp_better_messages_restrict_send_message = array();

        try {
            $this->assertFalse( Messaging::filter_better_messages_can_send_message( true, 8, 123 ) );
            $this->assertArrayHasKey( 'asn_membership', $bp_better_messages_restrict_send_message );
        } finally {
            if ( null === $previous ) {
                unset( $GLOBALS['asn_test_pmpro_levels'][8] );
            } else {
                $GLOBALS['asn_test_pmpro_levels'][8] = $previous;
            }
            $bp_better_messages_restrict_send_message = $previous_errors;
        }
    }

    public function test_better_messages_parallel_action_fails_closed_for_self_invalid_or_unavailable_transport(): void {
        $this->assertFalse( Messaging::better_messages_action( 7 )['available'] );

        $GLOBALS['asn_test_better_messages_enabled'] = true;
        $this->assertFalse( Messaging::better_messages_action( 999 )['available'] );

        $GLOBALS['asn_test_current_user_id'] = 7;
        $this->assertFalse( Messaging::better_messages_action( 7 )['available'] );
    }

    public function test_better_messages_audio_call_allows_level_one_and_level_two_members(): void {
        $GLOBALS['asn_test_better_messages_enabled'] = true;

        $this->assertTrue( Messaging::filter_better_messages_can_audio_call( true, 8, 1007 ) );
        $this->assertTrue( Messaging::filter_better_messages_can_audio_call( true, 7, 1007 ) );
        $this->assertFalse( Messaging::filter_better_messages_can_audio_call( false, 7, 1007 ) );
    }

    public function test_better_messages_video_call_requires_level_two_for_every_participant(): void {
        $GLOBALS['asn_test_better_messages_enabled'] = true;

        $this->assertFalse( Messaging::filter_better_messages_can_video_call( true, 8, 1007 ) );
        $this->assertFalse( Messaging::filter_better_messages_can_video_call( true, 7, 1007 ) );

        $previous = $GLOBALS['asn_test_pmpro_levels'][8];
        $GLOBALS['asn_test_pmpro_levels'][8] = 2;

        try {
            $this->assertTrue( Messaging::filter_better_messages_can_video_call( true, 7, 1007 ) );
        } finally {
            $GLOBALS['asn_test_pmpro_levels'][8] = $previous;
        }
    }

    public function test_better_messages_call_errors_match_membership_entitlements(): void {
        $GLOBALS['asn_test_better_messages_enabled'] = true;

        $this->assertSame(
            '',
            Messaging::filter_better_messages_call_create_error( '', 1007, 8, 'audio' )
        );
        $this->assertSame(
            'Video calls are available to ASN Level 2 members only.',
            Messaging::filter_better_messages_call_create_error( '', 1007, 8, 'video' )
        );
        $this->assertSame(
            'existing error',
            Messaging::filter_better_messages_call_join_error( 'existing error', 1007, 8, 'audio' )
        );
    }

    public function test_browser_launcher_checks_runtime_chain_and_contains_no_credentials(): void {
        $js = file_get_contents( dirname( __DIR__ ) . '/public/js/asn-messaging.js' );
        $this->assertStringContainsString( 'window.jqcc', $js );
        $this->assertStringContainsString( 'window.jqcc.cometchat', $js );
        $this->assertStringContainsString( "typeof window.jqcc.cometchat.launch === 'function'", $js );
        $this->assertStringContainsString( 'button.disabled = true', $js );
        $this->assertStringNotContainsString( 'apiKey', $js );
        $this->assertStringNotContainsString( 'authToken', $js );
    }
}
