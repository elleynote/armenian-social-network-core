<?php
use ASN\Core\Plugin;
use PHPUnit\Framework\TestCase;

final class PluginBootstrapTest extends TestCase {
    public function test_plugin_constants_are_defined(): void {
        $this->assertTrue( defined( 'ASN_CORE_VERSION' ) );
        $this->assertSame( '0.2.0', ASN_CORE_VERSION );
        $this->assertTrue( defined( 'ASN_CORE_FILE' ) );
        $this->assertTrue( defined( 'ASN_CORE_PATH' ) );
        $this->assertTrue( defined( 'ASN_CORE_URL' ) );
    }

    public function test_plugin_instance_is_singleton(): void {
        $this->assertSame( Plugin::instance(), Plugin::instance() );
    }

    public function test_boot_is_idempotent(): void {
        $plugin = Plugin::instance();
        $before = count( $GLOBALS['asn_test_hooks']['init'] ?? array() );
        $plugin->boot();
        $after = count( $GLOBALS['asn_test_hooks']['init'] ?? array() );

        $this->assertSame( $before, $after );
    }
}
