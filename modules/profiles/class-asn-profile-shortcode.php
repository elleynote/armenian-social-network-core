<?php
namespace ASN\Core\Profiles;

use ASN\Core\Messaging;
use ASN\Core\Features\Member_Features;
use ASN\Core\Features\Member_Safety;

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

        $profile['better_messages_action'] = Messaging::better_messages_action( $user_id );
        $profile['is_new_member'] = Member_Features::is_new_member( $user_id );
        $profile['here_for_labels'] = Member_Features::here_for_labels( $profile['im_here_for'] ?? '' );
        $profile['completion_score'] = Member_Features::profile_completion_score( $user_id );
        $profile['resume_profile_url'] = ! empty( $profile['is_owner'] )
            ? Member_Features::resume_profile_url( $user_id )
            : '';
        $profile['viewer_blocked_target'] = $viewer_id > 0 && $viewer_id !== $user_id
            ? Member_Safety::is_blocked_by( $viewer_id, $user_id )
            : false;
        $profile['blocked_between'] = $viewer_id > 0 && $viewer_id !== $user_id
            ? Member_Safety::is_blocked_between( $viewer_id, $user_id )
            : false;

        require __DIR__ . '/views/profile.php';
        return (string) ob_get_clean();
    }
}
