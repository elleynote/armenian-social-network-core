<?php
namespace ASN\Core\Integrations;

defined( 'ABSPATH' ) || exit;

final class PMPro_Integration {
    public static function is_available(): bool {
        return function_exists( 'pmpro_hasMembershipLevel' ) || function_exists( 'pmpro_getMembershipLevelForUser' );
    }

    public static function level_id( int $user_id ): ?int {
        if ( ! self::is_available() ) {
            return null;
        }

        if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
            $level = pmpro_getMembershipLevelForUser( $user_id );
            if ( is_object( $level ) && isset( $level->id ) ) {
                return (int) $level->id;
            }
            if ( is_array( $level ) && isset( $level['id'] ) ) {
                return (int) $level['id'];
            }
        }

        return null;
    }

    public static function has_level( int $level_id, int $user_id ): bool {
        if ( ! self::is_available() ) {
            return false;
        }

        if ( function_exists( 'pmpro_hasMembershipLevel' ) ) {
            return (bool) pmpro_hasMembershipLevel( $level_id, $user_id );
        }

        return self::level_id( $user_id ) === $level_id;
    }
}
