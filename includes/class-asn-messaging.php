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
        $viewer_id = (int) get_current_user_id();
        $valid = $target_user_id > 0 && false !== get_userdata( $target_user_id );
        $entitled = $valid
            && $viewer_id > 0
            && Memberships::can_text_chat( $viewer_id )
            && Memberships::can_text_chat( $target_user_id );
        $url = $entitled ? Better_Messages_Integration::conversation_url( $target_user_id ) : '';
        $available = '' !== $url;

        return array(
            'available'      => $available,
            'target_user_id' => $valid ? $target_user_id : 0,
            'transport'      => $available ? 'better-messages' : '',
            'url'            => $url,
            'entitled'       => $entitled,
        );
    }

    public static function filter_better_messages_can_send_message( $allowed, $user_id, $thread_id ): bool {
        if ( ! $allowed ) {
            return false;
        }

        if ( Memberships::can_text_chat( (int) $user_id ) ) {
            return true;
        }

        global $bp_better_messages_restrict_send_message;
        if ( ! is_array( $bp_better_messages_restrict_send_message ) ) {
            $bp_better_messages_restrict_send_message = array();
        }

        $bp_better_messages_restrict_send_message['asn_membership'] = 'Messaging is available to active ASN members.';

        return false;
    }
}
