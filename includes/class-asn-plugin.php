<?php
namespace ASN\Core;

use ASN\Core\Admin\Admin;

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
    }

    public function init(): void {
        // Foundation hook for future ASN modules.
    }
}
