<?php
namespace ASN\Core\Profiles;

use ASN\Core\Database;
use ASN\Core\Members;

defined( 'ABSPATH' ) || exit;

final class Profile_Index {
    public static function sync_user( int $user_id ): bool {
        global $wpdb;

        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return false;
        }

        $member = Members::find( $user_id );
        if ( ! $member ) {
            return false;
        }

        $spoken = Profile_Fields::split_spoken_proficiency( (string) ( $member['spoken_proficiency'] ?? '' ) );
        $existing = self::find( $user_id );
        $now = current_time( 'mysql' );
        $age = Profile_Fields::sanitize( 'age', $member['age'] ?? '' );
        $gender = Profile_Fields::sanitize( 'gender', $member['gender'] ?? '' );

        $data = array(
            'user_id'       => $user_id,
            'display_name'  => sanitize_text_field( (string) $member['display_name'] ),
            'country'       => (string) ( Profile_Fields::sanitize( 'country', $member['country'] ?? '' ) ?? '' ),
            'age'           => null === $age ? null : (int) $age,
            'gender'        => null === $gender ? '' : (string) $gender,
            'job_title'     => (string) ( Profile_Fields::sanitize( 'job_title', $member['job_title'] ?? '' ) ?? '' ),
            'dialect'       => $spoken['dialect'],
            'proficiency'   => $spoken['proficiency'],
            'here_for'      => (string) ( Profile_Fields::sanitize( 'im_here_for', $member['im_here_for'] ?? '' ) ?? '' ),
            'last_active_at'=> self::activity_value( $user_id ),
            'registered_at' => isset( $user->user_registered ) ? (string) $user->user_registered : null,
            'created_at'    => $existing['created_at'] ?? $now,
            'updated_at'    => $now,
        );

        return false !== $wpdb->replace( Database::table( 'profiles' ), $data );
    }

    public static function touch_activity( int $user_id, string $when = '' ): bool {
        global $wpdb;

        if ( $user_id <= 0 || false === get_userdata( $user_id ) ) {
            return false;
        }

        if ( '' === $when ) {
            $when = current_time( 'mysql' );
        }

        update_user_meta( $user_id, '_asn_last_active_at', $when );

        if ( null === self::find( $user_id ) ) {
            return self::sync_user( $user_id );
        }

        return false !== $wpdb->update(
            ASNCoreDatabase::table( 'profiles' ),
            array(
                'last_active_at' => $when,
                'updated_at'     => current_time( 'mysql' ),
            ),
            array( 'user_id' => $user_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );
    }

    private static function activity_value( int $user_id ): ?string {
        $value = trim( (string) get_user_meta( $user_id, '_asn_last_active_at', true ) );
        return '' === $value ? null : $value;
    }

    public static function find( int $user_id ): ?array {
        global $wpdb;

        if ( $user_id <= 0 ) {
            return null;
        }

        $table = Database::table( 'profiles' );
        $sql = $wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d LIMIT 1", $user_id );
        $row = $wpdb->get_row( $sql, defined( 'ARRAY_A' ) ? ARRAY_A : 'ARRAY_A' );

        return is_array( $row ) ? $row : null;
    }

    public static function sync_batch( int $offset, int $limit = 50 ): array {
        $offset = max( 0, $offset );
        $limit = max( 1, min( 50, $limit ) );
        $user_ids = get_users( array(
            'fields'  => 'ids',
            'orderby' => 'ID',
            'order'   => 'ASC',
            'number'  => $limit,
            'offset'  => $offset,
        ) );

        $processed = 0;
        $failed = 0;

        foreach ( (array) $user_ids as $user_id ) {
            ++$processed;
            if ( ! self::sync_user( (int) $user_id ) ) {
                ++$failed;
            }
        }

        return array(
            'processed'   => $processed,
            'failed'      => $failed,
            'next_offset' => $offset + $processed,
            'done'        => $processed < $limit,
        );
    }
}
