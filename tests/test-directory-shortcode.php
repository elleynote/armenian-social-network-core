<?php
use ASN\Core\Directory\Directory_Shortcode;
use PHPUnit\Framework\TestCase;

final class ASN_Directory_Shortcode_Test_WPDB extends ASN_Test_WPDB {
    public $total = 21;

    public function esc_like( $text ) {
        return addcslashes( (string) $text, '_%\\' );
    }

    public function get_var( $sql ) {
        if ( false !== stripos( $sql, 'SELECT COUNT(' ) ) {
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
                'here_for' => 'friendship,networking',
                'last_active_at' => '2026-09-27 00:00:00',
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
        $GLOBALS['asn_test_better_messages_enabled'] = false;
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
        $this->assertStringContainsString( '@testmember', $html );
        $this->assertStringContainsString( 'Fluent Speaker', $html );
        $this->assertStringContainsString( 'View Profile', $html );
        $this->assertStringContainsString( 'q=Test', $html );
        $this->assertStringContainsString( 'dialect=western', $html );
        $this->assertStringNotContainsString( 'private@example.test', $html );
        $this->assertStringNotContainsString( 'secret-hash', $html );
    }

    public function test_recent_member_gets_new_badge(): void {
        $previous = $GLOBALS['asn_test_users'][7]->user_registered;
        $GLOBALS['asn_test_users'][7]->user_registered = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );

        try {
            $html = ( new Directory_Shortcode() )->render();
            $this->assertStringContainsString( 'asn-member-card__new-badge', $html );
            $this->assertStringContainsString( '>New</span>', $html );
        } finally {
            $GLOBALS['asn_test_users'][7]->user_registered = $previous;
        }
    }

    public function test_shared_discovery_controls_render_on_explore(): void {
        $_GET = array(
            'age_min' => '25',
            'age_max' => '45',
            'gender' => 'Female',
            'job_title' => 'Teacher',
            'here_for' => 'friendship',
            'recent' => '1',
        );

        $html = ( new Directory_Shortcode() )->render();

        $this->assertStringContainsString( '<details class="asn-directory__advanced" open>', $html );
        $this->assertStringContainsString( 'Advanced filters', $html );
        $this->assertStringContainsString( 'name="age_min"', $html );
        $this->assertStringContainsString( 'name="age_max"', $html );
        $this->assertStringContainsString( 'name="gender"', $html );
        $this->assertStringContainsString( 'name="job_title"', $html );
        $this->assertStringContainsString( 'name="here_for"', $html );
        $this->assertStringContainsString( 'Recently active', $html );
        $this->assertStringContainsString( 'Saved profiles only', $html );
        $this->assertStringContainsString( 'Save this search', $html );
        $this->assertStringContainsString( 'asn-member-card__favorite', $html );
        $this->assertStringContainsString( 'Saved searches', $html );
        $this->assertStringContainsString( 'Who viewed me', $html );
    }

    public function test_explore_discovery_tools_and_interests_are_visible_without_existing_saved_searches(): void {
        $GLOBALS['asn_test_user_meta'][8]['_asn_saved_searches'] = array();

        $html = ( new Directory_Shortcode() )->render();

        $this->assertStringContainsString( 'All members', $html );
        $this->assertStringContainsString( 'Recently active', $html );
        $this->assertStringContainsString( 'Saved profiles', $html );
        $this->assertStringContainsString( 'Saved searches', $html );
        $this->assertStringContainsString( 'Who viewed me', $html );
        $this->assertStringContainsString( 'Choose filters below', $html );
        $this->assertStringContainsString( 'Suggested members', $html );
        $this->assertStringContainsString( 'Friendship', $html );
        $this->assertStringContainsString( 'Networking', $html );
    }

    public function test_parallel_better_messages_action_uses_client_chat_button_on_test_explore(): void {
        $GLOBALS['asn_test_better_messages_enabled'] = true;

        $html = ( new Directory_Shortcode() )->render();

        $this->assertStringContainsString( 'asn-member-card__view-profile', $html );
        $this->assertStringContainsString( 'asn-member-card__chat', $html );
        $this->assertStringContainsString( '>Message</a>', $html );
        $this->assertStringContainsString( 'action=asn_open_better_messages', html_entity_decode( $html, ENT_QUOTES, 'UTF-8' ) );
        $this->assertStringContainsString( 'target_user_id=7', html_entity_decode( $html, ENT_QUOTES, 'UTF-8' ) );
        $this->assertStringNotContainsString( '/messages/#conversation/1007', $html );
        $this->assertStringNotContainsString( 'Test Better Messages', $html );
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

    public function test_explore_enqueues_ajax_directory_script_and_marks_results_live(): void {
        $GLOBALS['asn_test_scripts'] = array();

        $html = ( new Directory_Shortcode() )->render();

        $handles = array_map(
            static function ( $script ) {
                return $script[0] ?? '';
            },
            $GLOBALS['asn_test_scripts']
        );

        $this->assertContains( 'asn-directory', $handles );
        $this->assertStringContainsString( 'aria-live="polite"', $html );
        $this->assertStringContainsString( 'data-asn-directory-results', $html );
        $this->assertStringContainsString( 'data-asn-directory-submit', $html );
        $this->assertStringContainsString( 'data-asn-next-url', $html );
        $this->assertStringContainsString( 'data-asn-load-sentinel', $html );
        $this->assertStringContainsString( 'data-asn-pagination-fallback', $html );
    }

    public function test_directory_script_supports_ajax_filters_and_infinite_scroll(): void {
        $script = file_get_contents( dirname( __DIR__ ) . '/public/js/asn-directory.js' );

        $this->assertStringContainsString( "document.addEventListener('submit'", $script );
        $this->assertStringContainsString( "document.addEventListener('click'", $script );
        $this->assertStringContainsString( 'fetch(', $script );
        $this->assertStringContainsString( 'event.preventDefault()', $script );
        $this->assertStringContainsString( 'history.pushState', $script );
        $this->assertStringContainsString( 'data-asn-directory-submit', $script );
        $this->assertStringContainsString( 'IntersectionObserver', $script );
        $this->assertStringContainsString( 'loadMore', $script );
        $this->assertStringContainsString( "rootMargin: '0px 0px 70% 0px'", $script );
        $this->assertStringContainsString( 'data-asn-next-url', $script );
    }

    public function test_elly_explore_visual_system_styles_filters_cards_and_pagination(): void {
        $css = file_get_contents( dirname( __DIR__ ) . '/public/css/asn-core.css' );

        $this->assertStringContainsString( '.asn-directory__filters', $css );
        $this->assertStringContainsString( 'background:#f3f3f3', $css );
        $this->assertStringContainsString( '.asn-member-card--v2', $css );
        $this->assertStringContainsString( 'border-radius:50%', $css );
        $this->assertStringContainsString( '.asn-pagination__link--current', $css );
        $this->assertStringContainsString( 'background:#f85d3f', $css );
        $this->assertStringContainsString( 'grid-template-columns:repeat(4,minmax(0,1fr))', $css );
        $this->assertStringContainsString( 'grid-template-columns:repeat(2,minmax(0,1fr))', $css );
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
        $this->assertStringContainsString( 'aria-label="Messaging unavailable"', $html );
        $this->assertStringNotContainsString( 'data-asn-message-user', $html );
    }
}
