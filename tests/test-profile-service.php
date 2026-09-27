<?php
use ASN\Core\Profiles\Profile_Photo;
use ASN\Core\Profiles\Profile_Service;
use PHPUnit\Framework\TestCase;

final class ProfileServiceTest extends TestCase {
    private $profile_pic;
    private $avatar;

    protected function setUp(): void {
        $this->profile_pic = $GLOBALS['asn_test_user_meta'][7]['profile_pic'] ?? null;
        $this->avatar = $GLOBALS['asn_test_avatar_urls'][7] ?? '';
    }

    protected function tearDown(): void {
        if ( null === $this->profile_pic ) {
            unset( $GLOBALS['asn_test_user_meta'][7]['profile_pic'] );
        } else {
            $GLOBALS['asn_test_user_meta'][7]['profile_pic'] = $this->profile_pic;
        }
        $GLOBALS['asn_test_avatar_urls'][7] = $this->avatar;
    }

    public function test_valid_member_returns_only_approved_public_profile_fields(): void {
        $profile = Profile_Service::find( 7, 7 );

        $this->assertSame( 7, $profile['id'] );
        $this->assertSame( 'Test Member', $profile['display_name'] );
        $this->assertSame( 'Australia', $profile['country'] );
        $this->assertSame( 'Jazz', $profile['my_favorite_music_is'] );
        $this->assertTrue( $profile['is_owner'] );
        $this->assertArrayNotHasKey( 'user_email', $profile );
        $this->assertArrayNotHasKey( 'user_pass', $profile );
        $this->assertArrayNotHasKey( 'wp_capabilities', $profile );
    }

    public function test_email_like_wordpress_display_name_is_never_exposed_publicly(): void {
        $original_display_name = $GLOBALS['asn_test_users'][7]->display_name;
        $GLOBALS['asn_test_users'][7]->display_name = 'private@example.test';

        try {
            $profile = Profile_Service::find( 7, 0 );
            $this->assertSame( 'Test Member', $profile['display_name'] );
            $this->assertStringNotContainsString( '@', $profile['display_name'] );
        } finally {
            $GLOBALS['asn_test_users'][7]->display_name = $original_display_name;
        }
    }

    public function test_nonexistent_member_returns_null_and_other_viewer_is_not_owner(): void {
        $this->assertNull( Profile_Service::find( 999, 7 ) );
        $this->assertFalse( Profile_Service::find( 7, 8 )['is_owner'] );
        $this->assertFalse( Profile_Service::find( 7, 0 )['is_owner'] );
    }

    public function test_legacy_profile_photo_wins_when_present(): void {
        $this->assertSame( 'https://example.test/profile-7.jpg', Profile_Photo::url( 7 ) );
    }

    public function test_missing_legacy_photo_falls_back_to_wordpress_avatar(): void {
        unset( $GLOBALS['asn_test_user_meta'][7]['profile_pic'] );
        $this->assertSame( 'https://example.test/avatar-7.jpg', Profile_Photo::url( 7 ) );
    }

    public function test_missing_avatar_falls_back_to_plugin_placeholder(): void {
        unset( $GLOBALS['asn_test_user_meta'][7]['profile_pic'] );
        $GLOBALS['asn_test_avatar_urls'][7] = '';
        $this->assertSame( ASN_CORE_URL . 'public/images/profile-placeholder.svg', Profile_Photo::url( 7 ) );
    }

    public function test_missing_profile_index_does_not_prevent_source_profile_display(): void {
        $GLOBALS['asn_test_profile_rows'] = array();
        $profile = Profile_Service::find( 7, 0 );
        $this->assertSame( 'Australia', $profile['country'] );
    }
}
