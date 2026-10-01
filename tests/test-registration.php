<?php
use ASN\Core\Integrations\PMPro_Integration;
use ASN\Core\Registration\Registration;
use ASN\Core\Registration\Registration_Shortcode;
use PHPUnit\Framework\TestCase;

final class ASN_Test_WC_Product {
    private $id;
    private $type;
    private $children;
    private $permalink;
    private $purchasable;
    private $in_stock;
    private $attributes;

    public function __construct(
        int $id,
        string $type,
        array $children = array(),
        string $permalink = '',
        bool $purchasable = true,
        bool $in_stock = true,
        array $attributes = array()
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->children = $children;
        $this->permalink = $permalink;
        $this->purchasable = $purchasable;
        $this->in_stock = $in_stock;
        $this->attributes = $attributes;
    }

    public function is_type( $types ): bool {
        return in_array( $this->type, (array) $types, true );
    }

    public function get_children(): array {
        return $this->children;
    }

    public function get_permalink(): string {
        return $this->permalink;
    }

    public function get_id(): int {
        return $this->id;
    }

    public function is_purchasable(): bool {
        return $this->purchasable;
    }

    public function is_in_stock(): bool {
        return $this->in_stock;
    }

    public function get_variation_attributes(): array {
        return $this->attributes;
    }
}

final class RegistrationTest extends TestCase {
    protected function setUp(): void {
        $GLOBALS['asn_test_wc_products'] = array();
        $GLOBALS['asn_test_scheduled_events'] = array();
        $GLOBALS['asn_test_new_user_notifications'] = array();
    }
    public function test_registration_shortcode_and_handlers_are_registered(): void {
        $GLOBALS['asn_test_shortcodes'] = array();

        $shortcode = new Registration_Shortcode();
        $shortcode->register();
        $shortcode->register();

        $registration = new Registration();
        $registration->register();

        $this->assertArrayHasKey( 'asn_register', $GLOBALS['asn_test_shortcodes'] );
        $this->assertCount( 1, $GLOBALS['asn_test_shortcodes'] );
        $this->assertNotEmpty( $GLOBALS['asn_test_hooks']['admin_post_nopriv_asn_register_account'] ?? array() );
        $this->assertNotEmpty( $GLOBALS['asn_test_hooks']['admin_post_asn_register_profile'] ?? array() );
        $this->assertNotEmpty( $GLOBALS['asn_test_hooks']['admin_post_asn_choose_free_plan'] ?? array() );
        $this->assertNotEmpty( $GLOBALS['asn_test_hooks']['asn_send_new_user_notification'] ?? array() );
        $this->assertNotEmpty( $GLOBALS['asn_test_hooks']['template_redirect'] ?? array() );
    }

    public function test_new_user_notification_is_scheduled_instead_of_sent_inline(): void {
        $this->assertTrue( Registration::schedule_new_user_notification( 8 ) );
        $this->assertCount( 1, $GLOBALS['asn_test_scheduled_events'] );
        $this->assertSame( 'asn_send_new_user_notification', $GLOBALS['asn_test_scheduled_events'][0]['hook'] );
        $this->assertSame( array( 8 ), $GLOBALS['asn_test_scheduled_events'][0]['args'] );
        $this->assertSame( array(), $GLOBALS['asn_test_new_user_notifications'] );

        $this->assertTrue( Registration::schedule_new_user_notification( 8 ) );
        $this->assertCount( 1, $GLOBALS['asn_test_scheduled_events'] );

        ( new Registration() )->send_new_user_notification( 8 );

        $this->assertSame(
            array(
                array(
                    'user_id' => 8,
                    'notify'  => 'both',
                ),
            ),
            $GLOBALS['asn_test_new_user_notifications']
        );
    }

    public function test_registration_renders_client_ui_v2_foundation(): void {
        $previous_user = $GLOBALS['asn_test_current_user_id'];
        $previous_logged_in = $GLOBALS['asn_test_logged_in'];
        $previous_level = $GLOBALS['asn_test_pmpro_levels'][8] ?? null;

        $GLOBALS['asn_test_current_user_id'] = 8;
        $GLOBALS['asn_test_logged_in'] = true;
        unset( $GLOBALS['asn_test_pmpro_levels'][8] );
        $_GET = array();

        try {
            $html = ( new Registration_Shortcode() )->render();

            $this->assertStringContainsString( 'asn-registration-v2', $html );
            $this->assertStringContainsString( 'Introduce yourself.', $html );
            $this->assertStringContainsString( 'Profile completion', $html );
            $this->assertStringContainsString( 'Help people get to know you.', $html );
        } finally {
            $GLOBALS['asn_test_current_user_id'] = $previous_user;
            $GLOBALS['asn_test_logged_in'] = $previous_logged_in;
            if ( null === $previous_level ) {
                unset( $GLOBALS['asn_test_pmpro_levels'][8] );
            } else {
                $GLOBALS['asn_test_pmpro_levels'][8] = $previous_level;
            }
            $_GET = array();
        }
    }

