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
        'im_here_for',
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
        'a_photo_of_me_on_holiday',
        'a_photo_of_me_doing_what_i_love_most',
        'a_photo_that_brings_back_good_memories',
        'a_photo_of_me_being_me',
        'a_photo_of_something_i_ve_done_recently',
        'a_photo_of_the_good_old_days',
    );

    private const PHOTO_PROMPT_KEYS = array(
        'a_photo_of_me_on_holiday',
        'a_photo_of_me_doing_what_i_love_most',
        'a_photo_that_brings_back_good_memories',
        'a_photo_of_me_being_me',
        'a_photo_of_something_i_ve_done_recently',
        'a_photo_of_the_good_old_days',
    );

    private const PROMPT_LABELS = array(
        'my_dream_holiday_destination_is' => 'My dream holiday destination is',
        'this_year_i_really_want_to' => 'This year I really want to',
        'a_lifelong_goal_of_mine_is_to' => 'A lifelong goal of mine is to',
        'my_favorite_music_is' => 'My favorite music is',
        'i_get_way_too_excited_about' => 'I get way too excited about',
        'something_i_m_really_really_good_at_is' => "Something I'm really really good at is",
        'my_greatest_childhood_memory_is' => 'My greatest childhood memory is',
        'my_biggest_fear_is' => 'My biggest fear is',
        'i_value_people_who' => 'I value people who',
        'after_work_you_can_find_me' => 'After work you can find me',
        'my_dream_job_is' => 'My dream job is',
        'the_greatest_thing_about_where_i_live_is' => 'The greatest thing about where I live is',
        'something_you_might_not_know_about_me_is' => 'Something you might not know about me is',
        'one_way_i_d_like_to_change_the_world_is' => "One way I'd like to change the world is",
        'the_best_piece_of_advice_i_ve_ever_received_is' => "The best piece of advice I've ever received is",
        'i_m_currently_trying_to_learn' => "I'm currently trying to learn",
        'one_thing_i_could_help_teach_you_about_is' => 'One thing I could help teach you about is',
        'a_photo_of_me_on_holiday' => 'A photo of me on holiday',
        'a_photo_of_me_doing_what_i_love_most' => 'A photo of me doing what I love most',
        'a_photo_that_brings_back_good_memories' => 'A photo that brings back good memories',
        'a_photo_of_me_being_me' => 'A photo of me being me',
        'a_photo_of_something_i_ve_done_recently' => "A photo of something I've done recently",
        'a_photo_of_the_good_old_days' => 'A photo of the good old days',
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

    public static function photo_prompt_keys(): array {
        return self::PHOTO_PROMPT_KEYS;
    }

    public static function completion_prompt_keys(): array {
        return array_values( array_diff( self::PROMPT_KEYS, self::PHOTO_PROMPT_KEYS ) );
    }

    public static function prompt_label( string $key ): string {
        return self::PROMPT_LABELS[ $key ] ?? ucwords( str_replace( '_', ' ', $key ) );
    }

    public static function sanitize( string $key, $value ) {
        if ( ! in_array( $key, self::editable_keys(), true ) ) {
            return null;
        }

        if ( 'im_here_for' === $key ) {
            return \ASN\Core\Features\Member_Features::normalize_here_for( $value );
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
            return in_array( $value, Legacy_Profile_Contract::gender_options(), true ) ? $value : null;
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
