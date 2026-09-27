<?php
namespace ASN\Core\Profiles;

use ASN\Core\Members;

defined( 'ABSPATH' ) || exit;

final class Profile_Service {
    public static function find( int $user_id, int $viewer_id = 0 ): ?array {
        $member = Members::find( $user_id );
        if ( ! $member ) {
            return null;
        }

        $profile = array(
            'id'           => (int) $member['id'],
            'display_name' => (string) $member['display_name'],
            'photo_url'    => Profile_Photo::url( $user_id ),
            'is_owner'     => $viewer_id > 0 && $viewer_id === $user_id,
        );

        foreach ( Profile_Fields::public_keys() as $key ) {
            $profile[ $key ] = Members::profile_meta( $user_id, $key, '' );
        }

        return $profile;
    }
}
