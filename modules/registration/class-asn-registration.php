<?php
namespace ASN\Core\Registration;

use ASN\Core\Integrations\PMPro_Integration;
use ASN\Core\Memberships;
use ASN\Core\Profiles\Legacy_Profile_Contract;
use ASN\Core\Profiles\Profile_Index;
use ASN\Core\Profiles\Profile_Fields;
use ASN\Core\Profiles\Profile_Service;

defined( 'ABSPATH' ) || exit;

final class Registration {
    private const DEFAULT_REGISTER_URL = '/asn-register-test/';
    private const DEFAULT_EXPLORE_URL = '/asn-explore-test/';

    public function register(): void {
        add_action( 'admin_post_nopriv_asn_register_account', array( $this, 'handle_account' ) );
        add_action( 'admin_post_asn_register_profile', array( $this, 'handle_profile' ) );
        add_action( 'admin_post_asn_register_onboarding_step', array( $this, 'handle_onboarding_step' ) );
        add_action( 'admin_post_asn_register_photos', array( $this, 'handle_photos' ) );
        add_action( 'admin_post_asn_choose_free_plan', array( $this, 'handle_free_plan' ) );
        add_action( 'asn_send_new_user_notification', array( $this, 'send_new_user_notification' ), 10, 1 );
        add_action( 'template_redirect', array( $this, 'maybe_redirect_completed_free_signup' ), 1 );
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

        self::schedule_new_user_notification( $user_id );

        $this->redirect( add_query_arg( 'asn_step', 'profile', $return_url ) );
    }

    public static function schedule_new_user_notification( int $user_id ): bool {
        if ( $user_id <= 0 || ! function_exists( 'wp_schedule_single_event' ) ) {
            return false;
        }

        $hook = 'asn_send_new_user_notification';
        $args = array( $user_id );

        if ( function_exists( 'wp_next_scheduled' ) && wp_next_scheduled( $hook, $args ) ) {
            return true;
        }

        $scheduled = wp_schedule_single_event( time() + 5, $hook, $args, true );

        if ( false === $scheduled ) {
            return false;
        }

        return ! function_exists( 'is_wp_error' ) || ! is_wp_error( $scheduled );
    }

    public function send_new_user_notification( $user_id ): void {
        $user_id = (int) $user_id;

        if ( $user_id <= 0 || ! function_exists( 'wp_new_user_notification' ) ) {
            return;
        }

        wp_new_user_notification( $user_id, null, 'both' );
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

        $first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
        $last_name = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
        $display_name = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '';
        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $country = isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '';
        $age = isset( $_POST['age'] ) ? absint( wp_unslash( $_POST['age'] ) ) : 0;
        $gender = isset( $_POST['gender'] ) ? sanitize_text_field( wp_unslash( $_POST['gender'] ) ) : '';
        $job_title = isset( $_POST['job_title'] ) ? sanitize_text_field( wp_unslash( $_POST['job_title'] ) ) : '';
        $spoken = isset( $_POST['spoken_proficiency'] ) ? sanitize_text_field( wp_unslash( $_POST['spoken_proficiency'] ) ) : '';
        $im_here_for = isset( $_POST['im_here_for'] ) && is_array( $_POST['im_here_for'] )
            ? wp_unslash( $_POST['im_here_for'] )
            : array();

        if (
            '' === $first_name
            || '' === $last_name
            || '' === $display_name
            || '' === $email
            || ! is_email( $email )
            || '' === $country
            || $age < 1
            || $age > 120
            || ! in_array( $gender, Legacy_Profile_Contract::gender_options(), true )
            || ! in_array( $spoken, Legacy_Profile_Contract::spoken_proficiency_options(), true )
        ) {
            $this->redirect_with_error( add_query_arg( 'asn_step', 'profile', $return_url ), 'profile' );
            return;
        }

        $email_owner = email_exists( $email );
        if ( $email_owner && (int) $email_owner !== $user_id ) {
            $this->redirect_with_error( add_query_arg( 'asn_step', 'profile', $return_url ), 'email' );
            return;
        }

        $result = Profile_Service::update_own_profile(
            $user_id,
            $user_id,
            array(
                'first_name'         => $first_name,
                'last_name'          => $last_name,
                'country'            => $country,
                'age'                => $age,
                'gender'             => $gender,
                'job_title'          => $job_title,
                'spoken_proficiency' => $spoken,
                'im_here_for'         => $im_here_for,
            )
        );

        if ( ! $result['success'] ) {
            $this->redirect_with_error( add_query_arg( 'asn_step', 'profile', $return_url ), 'profile' );
            return;
        }

        $updated_user = wp_update_user(
            array(
                'ID'           => $user_id,
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'display_name' => $display_name,
                'user_email'   => $email,
            )
        );

        if ( is_wp_error( $updated_user ) ) {
            $this->redirect_with_error( add_query_arg( 'asn_step', 'profile', $return_url ), 'profile' );
            return;
        }

        self::save_legacy_age_meta( $user_id, $age );

        $this->redirect( add_query_arg( 'asn_step', 'prompts', $return_url ) );
    }

