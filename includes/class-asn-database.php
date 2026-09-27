<?php
namespace ASN\Core;

defined( 'ABSPATH' ) || exit;

final class Database {
    public const VERSION = '1.1.0';
    public const VERSION_OPTION = 'asn_core_db_version';
    public const ERROR_OPTION = 'asn_core_db_error';

    private const TABLES = array(
        'profiles',
        'connections',
        'profile_views',
        'blocks',
        'reports',
        'notifications',
    );

    public static function table( string $name ): string {
        global $wpdb;

        $safe_name = preg_replace( '/[^a-z0-9_]/', '', strtolower( $name ) );
        return $wpdb->prefix . 'asn_' . $safe_name;
    }

    public static function current_version(): string {
        return (string) get_option( self::VERSION_OPTION, '' );
    }

    public static function install(): bool {
        global $wpdb;

        if ( ! function_exists( 'dbDelta' ) ) {
            $upgrade_file = ABSPATH . 'wp-admin/includes/upgrade.php';
            if ( ! file_exists( $upgrade_file ) ) {
                update_option( self::ERROR_OPTION, 'WordPress database upgrade utilities are unavailable.', false );
                return false;
            }
            require_once $upgrade_file;
        }

        if ( ! function_exists( 'dbDelta' ) ) {
            update_option( self::ERROR_OPTION, 'WordPress database upgrade utilities are unavailable.', false );
            return false;
        }

        $collate = $wpdb->get_charset_collate();

        foreach ( self::schema( $collate ) as $sql ) {
            dbDelta( $sql );
        }

        foreach ( self::TABLES as $name ) {
            $table = self::table( $name );
            $found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
            if ( $table !== $found ) {
                update_option( self::ERROR_OPTION, 'ASN Core could not verify its database tables.', false );
                return false;
            }
        }

        update_option( self::VERSION_OPTION, self::VERSION, false );
        delete_option( self::ERROR_OPTION );
        return true;
    }

    private static function schema( string $collate ): array {
        $profiles = self::table( 'profiles' );
        $connections = self::table( 'connections' );
        $views = self::table( 'profile_views' );
        $blocks = self::table( 'blocks' );
        $reports = self::table( 'reports' );
        $notifications = self::table( 'notifications' );

        return array(
            "CREATE TABLE {$profiles} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                display_name varchar(191) NOT NULL DEFAULT '',
                country varchar(100) NOT NULL DEFAULT '',
                age smallint unsigned NULL,
                gender varchar(50) NOT NULL DEFAULT '',
                job_title varchar(191) NOT NULL DEFAULT '',
                dialect varchar(50) NOT NULL DEFAULT '',
                proficiency varchar(100) NOT NULL DEFAULT '',
                registered_at datetime NULL DEFAULT NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY user_id (user_id),
                KEY display_name (display_name),
                KEY country (country),
                KEY job_title (job_title),
                KEY registered_at (registered_at),
                KEY dialect (dialect),
                KEY proficiency (proficiency)
            ) {$collate};",
            "CREATE TABLE {$connections} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                requester_id bigint(20) unsigned NOT NULL,
                recipient_id bigint(20) unsigned NOT NULL,
                status varchar(20) NOT NULL DEFAULT 'pending',
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY user_pair (requester_id,recipient_id),
                KEY requester_status (requester_id,status),
                KEY recipient_status (recipient_id,status)
            ) {$collate};",
            "CREATE TABLE {$views} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                viewer_id bigint(20) unsigned NOT NULL,
                profile_user_id bigint(20) unsigned NOT NULL,
                viewed_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY profile_time (profile_user_id,viewed_at),
                KEY viewer_time (viewer_id,viewed_at)
            ) {$collate};",
            "CREATE TABLE {$blocks} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                blocker_id bigint(20) unsigned NOT NULL,
                blocked_id bigint(20) unsigned NOT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY blocker_blocked (blocker_id,blocked_id),
                KEY blocked_id (blocked_id)
            ) {$collate};",
            "CREATE TABLE {$reports} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                reporter_id bigint(20) unsigned NOT NULL,
                target_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
                object_type varchar(30) NOT NULL DEFAULT 'profile',
                object_id bigint(20) unsigned NOT NULL DEFAULT 0,
                reason text NOT NULL,
                status varchar(20) NOT NULL DEFAULT 'open',
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY status_created (status,created_at),
                KEY target_user (target_user_id),
                KEY object_lookup (object_type,object_id)
            ) {$collate};",
            "CREATE TABLE {$notifications} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                recipient_id bigint(20) unsigned NOT NULL,
                actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
                type varchar(50) NOT NULL,
                object_type varchar(30) NOT NULL DEFAULT '',
                object_id bigint(20) unsigned NOT NULL DEFAULT 0,
                is_read tinyint(1) unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                read_at datetime NULL DEFAULT NULL,
                PRIMARY KEY  (id),
                KEY recipient_read_time (recipient_id,is_read,created_at),
                KEY actor_id (actor_id),
                KEY object_lookup (object_type,object_id)
            ) {$collate};",
        );
    }
}
