<?php
defined( 'ABSPATH' ) || exit;

$status = static function ( $available ): string {
    return $available ? 'Available' : 'Unavailable';
};
?>
<div class="wrap">
    <h1><?php echo esc_html( 'ASN Core' ); ?></h1>
    <p><?php echo esc_html( 'Diagnostics and controlled profile-index maintenance. This screen does not change billing, chat, SSO, or source member profile data.' ); ?></p>

    <table class="widefat striped" style="max-width: 900px;">
        <tbody>
            <tr><th><?php echo esc_html( 'ASN Core version' ); ?></th><td><?php echo esc_html( $data['version'] ); ?></td></tr>
            <tr><th><?php echo esc_html( 'ASN database schema' ); ?></th><td><?php echo esc_html( '' !== $data['database_version'] ? $data['database_version'] : 'Not installed' ); ?></td></tr>
            <tr><th><?php echo esc_html( 'WordPress users' ); ?></th><td><?php echo esc_html( (string) $data['wordpress_users'] ); ?></td></tr>
            <tr><th><?php echo esc_html( 'PMPro' ); ?></th><td><?php echo esc_html( $status( $data['pmpro_available'] ) ); ?></td></tr>
            <tr><th><?php echo esc_html( 'Premium access integration' ); ?></th><td><?php echo esc_html( $status( $data['premium_access_available'] ) ); ?></td></tr>
            <tr><th><?php echo esc_html( 'WooCommerce' ); ?></th><td><?php echo esc_html( $status( $data['woocommerce_available'] ) ); ?></td></tr>
            <tr><th><?php echo esc_html( 'WooCommerce Subscriptions' ); ?></th><td><?php echo esc_html( $status( $data['subscriptions_available'] ) ); ?></td></tr>
            <tr><th><?php echo esc_html( 'AtomChat' ); ?></th><td><?php echo esc_html( $status( $data['atomchat_active'] ) ); ?></td></tr>
            <tr><th><?php echo esc_html( 'Tun SSO / miniOrange' ); ?></th><td><?php echo esc_html( $status( $data['miniorange_active'] ) ); ?></td></tr>
        </tbody>
    </table>

    <h2><?php echo esc_html( 'Profile index sync' ); ?></h2>
    <p>
        <?php
        echo esc_html(
            $data['profile_sync_complete']
                ? 'Profile index backfill is complete.'
                : 'Next member offset: ' . (string) $data['profile_sync_offset']
        );
        ?>
    </p>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="asn_sync_profiles">
        <?php wp_nonce_field( 'asn_sync_profiles', 'asn_sync_profiles_nonce' ); ?>
        <button type="submit" class="button button-primary"><?php echo esc_html( 'Sync next 50 members' ); ?></button>
    </form>
</div>
