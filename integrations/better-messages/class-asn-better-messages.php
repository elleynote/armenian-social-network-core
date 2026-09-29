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
            && method_exists( $instance->functions, 'create_conversation_link' );
    }

    public static function conversation_url( int $target_user_id ): string {
        $viewer_id = (int) get_current_user_id();

        if ( $target_user_id <= 0 || $viewer_id <= 0 || $viewer_id === $target_user_id || ! self::is_available() ) {
            return '';
        }

        $url = Better_Messages()->functions->create_conversation_link(
            $target_user_id,
            '',
            '',
            true,
            true
        );

        return is_string( $url ) ? esc_url_raw( $url ) : '';
    }
}
