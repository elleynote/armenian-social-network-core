<?php
namespace ASN\Core\Profiles;

defined( 'ABSPATH' ) || exit;

final class Profile_Photo {
    public static function url( int $user_id ): string {
        if ( $user_id <= 0 || ! get_userdata( $user_id ) ) {
            return self::placeholder_url();
        }

        $source = Legacy_Profile_Contract::profile_photo_source();
        if ( 'user_meta_url' === ( $source['type'] ?? '' ) && ! empty( $source['key'] ) ) {
            $legacy = get_user_meta( $user_id, (string) $source['key'], true );
            if ( is_string( $legacy ) && '' !== trim( $legacy ) ) {
                return esc_url_raw( $legacy );
            }
        }

        $avatar = get_avatar_url( $user_id, array( 'size' => 256 ) );
        if ( is_string( $avatar ) && '' !== trim( $avatar ) ) {
            return esc_url_raw( $avatar );
        }

        return self::placeholder_url();
    }

    private static function placeholder_url(): string {
        return ASN_CORE_URL . 'public/images/profile-placeholder.svg';
    }
}