    public function test_birth_date_validation_returns_age_or_null(): void {
        $age = Registration::age_from_birth_date( '2010-01-01' );
        $this->assertNotNull( $age );
        $this->assertGreaterThanOrEqual( 15, $age );
        $this->assertLessThanOrEqual( 17, $age );

        $this->assertNull( Registration::age_from_birth_date( 'not-a-date' ) );
        $this->assertNull( Registration::age_from_birth_date( '2999-01-01' ) );
    }

    public function test_legacy_free_plan_redirect_is_rewritten_to_parallel_explore(): void {
        $explore = 'https://example.test/asn-explore-test/';

        $this->assertSame(
            $explore,
            Registration::rewrite_legacy_free_plan_redirect( 'https://example.test/register/?step=2', $explore )
        );
        $this->assertSame(
            'https://example.test/register/?step=1',
            Registration::rewrite_legacy_free_plan_redirect( 'https://example.test/register/?step=1', $explore )
        );
        $this->assertSame(
            'https://example.test/other/?step=2',
            Registration::rewrite_legacy_free_plan_redirect( 'https://example.test/other/?step=2', $explore )
        );
    }

    public function test_completed_free_signup_redirects_legacy_step_two_to_parallel_explore(): void {
        $previous_uri = $_SERVER['REQUEST_URI'] ?? null;
        $previous_get = $_GET;
        $previous_redirect = $GLOBALS['asn_test_redirect'];
        $previous_url = $GLOBALS['asn_test_user_meta'][8]['_asn_free_onboarding_redirect_url'] ?? null;
        $previous_expires = $GLOBALS['asn_test_user_meta'][8]['_asn_free_onboarding_redirect_expires'] ?? null;

        $_SERVER['REQUEST_URI'] = '/register/?step=2';
        $_GET = array( 'step' => '2' );
        $GLOBALS['asn_test_current_user_id'] = 8;
        $GLOBALS['asn_test_logged_in'] = true;
        $GLOBALS['asn_test_pmpro_levels'][8] = 1;
        $GLOBALS['asn_test_user_meta'][8]['_asn_free_onboarding_redirect_url'] = 'https://example.test/asn-explore-test/';
        $GLOBALS['asn_test_user_meta'][8]['_asn_free_onboarding_redirect_expires'] = time() + 300;
        $GLOBALS['asn_test_redirect'] = '';

        try {
            ( new Registration() )->maybe_redirect_completed_free_signup();
            $this->assertSame( 'https://example.test/asn-explore-test/', $GLOBALS['asn_test_redirect'] );
            $this->assertArrayNotHasKey( '_asn_free_onboarding_redirect_url', $GLOBALS['asn_test_user_meta'][8] );
            $this->assertArrayNotHasKey( '_asn_free_onboarding_redirect_expires', $GLOBALS['asn_test_user_meta'][8] );
        } finally {
            $_GET = $previous_get;
            if ( null === $previous_uri ) { unset( $_SERVER['REQUEST_URI'] ); } else { $_SERVER['REQUEST_URI'] = $previous_uri; }
            $GLOBALS['asn_test_redirect'] = $previous_redirect;
            if ( null === $previous_url ) { unset( $GLOBALS['asn_test_user_meta'][8]['_asn_free_onboarding_redirect_url'] ); } else { $GLOBALS['asn_test_user_meta'][8]['_asn_free_onboarding_redirect_url'] = $previous_url; }
            if ( null === $previous_expires ) { unset( $GLOBALS['asn_test_user_meta'][8]['_asn_free_onboarding_redirect_expires'] ); } else { $GLOBALS['asn_test_user_meta'][8]['_asn_free_onboarding_redirect_expires'] = $previous_expires; }
        }
    }