    public function handle_onboarding_step(): void {
        $return_url = $this->posted_url( 'return_url', site_url( self::DEFAULT_REGISTER_URL ) );
        $user_id = (int) get_current_user_id();

        if ( ! is_user_logged_in() || $user_id <= 0 ) {
            $this->redirect_with_error( $return_url, 'login' );
            return;
        }

        $nonce = isset( $_POST['asn_registration_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['asn_registration_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'asn_register_onboarding_step' ) ) {
            $this->redirect_with_error( $return_url, 'security' );
            return;
        }

        $step = isset( $_POST['step'] ) ? sanitize_key( wp_unslash( $_POST['step'] ) ) : '';
        $next_step = isset( $_POST['next_step'] ) ? sanitize_key( wp_unslash( $_POST['next_step'] ) ) : '';
        $allowed_transitions = array(
            'prompts'     => 'photos',
            'personality' => 'photos',
            'morning'     => 'photos',
            'planning'    => 'photos',
            'story'       => 'photos',
            'sharing'     => 'photos',
        );

        if ( ! isset( $allowed_transitions[ $step ] ) || $allowed_transitions[ $step ] !== $next_step ) {
            $this->redirect_with_error( add_query_arg( 'asn_step', 'profile', $return_url ), 'profile' );
            return;
        }

        $input = isset( $_POST['asn_profile'] ) && is_array( $_POST['asn_profile'] )
            ? wp_unslash( $_POST['asn_profile'] )
            : array();

        $allowed_keys = self::onboarding_fields_for_step( $step );
        $clean_input = array();

        foreach ( $allowed_keys as $key ) {
            $clean_input[ $key ] = isset( $input[ $key ] ) ? $input[ $key ] : '';
        }

        $result = Profile_Service::update_own_profile( $user_id, $user_id, $clean_input, false );
        if ( ! $result['success'] ) {
            $this->redirect_with_error( add_query_arg( 'asn_step', $step, $return_url ), 'profile' );
            return;
        }

        update_user_meta( $user_id, '_asn_onboarding_step', $next_step );
        $this->redirect( add_query_arg( 'asn_step', $next_step, $return_url ) );
    }

    public function handle_photos(): void {
        $return_url = $this->posted_url( 'return_url', site_url( self::DEFAULT_REGISTER_URL ) );
        $user_id = (int) get_current_user_id();

        if ( ! is_user_logged_in() || $user_id <= 0 ) {
            $this->redirect_with_error( $return_url, 'login' );
            return;
        }

        $nonce = isset( $_POST['asn_registration_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['asn_registration_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'asn_register_photos' ) ) {
            $this->redirect_with_error( add_query_arg( 'asn_step', 'photos', $return_url ), 'security' );
            return;
        }

        $allowed = array(
            'a_photo_of_me_doing_what_i_love_most',
            'a_photo_of_me_being_me',
            'a_photo_of_something_i_ve_done_recently',
            'a_photo_of_the_good_old_days',
        );

        $files = isset( $_FILES['asn_photos'] ) && is_array( $_FILES['asn_photos'] ) ? $_FILES['asn_photos'] : array();

        foreach ( $allowed as $key ) {
            $file = self::nested_upload( $files, $key );
            if ( empty( $file ) || empty( $file['name'] ) ) {
                continue;
            }

            $url = self::upload_profile_photo( $file, $user_id, $key );
            if ( '' === $url ) {
                $this->redirect_with_error( add_query_arg( 'asn_step', 'photos', $return_url ), 'upload' );
                return;
            }

            update_user_meta( $user_id, $key . '_image', $url );
        }

        if ( ! self::ensure_default_free_membership( $user_id ) ) {
            $this->redirect_with_error( add_query_arg( 'asn_step', 'photos', $return_url ), 'membership' );
            return;
        }

        update_user_meta( $user_id, '_asn_onboarding_step', 'plan' );
        $this->redirect( add_query_arg( 'asn_step', 'plan', $return_url ) );
    }

    public static function onboarding_fields_for_step( string $step ): array {
        $fields = Registration_Shortcode::all_prompt_fields();

        if ( in_array( $step, array( 'prompts', 'personality', 'morning', 'planning', 'story', 'sharing' ), true ) ) {
            return $fields;
        }

        return array();
    }

    private static function nested_upload( array $files, string $key ): array {
        if ( ! isset( $files['name'][ $key ] ) ) {
            return array();
        }

        return array(
            'name'     => $files['name'][ $key ] ?? '',
            'type'     => $files['type'][ $key ] ?? '',
            'tmp_name' => $files['tmp_name'][ $key ] ?? '',
            'error'    => $files['error'][ $key ] ?? UPLOAD_ERR_NO_FILE,
            'size'     => $files['size'][ $key ] ?? 0,
        );
    }

