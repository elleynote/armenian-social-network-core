<?php
namespace ASN\Core\Profiles;

defined( 'ABSPATH' ) || exit;

final class Legacy_Profile_Contract {
    public static function profile_photo_source(): array {
        return array(
            'type' => 'user_meta_url',
            'key'  => 'profile_pic',
        );
    }

    public static function gender_options(): array {
        return array(
            'Male',
            'Female',
            'Non Binary',
        );
    }

    public static function spoken_proficiency_options(): array {
        return array(
            'Eastern Armenian - Beginner',
            'Eastern Armenian - Intermediate',
            'Eastern Armenian - Advanced',
            'Eastern Armenian - Fluent',
            'Western Armenian - Beginner',
            'Western Armenian - Intermediate',
            'Western Armenian - Advanced',
            'Western Armenian - Fluent',
        );
    }

    public static function atomchat_launcher_name(): string {
        return 'jqcc.cometchat.launch';
    }
}
