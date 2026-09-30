<?php
namespace ASN\Core;

use ASN\Core\Admin\Admin;
use ASN\Core\Admin\Profile_Index_Admin;
use ASN\Core\Profiles\Profile_Shortcode;
use ASN\Core\Profiles\Profile_Form;
use ASN\Core\Directory\Directory_Shortcode;
use ASN\Core\Registration\Registration;
use ASN\Core\Registration\Registration_Shortcode;
use ASN\Core\Integrations\WooCommerce_Integration;

defined( 'ABSPATH' ) || exit;

final class Plugin {
    private static $instance = null;
    private $booted = false;

    private function __construct() {}

    public static function instance(): Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function boot(): void {
        if ( $this->booted ) {
            return;
        }

        $this->booted = true;
        add_action( 'init', array( $this, 'init' ) );
        add_filter( 'better_messages_can_send_message', array( Messaging::class, 'filter_better_messages_can_send_message' ), 10, 3 );
        add_filter( 'bp_better_messages_can_audio_call', array( Messaging::class, 'filter_better_messages_can_audio_call' ), 10, 3 );
        add_filter( 'bp_better_messages_can_video_call', array( Messaging::class, 'filter_better_messages_can_video_call' ), 10, 3 );
        add_filter( 'better_messages_call_create_custom_error', array( Messaging::class, 'filter_better_messages_call_create_error' ), 10, 4 );
        add_filter( 'better_messages_call_join_custom_error', array( Messaging::class, 'filter_better_messages_call_join_error' ), 10, 4 );
        add_filter( 'woocommerce_get_return_url', array( WooCommerce_Integration::class, 'filter_paid_return_url' ), 20, 2 );

        $admin = new Admin();
        $admin->register_hooks();

        $profile_index_admin = new Profile_Index_Admin();
        $profile_index_admin->register_hooks();

        $profile_shortcode = new Profile_Shortcode();
        $profile_shortcode->register();

        $profile_form = new Profile_Form();
        $profile_form->register();

        $directory_shortcode = new Directory_Shortcode();
        $directory_shortcode->register();

        $registration = new Registration();
        $registration->register();

        $registration_shortcode = new Registration_Shortcode();
        $registration_shortcode->register();
    }

    public function init(): void {
        if ( Database::VERSION !== Database::current_version() ) {
            Database::install();
        }
    }
}
