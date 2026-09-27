<?php
namespace ASN\Core\Integrations;

defined( 'ABSPATH' ) || exit;

final class AtomChat_Integration {
    public static function is_available(): bool {
        foreach ( (array) get_option( 'active_plugins', array() ) as $plugin ) {
            if ( false !== strpos( strtolower( (string) $plugin ), 'atomchat' ) ) {
                return true;
            }
        }
        return false;
    }
}
