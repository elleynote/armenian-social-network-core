<?php
namespace ASN\Core\Registration;

use ASN\Core\Memberships;
use ASN\Core\Profiles\Legacy_Profile_Contract;

defined( 'ABSPATH' ) || exit;

final class Registration_Shortcode {
    private const PAID_PRODUCT_ID = 152;

    private $registered = false;

    public function register(): void {
        if ( $this->registered ) {
            return;
        }

        $this->registered = true;
        add_shortcode( 'asn_register', array( $this, 'render' ) );
    }

    public function render( array $atts = array() ): string {
        $atts = shortcode_atts(
            array(
                'register_url' => site_url( '/asn-register-test/' ),
                'explore_url'  => site_url( '/asn-explore-test/' ),
                'paid_url'     => self::default_paid_checkout_url(),
            ),
            $atts,
            'asn_register'
        );

        $register_url = esc_url_raw( (string) $atts['register_url'] );
        $explore_url = esc_url_raw( (string) $atts['explore_url'] );
        $paid_url = esc_url_raw( (string) $atts['paid_url'] );

        wp_enqueue_style( 'asn-core', ASN_CORE_URL . 'public/css/asn-core.css', array(), ASN_CORE_VERSION );

        $user_id = (int) get_current_user_id();
        if ( $user_id > 0 && Memberships::can_text_chat( $user_id ) ) {
            return '<div class="asn-registration"><div class="asn-registration__card"><h2>Membership active</h2><p>Your ASN membership is active.</p><a class="asn-button" href="' . esc_url( $explore_url ) . '">Continue to Explore</a></div></div>';
        }

        $step = isset( $_GET['asn_step'] ) ? sanitize_key( wp_unslash( $_GET['asn_step'] ) ) : '';
        $error = isset( $_GET['asn_register_error'] ) ? sanitize_key( wp_unslash( $_GET['asn_register_error'] ) ) : '';

        ob_start();
        echo '<div class="asn-registration">';
        if ( '' !== $error ) {
            echo '<div class="asn-registration__notice">' . esc_html( self::error_message( $error ) ) . '</div>';
        }

        if ( $user_id <= 0 ) {
            self::render_account_form( $register_url );
        } elseif ( 'plan' === $step ) {
            self::render_plan_form( $register_url, $explore_url, $paid_url );
        } else {
            self::render_profile_form( $register_url );
        }

        echo '</div>';
        return (string) ob_get_clean();
    }

    public static function default_paid_checkout_url(): string {
        $fallback = site_url( '/checkout/?add-to-cart=' . self::PAID_PRODUCT_ID . '&quantity=1' );

        if ( ! function_exists( 'wc_get_product' ) ) {
            return $fallback;
        }

        $product = wc_get_product( self::PAID_PRODUCT_ID );
        if ( ! is_object( $product ) || ! is_callable( array( $product, 'is_type' ) ) ) {
            return $fallback;
        }

        if ( ! $product->is_type( array( 'variable', 'variable-subscription' ) ) ) {
            return $fallback;
        }

        $product_url = is_callable( array( $product, 'get_permalink' ) )
            ? (string) $product->get_permalink()
            : site_url( '/?p=' . self::PAID_PRODUCT_ID );

        if ( ! is_callable( array( $product, 'get_children' ) ) ) {
            return $product_url;
        }

        $variations = array();
        foreach ( (array) $product->get_children() as $variation_id ) {
            $variation_id = absint( $variation_id );
            if ( $variation_id <= 0 ) {
                continue;
            }

            $variation = wc_get_product( $variation_id );
            if ( ! is_object( $variation ) ) {
                continue;
            }

            if ( is_callable( array( $variation, 'is_purchasable' ) ) && ! $variation->is_purchasable() ) {
                continue;
            }

            if ( is_callable( array( $variation, 'is_in_stock' ) ) && ! $variation->is_in_stock() ) {
                continue;
            }

            $variations[] = $variation;
        }

        $variation = self::select_paid_variation( $variations );
        if ( null === $variation ) {
            return $product_url;
        }
        $variation_id = is_callable( array( $variation, 'get_id' ) ) ? (int) $variation->get_id() : 0;
        if ( $variation_id <= 0 ) {
            return $product_url;
        }

        $attributes = is_callable( array( $variation, 'get_variation_attributes' ) )
            ? (array) $variation->get_variation_attributes()
            : array();

        foreach ( $attributes as $attribute_value ) {
            if ( '' === (string) $attribute_value ) {
                return $product_url;
            }
        }

        $checkout_url = function_exists( 'wc_get_checkout_url' )
            ? (string) wc_get_checkout_url()
            : site_url( '/checkout/' );

        $checkout_url = add_query_arg( 'add-to-cart', self::PAID_PRODUCT_ID, $checkout_url );
        $checkout_url = add_query_arg( 'variation_id', $variation_id, $checkout_url );
        $checkout_url = add_query_arg( 'quantity', 1, $checkout_url );

        foreach ( $attributes as $attribute_name => $attribute_value ) {
            $checkout_url = add_query_arg(
                sanitize_key( (string) $attribute_name ),
                (string) $attribute_value,
                $checkout_url
            );
        }

        return $checkout_url;
    }

