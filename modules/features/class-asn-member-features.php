<?php
namespace ASN\Core\Features;

use ASN\Core\Profiles\Profile_Fields;

defined( 'ABSPATH' ) || exit;

final class Member_Features {
    public const NEW_MEMBER_DAYS = 14;

    private const HERE_FOR_OPTIONS = array(
        'friendship' => 'Friendship',
        'armenian-practice' => 'Armenian practice',
        'networking' => 'Networking',
        'business-connections' => 'Business connections',
        'community' => 'Community',
    );

    public static function here_for_options(): array {
        return self::HERE_FOR_OPTIONS;
    }

    public static function normalize_here_for( $value ): string {
        $values = is_array( $value ) ? $value : preg_split( '/\s*,\s*/', (string) $value );
        $clean = array();

        foreach ( (array) $values as $item ) {
            $key = sanitize_key( (string) $item );
            if ( isset( self::HERE_FOR_OPTIONS[ $key ] ) ) {
                $clean[ $key ] = $key;
            }
        }

        return implode( ',', array_values( $clean ) );
    }

    public static function here_for_labels( $value ): array {
        $normalized = self::normalize_here_for( $value );
        if ( '' === $normalized ) {
            return array();
        }

        $labels = array();
        foreach ( explode( ',', $normalized ) as $key ) {
            if ( isset( self::HERE_FOR_OPTIONS[ $key ] ) ) {
                $labels[] = self::HERE_FOR_OPTIONS[ $key ];
            }
        }

        return $labels;
    }

    public static function profile_completion_score( int $user_id ): int {
        if ( $user_id <= 0 ) {
            return 0;
        }

        $fields = Profile_Fields::completion_prompt_keys();
        $total = count( $fields );
        if ( 0 === $total ) {
            return 0;
        }

        $completed = 0;
        foreach ( $fields as $key ) {
            if ( '' !== trim( (string) get_user_meta( $user_id, $key, true ) ) ) {
                ++$completed;
            }
        }

        return (int) floor( ( $completed / $total ) * 100 );
    }

    public static function resume_profile_url( int $user_id, string $register_url = '' ): string {
        if ( $user_id <= 0 || false === get_userdata( $user_id ) ) {
            return '';
        }

        if ( '' === $register_url ) {
            $register_url = site_url( '/asn-register-test/' );
        }

        $score = self::profile_completion_score( $user_id );
        $step = sanitize_key( (string) get_user_meta( $user_id, '_asn_onboarding_step', true ) );
        $allowed = array( 'profile', 'prompts', 'photos', 'plan', 'complete' );

        if ( ! in_array( $step, $allowed, true ) ) {
            $step = $score < 100 ? 'prompts' : 'photos';
        }

        if ( $score < 100 && in_array( $step, array( 'photos', 'plan', 'complete' ), true ) ) {
            $step = 'prompts';
        }

        if ( $score >= 100 && 'complete' === $step ) {
            return '';
        }

        return add_query_arg( 'asn_step', $step, $register_url );
    }

    public static function is_new_member( int $user_id ): bool {
        $user = get_userdata( $user_id );
        if ( ! $user || empty( $user->user_registered ) ) {
            return false;
        }

        $registered = strtotime( (string) $user->user_registered );
        if ( false === $registered ) {
            return false;
        }

        $now = time();
        $window = self::NEW_MEMBER_DAYS * DAY_IN_SECONDS;

        return $registered <= $now && ( $now - $registered ) <= $window;
    }
}
