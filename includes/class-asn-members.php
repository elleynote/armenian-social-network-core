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
            'display_name' => self::public_display_name( $user_id, $user ),
        );

        foreach ( self::PROFILE_KEYS as $key ) {
            $data[ $key ] = self::profile_meta( $user_id, $key, '' );
        }

        return $data;
    }

    public static function public_display_name( int $user_id, $user = null ): string {
        if ( ! $user ) {
            $user = get_userdata( $user_id );
        }
        if ( ! $user ) {
            return '';
        }

        $first_name = trim( sanitize_text_field( (string) self::profile_meta( $user_id, 'first_name', '' ) ) );
        $last_name  = trim( sanitize_text_field( (string) self::profile_meta( $user_id, 'last_name', '' ) ) );
        $full_name  = trim( $first_name . ' ' . $last_name );

        if ( '' !== $full_name ) {
            return $full_name;
        }

        $display_name = trim( sanitize_text_field( (string) $user->display_name ) );
        if ( '' !== $display_name && false === strpos( $display_name, '@' ) ) {
            return $display_name;
        }

        return 'Member #' . (int) $user->ID;
    }

    public static function profile_meta( int $user_id, string $key, $default = null ) {
        if ( $user_id <= 0 ) {
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
