<?php
defined( 'ABSPATH' ) || exit;

$profile_url = add_query_arg( 'member', (int) $member['id'], $profile_base_url );
$profile_url = add_query_arg( 'tac_user', (int) $member['id'], $profile_url );
$message = $member['message_action'];
$better_messages = $member['better_messages_action'] ?? array();
?>
<article class="asn-member-card">
    <a class="asn-member-card__profile" href="<?php echo esc_url( $profile_url ); ?>">
        <img class="asn-member-card__photo" src="<?php echo esc_url( $member['photo_url'] ); ?>" alt="<?php echo esc_attr( $member['display_name'] ); ?>" loading="lazy" width="120" height="120">
        <h2 class="asn-member-card__name"><?php echo esc_html( $member['display_name'] ); ?></h2>
    </a>
    <?php if ( '' !== (string) ( $member['country'] ?? '' ) ) : ?><p class="asn-member-card__meta"><?php echo esc_html( $member['country'] ); ?></p><?php endif; ?>
    <?php if ( '' !== (string) ( $member['job_title'] ?? '' ) ) : ?><p class="asn-member-card__meta"><?php echo esc_html( $member['job_title'] ); ?></p><?php endif; ?>
    <?php if ( '' !== (string) ( $member['spoken_proficiency'] ?? '' ) ) : ?><p class="asn-member-card__meta"><?php echo esc_html( $member['spoken_proficiency'] ); ?></p><?php endif; ?>

    <div class="asn-member-card__actions">
        <?php if ( ! empty( $message['available'] ) ) : ?>
            <button class="asn-button asn-member-card__message" type="button" data-asn-message-user="<?php echo esc_attr( (string) $message['target_user_id'] ); ?>">Message</button>
        <?php elseif ( empty( $better_messages['available'] ) ) : ?>
            <button class="asn-button asn-member-card__message" type="button" disabled>Messaging unavailable</button>
        <?php endif; ?>

        <?php if ( ! empty( $better_messages['available'] ) && ! empty( $better_messages['url'] ) ) : ?>
            <a class="asn-button asn-member-card__message asn-member-card__message--better-messages" href="<?php echo esc_url( $better_messages['url'] ); ?>">Test Better Messages</a>
        <?php endif; ?>
    </div>
</article>
