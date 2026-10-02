<?php
defined( 'ABSPATH' ) || exit;

$profile_url = add_query_arg( 'member', (int) $member['id'], $profile_base_url );
$profile_url = add_query_arg( 'tac_user', (int) $member['id'], $profile_url );
$message = $member['message_action'];
$better_messages = $member['better_messages_action'] ?? array();
$age = trim( (string) ( $member['age'] ?? '' ) );
$username = trim( (string) ( $member['username'] ?? '' ) );
$spoken = trim( (string) ( $member['spoken_proficiency'] ?? '' ) );
$is_favorite = ! empty( $member['is_favorite'] );
$is_recently_active = ! empty( $member['is_recently_active'] );
$here_for_labels = (array) ( $member['here_for_labels'] ?? array() );
$speaker_label = '';
if ( '' !== $spoken ) {
    $parts = array_map( 'trim', explode( '-', $spoken, 2 ) );
    $speaker_label = trim( (string) ( $parts[1] ?? $parts[0] ) );
    if ( '' !== $speaker_label ) {
        $speaker_label .= ' Speaker';
    }
}
$return_to_explore = isset( $request_uri ) && is_scalar( $request_uri ) ? (string) $request_uri : site_url( '/asn-explore-test/' );
?>
<article class="asn-member-card asn-member-card--v2">
    <?php if ( get_current_user_id() > 0 && (int) get_current_user_id() !== (int) $member['id'] ) : ?>
        <form class="asn-member-card__favorite" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="asn_toggle_favorite">
            <input type="hidden" name="target_user_id" value="<?php echo esc_attr( (string) $member['id'] ); ?>">
            <input type="hidden" name="favorite" value="<?php echo $is_favorite ? '0' : '1'; ?>">
            <input type="hidden" name="return_url" value="<?php echo esc_url( $return_to_explore ); ?>">
            <?php wp_nonce_field( 'asn_member_discovery_' . (int) $member['id'], 'asn_discovery_nonce' ); ?>
            <button type="submit" aria-label="<?php echo esc_attr( $is_favorite ? 'Remove from saved profiles' : 'Save profile' ); ?>" title="<?php echo esc_attr( $is_favorite ? 'Saved profile' : 'Save profile' ); ?>">
                <span aria-hidden="true"><?php echo $is_favorite ? '&#9829;' : '&#9825;'; ?></span>
            </button>
        </form>
    <?php endif; ?>

    <a class="asn-member-card__profile" href="<?php echo esc_url( $profile_url ); ?>">
        <img class="asn-member-card__photo" src="<?php echo esc_url( $member['photo_url'] ); ?>" alt="<?php echo esc_attr( $member['display_name'] ); ?>" loading="lazy" width="160" height="160">
        <h2 class="asn-member-card__name">
            <?php echo esc_html( $member['display_name'] ); ?>
            <?php if ( '' !== $age ) : ?><span class="asn-member-card__age">, <?php echo esc_html( $age ); ?></span><?php endif; ?>
        </h2>
        <div class="asn-member-card__badges">
            <?php if ( ! empty( $member['is_new_member'] ) ) : ?>
                <span class="asn-member-card__new-badge">New</span>
            <?php endif; ?>
            <?php if ( $is_recently_active ) : ?>
                <span class="asn-member-card__active-badge">Recently active</span>
            <?php endif; ?>
        </div>
    </a>

    <?php if ( '' !== $username ) : ?>
        <p class="asn-member-card__username">@<?php echo esc_html( $username ); ?></p>
    <?php endif; ?>
    <?php if ( '' !== (string) ( $member['job_title'] ?? '' ) ) : ?><p class="asn-member-card__meta"><?php echo esc_html( $member['job_title'] ); ?></p><?php endif; ?>
    <?php if ( '' !== (string) ( $member['country'] ?? '' ) ) : ?><p class="asn-member-card__meta"><?php echo esc_html( $member['country'] ); ?></p><?php endif; ?>
    <?php if ( '' !== $speaker_label ) : ?><p class="asn-member-card__meta"><?php echo esc_html( $speaker_label ); ?></p><?php endif; ?>

    <?php if ( ! empty( $here_for_labels ) ) : ?>
        <div class="asn-member-card__here-for" aria-label="I'm here for">
            <?php foreach ( array_slice( $here_for_labels, 0, 3 ) as $label ) : ?>
                <span><?php echo esc_html( $label ); ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="asn-member-card__actions asn-member-card__actions--v2">
        <a class="asn-button asn-member-card__view-profile" href="<?php echo esc_url( $profile_url ); ?>">View Profile</a>

        <?php if ( ! empty( $better_messages['available'] ) && ! empty( $better_messages['url'] ) ) : ?>
            <a class="asn-button asn-member-card__chat" href="<?php echo esc_url( $better_messages['url'] ); ?>" aria-label="<?php echo esc_attr( 'Message ' . $member['display_name'] ); ?>">Message</a>
        <?php elseif ( ! empty( $message['available'] ) ) : ?>
            <button class="asn-button asn-member-card__chat" type="button" data-asn-message-user="<?php echo esc_attr( (string) $message['target_user_id'] ); ?>" aria-label="<?php echo esc_attr( 'Message ' . $member['display_name'] ); ?>">Message</button>
        <?php else : ?>
            <button class="asn-button asn-member-card__chat" type="button" disabled aria-label="Messaging unavailable">Message</button>
        <?php endif; ?>
    </div>
</article>
