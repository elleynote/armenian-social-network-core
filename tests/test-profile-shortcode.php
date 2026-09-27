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
