<?php
namespace ASN\Core\Integrations;

defined( 'ABSPATH' ) || exit;

final class WooCommerce_Integration {
    private const PREMIUM_PRODUCT_ID = 152;
    private const PAID_SUCCESS_PATH = '/asn-register-test/?asn_step=complete';

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

    public static function filter_paid_return_url( $return_url, $order ): string {
        $return_url = (string) $return_url;

        if ( ! is_object( $order )
            || ! is_callable( array( $order, 'is_paid' ) )
            || ! $order->is_paid()
            || ! is_callable( array( $order, 'get_user_id' ) )
            || ! is_callable( array( $order, 'get_items' ) )
        ) {
            return $return_url;
        }

        $user_id = (int) $order->get_user_id();
        if ( $user_id <= 0 ) {
            return $return_url;
        }

        $current_user_id = (int) get_current_user_id();
        if ( $current_user_id > 0 && $current_user_id !== $user_id ) {
            return $return_url;
        }

        foreach ( (array) $order->get_items() as $item ) {
            if ( ! is_object( $item ) || ! is_callable( array( $item, 'get_product_id' ) ) ) {
                continue;
            }

            if ( self::PREMIUM_PRODUCT_ID === (int) $item->get_product_id() ) {
                return site_url( self::PAID_SUCCESS_PATH );
            }
        }

        return $return_url;
    }
}
