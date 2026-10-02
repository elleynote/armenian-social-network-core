<?php
use ASN\Core\Database;
use PHPUnit\Framework\TestCase;

final class ReleaseManifestTest extends TestCase {
    public function test_release_versions_are_consistent(): void {
        $main = file_get_contents( dirname( __DIR__ ) . '/asn-core.php' );

        $this->assertStringContainsString( 'Version: 0.8.0', $main );
        $this->assertSame( '0.8.0', ASN_CORE_VERSION );
        $this->assertSame( '1.1.0', Database::VERSION );
    }

    public function test_release_builder_includes_only_runtime_roots(): void {
        $script = file_get_contents( dirname( __DIR__ ) . '/scripts/build-release.ps1' );

        foreach ( array(
            "'asn-core.php'",
            "'uninstall.php'",
            "'includes'",
            "'modules'",
            "'integrations'",
            "'admin'",
            "'public'",
        ) as $runtime ) {
            $this->assertStringContainsString( $runtime, $script );
        }

        foreach ( array(
            "'asn-core/.git/'",
            "'asn-core/.github/'",
            "'asn-core/tests/'",
            "'asn-core/docs/'",
            "'asn-core/scripts/'",
            "'asn-core/vendor/'",
            "'asn-core/build/'",
            "'asn-core/composer.json'",
            "'asn-core/composer.lock'",
            "'asn-core/phpunit.xml.dist'",
            "'asn-core/phpcs.xml.dist'",
        ) as $excluded ) {
            $this->assertStringContainsString( $excluded, $script );
        }

        $this->assertStringContainsString( ".Replace('\\', '/')", $script );
        $this->assertStringContainsString( "'asn-core/'", $script );
        $this->assertStringNotContainsString( 'Compress-Archive', $script );
    }
}
