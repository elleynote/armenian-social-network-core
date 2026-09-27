# ASN Core

ASN Core is the custom WordPress social-network foundation for [ArmenianSocialNetwork.com](https://armeniansocialnetwork.com/).

## v0.1 scope

Version 0.1 is intentionally non-destructive. It installs beside the current TunApp Customizations and AtomChat setup and does **not** replace any live member-facing feature.

It provides:

- plugin bootstrap and versioning
- versioned ASN database tables
- read-only access to existing WordPress users/profile metadata
- PMPro free/premium access helpers
- WooCommerce/WooCommerce Subscriptions availability helpers
- a read-only **ASN Core** WordPress admin diagnostics screen
- automated foundation/safety tests

It does not change Explore, Profile, Feed, registration, login, billing, AtomChat, or Tun SSO/miniOrange.

## Runtime integrations

- WordPress is the user identity source.
- PMPro is the membership/entitlement layer.
- WooCommerce Subscriptions + WooPayments remain the billing layer.
- AtomChat remains live during the rebuild.
- Tun SSO / miniOrange remains unchanged during the rebuild.
- Better Messages will be integrated in a later phase after the foundation is validated.

All optional integrations are detected defensively so ASN Core can load without them.

## Installation

For development, deploy the repository as a WordPress plugin folder named `asn-core` and activate **ASN Core** from WordPress Admin → Plugins.

Activation creates the initial ASN tables using the site's actual WordPress table prefix. It does not import, delete, or bulk-update existing members.

## Development

```bash
composer install
composer lint
composer test
```

Development work goes to `develop`. Only tested/approved releases are merged to `main` and tagged.

See `docs/development.md` and `docs/release-checklist.md`.

## Security

Never commit API keys, database credentials, WordPress salts, access tokens, app passwords, or other service secrets to this repository.