    public function test_registration_saves_legacy_birth_meta_for_tunapp_guard(): void {
        $previous_dob_date = $GLOBALS['asn_test_user_meta'][8]['dob_date'] ?? null;
        $previous_dob = $GLOBALS['asn_test_user_meta'][8]['dob'] ?? null;

        try {
            Registration::save_legacy_birth_meta( 8, '1990-05-10', 36 );

            $this->assertSame( '1990-05-10', $GLOBALS['asn_test_user_meta'][8]['dob_date'] );
            $this->assertSame( 36, $GLOBALS['asn_test_user_meta'][8]['dob'] );
        } finally {
            if ( null === $previous_dob_date ) {
                unset( $GLOBALS['asn_test_user_meta'][8]['dob_date'] );
            } else {
                $GLOBALS['asn_test_user_meta'][8]['dob_date'] = $previous_dob_date;
            }

            if ( null === $previous_dob ) {
                unset( $GLOBALS['asn_test_user_meta'][8]['dob'] );
            } else {
                $GLOBALS['asn_test_user_meta'][8]['dob'] = $previous_dob;
            }
        }
    }

    public function test_paid_signup_uses_existing_subscription_checkout_product(): void {
        $this->assertSame(
            'https://example.test/checkout/?add-to-cart=152&quantity=1',
            Registration_Shortcode::default_paid_checkout_url()
        );
    }

    public function test_paid_signup_auto_selects_the_only_available_variation(): void {
        $GLOBALS['asn_test_wc_products'][152] = new ASN_Test_WC_Product(
            152,
            'variable-subscription',
            array( 315 ),
            'https://example.test/product/unlimited-text-voice-and-video-chat/'
        );
        $GLOBALS['asn_test_wc_products'][315] = new ASN_Test_WC_Product(
            315,
            'subscription_variation',
            array(),
            '',
            true,
            true,
            array( 'attribute_billing_period' => 'month' )
        );

        $this->assertSame(
            'https://example.test/checkout/?add-to-cart=152&variation_id=315&quantity=1&attribute_billing_period=month',
            Registration_Shortcode::default_paid_checkout_url()
        );
    }

    public function test_paid_signup_prefers_monthly_when_monthly_and_annual_are_available(): void {
        $GLOBALS['asn_test_wc_products'][152] = new ASN_Test_WC_Product(
            152,
            'variable-subscription',
            array( 315, 316 ),
            'https://example.test/product/unlimited-text-voice-and-video-chat/'
        );
        $GLOBALS['asn_test_wc_products'][315] = new ASN_Test_WC_Product(
            315,
            'subscription_variation',
            array(),
            '',
            true,
            true,
            array( 'attribute_please-select' => 'Monthly' )
        );
        $GLOBALS['asn_test_wc_products'][316] = new ASN_Test_WC_Product(
            316,
            'subscription_variation',
            array(),
            '',
            true,
            true,
            array( 'attribute_please-select' => 'Annually' )
        );

        $this->assertSame(
            'https://example.test/checkout/?add-to-cart=152&variation_id=315&quantity=1&attribute_please-select=Monthly',
            Registration_Shortcode::default_paid_checkout_url()
        );
    }

    public function test_paid_signup_uses_product_page_when_multiple_variations_are_ambiguous(): void {
        $GLOBALS['asn_test_wc_products'][152] = new ASN_Test_WC_Product(
            152,
            'variable-subscription',
            array( 315, 316 ),
            'https://example.test/product/unlimited-text-voice-and-video-chat/'
        );
        $GLOBALS['asn_test_wc_products'][315] = new ASN_Test_WC_Product(
            315,
            'subscription_variation',
            array(),
            '',
            true,
            true,
            array( 'attribute_plan' => 'Standard' )
        );
        $GLOBALS['asn_test_wc_products'][316] = new ASN_Test_WC_Product(
            316,
            'subscription_variation',
            array(),
            '',
            true,
            true,
            array( 'attribute_plan' => 'Premium' )
        );

        $this->assertSame(
            'https://example.test/product/unlimited-text-voice-and-video-chat/',
            Registration_Shortcode::default_paid_checkout_url()
        );
    }

    public function test_pmpro_helper_assigns_free_level(): void {
        $previous = $GLOBALS['asn_test_pmpro_levels'][8] ?? null;
        unset( $GLOBALS['asn_test_pmpro_levels'][8] );

        try {
            $this->assertTrue( PMPro_Integration::assign_level( 1, 8 ) );
            $this->assertSame( 1, $GLOBALS['asn_test_pmpro_levels'][8] );
        } finally {
            if ( null === $previous ) {
                unset( $GLOBALS['asn_test_pmpro_levels'][8] );
            } else {
                $GLOBALS['asn_test_pmpro_levels'][8] = $previous;
            }
        }
    }
}
