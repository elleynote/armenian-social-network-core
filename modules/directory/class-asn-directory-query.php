<?php
namespace ASN\Core\Directory;

defined( 'ABSPATH' ) || exit;

final class Directory_Query {
    public const PER_PAGE = 20;
    public const MAX_PAGE = 10000;

    public static function from_request( array $request ): array {
        $q = self::text( $request['q'] ?? '' );
        $country = self::text( $request['country'] ?? '' );
        $dialect = self::allowed( $request['dialect'] ?? '', array( 'eastern', 'western' ) );
        $proficiency = self::allowed( $request['proficiency'] ?? '', array( 'beginner', 'intermediate', 'advanced', 'fluent' ) );
        $page = isset( $request['page'] ) && is_scalar( $request['page'] ) ? (int) $request['page'] : 1;
        $page = max( 1, min( self::MAX_PAGE, $page ) );

        return array(
            'q'           => $q,
            'dialect'     => $dialect,
            'proficiency' => $proficiency,
            'country'     => $country,
            'page'        => $page,
            'per_page'    => self::PER_PAGE,
        );
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
