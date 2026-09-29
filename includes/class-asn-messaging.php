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

    public static function filter_better_messages_can_audio_call( $can_call, $user_id, $thread_id ): bool {
        if ( ! $can_call ) {
            return false;
        }

        return self::can_use_call_type( (int) $user_id, (int) $thread_id, 'audio' );
    }

    public static function filter_better_messages_can_video_call( $can_call, $user_id, $thread_id ): bool {
        if ( ! $can_call ) {
            return false;
        }

        return self::can_use_call_type( (int) $user_id, (int) $thread_id, 'video' );
    }

    public static function filter_better_messages_call_create_error( $error, $thread_id, $user_id, $type ): string {
        if ( ! empty( $error ) ) {
            return (string) $error;
        }

        return self::call_permission_error( (int) $user_id, (int) $thread_id, (string) $type );
    }

    public static function filter_better_messages_call_join_error( $error, $thread_id, $user_id, $type ): string {
        if ( ! empty( $error ) ) {
            return (string) $error;
        }

        return self::call_permission_error( (int) $user_id, (int) $thread_id, (string) $type );
    }

    private static function call_permission_error( int $user_id, int $thread_id, string $type ): string {
        if ( 'audio' === $type ) {
            return self::can_use_call_type( $user_id, $thread_id, 'audio' )
                ? ''
                : 'Audio calls are available to active ASN members.';
        }

        if ( 'video' === $type ) {
            return self::can_use_call_type( $user_id, $thread_id, 'video' )
                ? ''
                : 'Video calls are available to ASN Level 2 members only.';
        }

        return 'This call type is not available.';
    }

    private static function can_use_call_type( int $user_id, int $thread_id, string $type ): bool {
        if ( $user_id <= 0 || $thread_id <= 0 ) {
            return false;
        }

        $user_entitled = 'video' === $type
            ? Memberships::can_video_chat( $user_id )
            : Memberships::can_voice_chat( $user_id );

        if ( ! $user_entitled ) {
            return false;
        }

        if ( ! function_exists( 'Better_Messages' ) ) {
            return false;
        }

        $better_messages = Better_Messages();
        if ( ! is_object( $better_messages ) || ! isset( $better_messages->functions ) ) {
            return false;
        }

        $functions = $better_messages->functions;
        if ( ! is_object( $functions ) || ! is_callable( array( $functions, 'get_recipients_ids' ) ) ) {
            return false;
        }

        $participant_ids = $functions->get_recipients_ids( $thread_id );
        if ( ! is_array( $participant_ids ) || empty( $participant_ids ) ) {
            return false;
        }

        foreach ( $participant_ids as $participant_id ) {
            $participant_id = (int) $participant_id;
            if ( $participant_id <= 0 ) {
                return false;
            }

            $participant_entitled = 'video' === $type
                ? Memberships::can_video_chat( $participant_id )
                : Memberships::can_voice_chat( $participant_id );

            if ( ! $participant_entitled ) {
                return false;
            }
        }

        return true;
    }
}
