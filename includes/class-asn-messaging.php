<?php
namespace ASN\Core;

use ASN\Core\Integrations\AtomChat_Integration;

defined( 'ABSPATH' ) || exit;

final class Messaging {
    public static function action( int $target_user_id ): array {
        $valid = $target_user_id > 0 && false !== get_userdata( $target_user_id );
        $available = $valid && AtomChat_Integration::is_available();

        return array(
            'available'      => $available,
            'target_user_id' => $valid ? $target_user_id : 0,
            'transport'      => $available ? 'atomchat' : '',
        );
    }
}
