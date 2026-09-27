<?php
require_once dirname( __DIR__ ) . '/modules/profiles/class-asn-legacy-profile-contract.php';

use ASN\Core\Profiles\Legacy_Profile_Contract;
use PHPUnit\Framework\TestCase;

final class LegacyProfileContractTest extends TestCase {
    public function test_profile_photo_source_matches_active_legacy_meta(): void {
        $this->assertSame( array( 'type' => 'user_meta_url', 'key' => 'profile_pic' ), Legacy_Profile_Contract::profile_photo_source() );
    }

    public function test_gender_options_match_active_legacy_registration(): void {
        $this->assertSame( array( 'male' => 'Male', 'female' => 'Female', 'non_binary' => 'Non Binary' ), Legacy_Profile_Contract::gender_options() );
    }

    public function test_spoken_proficiency_options_match_active_legacy_registration(): void {
        $this->assertSame( array(
            'Eastern Armenian - Beginner',
            'Eastern Armenian - Intermediate',
            'Eastern Armenian - Advanced',
            'Eastern Armenian - Fluent',
            'Western Armenian - Beginner',
            'Western Armenian - Intermediate',
             'Western Armenian - Advanced',
            'Western Armenian - Fluent',
        ), Legacy_Profile_Contract::spoken_proficiency_options() );
    }

    public function test_atomchat_launcher_name_is_legacy_cometchat_launcher(): void {
        $this->assertSame( 'jqcc.cometchat.launch', Legacy_Profile_Contract::atomchat_launcher_name() );
    }

    public function test_contract_contains_no_secret_material(): void {
        $payload = wp_json_encode( array(
            Legacy_Profile_Contract::profile_photo_source(),
            Legacy_Profile_Contract::yender_options(),
            Legacy_Profile_Contract::spoken_proficiency_options(),
            Legacy_Profile_Contract::atomchat_launcher_name(),
        ) );
        $this->assertStringNotContainsString( 'api-key', strtolower( $payload ) );
        $this->assertStringNotContainsString( 'token', strtolower( $payload ) );
        $this->assertStringNotContainsString( 'secret', strtolower( $payload ) );
    }
}
