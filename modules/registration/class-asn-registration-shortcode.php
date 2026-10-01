<?php
namespace ASN\Core\Registration;

use ASN\Core\Memberships;
use ASN\Core\Profiles\Legacy_Profile_Contract;
use ASN\Core\Profiles\Profile_Fields;
use ASN\Core\Profiles\Profile_Photo;
use ASN\Core\Profiles\Profile_Service;

defined( 'ABSPATH' ) || exit;

final class Registration_Shortcode {
    private const PAID_PRODUCT_ID = 152;

    private const ONBOARDING_STEPS = array(
        'profile',
        'personality',
        'morning',
        'planning',
        'story',
        'sharing',
        'photos',
        'plan',
        'complete',
    );

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
        wp_enqueue_script( 'asn-registration', ASN_CORE_URL . 'public/js/asn-registration.js', array(), ASN_CORE_VERSION, true );

        $user_id = (int) get_current_user_id();
        $step = isset( $_GET['asn_step'] ) ? sanitize_key( wp_unslash( $_GET['asn_step'] ) ) : '';
        $error = isset( $_GET['asn_register_error'] ) ? sanitize_key( wp_unslash( $_GET['asn_register_error'] ) ) : '';

        if ( $user_id > 0 && Memberships::can_text_chat( $user_id ) && 'complete' !== $step ) {
            return '<div class="asn-registration"><div class="asn-registration__card"><h2>Membership active</h2><p>Your ASN membership is active.</p><a class="asn-button" href="' . esc_url( $explore_url ) . '">Continue to Explore</a></div></div>';
        }

        ob_start();
        echo '<div class="asn-registration" data-asn-registration-v2>';
        if ( '' !== $error ) {
            echo '<div class="asn-registration__notice">' . esc_html( self::error_message( $error ) ) . '</div>';
        }

