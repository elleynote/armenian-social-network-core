<?php
namespace ASN\Core;

defined( 'ABSPATH' ) || exit;

final class Members {
    private const PROFILE_KEYS = array(
        'first_name',
        'last_name',
        'country',
        'age',
        'gender',
        'job_title',
        'spoken_proficiency',
    );

    public static function find( int $user_id ): ?array {
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return null;
        }

        $data = array(
            'id'           => (int) $user->ID,
            'display_name' => (string) $user->display_name,
        );

        foreach ( self::PROFILE_KEYS as $key ) {
            $data[ $key ] = self::profile_meta( $user_id, $key, '' );
        }

        return $data;
    }

    public static function profile_meta( int $user_id, string $key, $default = null ) {
        if ( ! get_userdata( $user_id ) ) {
            return $default;
        }

        if ( function_exists( 'metadata_exists' ) && ! metadata_exists( 'user', $user_id, $key ) ) {
            return $default;
        }

        $value = get_user_meta( $user_id, $key, true );
        if ( '' === $value && function_exists( 'metadata_exists' ) && ! metadata_exists( 'user', $user_id, $key ) ) {
            return $default;
        }

        return $value;
    }
}
