<?php
namespace ASN\Core\Directory;

use ASN\Core\Messaging;
use ASN\Core\Profiles\Profile_Service;

defined( 'ABSPATH' ) || exit;

final class Directory_Shortcode {
    private $registered = false;

    public function register(): void {
        if ( $this->registered ) {
            return;
        }
        $this->registered = true;
        add_shortcode( 'asn_explore', array( $this, 'render' ) );
    }

    public function render( array $atts = array() ): string {
        wp_enqueue_style( 'asn-core', ASN_CORE_URL . 'public/css/asn-core.css', array(), ASN_CORE_VERSION );
        wp_enqueue_script( 'asn-messaging', ASN_CORE_URL . 'public/js/asn-messaging.js', array(), ASN_CORE_VERSION, true );
        wp_enqueue_script( 'asn-directory', ASN_CORE_URL . 'public/js/asn-directory.js', array(), ASN_CORE_VERSION, true );

        $filters = Directory_Query::from_request( $_GET );
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) && is_scalar( $_SERVER['REQUEST_URI'] )
            ? wp_unslash( (string) $_SERVER['REQUEST_URI'] )
            : '/';
        $form_action = explode( '?', $request_uri, 2 )[0];
        if ( '' === $form_action ) {
            $form_action = '/';
        }

        $profile_base_url = isset( $atts['profile_url'] ) && is_scalar( $atts['profile_url'] )
            ? esc_url_raw( (string) $atts['profile_url'] )
            : site_url( '/profile/' );
        if ( '' === $profile_base_url ) {
            $profile_base_url = site_url( '/profile/' );
        }

        $result = Directory_Service::search( $filters );
        $viewer_id = (int) get_current_user_id();
        $members = array();

        foreach ( $result['items'] as $item ) {
            $user_id = isset( $item['user_id'] ) ? (int) $item['user_id'] : 0;
            $profile = Profile_Service::find( $user_id, $viewer_id );
            if ( ! $profile ) {
                continue;
            }
            $profile['message_action'] = Messaging::action( $user_id );
            $members[] = $profile;
        }

        $pagination_urls = array();
        for ( $page = 1; $page <= (int) $result['pages']; ++$page ) {
            $params = array(
                'q'           => $filters['q'],
                'dialect'     => $filters['dialect'],
                'proficiency' => $filters['proficiency'],
                'country'     => $filters['country'],
                'page'        => $page,
            );
            $params = array_filter( $params, static function ( $value ) {
                return '' !== $value;
            } );
            $pagination_urls[ $page ] = '?' . http_build_query( $params, '', '&', PHP_QUERY_RFC3986 );
        }

        ob_start();
        require __DIR__ . '/views/directory.php';
        return (string) ob_get_clean();
    }
}
