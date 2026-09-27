# ASN Core

ASN Core is the custom WordPress social-network application layer for ArmenianSocialNetwork.com.

## v0.2.2 candidate

Version 0.2 adds the first parallel member-facing replacements while keeping the current live social experience intact.

It provides:

- the `[asn_profile]` profile view for existing WordPress members
- secure edit-own-profile handling for approved existing fields
- a rebuildable/indexed `asn_profiles` search layer
- administrator batch backfill controls for the profile index
- the `[asn_explore]` member directory
- search plus dialect, proficiency, and country filters
- fixed 20-member pagination
- a transport-neutral message action backed by the existing AtomChat launcher
- responsive, `asn-` scoped front-end styles
- a repeatable WordPress-safe release ZIP builder

The new Profile and Explore shortcodes are intended for separate test pages first. v0.2 does **not** automatically replace `[tac_user_profile]` or `[tac_contacts]`.

It also does not change Feed, registration, login, billing, WooCommerce subscriptions/prices, AtomChat configuration, or Tun SSO / miniOrange.

## Data ownership

- WordPress remains the only user identity source.
- Existing profile fields remain in WordPress user data/user meta.
- `asn_profiles` is a rebuildable directory index, not a second user database.
- PMPro remains the access/entitlement layer.
- WooCommerce Subscriptions + WooPayments remain the billing source of truth.
- AtomChat remains live during v0.2.

## Development

```bash
composer install
composer lint
composer test
```

Build the manual WordPress package with PowerShell:

```powershell
pwsh -File scripts/build-release.ps1 -Version 0.2.2
```

The verified output is:

```
build/asn-core-v0.2.2.zip
```

The archive contains one top-level `asn-core/` folder and runtime files only.

Development work goes to `develop`. Only a tested and manually approved release is promoted to `main` and tagged.

See `docs/development.md` and `docs/release-checklist.md`.

## Security

Never commit API keys, database credentials, WordPress salts, access tokens, app passwords, or other service secrets.
