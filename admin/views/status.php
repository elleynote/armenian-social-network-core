<?php
defined( 'ABSPATH' ) || exit;

$status = static function ( $available ): string {
    return $available ? 'Available' : 'Unavailable';
};
?>
<div class="wrap">
    <h1><?php echo esc_html( 'ASN Core' ); ?></h1>
    <p><?php echo esc_html( 'Read-only foundation diagnostics. This screen does not change member, billing, chat, or SSO data.' ); ?></p>

    <table class="widefat striped" style="max-width: 900px;">
        <tbody>
            <tr><th><?php echo esc_html( 'ASN Core version' ); ?></th><td><?php echo esc_html( $data['version'] ); ?></td></tr>
            <tr><th><?php echo esc_html( 'ASN database schema' ); ?></th><td><?php echo esc_html( $data['database_version'] ?: 'Not installed' ); ?></td></tr>
            <tr><th><?php echo esc_html( 'WordPress users' ); ?></th><td><?php echo esc_html( (string) $data['wordpress_users'] ); ?></td></tr>
            <tr><th><?php echo esc_html( 'PMPro' ); ?></th><td><?php echo esc_html( $status( $data['pmpro_available'] ) ); ?></td></tr>
            <tr><th><?php echo esc_html( 'Premium access integration' ); ?></th><td><?php echo esc_html( $status( $data['premium_access_available'] ) ); ?></td></tr>
            <tr><th><?php echo esc_html( 'WooCommerce' ); ?></th><td><?php echo esc_html( $status( $data['woocommerce_available'] ) ); ?></td></tr>
            <tr><th><?php echo esc_html( 'WooCommerce Subscriptions' ); ?></th><td><?php echo esc_html( $status( $data['subscriptions_available'] ) ); ?></td></tr>
            <tr><th><?php echo esc_html( 'AtomChat' ); ?></th><td><?php echo esc_html( $status( $data['atomchat_active'] ) ); ?></td></tr>
            <tr><th><?php echo esc_html( 'Tun SSO / miniOrange' ); ?></th><td><?php echo esc_html( $status( $data['miniorange_active'] ) ); ?></td></tr>
        </tbody>
    </table>
</div>
