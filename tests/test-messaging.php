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
        $this->assertSame( 'https://example.test/messages/#conversation/7', $action['url'] );
    }

    public function test_better_messages_parallel_action_fails_closed_for_self_invalid_or_unavailable_transport(): void {
        $this->assertFalse( Messaging::better_messages_action( 7 )['available'] );

        $GLOBALS['asn_test_better_messages_enabled'] = true;
        $this->assertFalse( Messaging::better_messages_action( 999 )['available'] );

        $GLOBALS['asn_test_current_user_id'] = 7;
        $this->assertFalse( Messaging::better_messages_action( 7 )['available'] );
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
