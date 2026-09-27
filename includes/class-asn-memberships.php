<?php
namespace ASN\Core;

use ASN\Core\Integrations\PMPro_Integration;

defined( 'ABSPATH' ) || exit;

final class Memberships {
    public const PREMIUM_LEVEL_ID = 2;

    public static function is_available(): bool {
        return PMPro_Integration::is_available();
    }

    public static function level_id( int $user_id ): ?int {
        return PMPro_Integration::level_id( $user_id );
    }

    public static function is_premium( int $user_id ): bool {
        return PMPro_Integration::has_level( self::PREMIUM_LEVEL_ID, $user_id );
    }
}
