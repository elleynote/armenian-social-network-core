<?php
defined( 'ABSPATH' ) || exit;

if ( ! empty( $profile['is_owner'] ) && isset( $_GET['asn_edit_profile'] ) ) {
    require __DIR__ . '/profile-edit.php';
    return;
}

$basic_fields = array(
    'country'            => 'Country',
    'age'                => 'Age',
    'gender'             => 'Gender',
    'job_title'          => 'Job title',
    'spoken_proficiency' => 'Spoken proficiency',
);
?>
<section class="asn-profile" aria-labelledby="asn-profile-name">
    <header class="asn-profile__header">
        <img class="asn-profile__photo" src="<?php echo esc_url( $profile['photo_url'] ); ?>" alt="<?php echo esc_attr( $profile['display_name'] ); ?>" loading="lazy" width="160" height="160">
        <div class="asn-profile__intro">
            <h1 id="asn-profile-name" class="asn-profile__name"><?php echo esc_html( $profile['display_name'] ); ?></h1>
            <?php foreach ( $basic_fields as $key => $label ) : ?>
                <?php if ( '' !== (string) ( $profile[ $key ] ?? '' ) ) : ?>
                    <p class="asn-profile__meta"><strong><?php echo esc_html( $label ); ?>:</strong> <?php echo esc_html( $profile[ $key ] ); ?></p>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if ( ! empty( $profile['is_owner'] ) ) : ?>
                <a class="asn-button asn-profile__edit" href="?member=<?php echo esc_attr( (string) $profile['id'] ); ?>&amp;asn_edit_profile=1">Edit profile</a>
            <?php endif; ?>
        </div>
    </header>

    <div class="asn-profile__prompts">
        <?php foreach ( \ASN\Core\Profiles\Profile_Fields::prompt_keys() as $key ) : ?>
            <?php $value = (string) ( $profile[ $key ] ?? '' ); ?>
            <?php if ( '' !== $value ) : ?>
                <article class="asn-profile__prompt">
                    <h2 class="asn-profile__prompt-title"><?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></h2>
                    <p><?php echo esc_html( $value ); ?></p>
                </article>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>
