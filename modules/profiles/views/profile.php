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

$profile_url = add_query_arg( 'member', (int) $profile['id'], site_url( '/asn-profile-test/' ) );
$completion_score = (int) ( $profile['completion_score'] ?? 0 );
$resume_profile_url = (string) ( $profile['resume_profile_url'] ?? '' );
$here_for_labels = (array) ( $profile['here_for_labels'] ?? array() );
$viewer_blocked_target = ! empty( $profile['viewer_blocked_target'] );
$blocked_between = ! empty( $profile['blocked_between'] );
$is_favorite = ! empty( $profile['is_favorite'] );
$is_recently_active = ! empty( $profile['is_recently_active'] );
$profile_viewers = (array) ( $profile['profile_viewers'] ?? array() );
$notice_key = isset( $_GET['asn_profile_notice'] ) ? sanitize_key( wp_unslash( $_GET['asn_profile_notice'] ) ) : '';
$feature_notice = isset( $_GET['asn_feature_notice'] ) ? sanitize_key( wp_unslash( $_GET['asn_feature_notice'] ) ) : '';
$notice_messages = array(
    'reported' => 'Thanks. This profile has been reported for review.',
    'blocked' => 'This member is now blocked.',
    'unblocked' => 'This member has been unblocked.',
    'report_failed' => 'We could not submit the report. Please try again.',
    'block_failed' => 'We could not update the block. Please try again.',
    'security' => 'Please refresh the page and try again.',
    'not_allowed' => 'That action is not available.',
);
$feature_notice_messages = array(
    'favorite_saved' => 'Profile saved to your favorites.',
    'favorite_removed' => 'Profile removed from your favorites.',
    'favorite_failed' => 'We could not update that favorite.',
    'security' => 'Please refresh the page and try again.',
    'not_allowed' => 'That action is not available.',
);
?>
<?php if ( '' !== $notice_key && isset( $notice_messages[ $notice_key ] ) ) : ?>
    <div class="asn-profile-v2__notice"><?php echo esc_html( $notice_messages[ $notice_key ] ); ?></div>
<?php endif; ?>
<?php if ( '' !== $feature_notice && isset( $feature_notice_messages[ $feature_notice ] ) ) : ?>
    <div class="asn-profile-v2__notice"><?php echo esc_html( $feature_notice_messages[ $feature_notice ] ); ?></div>
<?php endif; ?>

