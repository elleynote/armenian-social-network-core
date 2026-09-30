<?php
use ASN\Core\Plugin;
use ASN\Core\Database;
use PHPUnit\Framework\TestCase;

final class PluginBootstrapTest extends TestCase {
    public function test_plugin_constants_are_defined(): void {
        $this->assertTrue( defined( 'ASN_CORE_VERSION' ) );
        $this->assertSame( '0.3.1', ASN_CORE_VERSION );
        $this->assertTrue( defined( 'ASN_CORE_FILE' ) );
        $this->assertTrue( defined( 'ASN_CORE_PATH' ) );
        $this->assertTrue( defined( 'ASN_CORE_URL' ) );
    }

    public function test_better_messages_membership_filter_is_registered(): void {
        $filters = $GLOBALS['asn_test_filters']['better_messages_can_send_message'] ?? array();
        $this->assertNotEmpty( $filters );
        $this->assertSame( 3, $filters[0]['accepted_args'] );
    }

    public function test_better_messages_call_filters_are_registered(): void {
        $audio = $GLOBALS['asn_test_filters']['bp_better_messages_can_audio_call'] ?? array();
        $video = $GLOBALS['asn_test_filters']['bp_better_messages_can_video_call'] ?? array();
        $create = $GLOBALS['asn_test_filters']['better_messages_call_create_custom_error'] ?? array();
        $join = $GLOBALS['asn_test_filters']['better_messages_call_join_custom_error'] ?? array();

        $this->assertSame( 3, $audio[0]['accepted_args'] ?? 0 );
        $this->assertSame( 3, $video[0]['accepted_args'] ?? 0 );
        $this->assertSame( 4, $create[0]['accepted_args'] ?? 0 );
        $this->assertSame( 4, $join[0]['accepted_args'] ?? 0 );
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

    public function test_init_upgrades_pending_database_schema(): void {
        $options = $GLOBALS['asn_test_options'];
        $tables = $GLOBALS['asn_test_tables'];
        $sql = $GLOBALS['asn_test_dbdelta_sql'];
        $prefix = $GLOBALS['wpdb']->prefix;
        $fail_table = $GLOBALS['asn_test_fail_table'];

        try {
            $GLOBALS['asn_test_options'][ Database::VERSION_OPTION ] = '1.0.0';
            $GLOBALS['asn_test_tables'] = array();
            $GLOBALS['asn_test_dbdelta_sql'] = array();
            $GLOBALS['asn_test_fail_table'] = '';
            $GLOBALS['wpdb']->prefix = 'custom_';

            Plugin::instance()->init();

            $this->assertSame( Database::VERSION, get_option( Database::VERSION_OPTION ) );
            $this->assertNotEmpty( $GLOBALS['asn_test_dbdelta_sql'] );
        } finally {
            $GLOBALS['asn_test_options'] = $options;
            $GLOBALS['asn_test_tables'] = $tables;
            $GLOBALS['asn_test_dbdelta_sql'] = $sql;
            $GLOBALS['asn_test_fail_table'] = $fail_table;
            $GLOBALS['wpdb']->prefix = $prefix;
        }
    }

}