    public static function select_paid_variation( array $variations ) {
        if ( 1 === count( $variations ) ) {
            return $variations[0];
        }

        $monthly = array();
        foreach ( $variations as $variation ) {
            if ( ! is_object( $variation ) || ! is_callable( array( $variation, 'get_variation_attributes' ) ) ) {
                continue;
            }

            foreach ( (array) $variation->get_variation_attributes() as $attribute_value ) {
                $normalized = strtolower( trim( (string) $attribute_value ) );
                if ( in_array( $normalized, array( 'month', 'monthly' ), true ) ) {
                    $monthly[] = $variation;
                    break;
                }
            }
        }

        return 1 === count( $monthly ) ? $monthly[0] : null;
    }

    private static function render_account_form( string $register_url ): void {
        echo '<div class="asn-registration__card">';
        echo '<h2>Let&#8217;s get started</h2>';
        echo '<form class="asn-registration__form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="asn_register_account">';
        echo '<input type="hidden" name="return_url" value="' . esc_attr( $register_url ) . '">';
        wp_nonce_field( 'asn_register_account', 'asn_registration_nonce' );

        self::field( 'email', 'Email address', 'email', true );
        self::field( 'first_name', 'First name', 'text', true );
        self::field( 'username', 'Username', 'text', true );
        self::field( 'password', 'Choose a password', 'password', true );

        echo '<label class="asn-registration__check"><input type="checkbox" name="terms" value="1" required> <span>I agree to the Terms of Service and Privacy Policy.</span></label>';
        echo '<button class="asn-button asn-registration__submit" type="submit">Next Step</button>';
        echo '</form>';
        echo '<p class="asn-registration__login">Already have an account? <a href="' . esc_url( site_url( '/login/' ) ) . '">Log in</a></p>';
        echo '</div>';
    }

    private static function render_profile_form( string $register_url ): void {
        echo '<div class="asn-registration__card">';
        echo '<h2>Great! Now, introduce yourself</h2>';
        echo '<form class="asn-registration__form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="asn_register_profile">';
        echo '<input type="hidden" name="return_url" value="' . esc_attr( $register_url ) . '">';
        wp_nonce_field( 'asn_register_profile', 'asn_registration_nonce' );

        self::country_field();
        self::field( 'birth_date', 'Birth date', 'date', true );

        echo '<label class="asn-field"><span class="asn-field__label">Gender</span><select class="asn-field__input" name="gender" required>';
        echo '<option value="">Select gender</option>';
        foreach ( Legacy_Profile_Contract::gender_options() as $option ) {
            echo '<option value="' . esc_attr( $option ) . '">' . esc_html( $option ) . '</option>';
        }
        echo '</select></label>';

        self::field( 'job_title', 'Job title', 'text', false );

        echo '<label class="asn-field"><span class="asn-field__label">Spoken proficiency</span><select class="asn-field__input" name="spoken_proficiency" required>';
        echo '<option value="">Select proficiency</option>';
        foreach ( Legacy_Profile_Contract::spoken_proficiency_options() as $option ) {
            echo '<option value="' . esc_attr( $option ) . '">' . esc_html( $option ) . '</option>';
        }
        echo '</select></label>';

        echo '<p class="asn-registration__hint">You can add or change your profile picture after registration from My Profile.</p>';
        echo '<button class="asn-button asn-registration__submit" type="submit">Create Profile</button>';
        echo '</form>';
        echo '</div>';
    }

