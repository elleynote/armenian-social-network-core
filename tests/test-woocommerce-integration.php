<?php
use ASN\Core\Integrations\WooCommerce_Integration;
use PHPUnit\Framework\TestCase;

final class ASN_Test_WC_Order_Item {
    private $product_id;

    public function __construct( int $product_id ) {
        $this->product_id = $product_id;
    }

    public function get_product_id(): int {
        return $this->product_id;
    }
}

final class ASN_Test_WC_Order {
    private $paid;
    private $user_id;
    private $items;

    public function __construct( bool $paid, int $user_id, array $items ) {
        $this->paid = $paid;
        $this->user_id = $user_id;
        $this->items = $items;
    }

    public function is_paid(): bool {
        return $this->paid;
    }

    public function get_user_id(): int {
        return $this->user_id;
    }

    public function get_items(): array {
        return $this->items;
    }
}

final class WooCommerceIntegrationTest extends TestCase {
    public function test_paid_level_two_order_redirects_to_parallel_explore(): void {
        $previous_user = $GLOBALS['asn_test_current_user_id'];
        $GLOBALS['asn_test_current_user_id'] = 7;

        try {
            $order = new ASN_Test_WC_Order(
                true,
                7,
                array( new ASN_Test_WC_Order_Item( 152 ) )
            );

            $this->assertSame(
                'https://example.test/asn-register-test/?asn_step=complete',
                WooCommerce_Integration::filter_paid_return_url(
                    'https://example.test/checkout/order-received/1139/',
                    $order
                )
            );
        } finally {
            $GLOBALS['asn_test_current_user_id'] = $previous_user;
        }
    }

    public function test_unpaid_or_other_product_order_keeps_normal_return_url(): void {
        $original = 'https://example.test/checkout/order-received/1139/';

        $unpaid = new ASN_Test_WC_Order(
            false,
            7,
            array( new ASN_Test_WC_Order_Item( 152 ) )
        );
        $other_product = new ASN_Test_WC_Order(
            true,
            7,
            array( new ASN_Test_WC_Order_Item( 999 ) )
        );

        $this->assertSame( $original, WooCommerce_Integration::filter_paid_return_url( $original, $unpaid ) );
        $this->assertSame( $original, WooCommerce_Integration::filter_paid_return_url( $original, $other_product ) );
    }

    public function test_paid_order_for_another_logged_in_user_keeps_normal_return_url(): void {
        $previous_user = $GLOBALS['asn_test_current_user_id'];
        $GLOBALS['asn_test_current_user_id'] = 7;

        try {
            $original = 'https://example.test/checkout/order-received/1139/';
            $order = new ASN_Test_WC_Order(
                true,
                8,
                array( new ASN_Test_WC_Order_Item( 152 ) )
            );

            $this->assertSame(
                $original,
                WooCommerce_Integration::filter_paid_return_url( $original, $order )
            );
        } finally {
            $GLOBALS['asn_test_current_user_id'] = $previous_user;
        }
    }
}
