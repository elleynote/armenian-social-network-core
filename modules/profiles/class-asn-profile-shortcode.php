<?php
namespace ASN\Core\Profiles;

defined( 'ABSPATH' ) || exit;

final class Profile_Shortcode {
    private $registered = false;

    public function register(): void {
        if ( $this->registered ) {
            return;
        }

        $this->registered = true;
        add_shortcode( 'asn_profile', array( $this, 'render' ) );
    }

    public function render( array $atts = array() ): string {
        wp_enqueue_style( 'asn-core', ASN_CORE_URL . 'public/css/asn-core.css', array(), ASN_CORE_VERSION );

        $viewer_id = (int) get_current_user_id();
        $has_member = isset( $_GET['member'] );
        $user_id = $has_member ? absint( $_GET['member'] ) : $viewer_id;
        $profile = $user_id > 0 ? Profile_Service::find( $user_id, $viewer_id ) : null;

        ob_start();
        if ( ! $profile ) {
            echo '<div class="asn-profile asn-profile--empty"><p>' . esc_html( 'Member not found' ) . '</p></div>';
            return (string) ob_get_clean();
        }

        require __DIR__ . '/views/profile.php';
        return (string) ob_get_clean();
    }
}
