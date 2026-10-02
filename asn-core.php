<?php
/**
 * Plugin Name: ASN Core
 * Plugin URI: https://armeniansocialnetwork.com/
 * Description: Core social-network foundation for Armenian Social Network.
 * Version: 0.8.1
 * Author: Imran Gul
 * Text Domain: asn-core
 */

defined( 'ABSPATH' ) || exit;

define( 'ASN_CORE_VERSION', '0.8.1' );
define( 'ASN_CORE_FILE', __FILE__ );
define( 'ASN_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'ASN_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once ASN_CORE_PATH . 'includes/class-asn-database.php';
require_once ASN_CORE_PATH . 'includes/class-asn-members.php';
require_once ASN_CORE_PATH . 'modules/profiles/class-asn-legacy-profile-contract.php';
require_once ASN_CORE_PATH . 'modules/profiles/class-asn-profile-fields.php';
require_once ASN_CORE_PATH . 'modules/profiles/class-asn-profile-index.php';
require_once ASN_CORE_PATH . 'modules/profiles/class-asn-profile-photo.php';
require_once ASN_CORE_PATH . 'modules/profiles/class-asn-profile-service.php';
require_once ASN_CORE_PATH . 'modules/features/class-asn-member-features.php';
require_once ASN_CORE_PATH . 'modules/features/class-asn-member-safety.php';
require_once ASN_CORE_PATH . 'modules/features/class-asn-member-discovery.php';
require_once ASN_CORE_PATH . 'modules/profiles/class-asn-profile-shortcode.php';
require_once ASN_CORE_PATH . 'modules/profiles/class-asn-profile-form.php';
require_once ASN_CORE_PATH . 'modules/directory/class-asn-directory-query.php';
require_once ASN_CORE_PATH . 'modules/directory/class-asn-directory-service.php';
require_once ASN_CORE_PATH . 'modules/directory/class-asn-directory-shortcode.php';
require_once ASN_CORE_PATH . 'modules/registration/class-asn-registration.php';
require_once ASN_CORE_PATH . 'modules/registration/class-asn-registration-shortcode.php';
require_once ASN_CORE_PATH . 'integrations/pmpro/class-asn-pmpro.php';
require_once ASN_CORE_PATH . 'integrations/atomchat/class-asn-atomchat.php';
require_once ASN_CORE_PATH . 'integrations/better-messages/class-asn-better-messages.php';
require_once ASN_CORE_PATH . 'includes/class-asn-messaging.php';
require_once ASN_CORE_PATH . 'integrations/woocommerce/class-asn-woocommerce.php';
require_once ASN_CORE_PATH . 'includes/class-asn-memberships.php';
require_once ASN_CORE_PATH . 'admin/class-asn-admin.php';
require_once ASN_CORE_PATH . 'admin/class-asn-profile-index-admin.php';
require_once ASN_CORE_PATH . 'includes/class-asn-plugin.php';
require_once ASN_CORE_PATH . 'includes/class-asn-activator.php';
require_once ASN_CORE_PATH . 'includes/class-asn-deactivator.php';

register_activation_hook( ASN_CORE_FILE, array( 'ASN\\Core\\Activator', 'activate' ) );
register_deactivation_hook( ASN_CORE_FILE, array( 'ASN\\Core\\Deactivator', 'deactivate' ) );

ASN\Core\Plugin::instance()->boot();
