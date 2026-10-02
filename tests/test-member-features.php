<?php
use ASN\Core\Features\Member_Features;
use PHPUnit\Framework\TestCase;

final class MemberFeaturesTest extends TestCase {
    public function test_here_for_values_are_normalized_and_labeled(): void {
        $stored = Member_Features::normalize_here_for(
            array( 'friendship', 'networking', 'friendship', 'not-real' )
        );

        $this->assertSame( 'friendship,networking', $stored );
        $this->assertSame(
            array( 'Friendship', 'Networking' ),
            Member_Features::here_for_labels( $stored )
        );
    }

    public function test_profile_completion_is_based_on_all_profile_cards(): void {
        $user_id = 8;
        $previous = $GLOBALS['asn_test_user_meta'][ $user_id ];

        try {
            foreach ( \ASN\Core\Profiles\Profile_Fields::completion_prompt_keys() as $key ) {
                unset( $GLOBALS['asn_test_user_meta'][ $user_id ][ $key ] );
            }

            $this->assertSame( 0, Member_Features::profile_completion_score( $user_id ) );

            foreach ( \ASN\Core\Profiles\Profile_Fields::completion_prompt_keys() as $key ) {
                $GLOBALS['asn_test_user_meta'][ $user_id ][ $key ] = 'Completed';
            }

            $this->assertSame( 100, Member_Features::profile_completion_score( $user_id ) );
        } finally {
            $GLOBALS['asn_test_user_meta'][ $user_id ] = $previous;
        }
    }

    public function test_incomplete_member_resumes_profile_cards(): void {
        $user_id = 8;
        $previous = $GLOBALS['asn_test_user_meta'][ $user_id ];
        $GLOBALS['asn_test_user_meta'][ $user_id ]['_asn_onboarding_step'] = 'plan';

        try {
            foreach ( \ASN\Core\Profiles\Profile_Fields::completion_prompt_keys() as $key ) {
                unset( $GLOBALS['asn_test_user_meta'][ $user_id ][ $key ] );
            }

            $this->assertSame(
                'https://example.test/asn-register-test/?asn_step=prompts',
                Member_Features::resume_profile_url( $user_id )
            );
        } finally {
            $GLOBALS['asn_test_user_meta'][ $user_id ] = $previous;
        }
    }

    public function test_new_member_badge_uses_short_registration_window(): void {
        $previous = $GLOBALS['asn_test_users'][8]->user_registered;
        $GLOBALS['asn_test_users'][8]->user_registered = gmdate( 'Y-m-d H:i:s', time() - ( 3 * DAY_IN_SECONDS ) );

        try {
            $this->assertTrue( Member_Features::is_new_member( 8 ) );
            $this->assertFalse( Member_Features::is_new_member( 7 ) );
        } finally {
            $GLOBALS['asn_test_users'][8]->user_registered = $previous;
        }
    }
}
