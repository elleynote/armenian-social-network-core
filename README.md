# ASN Core

ASN Core is the custom WordPress social-network application layer for ArmenianSocialNetwork.com.

## v0.5.2 candidate

Version 0.3 adds a parallel registration/onboarding path while keeping the current live registration and billing flows intact.

It provides:

- the `[asn_profile]` profile view for existing WordPress members
- secure edit-own-profile handling for approved existing fields
- a rebuildable/indexed `asn_profiles` search layer
- administrator batch backfill controls for the profile index
- the `[asn_explore]` member directory
- search plus dialect, proficiency, and country filters
- fixed 20-member pagination
- a transport-neutral AtomChat message action
- a parallel Better Messages test action using the same WordPress user IDs
- PMPro-backed Better Messages text-chat gating for levels 1 and 2
- WebSocket live-call gating: Level 1 and Level 2 can use audio calls; video calls require Level 2 for every participant
- a parallel `[asn_register]` signup/onboarding flow for testing account creation, profile details, plan selection, and automatic PMPro Level 1 assignment
- responsive, `asn-` scoped front-end styles
- a repeatable WordPress-safe release ZIP builder

The new Profile, Explore, and Registration shortcodes are intended for separate test pages first. v0.3 does **not** automatically replace `[tac_user_profile]`, `[tac_contacts]`, or the live `[tac_reg_form]` registration page.

The Better Messages action is intentionally labeled as a test action. AtomChat remains the current production transport until Better Messages passes live testing and a controlled transport cutover is approved.

It does not change the live registration page, Feed, login, WooCommerce subscription prices/payment records, AtomChat configuration, or Tun SSO / miniOrange. The paid signup choice continues into the existing WooCommerce upgrade flow.

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
pwsh -File scripts/build-release.ps1 -Version 0.5.2
```

The verified output is:

```
build/asn-core-v0.5.2.zip
```

The archive contains one top-level `asn-core/` folder and runtime files only.

Development work goes to `develop`. Only a tested and manually approved release is promoted to `main` and tagged.

See `docs/development.md` and `docs/release-checklist.md`.

## Security

Never commit API keys, database credentials, WordPress salts, access tokens, app passwords, or other service secrets.
