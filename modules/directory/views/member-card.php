<?php
defined( 'ABSPATH' ) || exit;

$profile_url = add_query_arg( 'member', (int) $member['id'], $profile_base_url );
$profile_url = add_query_arg( 'tac_user', (int) $member['id'], $profile_url );
$message = $member['message_action'];
$better_messages = $member['better_messages_action'] ?? array();
$age = trim( (string) ( $member['age'] ?? '' ) );
$username = trim( (string) ( $member['username'] ?? '' ) );
$spoken = trim( (string) ( $member['spoken_proficiency'] ?? '' ) );
$speaker_label = '';
if ( '' !== $spoken ) {
    $parts = array_map( 'trim', explode( '-', $spoken, 2 ) );
    $speaker_label = trim( (string) ( $parts[1] ?? $parts[0] ) );
    if ( '' !== $speaker_label ) {
        $speaker_label .= ' Speaker';
    }
}
?>
<article class="asn-member-card asn-member-card--v2">
    <a class="asn-member-card__profile" href="<?php echo esc_url( $profile_url ); ?>">
        <img class="asn-member-card__photo" src="<?php echo esc_url( $member['photo_url'] ); ?>" alt="<?php echo esc_attr( $member['display_name'] ); ?>" loading="lazy" width="160" height="160">
        <h2 class="asn-member-card__name">
            <?php echo esc_html( $member['display_name'] ); ?>
            <?php if ( '' !== $age ) : ?><span class="asn-member-card__age">, <?php echo esc_html( $age ); ?></span><?php endif; ?>
        </h2>
    </a>

    <?php if ( '' !== $username ) : ?>
        <p class="asn-member-card__username">@<?php echo esc_html( $username ); ?></p>
    <?php endif; ?>
    <?php if ( '' !== (string) ( $member['job_title'] ?? '' ) ) : ?><p class="asn-member-card__meta"><?php echo esc_html( $member['job_title'] ); ?></p><?php endif; ?>
    <?php if ( '' !== (string) ( $member['country'] ?? '' ) ) : ?><p class="asn-member-card__meta"><?php echo esc_html( $member['country'] ); ?></p><?php endif; ?>
    <?php if ( '' !== $speaker_label ) : ?><p class="asn-member-card__meta"><?php echo esc_html( $speaker_label ); ?></p><?php endif; ?>

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