    private static function render_plan_form( string $register_url, string $explore_url, string $paid_url ): void {
        echo '<div class="asn-registration__card asn-registration__card--wide">';
        echo '<h2>Choose a plan</h2>';
        echo '<div class="asn-registration__plans">';

        echo '<div class="asn-registration__plan">';
        echo '<h3>Level 1 - Free</h3>';
        echo '<p>Text and live voice chat.</p><p>No video chat.</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="asn_choose_free_plan">';
        echo '<input type="hidden" name="return_url" value="' . esc_attr( $register_url ) . '">';
        echo '<input type="hidden" name="explore_url" value="' . esc_attr( $explore_url ) . '">';
        wp_nonce_field( 'asn_choose_free_plan', 'asn_registration_nonce' );
        echo '<button class="asn-button asn-registration__submit" type="submit">Continue with Free</button>';
        echo '</form>';
        echo '</div>';

        echo '<div class="asn-registration__plan">';
        echo '<h3>Level 2 - Paid</h3>';
        echo '<p>Everything in Level 1 plus live video chat.</p><p>Paid membership continues through the existing secure WooCommerce subscription flow.</p>';
        echo '<a class="asn-button asn-registration__submit" href="' . esc_url( $paid_url ) . '">Choose Paid</a>';
        echo '</div>';

        echo '</div>';
        echo '</div>';
    }

    private static function country_field(): void {
        $countries = array();
        if ( function_exists( 'WC' ) ) {
            $woocommerce = WC();
            if ( is_object( $woocommerce ) && isset( $woocommerce->countries ) && is_object( $woocommerce->countries ) && is_callable( array( $woocommerce->countries, 'get_countries' ) ) ) {
                $countries = (array) $woocommerce->countries->get_countries();
            }
        }

        if ( empty( $countries ) ) {
            self::field( 'country', 'Country', 'text', true );
            return;
        }

        echo '<label class="asn-field"><span class="asn-field__label">Country</span><select class="asn-field__input" name="country" required>';
        echo '<option value="">Select country</option>';
        foreach ( $countries as $country ) {
            echo '<option value="' . esc_attr( (string) $country ) . '">' . esc_html( (string) $country ) . '</option>';
        }
        echo '</select></label>';
    }

    private static function field( string $name, string $label, string $type, bool $required ): void {
        echo '<label class="asn-field"><span class="asn-field__label">' . esc_html( $label ) . '</span><input class="asn-field__input" type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '"' . ( $required ? ' required' : '' ) . '></label>';
    }

    private static function error_message( string $error ): string {
        $messages = array(
            'security'   => 'Please refresh the page and try again.',
            'terms'      => 'Please accept the Terms of Service and Privacy Policy.',
            'email'      => 'Please enter a valid email address.',
            'required'   => 'Please complete all required fields. Passwords must be at least 8 characters.',
            'exists'     => 'That email address or username is already registered.',
            'create'     => 'We could not create the account. Please try again.',
            'login'      => 'Please log in to continue.',
            'profile'    => 'Please check your profile details and try again.',
            'membership' => 'We could not activate the free membership. Please try again or contact support.',
        );

        return $messages[ $error ] ?? 'Something went wrong. Please try again.';
    }
}
