<?php
defined( 'ABSPATH' ) || exit;

if ( ! empty( $profile['is_owner'] ) && isset( $_GET['asn_edit_profile'] ) ) {
    require __DIR__ . '/profile-edit.php';
    return;
}

$better_messages = $profile['better_messages_action'] ?? array();
$age = trim( (string) ( $profile['age'] ?? '' ) );
$username = trim( (string) ( $profile['username'] ?? '' ) );
$job_title = trim( (string) ( $profile['job_title'] ?? '' ) );
$country = trim( (string) ( $profile['country'] ?? '' ) );
$spoken = trim( (string) ( $profile['spoken_proficiency'] ?? '' ) );
$speaker = '';

if ( '' !== $spoken ) {
    $parts = array_map( 'trim', explode( '-', $spoken, 2 ) );
    $speaker = trim( (string) ( $parts[1] ?? $parts[0] ) );
    if ( '' !== $speaker ) {
        $speaker .= ' Speaker';
    }
}

$name_line = trim( (string) ( $profile['display_name'] ?? '' ) );
if ( '' !== $age ) {
    $name_line .= ', ' . $age;
}

$groups = array(
    'me' => array(
        'title' => 'What makes me, me.',
        'fields' => array(
            'my_favorite_music_is',
            'i_get_way_too_excited_about',
            'after_work_you_can_find_me',
            'the_greatest_thing_about_where_i_live_is',
        ),
    ),
    'morning' => array(
        'title' => 'What gets me out of bed in the morning?',
        'fields' => array(
            'something_i_m_really_really_good_at_is',
            'something_you_might_not_know_about_me_is',
            'i_value_people_who',
        ),
    ),
    'planning' => array(
        'title' => 'What I’m planning next?',
        'fields' => array(
            'my_dream_job_is',
            'this_year_i_really_want_to',
            'a_lifelong_goal_of_mine_is_to',
            'my_dream_holiday_destination_is',
            'one_way_i_d_like_to_change_the_world_is',
        ),
    ),
    'story' => array(
        'title' => 'How I became me.',
        'fields' => array(
            'my_greatest_childhood_memory_is',
            'my_biggest_fear_is',
            'the_best_piece_of_advice_i_ve_ever_received_is',
        ),
    ),
    'share' => array(
        'title' => 'What I can share.',
        'fields' => array(
            'i_m_currently_trying_to_learn',
            'one_thing_i_could_help_teach_you_about_is',
        ),
    ),
);

$photos = array(
    'a_photo_of_me_doing_what_i_love_most' => 'A photo of me doing what I love most.',
    'a_photo_of_me_being_me' => 'A photo of me being me.',
    'a_photo_of_something_i_ve_done_recently' => "A photo of something I've done recently.",
    'a_photo_of_the_good_old_days' => 'A photo of the good old days.',
);
?>
<section class="asn-profile asn-profile--v2" aria-labelledby="asn-profile-name">
    <aside class="asn-profile-v2__sidebar">
        <div class="asn-profile-v2__identity-card">
            <h1 id="asn-profile-name" class="asn-profile-v2__name"><?php echo esc_html( $name_line ); ?></h1>

            <img class="asn-profile-v2__photo" src="<?php echo esc_url( $profile['photo_url'] ); ?>" alt="<?php echo esc_attr( $profile['display_name'] ); ?>" loading="lazy" width="220" height="220">

            <?php if ( '' !== $username ) : ?>
                <p class="asn-profile-v2__username">@<?php echo esc_html( $username ); ?></p>
            <?php endif; ?>

            <div class="asn-profile-v2__meta">
                <?php if ( '' !== $job_title ) : ?><p><?php echo esc_html( $job_title ); ?></p><?php endif; ?>
                <?php if ( '' !== $country ) : ?><p><?php echo esc_html( $country ); ?></p><?php endif; ?>
                <?php if ( '' !== $speaker ) : ?><p><?php echo esc_html( $speaker ); ?></p><?php endif; ?>
            </div>

            <?php if ( ! empty( $profile['is_owner'] ) ) : ?>
                <a class="asn-profile-v2__primary-action" href="?member=<?php echo esc_attr( (string) $profile['id'] ); ?>&amp;asn_edit_profile=1">Edit Profile</a>
            <?php elseif ( ! empty( $better_messages['available'] ) && ! empty( $better_messages['url'] ) ) : ?>
                <a class="asn-profile-v2__primary-action" href="<?php echo esc_url( $better_messages['url'] ); ?>">Message</a>
            <?php else : ?>
                <span class="asn-profile-v2__primary-action asn-profile-v2__primary-action--disabled">Message</span>
            <?php endif; ?>
        </div>
    </aside>

    <div class="asn-profile-v2__content">
        <div class="asn-profile-v2__summary-grid">
            <?php foreach ( $groups as $slug => $group ) : ?>
                <?php
                $answers = array();
                foreach ( $group['fields'] as $key ) {
                    $value = trim( (string) ( $profile[ $key ] ?? '' ) );
                    if ( '' !== $value ) {
                        $answers[] = array(
                            'label' => ASNCoreProfilesProfile_Fields::prompt_label( $key ),
                            'value' => $value,
                        );
                    }
                }
                ?>
                <article class="asn-profile-v2__summary-card asn-profile-v2__summary-card--<?php echo esc_attr( $slug ); ?>">
                    <h2><?php echo esc_html( $group['title'] ); ?></h2>

                    <?php if ( empty( $answers ) ) : ?>
                        <p class="asn-profile-v2__empty">Not updated yet.</p>
                    <?php else : ?>
                        <div class="asn-profile-v2__answers">
                            <?php foreach ( $answers as $answer ) : ?>
                                <div class="asn-profile-v2__answer">
                                    <span class="asn-profile-v2__answer-label"><?php echo esc_html( $answer['label'] ); ?></span>
                                    <p><?php echo esc_html( $answer['value'] ); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

        <section class="asn-profile-v2__gallery" aria-labelledby="asn-profile-gallery-title">
            <h2 id="asn-profile-gallery-title">A glimpse into my world.</h2>

            <div class="asn-profile-v2__gallery-grid">
                <?php foreach ( $photos as $key => $label ) : ?>
                    <?php $image = trim( (string) ( $profile['prompt_images'][ $key ] ?? '' ) ); ?>
                    <figure class="asn-profile-v2__gallery-item">
                        <?php if ( '' !== $image ) : ?>
                            <img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $label ); ?>" loading="lazy">
                        <?php else : ?>
                            <span class="asn-profile-v2__gallery-placeholder" aria-hidden="true"></span>
                        <?php endif; ?>
                        <figcaption><?php echo esc_html( $label ); ?></figcaption>
                    </figure>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</section>