        if ( $user_id <= 0 ) {
            self::render_account_form( $register_url );
        } else {
            if ( ! in_array( $step, self::ONBOARDING_STEPS, true ) ) {
                $step = 'profile';
            }

            switch ( $step ) {
                case 'personality':
                    self::render_prompt_step(
                        $user_id,
                        $register_url,
                        'personality',
                        'Love it. Now tell us what makes you, you.',
                        'Give people a little glimpse into your world.',
                        30,
                        'Complete your profile.',
                        array(
                            'my_favorite_music_is' => 'My favourite music is…',
                            'i_get_way_too_excited_about' => 'I get way too excited about…',
                            'after_work_you_can_find_me' => 'After work, you can find me…',
                            'the_greatest_thing_about_where_i_live_is' => 'The greatest thing about where I live is…',
                        ),
                        'morning'
                    );
                    break;

                case 'morning':
                    self::render_prompt_step(
                        $user_id,
                        $register_url,
                        'morning',
                        'Nice. Now what gets you out of bed in the morning?',
                        'The interesting stuff people won’t learn from your bio.',
                        60,
                        'Almost completed.',
                        array(
                            'something_i_m_really_really_good_at_is' => "Something I'm really, really good at…",
                            'something_you_might_not_know_about_me_is' => 'Something you might not know about me is…',
                            'i_value_people_who' => 'I value people who…',
                        ),
                        'planning'
                    );
                    break;

                case 'planning':
                    self::render_prompt_step(
                        $user_id,
                        $register_url,
                        'planning',
                        'What are you planning next?',
                        'Big plans, little plans, we want to hear them all!',
                        60,
                        'Almost completed.',
                        array(
                            'my_dream_job_is' => 'My dream job is…',
                            'this_year_i_really_want_to' => 'This year I really want to…',
                            'a_lifelong_goal_of_mine_is_to' => 'A lifelong goal of mine is to…',
                            'my_dream_holiday_destination_is' => 'My dream holiday destination is…',
                            'one_way_i_d_like_to_change_the_world_is' => "One way I'd like to change the world is…",
                        ),
                        'story'
                    );
                    break;

                case 'story':
                    self::render_prompt_step(
                        $user_id,
                        $register_url,
                        'story',
                        'Now we want to know how you became you.',
                        'Share a little of what’s shaped the person you are today',
                        60,
                        'Almost completed.',
                        array(
                            'my_greatest_childhood_memory_is' => 'My greatest childhood memory is…',
                            'my_biggest_fear_is' => 'My biggest fear is…',
                            'the_best_piece_of_advice_i_ve_ever_received_is' => "The best piece of advice I've received is…",
                        ),
                        'sharing'
                    );
                    break;

                case 'sharing':
                    self::render_prompt_step(
                        $user_id,
                        $register_url,
                        'sharing',
                        'You’ve got something to share, so does everyone here.',
                        'What could you teach someone? And what could they teach you in return?',
                        90,
                        'A few final touches.',
                        array(
                            'i_m_currently_trying_to_learn' => "I'm currently trying to learn…",
                            'one_thing_i_could_help_teach_you_about_is' => 'One thing I could help teach you about is…',
                        ),
                        'photos'
                    );
                    break;

                case 'photos':
                    self::render_photos_step( $user_id, $register_url );
                    break;

                case 'plan':
                    self::render_plan_form( $register_url, $explore_url, $paid_url, $user_id );
                    break;

                case 'complete':
                    self::render_complete( $user_id, $explore_url );
                    break;

                case 'profile':
                default:
                    self::render_profile_form( $register_url, $user_id );
                    break;
            }
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

    private static function render_profile_form( string $register_url, int $user_id ): void {
        $profile = Profile_Service::find( $user_id, $user_id ) ?: array();
        $user = get_userdata( $user_id );

        self::screen_open(
            'Introduce yourself.',
            "Let's start with the basics.",
            0,
            'Complete your profile.',
            $user_id
        );

        echo '<form class="asn-registration-v2__form asn-registration-v2__form--basics" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="asn_register_profile">';
        echo '<input type="hidden" name="return_url" value="' . esc_attr( $register_url ) . '">';
        wp_nonce_field( 'asn_register_profile', 'asn_registration_nonce' );

        echo '<div class="asn-registration-v2__panel asn-registration-v2__panel--two">';
        self::field( 'first_name', 'First Name', 'text', true, (string) ( $profile['first_name'] ?? '' ) );
        self::field( 'last_name', 'Last Name', 'text', true, (string) ( $profile['last_name'] ?? '' ) );
        self::field( 'display_name', 'Display name publicly as', 'text', true, $user && isset( $user->display_name ) ? (string) $user->display_name : '' );
        self::field( 'email', 'Email', 'email', true, $user && isset( $user->user_email ) ? (string) $user->user_email : '' );
        echo '</div>';

        echo '<div class="asn-registration-v2__panel">';
        self::country_field( (string) ( $profile['country'] ?? '' ) );
        self::field( 'age', 'Age', 'number', true, (string) ( $profile['age'] ?? '' ), '1', '120' );

        echo '<label class="asn-field"><span class="asn-field__label">Gender</span><select class="asn-field__input" name="gender" required>';
        echo '<option value="">Select gender</option>';
        foreach ( Legacy_Profile_Contract::gender_options() as $option ) {
            echo '<option value="' . esc_attr( $option ) . '"' . selected( (string) ( $profile['gender'] ?? '' ), $option, false ) . '>' . esc_html( $option ) . '</option>';
        }
        echo '</select></label>';

        self::field( 'job_title', 'Job Title', 'text', false, (string) ( $profile['job_title'] ?? '' ) );

        echo '<label class="asn-field"><span class="asn-field__label">Spoken Proficiency</span><select class="asn-field__input" name="spoken_proficiency" required>';
        echo '<option value="">Select proficiency</option>';
        foreach ( Legacy_Profile_Contract::spoken_proficiency_options() as $option ) {
            echo '<option value="' . esc_attr( $option ) . '"' . selected( (string) ( $profile['spoken_proficiency'] ?? '' ), $option, false ) . '>' . esc_html( $option ) . '</option>';
        }
        echo '</select></label>';
        echo '</div>';

        echo '<div class="asn-registration-v2__next-row"><button class="asn-button asn-registration-v2__next" type="submit">Next &#8594;</button></div>';
        echo '</form>';

        self::screen_close( 0, 'Complete your profile.', $user_id );
    }

    private static function render_prompt_step(
        int $user_id,
        string $register_url,
        string $step,
        string $title,
        string $subtitle,
        int $progress,
        string $status,
        array $fields,
        string $next_step
    ): void {
        $profile = Profile_Service::find( $user_id, $user_id ) ?: array();

        self::screen_open( $title, $subtitle, $progress, $status, $user_id );

        echo '<form class="asn-registration-v2__form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="asn_register_onboarding_step">';
        echo '<input type="hidden" name="return_url" value="' . esc_attr( $register_url ) . '">';
        echo '<input type="hidden" name="step" value="' . esc_attr( $step ) . '">';
        echo '<input type="hidden" name="next_step" value="' . esc_attr( $next_step ) . '">';
        wp_nonce_field( 'asn_register_onboarding_step', 'asn_registration_nonce' );

        echo '<div class="asn-registration-v2__panel asn-registration-v2__prompt-panel">';
        foreach ( $fields as $key => $label ) {
            echo '<label class="asn-field asn-registration-v2__prompt-field">';
            echo '<span class="asn-field__label">' . esc_html( $label ) . '</span>';
            echo '<textarea class="asn-field__input asn-registration-v2__textarea" name="asn_profile[' . esc_attr( $key ) . ']" maxlength="1000">' . esc_html( (string) ( $profile[ $key ] ?? '' ) ) . '</textarea>';
            echo '</label>';
        }
        echo '</div>';

        echo '<div class="asn-registration-v2__next-row"><button class="asn-button asn-registration-v2__next" type="submit">Next &#8594;</button></div>';
        echo '</form>';

        self::screen_close( $progress, $status, $user_id );
    }

    private static function render_photos_step( int $user_id, string $register_url ): void {
        $profile = Profile_Service::find( $user_id, $user_id ) ?: array();
        $photos = array(
            'a_photo_of_me_doing_what_i_love_most' => 'A photo of me doing what I love most.',
            'a_photo_of_me_being_me' => 'A photo of me being me.',
            'a_photo_of_something_i_ve_done_recently' => "A photo of something I've done recently.",
            'a_photo_of_the_good_old_days' => 'A photo of the good old days.',
        );

        self::screen_open(
            'Nearly there! Show us a glimpse of your world.',
            'Add a few photos that say more about you than words',
            90,
            'A few final touches.',
            $user_id
        );

        echo '<form class="asn-registration-v2__form" method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="asn_register_photos">';
        echo '<input type="hidden" name="return_url" value="' . esc_attr( $register_url ) . '">';
        wp_nonce_field( 'asn_register_photos', 'asn_registration_nonce' );

        echo '<div class="asn-registration-v2__photo-grid">';
        foreach ( $photos as $key => $label ) {
            $existing = (string) ( $profile['prompt_images'][ $key ] ?? '' );
            echo '<label class="asn-registration-v2__photo-card">';
            echo '<span class="asn-registration-v2__photo-title">' . esc_html( $label ) . '</span>';
            if ( '' !== $existing ) {
                echo '<img class="asn-registration-v2__photo-preview" src="' . esc_url( $existing ) . '" alt="">';
            } else {
                echo '<span class="asn-registration-v2__photo-plus" aria-hidden="true">+</span>';
            }
            echo '<span class="asn-registration-v2__photo-add">Add a photo</span>';
            echo '<input class="asn-registration-v2__photo-input" type="file" name="asn_photos[' . esc_attr( $key ) . ']" accept="image/jpeg,image/png,image/webp,image/gif">';
            echo '</label>';
        }
        echo '</div>';

        echo '<div class="asn-registration-v2__next-row"><button class="asn-button asn-registration-v2__next" type="submit">Save Profile</button></div>';
        echo '</form>';

        self::screen_close( 90, 'A few final touches.', $user_id );
    }

    private static function render_plan_form( string $register_url, string $explore_url, string $paid_url, int $user_id ): void {
        self::screen_open(
            'Choose your membership.',
            'Pick the option that works for you.',
            90,
            'A few final touches.',
            $user_id
        );

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

        self::screen_close( 90, 'A few final touches.', $user_id );
    }

    private static function render_complete( int $user_id, string $explore_url ): void {
        delete_user_meta( $user_id, '_asn_free_onboarding_redirect_url' );
        delete_user_meta( $user_id, '_asn_free_onboarding_redirect_expires' );
        update_user_meta( $user_id, '_asn_onboarding_step', 'complete' );

        self::screen_open(
            'All done!',
            "Let's introduce you to the community.",
            100,
            'Profile completed.',
            $user_id
        );
        echo '<div class="asn-registration-v2__complete" data-asn-complete-redirect="' . esc_attr( $explore_url ) . '">';
        echo '<a class="asn-registration-v2__complete-fallback" href="' . esc_url( $explore_url ) . '">Continue to Explore</a>';
        echo '</div>';
        self::screen_close( 100, 'Profile completed.', $user_id );
    }

    private static function screen_open( string $title, string $subtitle, int $progress, string $status, int $user_id ): void {
        echo '<div class="asn-registration-v2">';
        echo '<main class="asn-registration-v2__main">';
        echo '<header class="asn-registration-v2__heading">';
        echo '<h1>' . esc_html( $title ) . '</h1>';
        echo '<p>' . esc_html( $subtitle ) . '</p>';
        echo '</header>';
    }

    private static function screen_close( int $progress, string $status, int $user_id ): void {
        echo '</main>';
        self::render_sidebar( $progress, $status, $user_id );
        echo '</div>';
    }

    public static function render_sidebar( int $progress, string $status, int $user_id ): void {
        $progress = max( 0, min( 100, $progress ) );
        $profile = Profile_Service::find( $user_id, $user_id ) ?: array();

        $display_name = trim( (string) ( $profile['display_name'] ?? '' ) );
        $age = trim( (string) ( $profile['age'] ?? '' ) );
        $username = trim( (string) ( $profile['username'] ?? '' ) );
        $job = trim( (string) ( $profile['job_title'] ?? '' ) );
        $country = trim( (string) ( $profile['country'] ?? '' ) );
        $spoken = trim( (string) ( $profile['spoken_proficiency'] ?? '' ) );
        $speaker = '';

        if ( '' !== $spoken ) {
            $parts = array_map( 'trim', explode( '-', $spoken, 2 ) );
            $speaker = trim( (string) ( $parts[1] ?? $parts[0] ) );
            if ( '' !== $speaker ) {
                $speaker .= ' Speaker';
            }
        }

        $name_line = $display_name;
        if ( '' !== $age ) {
            $name_line .= ', ' . $age;
        }

        echo '<aside class="asn-registration-v2__sidebar">';
        echo '<section class="asn-registration-v2__sidebar-card asn-registration-v2__progress">';
        echo '<div class="asn-registration-v2__progress-title"><h2>Profile completion</h2><p>' . esc_html( $status ) . '</p></div>';
        echo '<div class="asn-registration-v2__progress-row"><div class="asn-registration-v2__progress-track"><span style="width:' . esc_attr( (string) $progress ) . '%"></span></div><strong>' . esc_html( (string) $progress ) . '%</strong></div>';

        echo '<div class="asn-registration-v2__profile-preview">';
        echo '<img class="asn-registration-v2__profile-photo" src="' . esc_url( Profile_Photo::url( $user_id ) ) . '" alt="">';
        if ( '' !== $name_line ) {
            echo '<strong class="asn-registration-v2__profile-name" data-asn-preview-name>' . esc_html( $name_line ) . '</strong>';
        }
        if ( '' !== $username ) {
            echo '<span class="asn-registration-v2__profile-username" data-asn-preview-username>@' . esc_html( $username ) . '</span>';
        }
        if ( '' !== $job ) {
            echo '<span data-asn-preview-job>' . esc_html( $job ) . '</span>';
        }
        if ( '' !== $country ) {
            echo '<span data-asn-preview-country>' . esc_html( $country ) . '</span>';
        }
        if ( '' !== $speaker ) {
            echo '<span data-asn-preview-speaker>' . esc_html( $speaker ) . '</span>';
        }

        echo '<div class="asn-registration-v2__profile-actions">';
        echo '<a class="asn-registration-v2__profile-button" href="' . esc_url( add_query_arg( 'member', $user_id, site_url( '/asn-profile-test/' ) ) ) . '">View Profile</a>';
        echo '<a class="asn-registration-v2__profile-chat" href="' . esc_url( site_url( '/messages/' ) ) . '" aria-label="Open messages">&#128172;</a>';
        echo '</div>';
        echo '</div>';
        echo '</section>';

        echo '<section class="asn-registration-v2__sidebar-card asn-registration-v2__help">';
        echo '<h2>Help people get to know you.</h2>';
        echo '<ul>';
        echo '<li>Share who you are, where you’re from and what connects you to the Armenian community.</li>';
        echo '<li>Find Armenians you have something in common with.</li>';
        echo '<li>Make better connections.</li>';
        echo '<li>Make it easier to connect through shared interests, locations, backgrounds and experiences.</li>';
        echo '</ul>';
        echo '<p>The more we know about each other, the easier it is to build friendships, professional connections and community across the diaspora.</p>';
        echo '</section>';
        echo '</aside>';
    }

    private static function country_field( string $selected_country = '' ): void {
        $countries = array();
        if ( function_exists( 'WC' ) ) {
            $woocommerce = WC();
            if ( is_object( $woocommerce ) && isset( $woocommerce->countries ) && is_object( $woocommerce->countries ) && is_callable( array( $woocommerce->countries, 'get_countries' ) ) ) {
                $countries = (array) $woocommerce->countries->get_countries();
            }
        }

        if ( empty( $countries ) ) {
            self::field( 'country', 'Country', 'text', true, $selected_country );
            return;
        }

        echo '<label class="asn-field"><span class="asn-field__label">Country</span><select class="asn-field__input" name="country" required>';
        echo '<option value="">Select country</option>';
        foreach ( $countries as $country ) {
            echo '<option value="' . esc_attr( (string) $country ) . '"' . selected( $selected_country, (string) $country, false ) . '>' . esc_html( (string) $country ) . '</option>';
        }
        echo '</select></label>';
    }

    private static function field(
        string $name,
        string $label,
        string $type,
        bool $required,
        string $value = '',
        string $min = '',
        string $max = ''
    ): void {
        $attributes = '';
        if ( $required ) {
            $attributes .= ' required';
        }
        if ( '' !== $min ) {
            $attributes .= ' min="' . esc_attr( $min ) . '"';
        }
        if ( '' !== $max ) {
            $attributes .= ' max="' . esc_attr( $max ) . '"';
        }

        echo '<label class="asn-field"><span class="asn-field__label">' . esc_html( $label ) . '</span><input class="asn-field__input" type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"' . $attributes . '></label>';
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
            'upload'     => 'One or more photos could not be uploaded. Please try again.',
        );

        return $messages[ $error ] ?? 'Something went wrong. Please try again.';
    }
}
