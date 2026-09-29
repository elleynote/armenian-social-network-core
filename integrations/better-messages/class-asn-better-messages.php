<?php
namespace ASN\Core\Integrations;

defined( 'ABSPATH' ) || exit;

final class Better_Messages_Integration {
    public static function is_available(): bool {
        if ( ! function_exists( 'Better_Messages' ) ) {
            return false;
        }

        $instance = Better_Messages();

        return is_object( $instance )
            && isset( $instance->functions )
            && is_object( $instance->functions )
            && method_exists( $instance->functions, 'get_private_conversation_id' )
            && method_exists( $instance->functions, 'get_user_messages_url' );
    }

    public static function conversation_url( int $target_user_id ): string {
        $viewer_id = (int) get_current_user_id();

        if ( $target_user_id <= 0 || $viewer_id <= 0 || $viewer_id === $target_user_id || ! self::is_available() ) {
            return '';
        }

        $result = Better_Messages()->functions->get_private_conversation_id(
            $target_user_id,
            $viewer_id,
            true,
            ''
        );

        if (
            ! is_array( $result )
            || ! in_array( $result['result'] ?? '', array( 'thread_created', 'thread_found' ), true )
            || empty( $result['thread_id'] )
        ) {
            return '';
        }

        $thread_id = absint( $result['thread_id'] );
        if ( $thread_id <= 0 ) {
            return '';
        }

        $url = Better_Messages()->functions->get_user_messages_url( $viewer_id, $thread_id );

        return is_string( $url ) ? esc_url_raw( $url ) : '';
    }
}
