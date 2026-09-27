<?php
defined( 'ABSPATH' ) || exit;

if ( ! empty( $profile['is_owner'] ) && isset( $_GET['asn_edit_profile'] ) ) {
    require __DIR__ . '/profile-edit.php';
    return;
}

$summary_fields = array(
    'age'                => 'Age',
    'gender'             => 'Gender',
    'job_title'          => 'Job title',
    'spoken_proficiency' => 'Spoken proficiency',
);
?>
<section class="asn-profile" aria-labelledby="asn-profile-name">
    <header class="asn-profile__header">
        <img class="asn-profile__photo" src="<?php echo esc_url( $profile['photo_url'] ); ?>" alt="<?php echo esc_attr( $profile['display_name'] ); ?>" loading="lazy" width="120" height="120">
        <div class="asn-profile__intro">
            <h1 id="asn-profile-name" class="asn-profile__name"><?php echo esc_html( $profile['display_name'] ); ?></h1>

            <?php if ( ! empty( $profile['email'] ) ) : ?>
                <p class="asn-profile__email"><?php echo esc_html( $profile['email'] ); ?></p>
            <?php endif; ?>

            <div class="asn-profile__details">
                <?php foreach ( $summary_fields as $key => $label ) : ?>
                    <?php if ( '' !== (string) ( $profile[ $key ] ?? '' ) ) : ?>
                        <p class="asn-profile__meta"><strong><?php echo esc_html( $label ); ?>:</strong> <?php echo esc_html( $profile[ $key ] ); ?></p>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <?php if ( ! empty( $profile['is_owner'] ) ) : ?>
                <a class="asn-button asn-profile__edit" href="?member=<?php echo esc_attr( (string) $profile['id'] ); ?>&amp;asn_edit_profile=1">Edit profile details</a>
            <?php endif; ?>
        </div>
    </header>

    <?php if ( ! empty( $profile['is_owner'] ) ) : ?>
        <form class="asn-profile__cards-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="asn_update_profile">
            <input type="hidden" name="target_user_id" value="<?php echo esc_attr( (string) $profile['id'] ); ?>">
            <?php wp_nonce_field( 'asn_update_profile', 'asn_profile_nonce' ); ?>
    <?php endif; ?>

    <div class="asn-profile__cards">
        <?php foreach ( ASNCoreProfilesProfile_Fields::prompt_keys() as $key ) : ?>
            <?php
            $value = trim( (string) ( $profile[ $key ] ?? '' ) );
            $image = (string) ( $profile['prompt_images'][ $key ] ?? '' );
            ?>
            <article class="asn-profile-card">
                <header class="asn-profile-card__member">
                    <img class="asn-profile-card__avatar" src="<?php echo esc_url( $profile['photo_url'] ); ?>" alt="" loading="lazy" width="46" height="46">
                    <div class="asn-profile-card__member-copy">
                        <strong class="asn-profile-card__member-name"><?php echo esc_html( $profile['display_name'] ); ?></strong>
                        <?php if ( '' !== (string) ( $profile['spoken_proficiency'] ?? '' ) ) : ?>
                            <span class="asn-profile-card__member-proficiency"><?php echo esc_html( $profile['spoken_proficiency'] ); ?></span>
                        <?php endif; ?>
                    </div>
                </header>

                <div class="asn-profile-card__body">
                    <h2 class="asn-profile-card__title"><?php echo esc_html( ASNCoreProfilesProfile_Fields::prompt_label( $key ) ); ?></h2>

                    <?php if ( ! empty( $profile['is_owner'] ) ) : ?>
                        <textarea class="asn-profile-card__textarea" maxlength="1000" name="asn_profile[<?php echo esc_attr( $key ); ?>]" aria-label="<?php echo esc_attr( ASNCoreProfilesProfile_Fields::prompt_label( $key ) ); ?>" placeholder="Not updated yet"><?php echo esc_html( $value ); ?></textarea>
                    <?php elseif ( '' === $value ) : ?>
                        <p class="asn-profile-card__answer asn-profile-card__answer--empty">Not updated yet</p>
                    <?php else : ?>
                        <p class="asn-profile-card__answer"><?php echo esc_html( $value ); ?></p>
                    <?php endif; ?>

                    <?php if ( '' !== $image ) : ?>
                        <img class="asn-profile-card__image" src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( ASNCoreProfilesProfile_Fields::prompt_label( $key ) ); ?>" loading="lazy">
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ( ! empty( $profile['is_owner'] ) ) : ?>
            <div class="asn-profile__cards-actions">
                <button class="asn-button asn-profile__cards-submit" type="submit">Save profile cards</button>
            </div>
        </form>
    <?php endif; ?>
</section>
