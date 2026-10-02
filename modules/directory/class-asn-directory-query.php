<?php
namespace ASN\Core\Directory;

use ASN\Core\Features\Member_Features;
use ASN\Core\Profiles\Legacy_Profile_Contract;

defined( 'ABSPATH' ) || exit;

final class Directory_Query {
    public const PER_PAGE = 20;
    public const MAX_PAGE = 10000;

    public static function from_request( array $request ): array {
        $q = self::text( $request['q'] ?? '' );
        $country = self::text( $request['country'] ?? '' );
        $dialect = self::allowed( $request['dialect'] ?? '', array( 'eastern', 'western' ) );
        $proficiency = self::allowed( $request['proficiency'] ?? '', array( 'beginner', 'intermediate', 'advanced', 'fluent' ) );
        $gender = self::allowed( $request['gender'] ?? '', Legacy_Profile_Contract::gender_options() );
        $job_title = self::text( $request['job_title'] ?? '' );
        $here_for = self::allowed( $request['here_for'] ?? '', array_keys( Member_Features::here_for_options() ) );
        $age_min = self::bounded_int( $request['age_min'] ?? 0, 1, 120 );
        $age_max = self::bounded_int( $request['age_max'] ?? 0, 1, 120 );
        if ( $age_min > 0 && $age_max > 0 && $age_min > $age_max ) {
            $swap = $age_min;
            $age_min = $age_max;
            $age_max = $swap;
        }

        $recent = self::truthy( $request['recent'] ?? false );
        $favorites = self::truthy( $request['favorites'] ?? false );
        $viewers = self::truthy( $request['viewers'] ?? false );
        $page = isset( $request['page'] ) && is_scalar( $request['page'] ) ? (int) $request['page'] : 1;
        $page = max( 1, min( self::MAX_PAGE, $page ) );

        return array(
            'q'           => $q,
            'dialect'     => $dialect,
            'proficiency' => $proficiency,
            'country'     => $country,
            'gender'      => $gender,
            'job_title'   => $job_title,
            'here_for'    => $here_for,
            'age_min'     => $age_min,
            'age_max'     => $age_max,
            'recent'      => $recent,
            'favorites'   => $favorites,
            'viewers'     => $viewers,
            'page'        => $page,
            'per_page'    => self::PER_PAGE,
        );
    }

    public static function has_active_filters( array $filters ): bool {
        foreach ( array( 'q', 'dialect', 'proficiency', 'country', 'gender', 'job_title', 'here_for', 'age_min', 'age_max', 'recent', 'favorites', 'viewers' ) as $key ) {
            if ( ! empty( $filters[ $key ] ) ) {
                return true;
            }
        }

        return false;
    }

    public static function is_united_states_country( string $country ): bool {
        $normalized = strtolower( trim( $country ) );
        $normalized = preg_replace( '/[^a-z]/', '', $normalized );

        return in_array(
            $normalized,
            array( 'us', 'usa', 'unitedstates', 'unitedstatesofamerica' ),
            true
        );
    }

    private static function bounded_int( $value, int $min, int $max ): int {
        if ( ! is_scalar( $value ) || '' === trim( (string) $value ) ) {
            return 0;
        }

        $value = (int) $value;
        return ( $value >= $min && $value <= $max ) ? $value : 0;
    }

    private static function truthy( $value ): bool {
        return in_array( strtolower( trim( (string) $value ) ), array( '1', 'yes', 'true', 'on' ), true );
    }

    private static function text( $value ): string {
        if ( ! is_scalar( $value ) ) {
            return '';
        }
        return sanitize_text_field( wp_unslash( (string) $value ) );
    }

    private static function allowed( $value, array $allowed ): string {
        $value = self::text( $value );
        return in_array( $value, $allowed, true ) ? $value : '';
    }
}