<section class="asn-profile asn-profile--v2" aria-labelledby="asn-profile-name">
    <aside class="asn-profile-v2__sidebar">
        <div class="asn-profile-v2__identity-card">
            <div class="asn-profile-v2__badges">
                <?php if ( ! empty( $profile['is_new_member'] ) ) : ?>
                    <span class="asn-profile-v2__new-badge">New member</span>
                <?php endif; ?>
                <?php if ( $is_recently_active ) : ?>
                    <span class="asn-profile-v2__active-badge">Recently active</span>
                <?php endif; ?>
            </div>

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

            <?php if ( ! empty( $here_for_labels ) ) : ?>
                <div class="asn-profile-v2__here-for" aria-label="I'm here for">
                    <?php foreach ( $here_for_labels as $label ) : ?>
                        <span><?php echo esc_html( $label ); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $profile['is_owner'] ) ) : ?>
                <div class="asn-profile-v2__completion">
                    <div class="asn-profile-v2__completion-copy">
                        <span>Profile completion</span>
                        <strong><?php echo esc_html( (string) $completion_score ); ?>%</strong>
                    </div>
                    <div class="asn-profile-v2__completion-track"><span style="width:<?php echo esc_attr( (string) $completion_score ); ?>%"></span></div>
                    <?php if ( '' !== $resume_profile_url ) : ?>
                        <a class="asn-profile-v2__resume" href="<?php echo esc_url( $resume_profile_url ); ?>">Resume Profile Setup</a>
                    <?php endif; ?>
                </div>
                <a class="asn-profile-v2__primary-action" href="?member=<?php echo esc_attr( (string) $profile['id'] ); ?>&amp;asn_edit_profile=1">Edit Profile</a>
            <?php elseif ( ! empty( $better_messages['available'] ) && ! empty( $better_messages['url'] ) ) : ?>
                <a class="asn-profile-v2__primary-action" href="<?php echo esc_url( $better_messages['url'] ); ?>">Message</a>
            <?php else : ?>
                <span class="asn-profile-v2__primary-action asn-profile-v2__primary-action--disabled"><?php echo $blocked_between ? 'Messaging unavailable' : 'Message'; ?></span>
            <?php endif; ?>

            <?php if ( empty( $profile['is_owner'] ) && get_current_user_id() > 0 ) : ?>
                <form class="asn-profile-v2__favorite-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <input type="hidden" name="action" value="asn_toggle_favorite">
                    <input type="hidden" name="target_user_id" value="<?php echo esc_attr( (string) $profile['id'] ); ?>">
                    <input type="hidden" name="favorite" value="<?php echo $is_favorite ? '0' : '1'; ?>">
                    <input type="hidden" name="return_url" value="<?php echo esc_url( $profile_url ); ?>">
                    <?php wp_nonce_field( 'asn_member_discovery_' . (int) $profile['id'], 'asn_discovery_nonce' ); ?>
                    <button class="asn-profile-v2__favorite-button" type="submit"><?php echo $is_favorite ? 'Remove Saved Profile' : 'Save Profile'; ?></button>
                </form>

                <div class="asn-profile-v2__safety-actions">
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <input type="hidden" name="action" value="<?php echo $viewer_blocked_target ? 'asn_unblock_profile' : 'asn_block_profile'; ?>">
                        <input type="hidden" name="target_user_id" value="<?php echo esc_attr( (string) $profile['id'] ); ?>">
                        <input type="hidden" name="return_url" value="<?php echo esc_url( $profile_url ); ?>">
                        <?php wp_nonce_field( 'asn_profile_safety_' . (int) $profile['id'], 'asn_safety_nonce' ); ?>
                        <button class="asn-profile-v2__safety-button" type="submit"><?php echo $viewer_blocked_target ? 'Unblock Profile' : 'Block Profile'; ?></button>
                    </form>

                    <details class="asn-profile-v2__report">
                        <summary>Report Profile</summary>
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <input type="hidden" name="action" value="asn_report_profile">
                            <input type="hidden" name="target_user_id" value="<?php echo esc_attr( (string) $profile['id'] ); ?>">
                            <input type="hidden" name="return_url" value="<?php echo esc_url( $profile_url ); ?>">
                            <?php wp_nonce_field( 'asn_profile_safety_' . (int) $profile['id'], 'asn_safety_nonce' ); ?>
                            <label>
                                <span>Reason</span>
                                <select name="reason" required>
                                    <option value="">Choose a reason</option>
                                    <?php foreach ( \ASN\Core\Features\Member_Safety::report_reasons() as $reason_key => $reason_label ) : ?>
                                        <option value="<?php echo esc_attr( $reason_key ); ?>"><?php echo esc_html( $reason_label ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <button type="submit">Submit Report</button>
                        </form>
                    </details>
                </div>
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
                            'label' => \ASN\Core\Profiles\Profile_Fields::prompt_label( $key ),
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
                                    <?php if ( empty( $profile['is_owner'] ) && ! empty( $better_messages['available'] ) && ! empty( $better_messages['url'] ) ) : ?>
                                        <a class="asn-profile-v2__conversation-starter" href="<?php echo esc_url( $better_messages['url'] ); ?>">Message about this</a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ( ! empty( $profile['is_owner'] ) ) : ?>
            <section class="asn-profile-v2__viewers" aria-labelledby="asn-profile-viewers-title">
                <div class="asn-profile-v2__section-heading">
                    <h2 id="asn-profile-viewers-title">Who viewed my profile</h2>
                    <p>Recent members who opened your profile.</p>
                </div>

                <?php if ( empty( $profile_viewers ) ) : ?>
                    <p class="asn-profile-v2__empty">No profile views yet.</p>
                <?php else : ?>
                    <div class="asn-profile-v2__viewer-grid">
                        <?php foreach ( $profile_viewers as $viewer_profile ) : ?>
                            <?php $viewer_url = add_query_arg( 'member', (int) $viewer_profile['id'], site_url( '/asn-profile-test/' ) ); ?>
                            <a class="asn-profile-v2__viewer" href="<?php echo esc_url( $viewer_url ); ?>">
                                <img src="<?php echo esc_url( $viewer_profile['photo_url'] ); ?>" alt="<?php echo esc_attr( $viewer_profile['display_name'] ); ?>" loading="lazy" width="56" height="56">
                                <span>
                                    <strong><?php echo esc_html( $viewer_profile['display_name'] ); ?></strong>
                                    <?php if ( ! empty( $viewer_profile['country'] ) ) : ?><small><?php echo esc_html( $viewer_profile['country'] ); ?></small><?php endif; ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

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
