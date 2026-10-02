<?php
require_once dirname( __DIR__ ) . '/modules/profiles/class-asn-legacy-profile-contract.php';
require_once dirname( __DIR__ ) . '/modules/profiles/class-asn-profile-fields.php';

use ASN\Core\Profiles\Legacy_Profile_Contract;
use ASN\Core\Profiles\Profile_Fields;
use PHPUnit\Framework\TestCase;

final class ProfileFieldsTest extends TestCase {
    public function test_only_approved_fields_are_public_and_editable(): void {
        $this->assertCount( 31, Profile_Fields::public_keys() );
        $this->assertSame( Profile_Fields::public_keys(), Profile_Fields::editable_keys() );
        $this->assertNotContains( 'user_email', Profile_Fields::editable_keys(), true );
        $this->assertNotContains( 'wp_capabilities', Profile_Fields::editable_keys(), true );
    }

    public function test_all_legacy_profile_card_prompts_and_photo_prompts_are_defined(): void {
        $this->assertCount( 23, Profile_Fields::prompt_keys() );
        $this->assertSame(
            array(
                'a_photo_of_me_on_holiday',
                'a_photo_of_me_doing_what_i_love_most',
                'a_photo_that_brings_back_good_memories',
                'a_photo_of_me_being_me',
                'a_photo_of_something_i_ve_done_recently',
                'a_photo_of_the_good_old_days',
            ),
            Profile_Fields::photo_prompt_keys()
        );
        $this->assertSame( "I'm currently trying to learn", Profile_Fields::prompt_label( 'i_m_currently_trying_to_learn' ) );
        $this->assertSame( 'A photo of me on holiday', Profile_Fields::prompt_label( 'a_photo_of_me_on_holiday' ) );
    }

    public function test_age_is_bounded_to_defensive_legacy_compatible_range(): void {
        $this->assertSame( 35, Profile_Fields::sanitize( 'age', '35' ) );
        $this->assertNull( Profile_Fields::sanitize( 'age', '0' ) );
        $this->assertNull( Profile_Fields::sanitize( 'age', '121' ) );
        $this->assertNull( Profile_Fields::sanitize( 'age', 'abc' ) );
    }

    public function test_gender_and_proficiency_use_audited_allowlists(): void {
        foreach ( Legacy_Profile_Contract::gender_options() as $value ) {
            $this->assertSame( $value, Profile_Fields::sanitize( 'gender', $value ) );
        }
        foreach ( Legacy_Profile_Contract::spoken_proficiency_options() as $value ) {
            $this->assertSame( $value, Profile_Fields::sanitize( 'spoken_proficiency', $value ) );
        }
        $this->assertNull( Profile_Fields::sanitize( 'gender', '__invalid__' ) );
        $this->assertNull( Profile_Fields::sanitize( 'spoken_proficiency', '__invalid__' ) );
    }

    public function test_spoken_proficiency_is_split_into_canonical_filter_values(): void {
        $this->assertSame( array( 'dialect' => 'western', 'proficiency' => 'fluent' ), Profile_Fields::split_spoken_proficiency( 'Western Armenian - Fluent' ) );
        $this->assertSame( array( 'dialect' => 'eastern', 'proficiency' => 'beginner' ), Profile_Fields::split_spoken_proficiency( 'Eastern Armenian - Beginner' ) );
    }

    public function test_text_and_prompt_values_are_sanitized_and_capped(): void {
        $this->assertSame( 'Developer', Profile_Fields::sanitize( 'job_title', '<b>Developer</b>' ) );
        $long = str_repeat( 'x', 1100 );
        $this->assertSame( 1000, strlen( Profile_Fields::sanitize( 'my_favorite_music_is', $long ) ) );
        $this->assertNull( Profile_Fields::sanitize( 'user_email', 'private@example.com' ) );
    }
}
