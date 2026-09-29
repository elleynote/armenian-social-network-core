<?php
namespace ASN\Core;

use ASN\Core\Integrations\AtomChat_Integration;
use ASN\Core\Integrations\Better_Messages_Integration;

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

    public static function better_messages_action( int $target_user_id ): array {
        $valid = $target_user_id > 0 && false !== get_userdata( $target_user_id );
        $url = $valid ? Better_Messages_Integration::conversation_url( $target_user_id ) : '';
        $available = '' !== $url;

        return array(
            'available'      => $available,
            'target_user_id' => $valid ? $target_user_id : 0,
            'transport'      => $available ? 'better-messages' : '',
            'url'            => $url,
        );
    }
}
