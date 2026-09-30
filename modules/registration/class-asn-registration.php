<?php
namespace ASN\Core\Registration;

use ASN\Core\Integrations\PMPro_Integration;
use ASN\Core\Memberships;
use ASN\Core\Profiles\Legacy_Profile_Contract;
use ASN\Core\Profiles\Profile_Index;
use ASN\Core\Profiles\Profile_Service;

defined( 'ABSPATH' ) || exit;

final class Registration {
    private const DEFAULT_REGISTER_URL = '/asn-register-test/';
    private const DEFAULT_EXPLORE_URL = '/asn-explore-test/';

    public function register(): void {
        add_action( 'admin_post_nopriv_asn_register_account', array( $this, 'handle_account' ) );
        add_action( 'admin_post_asn_register_profile', array( $this, 'handle_profile' ) );
        add_action( 'admin_post_asn_choose_free_plan', array( $this, 'handle_free_plan' ) );
    }

    public function handle_account(): void {
        $return_url = $this->posted_url( 'return_url', site_url( self::DEFAULT_REGISTER_URL ) );

        $nonce = isset( $_POST['asn_registration_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['asn_registration_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'asn_register_account' ) ) {
            $this->redirect_with_error( $return_url, 'security' );
            return;
        }

        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
        $username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ), true ) : '';
        $password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
        $terms = isset( $_POST['terms'] ) && '1' === (string) wp_unslash( $_POST['terms'] );

        if ( ! $terms ) {
            $this->redirect_with_error( $return_url, 'terms' );
            return;
        }

        if ( '' === $email || ! is_email( $email ) ) {
            $this->redirect_with_error( $return_url, 'email' );
            return;
        }

        if ( '' === $first_name || '' === $username || strlen( $password ) < 8 ) {
            $this->redirect_with_error( $return_url, 'required' );
            return;
        }

        if ( email_exists( $email ) || username_exists( $username ) ) {
            $this->redirect_with_error( $return_url, 'exists' );
            return;
        }

        $user_id = wp_insert_user(
            array(
                'user_login'   => $username,
                'user_pass'    => $password,
                'user_email'   => $email,
                'first_name'   => $first_name,
                'display_name' => $first_name,
            )
        );

        if ( is_wp_error( $user_id ) || (int) $user_id <= 0 ) {
            $this->redirect_with_error( $return_url, 'create' );
            return;
        }

        $user_id = (int) $user_id;
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id, true );

        if ( function_exists( 'wp_new_user_notification' ) ) {
            wp_new_user_notification( $user_id, null, 'both' );
        }

        $this->redirect( add_query_arg( 'asn_step', 'profile', $return_url ) );
    }

    public function handle_profile(): void {
        $return_url = $this->posted_url( 'return_url', site_url( self::DEFAULT_REGISTER_URL ) );
        $user_id = (int) get_current_user_id();

        if ( ! is_user_logged_in() || $user_id <= 0 ) {
            $this->redirect_with_error( $return_url, 'login' );
            return;
        }

        $nonce = isset( $_POST['asn_registration_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['asn_registration_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'asn_register_profile' ) ) {
            $this->redirect_with_error( $return_url, 'security' );
            return;
        }

        $country = isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '';
        $birth_date = isset( $_POST['birth_date'] ) ? sanitize_text_field( wp_unslash( $_POST['birth_date'] ) ) : '';
        $gender = isset( $_POST['gender'] ) ? sanitize_text_field( wp_unslash( $_POST['gender'] ) ) : '';
        $job_title = isset( $_POST['job_title'] ) ? sanitize_text_field( wp_unslash( $_POST['job_title'] ) ) : '';
        $spoken = isset( $_POST['spoken_proficiency'] ) ? sanitize_text_field( wp_unslash( $_POST['spoken_proficiency'] ) ) : '';

        $age = self::age_from_birth_date( $birth_date );
        if (
            '' === $country
            || null === $age
            || ! in_array( $gender, Legacy_Profile_Contract::gender_options(), true )
            || ! in_array( $spoken, Legacy_Profile_Contract::spoken_proficiency_options(), true )
        ) {
            $this->redirect_with_error( add_query_arg( 'asn_step', 'profile', $return_url ), 'profile' );
            return;
        }

        $result = Profile_Service::update_own_profile(
            $user_id,
            $user_id,
            array(
                'country'            => $country,
                'age'                => $age,
                'gender'             => $gender,
                'job_title'          => $job_title,
                'spoken_proficiency' => $spoken,
            )
        );

        if ( ! $result['success'] ) {
            $this->redirect_with_error( add_query_arg( 'asn_step', 'profile', $return_url ), 'profile' );
            return;
        }

        update_user_meta( $user_id, 'birth_date', $birth_date );

        $this->redirect( add_query_arg( 'asn_step', 'plan', $return_url ) );
    }

    public function handle_free_plan(): void {
        $return_url = $this->posted_url( 'return_url', site_url( self::DEFAULT_REGISTER_URL ) );
        $explore_url = $this->posted_url( 'explore_url', site_url( self::DEFAULT_EXPLORE_URL ) );
        $user_id = (int) get_current_user_id();

        if ( ! is_user_logged_in() || $user_id <= 0 ) {
            $this->redirect_with_error( $return_url, 'login' );
            return;
        }

        $nonce = isset( $_POST['asn_registration_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['asn_registration_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'asn_choose_free_plan' ) ) {
            $this->redirect_with_error( add_query_arg( 'asn_step', 'plan', $return_url ), 'security' );
            return;
        }

        if ( ! PMPro_Integration::assign_level( Memberships::FREE_LEVEL_ID, $user_id ) ) {
            $this->redirect_with_error( add_query_arg( 'asn_step', 'plan', $return_url ), 'membership' );
            return;
        }

        Profile_Index::sync_user( $user_id );
        $this->redirect( $explore_url );
    }

    public static function age_from_birth_date( string $birth_date ): ?int {
        $birth_date = trim( $birth_date );
        $date = \DateTimeImmutable::createFromFormat( '!Y-m-d', $birth_date );
        $errors = \DateTimeImmutable::getLastErrors();

        if ( false === $date || ( is_array( $errors ) && ( $errors['warning_count'] > 0 || $errors['error_count'] > 0 ) ) ) {
            return null;
        }

        $today = new \DateTimeImmutable( 'today' );
        if ( $date > $today ) {
            return null;
        }

        $age = (int) $date->diff( $today )->y;
        return ( $age >= 1 && $age <= 120 ) ? $age : null;
    }

    private function posted_url( string $key, string $fallback ): string {
        $value = isset( $_POST[ $key ] ) ? esc_url_raw( wp_unslash( $_POST[ $key ] ) ) : '';
        if ( '' === $value ) {
            return $fallback;
        }

        return wp_validate_redirect( $value, $fallback );
    }

    private function redirect_with_error( string $url, string $error ): void {
        $this->redirect( add_query_arg( 'asn_register_error', $error, $url ) );
    }

    private function redirect( string $url ): void {
        wp_safe_redirect( $url );
        if ( ! defined( 'ASN_CORE_TESTING' ) || ! ASN_CORE_TESTING ) {
            exit;
        }
    }
}
