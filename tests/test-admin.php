<?php
use ASN\Core\Admin\Admin;
use PHPUnit\Framework\TestCase;

final class AdminTest extends TestCase {
    protected function tearDown(): void {
        $GLOBALS['asn_test_current_user_can'] = true;
    }

    public function test_status_screen_rejects_non_administrators(): void {
        $GLOBALS['asn_test_current_user_can'] = false;
        $this->expectException( RuntimeException::class );
        ( new Admin() )->render_status_page();
    }

    public function test_menu_requires_manage_options(): void {
        $admin = new Admin();
        $admin->register_menu();

        $this->assertSame( 'manage_options', $GLOBALS['asn_test_menu'][2] );
        $this->assertSame( 'asn-core', $GLOBALS['asn_test_menu'][3] );
    }

    public function test_status_screen_is_read_only_and_renders_known_statuses(): void {
        $GLOBALS['asn_test_options']['active_plugins'] = array(
            'atomchat/atomchat.php',
            'miniorange-login-with-eve-online-google-facebook/miniorange.php',
        );

        ob_start();
        ( new Admin() )->render_status_page();
        $html = ob_get_clean();

        $this->assertStringContainsString( 'ASN Core', $html );
        $this->assertStringContainsString( '567', $html );
        $this->assertStringContainsString( 'AtomChat', $html );
        $this->assertStringContainsString( 'Tun SSO / miniOrange', $html );
        $this->assertStringNotContainsString( 'API key', $html );
        $this->assertStringNotContainsString( 'password', strtolower( $html ) );
    }
}
