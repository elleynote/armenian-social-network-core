<?php
namespace ASN\Core\Profiles;

defined( 'ABSPATH' ) || exit;

final class Profile_Fields {
    private const BASIC_KEYS = array(
        'first_name',
        'last_name',
        'country',
        'age',
        'gender',
        'job_title',
        'spoken_proficiency',
    );

    private const PROMPT_KEYS = array(
        'my_dream_holiday_destination_is',
        'this_year_i_really_want_to',
        'a_lifelong_goal_of_mine_is_to',
        'my_favorite_music_is',
        'i_get_way_too_excited_about',
        'something_i_m_really_really_good_at_is',
        'my_greatest_childhood_memory_is',
        'my_biggest_fear_is',
        'i_value_people_who',
        'after_work_you_can_find_me',
        'my_dream_job_is',
        'the_greatest_thing_about_where_i_live_is',
        'something_you_might_not_know_about_me_is',
        'one_way_i_d_like_to_change_the_world_is',
        'the_best_piece_of_advice_i_ve_ever_received_is',
        'i_m_currently_trying_to_learn',
        'one_thing_i_could_help_teach_you_about_is',
    );

    public static function public_keys(): array {
        return array_merge( self::BASIC_KEYS, self::PROMPT_KEYS );
    }

    public static function editable_keys(): array {
        return self::public_keys();
    }

    public static function prompt_keys(): array {
        return self::PROMPT_KEYS;
    }

    public static function sanitize( string $key, $value ) {
        if ( ! in_array( $key, self::editable_keys(), true ) ) {
            return null;
        }

        if ( 'age' === $key ) {
            if ( ! is_scalar( $value ) || ! preg_match( '/^\d{1,3}$/', trim( (string) $value ) ) ) {
                return null;
            }
            $age = (int) $value;
            return ( $age >= 1 && $age <= 120 ) ? $age : null;
        }

        if ( 'gender' === $key ) {
            $value = (string) $value;
            return array_key_exists( $value, Legacy_Profile_Contract::gender_options() ) ? $value : null;
        }

        if ( 'spoken_proficiency' === $key ) {
            $value = (string) $value;
            return in_array( $value, Legacy_Profile_Contract::spoken_proficiency_options(), true ) ? $value : null;
        }

        if ( in_array( $key, self::PROMPT_KEYS, true ) ) {
            $value = sanitize_textarea_field( (string) $value );
            return self::truncate( $value, 1000 );
        }

        $value = sanitize_text_field( (string) $value );
        $max = 'job_title' === $key ? 191 : 100;
        return self::truncate( $value, $max );
    }

    public static function split_spoken_proficiency( string $value ): array {
        if ( ! in_array( $value, Legacy_Profile_Contract::spoken_proficiency_options(), true ) ) {
            return array( 'dialect' => '', 'proficiency' => '' );
        }

        $parts = array_map( 'trim', explode( '-', $value, 2 ) );
        $dialect_words = preg_split( '/\s+/', $parts[0] );
        $dialect = strtolower( (string) ( $dialect_words[0] ?? '' ) );
        $proficiency = strtolower( (string) ( $parts[1] ?? '' ) );

        return array(
            'dialect'     => in_array( $dialect, array( 'eastern', 'western' ), true ) ? $dialect : '',
            'proficiency' => in_array( $proficiency, array( 'beginner', 'intermediate', 'advanced', 'fluent' ), true ) ? $proficiency : '',
        );
    }

    private static function truncate( string $value, int $max ): string {
        if ( function_exists( 'mb_substr' ) ) {
            return mb_substr( $value, 0, $max );
        }
        return substr( $value, 0, $max );
    }
}
