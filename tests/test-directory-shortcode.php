<?php
use ASN\Core\Directory\Directory_Shortcode;
use PHPUnit\Framework\TestCase;

final class ASN_Directory_Shortcode_Test_WPDB extends ASN_Test_WPDB {
    public $total = 21;

    public function esc_like( $text ) {
        return addcslashes( (string) $text, '_%\\' );
    }

    public function get_var( $sql ) {
        if ( false !== stripos( $sql, 'SELECT COUNT(*) FROM' ) ) {
            return $this->total;
        }
        return parent::get_var( $sql );
    }

    public function get_results( $sql, $output = null ) {
        if ( 0 === $this->total ) {
            return array();
        }
        return array(
            array(
                'user_id' => 7,
                'display_name' => 'Test Member',
                'country' => 'Australia',
                'age' => 35,
                'gender' => 'Female',
                'job_title' => 'Teacher',
                'dialect' => 'western',
                'proficiency' => 'fluent',
                'registered_at' => '2025-01-02 03:04:05',
            ),
        );
    }
}

final class DirectoryShortcodeTest extends TestCase {
    private $wpdb;
    private $active_plugins;

    protected function setUp(): void {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->active_plugins = get_option( 'active_plugins', array() );
        $wpdb = new ASN_Directory_Shortcode_Test_WPDB();
        $wpdb->prefix = 'custom_';
        $GLOBALS['asn_test_options']['active_plugins'] = array( 'atomchat/atomchat.php' );
        $GLOBALS['asn_test_current_user_id'] = 8;
        $GLOBALS['asn_test_shortcodes'] = array();
        $_GET = array();
    }

    protected function tearDown(): void {
        global $wpdb;
        $wpdb = $this->wpdb;
        $GLOBALS['asn_test_options']['active_plugins'] = $this->active_plugins;
        $_GET = array();
    }

    public function test_shortcode_registers_once(): void {
        $shortcode = new Directory_Shortcode();
        $shortcode->register();
        $shortcode->register();

        $this->assertArrayHasKey( 'asn_explore', $GLOBALS['asn_test_shortcodes'] );
        $this->assertCount( 1, $GLOBALS['asn_test_shortcodes'] );
    }

    public function test_filters_member_card_profile_link_and_message_action_render_safely(): void {
        $_GET = array(
            'q' => '<b>Test</b>',
            'dialect' => 'western',
            'proficiency' => 'fluent',
            'country' => 'Australia',
            'page' => '2',
        );

        $html = ( new Directory_Shortcode() )->render();

        $this->assertStringContainsString( 'value="Test"', $html );
        $this->assertStringContainsString( 'value="western" selected', $html );
        $this->assertStringContainsString( 'value="fluent" selected', $html );
        $this->assertStringContainsString( 'Test Member', $html );
        $this->assertStringContainsString( 'asn-member-card', $html );
        $this->assertStringContainsString( 'member=7', $html );
        $this->assertStringContainsString( 'tac_user=7', $html );
        $this->assertStringContainsString( 'data-asn-message-user="7"', $html );
        $this->assertStringContainsString( 'q=Test', $html );
        $this->assertStringContainsString( 'dialect=western', $html );
        $this->assertStringNotContainsString( 'private@example.test', $html );
        $this->assertStringNotContainsString( 'secret-hash', $html );
    }

    public function test_filter_form_posts_get_parameters_to_current_page_explicitly(): void {
        $previous_uri = $_SERVER['REQUEST_URI'] ?? null;
        $_SERVER['REQUEST_URI'] = '/asn-explore-test/?country=Old';

        try {
            $html = ( new Directory_Shortcode() )->render();

            $this->assertStringContainsString(
                '<form class="asn-directory__filters" method="get" action="/asn-explore-test/">',
                $html
            );
        } finally {
            if ( null === $previous_uri ) {
                unset( $_SERVER['REQUEST_URI'] );
            } else {
                $_SERVER['REQUEST_URI'] = $previous_uri;
            }
        }
    }

    public function test_profile_url_attribute_targets_parallel_asn_profile_page(): void {
        $html = ( new Directory_Shortcode() )->render( array(
            'profile_url' => 'https://example.test/asn-profile-test/',
        ) );

        $this->assertStringContainsString( 'https://example.test/asn-profile-test/?member=7', $html );
        $this->assertStringNotContainsString( 'https://example.test/profile/?member=7', $html );
    }

    public function test_empty_results_and_missing_transport_are_controlled(): void {
        global $wpdb;
        $wpdb->total = 0;
        $html = ( new Directory_Shortcode() )->render();
        $this->assertStringContainsString( 'No members found.', $html );

        $wpdb->total = 1;
        $GLOBALS['asn_test_options']['active_plugins'] = array();
        $html = ( new Directory_Shortcode() )->render();
        $this->assertStringContainsString( 'Messaging unavailable', $html );
        $this->assertStringNotContainsString( 'data-asn-message-user', $html );
    }
}