    public static function upload_profile_photo( array $file, int $user_id, string $key ): string {
        if ( $user_id <= 0 || empty( $file['name'] ) || UPLOAD_ERR_OK !== (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
            return '';
        }

        $allowed_keys = Profile_Fields::photo_prompt_keys();
        if ( ! in_array( $key, $allowed_keys, true ) ) {
            return '';
        }

        $GLOBALS['asn_registration_upload_file'] = $file;
        $_FILES['asn_registration_upload_file'] = $file;

        if ( ! function_exists( 'media_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }

        $attachment_id = media_handle_upload( 'asn_registration_upload_file', 0, array(), array( 'test_form' => false ) );
        unset( $_FILES['asn_registration_upload_file'], $GLOBALS['asn_registration_upload_file'] );

        if ( is_wp_error( $attachment_id ) || (int) $attachment_id <= 0 ) {
            return '';
        }

        $url = wp_get_attachment_url( (int) $attachment_id );
        return is_string( $url ) ? esc_url_raw( $url ) : '';
    }

    public static function save_legacy_age_meta( int $user_id, int $age ): void {
        if ( $user_id <= 0 || $age < 1 || $age > 120 ) {
            return;
        }

        update_user_meta( $user_id, 'dob', $age );

        $existing = (string) get_user_meta( $user_id, 'dob_date', true );
        if ( '' === $existing ) {
            update_user_meta( $user_id, 'dob_date', 'age-only' );
        }
    }

    public static function ensure_default_free_membership( int $user_id ): bool {
        if ( $user_id <= 0 || false === get_userdata( $user_id ) ) {
            return false;
        }

        if ( Memberships::can_text_chat( $user_id ) ) {
            return true;
        }

        if ( ! PMPro_Integration::assign_level( Memberships::FREE_LEVEL_ID, $user_id ) ) {
            return false;
        }

        Profile_Index::sync_user( $user_id );
        return Memberships::can_text_chat( $user_id );
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

        $complete_url = add_query_arg( 'asn_step', 'complete', $return_url );
        update_user_meta( $user_id, '_asn_free_onboarding_redirect_url', $complete_url );
        update_user_meta( $user_id, '_asn_free_onboarding_redirect_expires', time() + 900 );

        $redirect_filter = static function ( $location, $status ) use ( $complete_url ) {
            return self::rewrite_legacy_free_plan_redirect( (string) $location, $complete_url );
        };
        add_filter( 'wp_redirect', $redirect_filter, 999, 2 );

        $assigned = PMPro_Integration::assign_level( Memberships::FREE_LEVEL_ID, $user_id );

        remove_filter( 'wp_redirect', $redirect_filter, 999 );

        if ( ! $assigned ) {
            delete_user_meta( $user_id, '_asn_free_onboarding_redirect_url' );
            delete_user_meta( $user_id, '_asn_free_onboarding_redirect_expires' );
            $this->redirect_with_error( add_query_arg( 'asn_step', 'plan', $return_url ), 'membership' );
            return;
        }

        Profile_Index::sync_user( $user_id );
        update_user_meta( $user_id, '_asn_onboarding_step', 'complete' );
        $this->redirect( $complete_url );
    }

    public function maybe_redirect_completed_free_signup(): void {
        $user_id = (int) get_current_user_id();
        if ( ! is_user_logged_in() || $user_id <= 0 ) {
            return;
        }

        $step = isset( $_GET['step'] ) ? sanitize_text_field( wp_unslash( $_GET['step'] ) ) : '';
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
        $path = (string) parse_url( $request_uri, PHP_URL_PATH );

        if ( '2' !== $step || '/register/' !== trailingslashit( $path ) ) {
            return;
        }

        $explore_url = (string) get_user_meta( $user_id, '_asn_free_onboarding_redirect_url', true );
        $expires = (int) get_user_meta( $user_id, '_asn_free_onboarding_redirect_expires', true );

        if ( '' === $explore_url || $expires < time() || ! Memberships::can_text_chat( $user_id ) ) {
            if ( $expires > 0 && $expires < time() ) {
                delete_user_meta( $user_id, '_asn_free_onboarding_redirect_url' );
                delete_user_meta( $user_id, '_asn_free_onboarding_redirect_expires' );
            }
            return;
        }

        delete_user_meta( $user_id, '_asn_free_onboarding_redirect_url' );
        delete_user_meta( $user_id, '_asn_free_onboarding_redirect_expires' );
        $this->redirect( $explore_url );
    }

    public static function save_legacy_birth_meta( int $user_id, string $birth_date, int $age ): void {
        if ( $user_id <= 0 || '' === trim( $birth_date ) || $age <= 0 ) {
            return;
        }

        update_user_meta( $user_id, 'dob_date', $birth_date );
        update_user_meta( $user_id, 'dob', $age );
    }

    public static function rewrite_legacy_free_plan_redirect( string $location, string $explore_url ): string {
        $parts = parse_url( $location );
        if ( false === $parts ) {
            return $location;
        }

        $path = isset( $parts['path'] ) ? rtrim( (string) $parts['path'], '/' ) : '';
        if ( '/register' !== $path ) {
            return $location;
        }

        $query = array();
        if ( isset( $parts['query'] ) ) {
            parse_str( (string) $parts['query'], $query );
        }

        return isset( $query['step'] ) && '2' === (string) $query['step'] ? $explore_url : $location;
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
