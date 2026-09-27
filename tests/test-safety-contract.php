<?php
use PHPUnit\Framework\TestCase;

final class SafetyContractTest extends TestCase {
    private function production_php(): string {
        $root = dirname( __DIR__ );
        $paths = array_merge(
            glob( $root . '/*.php' ) ?: array(),
            glob( $root . '/includes/*.php' ) ?: array(),
            glob( $root . '/admin/*.php' ) ?: array(),
            glob( $root . '/integrations/*/*.php' ) ?: array(),
            glob( $root . '/modules/*/*.php' ) ?: array(),
            glob( $root . '/modules/*/views/*.php' ) ?: array()
        );

        $source = '';
        foreach ( $paths as $path ) {
            $source .= "\n" . file_get_contents( $path );
        }

        return $source;
    }

    public function test_v02_does_not_override_legacy_social_shortcodes(): void {
        $source = $this->production_php();

        foreach ( array( 'tac_contacts', 'tac_user_profile', 'tac_feeds', 'tac_reg_form' ) as $shortcode ) {
            $this->assertDoesNotMatchRegularExpression(
                '/add_shortcode\s*\(\s*[\'\"]' . preg_quote( $shortcode, '/' ) . '[\'\"]/',
                $source
            );
        }
    }

    public function test_v02_does_not_remove_atomchat_hooks_or_change_tun_sso_options(): void {
        $source = $this->production_php();

        $this->assertDoesNotMatchRegularExpression(
            '/remove_(action|filter)\s*\([^;]*atomchat/i',
            $source
        );
        $this->assertDoesNotMatchRegularExpression(
            '/(update|delete)_option\s*\([^;]*(miniorange|oauth)/i',
            $source
        );
    }

    public function test_v02_does_not_mutate_woocommerce_prices_or_subscriptions(): void {
        $source = $this->production_php();

        $this->assertDoesNotMatchRegularExpression(
            '/update_post_meta\s*\([^;]*(_price|_regular_price|_sale_price)/i',
            $source
        );
        $this->assertDoesNotMatchRegularExpression(
            '/->\s*(save|update_status|cancel_order)\s*\([^;]*subscription/i',
            $source
        );
    }

    public function test_uninstall_preserves_tables_and_users(): void {
        $uninstall = file_get_contents( dirname( __DIR__ ) . '/uninstall.php' );

        $this->assertStringNotContainsString( 'DROP TABLE', strtoupper( $uninstall ) );
        $this->assertStringNotContainsString( 'delete_user', strtolower( $uninstall ) );
        $this->assertStringNotContainsString( 'wp_delete_user', strtolower( $uninstall ) );
    }
}
