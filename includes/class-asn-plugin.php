<?php
namespace ASN\Core;

use ASN\Core\Admin\Admin;
use ASN\Core\Admin\Profile_Index_Admin;
use ASN\Core\Profiles\Profile_Shortcode;
use ASN\Core\Profiles\Profile_Form;
use ASN\Core\Directory\Directory_Shortcode;

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
    }

    public function init(): void {
        if ( Database::VERSION !== Database::current_version() ) {
            Database::install();
        }
    }
}
