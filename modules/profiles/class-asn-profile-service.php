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

        $user = get_userdata( $user_id );
        $profile = array(
            'id'           => (int) $member['id'],
            'display_name' => (string) $member['display_name'],
            'username'     => $user && isset( $user->user_login ) ? sanitize_user( (string) $user->user_login, true ) : '',
            'photo_url'    => Profile_Photo::url( $user_id ),
            'is_owner'     => $viewer_id > 0 && $viewer_id === $user_id,
        );

        if ( $viewer_id > 0 ) {
            if ( $user && isset( $user->user_email ) ) {
                $profile['email'] = sanitize_email( (string) $user->user_email );
            }
        }

        foreach ( Profile_Fields::public_keys() as $key ) {
            $profile[ $key ] = Members::profile_meta( $user_id, $key, '' );
        }

        $profile['prompt_images'] = array();
        foreach ( Profile_Fields::photo_prompt_keys() as $key ) {
            $image = Members::profile_meta( $user_id, $key . '_image', '' );
            $profile['prompt_images'][ $key ] = is_string( $image ) ? esc_url_raw( $image ) : '';
        }

        return $profile;
    }

    public static function update_own_profile( int $actor_user_id, int $target_user_id, array $input ): array {
        $result = array(
            'success'      => false,
            'updated'      => array(),
            'index_synced' => false,
            'errors'       => array(),
        );

        if ( $actor_user_id <= 0 || $actor_user_id !== $target_user_id || ! get_userdata( $target_user_id ) ) {
            $result['errors'][] = 'not_allowed';
            return $result;
        }

        $allowed = Profile_Fields::editable_keys();
        $clean = array();

        foreach ( $input as $key => $value ) {
            $key = (string) $key;
            if ( ! in_array( $key, $allowed, true ) ) {
                $result['errors'][] = 'invalid_field:' . $key;
                continue;
            }

            if ( 'im_here_for' !== $key && ! is_scalar( $value ) && null !== $value ) {
                $result['errors'][] = 'invalid_value:' . $key;
                continue;
            }

            if ( 'im_here_for' !== $key && '' === trim( (string) $value ) ) {
                $clean[ $key ] = '';
                continue;
            }

            $sanitized = Profile_Fields::sanitize( $key, $value );
            if ( null === $sanitized ) {
                $result['errors'][] = 'invalid_value:' . $key;
                continue;
            }
            $clean[ $key ] = $sanitized;
        }

        if ( ! empty( $result['errors'] ) ) {
            return $result;
        }

        foreach ( $clean as $key => $value ) {
            update_user_meta( $target_user_id, $key, $value );
            $result['updated'][] = $key;
        }

        $result['success'] = true;
        $result['index_synced'] = Profile_Index::sync_user( $target_user_id );
        if ( ! $result['index_synced'] ) {
            $result['errors'][] = 'index_sync_failed';
        }

        return $result;
    }

}
