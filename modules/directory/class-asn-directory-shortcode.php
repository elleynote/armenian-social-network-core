<?php
namespace ASN\Core\Directory;

use ASN\Core\Features\Member_Discovery;
use ASN\Core\Features\Member_Features;
use ASN\Core\Features\Member_Safety;
use ASN\Core\Messaging;
use ASN\Core\Profiles\Profile_Photo;

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
            : site_url( '/asn-profile-test/' );
        if ( '' === $profile_base_url ) {
            $profile_base_url = site_url( '/asn-profile-test/' );
        }

        $result = Directory_Service::search( $filters );
        $viewer_id = (int) get_current_user_id();
        $blocked_ids = $viewer_id > 0 ? Member_Safety::blocked_user_ids( $viewer_id ) : array();
        $favorite_ids = $viewer_id > 0 ? Member_Discovery::favorite_ids( $viewer_id ) : array();
        $members = array();

        foreach ( $result['items'] as $item ) {
            $profile = self::hydrate_member( $item, $viewer_id, $blocked_ids, $favorite_ids, $request_uri );
            if ( $profile ) {
                $members[] = $profile;
            }
        }

        $suggested_members = array();
        if ( $viewer_id > 0 && 1 === (int) $filters['page'] && ! Directory_Query::has_active_filters( $filters ) ) {
            foreach ( Directory_Service::suggested( $viewer_id, 4, $blocked_ids ) as $item ) {
                $profile = self::hydrate_member( $item, $viewer_id, $blocked_ids, $favorite_ids, $request_uri );
                if ( $profile ) {
                    $suggested_members[] = $profile;
                }
            }
        }

        $saved_searches = $viewer_id > 0 ? Member_Discovery::saved_searches( $viewer_id ) : array();
        $show_suggested_section = $viewer_id > 0 && 1 === (int) $filters['page'] && ! Directory_Query::has_active_filters( $filters );
        $quick_links = array(
            'all'       => $form_action,
            'recent'    => add_query_arg( 'recent', '1', $form_action ),
            'favorites' => add_query_arg( 'favorites', '1', $form_action ),
            'viewers'   => add_query_arg( 'viewers', '1', $form_action ),
        );
        $directory_heading = ! empty( $filters['viewers'] ) ? 'Who viewed my profile' : 'Explore members';
        $feature_notice = isset( $_GET['asn_feature_notice'] ) ? sanitize_key( wp_unslash( $_GET['asn_feature_notice'] ) ) : '';
        $notice_messages = array(
            'favorite_saved' => 'Profile saved to your favorites.',
            'favorite_removed' => 'Profile removed from your favorites.',
            'favorite_failed' => 'We could not update that favorite.',
            'search_saved' => 'Search saved.',
            'search_empty' => 'Choose at least one filter before saving a search.',
            'search_deleted' => 'Saved search deleted.',
            'search_delete_failed' => 'We could not delete that saved search.',
            'security' => 'Please refresh the page and try again.',
            'not_allowed' => 'That action is not available.',
        );

        $pagination_urls = array();
        for ( $page = 1; $page <= (int) $result['pages']; ++$page ) {
            $params = self::pagination_params( $filters, $page );
            $pagination_urls[ $page ] = '?' . http_build_query( $params, '', '&', PHP_QUERY_RFC3986 );
        }

        ob_start();
        require __DIR__ . '/views/directory.php';
        return (string) ob_get_clean();
    }

    private static function hydrate_member( array $item, int $viewer_id, array $blocked_ids = array(), array $favorite_ids = array(), string $return_url = '' ): ?array {
        $user_id = isset( $item['user_id'] ) ? (int) $item['user_id'] : 0;
        $user = $user_id > 0 ? get_userdata( $user_id ) : false;
        if ( ! $user ) {
            return null;
        }

        $dialect = trim( (string) ( $item['dialect'] ?? '' ) );
        $proficiency = trim( (string) ( $item['proficiency'] ?? '' ) );
        $spoken = '';
        if ( '' !== $dialect || '' !== $proficiency ) {
            $spoken = trim(
                ( '' !== $dialect ? ucfirst( $dialect ) . ' Armenian' : '' )
                . ( '' !== $dialect && '' !== $proficiency ? ' - ' : '' )
                . ( '' !== $proficiency ? ucfirst( $proficiency ) : '' )
            );
        }

        $is_blocked = in_array( $user_id, $blocked_ids, true );
        $better_messages = Messaging::better_messages_action( $user_id, $return_url, true, $is_blocked );
        $registered = isset( $user->user_registered ) ? strtotime( (string) $user->user_registered ) : false;
        $is_new_member = false !== $registered
            && $registered <= time()
            && ( time() - $registered ) <= ( Member_Features::NEW_MEMBER_DAYS * DAY_IN_SECONDS );
        $last_active = strtotime( (string) ( $item['last_active_at'] ?? '' ) );
        $recent_cutoff = strtotime( Member_Discovery::recent_cutoff() );
        $is_recently_active = false !== $last_active && false !== $recent_cutoff && $last_active >= $recent_cutoff;

        $profile = array(
            'id'                     => $user_id,
            'display_name'           => sanitize_text_field( (string) ( $item['display_name'] ?? '' ) ),
            'username'               => isset( $user->user_login ) ? sanitize_user( (string) $user->user_login, true ) : '',
            'photo_url'              => Profile_Photo::url( $user_id ),
            'age'                    => (string) ( $item['age'] ?? '' ),
            'job_title'              => sanitize_text_field( (string) ( $item['job_title'] ?? '' ) ),
            'country'                => sanitize_text_field( (string) ( $item['country'] ?? '' ) ),
            'spoken_proficiency'     => $spoken,
            'im_here_for'            => (string) ( $item['here_for'] ?? '' ),
            'message_action'         => ! empty( $better_messages['available'] ) ? array( 'available' => false ) : Messaging::action( $user_id ),
            'better_messages_action' => $better_messages,
            'is_new_member'          => $is_new_member,
            'is_recently_active'     => $is_recently_active,
            'is_favorite'            => $viewer_id > 0 && in_array( $user_id, $favorite_ids, true ),
            'here_for_labels'        => Member_Features::here_for_labels( $item['here_for'] ?? '' ),
        );

        return $profile;
    }

    private static function pagination_params( array $filters, int $page ): array {
        $params = array(
            'q'           => $filters['q'],
            'dialect'     => $filters['dialect'],
            'proficiency' => $filters['proficiency'],
            'country'     => $filters['country'],
            'gender'      => $filters['gender'],
            'job_title'   => $filters['job_title'],
            'here_for'    => $filters['here_for'],
            'age_min'     => $filters['age_min'],
            'age_max'     => $filters['age_max'],
            'recent'      => $filters['recent'] ? '1' : '',
            'favorites'   => $filters['favorites'] ? '1' : '',
            'viewers'     => $filters['viewers'] ? '1' : '',
            'page'        => $page,
        );

        return array_filter(
            $params,
            static function ( $value ) {
                return '' !== $value && 0 !== $value;
            }
        );
    }
}
