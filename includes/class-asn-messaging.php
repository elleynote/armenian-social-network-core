<?php
namespace ASN\Core;

use ASN\Core\Integrations\AtomChat_Integration;
use ASN\Core\Integrations\Better_Messages_Integration;
use ASN\Core\Features\Member_Safety;

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

    public static function better_messages_action( int $target_user_id, string $return_url = '' ): array {
        $viewer_id = (int) get_current_user_id();
        $valid = $target_user_id > 0 && false !== get_userdata( $target_user_id );
        $entitled = $valid
            && $viewer_id > 0
            && Memberships::can_text_chat( $viewer_id )
            && Memberships::can_text_chat( $target_user_id )
            && ! Member_Safety::is_blocked_between( $viewer_id, $target_user_id );
        $available = $entitled && Better_Messages_Integration::is_available();
        $url = $available ? self::better_messages_launch_url( $target_user_id, $return_url ) : '';

        return array(
            'available'      => $available,
            'target_user_id' => $valid ? $target_user_id : 0,
            'transport'      => $available ? 'better-messages' : '',
            'url'            => $url,
            'entitled'       => $entitled,
        );
    }

    public static function handle_open_better_messages(): void {
        $viewer_id = (int) get_current_user_id();
        $target_user_id = isset( $_GET['target_user_id'] ) ? absint( wp_unslash( $_GET['target_user_id'] ) ) : 0;
        $fallback = add_query_arg( 'member', $target_user_id, site_url( '/asn-profile-test/' ) );
        $return_url = isset( $_GET['return_url'] ) ? esc_url_raw( wp_unslash( $_GET['return_url'] ) ) : $fallback;
        $return_url = wp_validate_redirect( $return_url, $fallback );
        $nonce = isset( $_GET['asn_messaging_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['asn_messaging_nonce'] ) ) : '';

        $allowed = is_user_logged_in()
            && $viewer_id > 0
            && $target_user_id > 0
            && $viewer_id !== $target_user_id
            && wp_verify_nonce( $nonce, 'asn_open_better_messages_' . $target_user_id )
            && false !== get_userdata( $target_user_id )
            && Memberships::can_text_chat( $viewer_id )
            && Memberships::can_text_chat( $target_user_id )
            && ! Member_Safety::is_blocked_between( $viewer_id, $target_user_id )
            && Better_Messages_Integration::is_available();

        if ( ! $allowed ) {
            self::redirect( add_query_arg( 'asn_message_notice', 'unavailable', $return_url ) );
            return;
        }

        $conversation_url = Better_Messages_Integration::conversation_url( $target_user_id );
        if ( '' === $conversation_url ) {
            self::redirect( add_query_arg( 'asn_message_notice', 'unavailable', $return_url ) );
            return;
        }

        self::redirect( $conversation_url );
    }

    private static function better_messages_launch_url( int $target_user_id, string $return_url = '' ): string {
        if ( '' === $return_url ) {
            $return_url = add_query_arg( 'member', $target_user_id, site_url( '/asn-profile-test/' ) );
        }

        $url = admin_url( 'admin-post.php' );
        $url = add_query_arg( 'action', 'asn_open_better_messages', $url );
        $url = add_query_arg( 'target_user_id', $target_user_id, $url );
        $url = add_query_arg( 'return_url', $return_url, $url );
        $url = add_query_arg( 'asn_messaging_nonce', wp_create_nonce( 'asn_open_better_messages_' . $target_user_id ), $url );

        return esc_url_raw( $url );
    }

    private static function redirect( string $url ): void {
        wp_safe_redirect( $url );
        if ( ! defined( 'ASN_CORE_TESTING' ) || ! ASN_CORE_TESTING ) {
            exit;
        }
    }

    public static function filter_better_messages_can_send_message( $allowed, $user_id, $thread_id ): bool {
        if ( ! $allowed ) {
            return false;
        }

        if ( Memberships::can_text_chat( (int) $user_id ) && ! self::thread_has_blocked_pair( (int) $user_id, (int) $thread_id ) ) {
            return true;
        }

        global $bp_better_messages_restrict_send_message;
        if ( ! is_array( $bp_better_messages_restrict_send_message ) ) {
            $bp_better_messages_restrict_send_message = array();
        }

        if ( self::thread_has_blocked_pair( (int) $user_id, (int) $thread_id ) ) {
            $bp_better_messages_restrict_send_message['asn_blocked'] = 'Messaging is unavailable because one of you has blocked the other.';
        } else {
            $bp_better_messages_restrict_send_message['asn_membership'] = 'Messaging is available to active ASN members.';
        }

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

    private static function thread_has_blocked_pair( int $user_id, int $thread_id ): bool {
        if ( $user_id <= 0 || $thread_id <= 0 || ! function_exists( 'Better_Messages' ) ) {
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

        $participants = $functions->get_recipients_ids( $thread_id );
        if ( ! is_array( $participants ) ) {
            return false;
        }

        foreach ( $participants as $participant_id ) {
            $participant_id = (int) $participant_id;
            if ( $participant_id > 0 && $participant_id !== $user_id && Member_Safety::is_blocked_between( $user_id, $participant_id ) ) {
                return true;
            }
        }

        return false;
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

        if ( ! $user_entitled || self::thread_has_blocked_pair( $user_id, $thread_id ) ) {
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
