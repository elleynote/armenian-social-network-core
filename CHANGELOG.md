# Changelog

## 0.2.2 - Profile parity patch

- Restored all 23 legacy profile-card prompts on the new ASN profile page.
- Empty profile-card answers now remain visible as `Not updated yet`, matching the legacy experience.
- Existing six legacy profile-card images are read from their verified `<field>_image` user-meta keys and displayed when present.
- Updated the new profile layout to a compact centered member summary with stacked legacy-style cards.
- Preserved the 0.2.1 privacy protection that prevents email-like display names from appearing publicly.


## 0.2.1 - Privacy patch

- Prevented email-like WordPress display names from being exposed on ASN public profiles.
- Prefer approved first/last profile names for public member display names, with a neutral member fallback when needed.
- Prevented email-like display names from being stored in the ASN profile search index.


## 0.2.0 - Profiles and Explore candidate

- Added approved profile-field contracts based on the active legacy implementation.
- Added profile index schema 1.1.0 with age, job title, registration date, dialect, and proficiency indexing.
- Added idempotent single-member and batch profile-index synchronization.
- Added public profile service with legacy profile-photo compatibility and safe fallbacks.
- Added parallel `[asn_profile]` view and secure edit-own-profile flow.
- Added administrator profile-index backfill controls in batches of 50.
- Added indexed `[asn_explore]` search, dialect/proficiency/country filters, and 20-member pagination.
- Added transport-neutral messaging action backed by the existing AtomChat runtime launcher.
- Added scoped responsive Profile/Explore styles.
- Added a portable PowerShell release builder that creates Linux-safe WordPress ZIP paths.
- Preserved live legacy Profile/Explore shortcodes, Feed, registration, login, billing, AtomChat configuration, and Tun SSO behavior.

## 0.1.0 - Foundation candidate

- Added ASN Core WordPress plugin bootstrap.
- Added safe activation/deactivation handling.
- Added versioned ASN database installer for profiles, connections, profile views, blocks, reports, and notifications.
- Added read-only existing-member/profile helpers.
- Added PMPro membership helpers and WooCommerce availability helpers.
- Added read-only administrator diagnostics screen.
- Added CI, coding standards, tests, and safety contract.
- Preserved all existing live Explore/Profile/Feed/registration, AtomChat, billing, and Tun SSO behavior.
