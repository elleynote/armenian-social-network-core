<?php
defined( 'ABSPATH' ) || exit;

$basic_labels = array(
    'first_name'         => 'First name',
    'last_name'          => 'Last name',
    'country'            => 'Country',
    'age'                => 'Age',
    'job_title'          => 'Job title',
);
?>
<section class="asn-profile-edit" aria-labelledby="asn-profile-edit-title">
    <h1 id="asn-profile-edit-title" class="asn-profile-edit__title">Edit profile</h1>
    <form class="asn-profile-edit__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="asn_update_profile">
        <input type="hidden" name="target_user_id" value="<?php echo esc_attr( (string) $profile['id'] ); ?>">
        <?php wp_nonce_field( 'asn_update_profile', 'asn_profile_nonce' ); ?>

        <?php foreach ( $basic_labels as $key => $label ) : ?>
            <label class="asn-field">
                <span class="asn-field__label"><?php echo esc_html( $label ); ?></span>
                <input class="asn-field__input" type="<?php echo 'age' === $key ? 'number' : 'text'; ?>" <?php echo 'age' === $key ? 'min="1" max="120"' : ''; ?> name="asn_profile[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( (string) ( $profile[ $key ] ?? '' ) ); ?>">
            </label>
        <?php endforeach; ?>

        <label class="asn-field">
            <span class="asn-field__label">Gender</span>
            <select class="asn-field__input" name="asn_profile[gender]">
                <option value="">Select</option>
                <?php foreach ( \ASN\Core\Profiles\Legacy_Profile_Contract::gender_options() as $option ) : ?>
                    <option value="<?php echo esc_attr( $option ); ?>"<?php echo (string) ( $profile['gender'] ?? '' ) === $option ? ' selected' : ''; ?>><?php echo esc_html( $option ); ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="asn-field">
            <span class="asn-field__label">Spoken proficiency</span>
            <select class="asn-field__input" name="asn_profile[spoken_proficiency]">
                <option value="">Select</option>
                <?php foreach ( \ASN\Core\Profiles\Legacy_Profile_Contract::spoken_proficiency_options() as $option ) : ?>
                    <option value="<?php echo esc_attr( $option ); ?>"<?php echo (string) ( $profile['spoken_proficiency'] ?? '' ) === $option ? ' selected' : ''; ?>><?php echo esc_html( $option ); ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <fieldset class="asn-profile-edit__here-for">
            <legend>I'm here for</legend>
            <div class="asn-profile-edit__choice-grid">
                <?php
                $selected_here_for = explode( ',', \ASN\Core\Features\Member_Features::normalize_here_for( $profile['im_here_for'] ?? '' ) );
                foreach ( \ASN\Core\Features\Member_Features::here_for_options() as $key => $label ) :
                    ?>
                    <label>
                        <input type="checkbox" name="asn_profile[im_here_for][]" value="<?php echo esc_attr( $key ); ?>"<?php echo in_array( $key, $selected_here_for, true ) ? ' checked' : ''; ?>>
                        <span><?php echo esc_html( $label ); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <?php foreach ( \ASN\Core\Profiles\Profile_Fields::prompt_keys() as $key ) : ?>
            <label class="asn-field">
                <span class="asn-field__label"><?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></span>
                <textarea class="asn-field__input asn-field__textarea" maxlength="1000" name="asn_profile[<?php echo esc_attr( $key ); ?>]"><?php echo esc_html( (string) ( $profile[ $key ] ?? '' ) ); ?></textarea>
            </label>
        <?php endforeach; ?>

        <button class="asn-button asn-profile-edit__submit" type="submit">Save profile</button>
    </form>
</section>
