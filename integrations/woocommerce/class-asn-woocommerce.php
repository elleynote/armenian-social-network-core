<?php
namespace ASN\Core\Integrations;

defined( 'ABSPATH' ) || exit;

final class WooCommerce_Integration {
    public static function is_available(): bool {
        return class_exists( 'WooCommerce' ) || function_exists( 'WC' );
    }

    public static function subscriptions_available(): bool {
        if ( ! self::is_available() ) {
            return false;
        }

        return class_exists( 'WC_Subscriptions' )
            || function_exists( 'wcs_get_users_subscriptions' )
            || function_exists( 'wcs_get_subscriptions' );
    }
}
