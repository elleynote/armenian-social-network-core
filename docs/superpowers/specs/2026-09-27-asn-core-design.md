# Armenian Social Network Core — Design Specification

**Date:** 2026-09-27  
**Repository:** `elleynote/armenian-social-network-core`  
**Project:** Armenian Social Network rebuild  
**Status:** Design ready for implementation planning

## 1. Goal

Build a new modular WordPress plugin named **ASN Core** that becomes the social-network application layer for ArmenianSocialNetwork.com while preserving the current WordPress users, memberships, billing, and working live experience during migration.

The rebuild must improve reliability, speed, maintainability, moderation, and future feature development while avoiding a second user database and avoiding risky all-at-once replacement.

## 2. Existing System We Are Preserving

The current site already has the core business systems in WordPress:

- WordPress is the main user and authentication system.
- Existing member/profile data is stored in WordPress user data and user meta.
- Paid Memberships Pro (PMPro) provides free/premium access rules.
- WooCommerce Subscriptions + WooPayments handle paid billing.
- Astra remains the active theme.
- WordPress Media remains the initial media storage system.
- miniOrange / Tun SSO remains unchanged during the rebuild unless the client separately approves changes later.
- AtomChat remains live until the replacement messaging layer has been tested and approved.

Current social functionality is mainly provided by the legacy TunApp Customizations plugin:
- `[tac_reg_form]` — registration
- `[tac_contacts]` — Explore/member directory
- `[tac_user_profile]` — profile
- `[tac_feeds]` — feed
- AtomChat integration for messaging, voice/video, presence, offline notifications, blocking/history, and chat UI

## 3. Product Direction

ASN Core will progressively replace the legacy social layer while keeping WordPress as the single source of truth for user identity.

The plugin will eventually provide:

- Member profiles
- Profile editing
- Explore/member directory
- Search and filters
- Premium/advanced filters
- Connections
- Profile views
- Extra profile photos
- Instagram/website links
- Feed
- Comments/reactions
- Blocking/reporting
- Moderation
- Notifications
- Membership-aware feature access
- Better Messages integration
- Admin tools for social-network management

## 4. Architecture

### 4.1 WordPress remains the application core

WordPress continues to own:
- User accounts and passwords
- User IDs
- Authentication
- Existing profile metadata
- PMPro membership access
- WooCommerce subscriptions and payments
- Media library
- Site administration

ASN Core uses the existing WordPress user ID as the identity key everywhere. No duplicate ASN user account system will be created.

### 4.2 ASN Core is modular

Planned plugin structure:

```
asn-core/
├── asn-core.php
├── README.md
├── CHANGELOG.md
├── uninstall.php
├── includes/
│   ├── class-asn-plugin.php
│   ├── class-asn-database.php
│   ├── class-asn-members.php
│   └── class-asn-memberships.php
├── modules/
│   ├── profiles/
│   ├── directory/
│   ├── connections/
│   ├── profile-views/
│   ├── feed/
│   ├── moderation/
│   └── notifications/
├── integrations/
│   ├── pmpro/
│   ├── woocommerce/
│   └── better-messages/
├── admin/
├── public/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── tests/
└── docs/
```

Each module must have one clear responsibility and must not directly depend on theme-specific template code.

## 5. Data Design

Existing simple profile fields continue to use WordPress user meta where practical.

High-volume or relational social data will use dedicated indexed ASN tables rather than being stored as large user-meta collections.

Planned tables include:

- `wp_asn_profiles` — normalized/indexed searchable profile data
- `wp_asn_connections` — connection requests and accepted relationships
- `wp_asn_profile_views` — profile-view events
- `wp_asn_blocks` — user blocks
- `wp_asn_reports` — profile/feed moderation reports
- `wp_asn_notifications` — ASN notification records

Additional feed/reaction tables may be introduced only when the feed module is implemented and only if native WordPress post/comment storage is insufficient.

The original WordPress user ID remains the foreign key for all ASN data.

## 6. Membership and Billing

### Billing source of truth
WooCommerce Subscriptions remains the billing source of truth.

### Access source of truth
PMPro remains the access/entitlement layer for free and premium users.

ASN Core will expose a single membership helper interface so feature modules do not contain scattered PMPro/WooCommerce logic.

Examples:
- Free user: standard profiles, Explore, messaging
- Premium user: premium filters, profile-view identities, extra profile capabilities, and other approved premium features

The current PMPro/WooCommerce displayed-price discrepancy will not be changed automatically. It must first be verified against the site's currency/conversion setup because the client indicated that one displayed price may be USD and the other may be AUD.

## 7. Messaging, Voice and Video

AtomChat stays active during development.

The planned replacement is **Better Messages WebSocket** because it integrates directly with WordPress users and avoids creating a separate chat identity database.

ASN Core will own the business rules around messaging access, while Better Messages owns realtime transport.

Initial production focus:
- Reliable one-to-one text chat
- Presence
- Typing/read state where supported
- Offline/web notifications
- Blocking/reporting integration

Voice/video support may remain technically available through Better Messages, but video should be hidden/disabled in the product interface by default unless the client later decides it is useful. The client indicated that video is currently rarely or never used.

ASN Core must isolate Better Messages behind an integration layer so it can be replaced later without rewriting profiles, Explore, memberships, or connections.

## 8. Registration and Login

### Login
Existing WordPress/PMPro login remains in place.

### Registration
The existing registration flow is currently powered by `[tac_reg_form]`.

The client has said she may request small signup-flow changes. Therefore:

- Phase 1 will not redesign registration.
- Existing signup behavior will be preserved during the foundation build.
- Registration will be migrated into ASN Core only after the client supplies/approves the desired signup-flow changes.

### Tun SSO / miniOrange
Tun SSO remains unchanged during the rebuild.

Its original purpose is to allow Tun users easier access to the network. It is not a dependency of ASN Core's architecture and is not part of the initial removal scope.

## 9. User Reconciliation

WordPress currently contains more users than AtomChat.

Before final AtomChat cutover:
- Compare WordPress members against AtomChat users.
- Identify spam/test/orphan accounts.
- Do not automatically create or delete users based only on the count mismatch.
- WordPress remains authoritative for valid member identity.
- The client will separately review whether mismatched accounts are spam.

## 10. Profiles and Explore

The current Explore path unnecessarily depends on AtomChat-related user-list logic even though most profile data already lives in WordPress.

ASN Core will query WordPress/ASN profile data directly.

Explore must support:
- Fast paginated member listing
- Search
- Dialect filter
- Proficiency filter
- Country/basic filters
- Premium filters added only when approved
- Mobile-first responsive member cards
- Direct profile navigation
- Messaging entry point

Profile pages must support:
- Existing profile data
- Main profile photo
- Approved existing profile-card fields
- Edit-own-profile behavior
- Additional photos in a later phase
- Instagram/website links in a later phase
- Privacy-safe public/private field separation

## 11. Feed

The legacy `[tac_feeds]` implementation will remain until the feed module is rebuilt.

The new feed must eventually support:
- Posts
- Photo posts
- Comments
- Reactions if approved
- Delete-own-content
- Report content
- Admin moderation
- Spam/rate controls

Feed implementation will be designed so it does not block Phase 1–3 delivery.

## 12. Moderation and Safety

ASN Core must support:
- Block user
- Report profile
- Report feed content
- Admin moderation queue
- Rate limiting where needed
- Sanitization/escaping of all user-generated content
- Nonce and capability checks for state-changing requests
- Privacy rules that prevent exposure of member email/billing/admin information

Message moderation/reporting should integrate with Better Messages rather than duplicating its realtime message storage.

## 13. Performance

Dynamic authenticated social pages must not depend on full-page caching.

Planned behavior:
- Logged-in member pages stay dynamic.
- Explore/Profile/Feed/Account/messaging routes will be explicitly protected from inappropriate full-page caching.
- Searchable profile data will use indexed fields/tables.
- Media uses lazy loading and appropriate image sizes.
- Expensive tasks should move to scheduled/background processing when required.
- Redis/CDN/object storage are optional later optimizations, not Phase 1 requirements.

## 14. Deployment and Source Control

GitHub is the canonical source for ASN Core.

Repository:
`elleynote/armenian-social-network-core`

Branch strategy:
- `main` — deployable/approved releases
- `develop` — integration/testing work
- feature branches — optional for larger isolated changes

Release strategy:
- Semantic versions such as `0.1.0`, `0.2.0`, `1.0.0`
- Version bump and changelog for releases
- Git tags/releases for rollback points

Deployment direction:
- Private GitHub repository
- WP Pusher as the preferred WordPress deployment mechanism
- Production should deploy approved `main` releases, not arbitrary development commits
- No routine live-file editing in WP File Manager after the GitHub workflow is established

## 15. Migration Strategy

Migration is progressive and reversible.

1. Build ASN Core beside the existing plugin.
2. Do not replace live social pages during the foundation phase.
3. Build/test new profiles and Explore.
4. Integrate and test Better Messages while AtomChat remains live.
5. Add connections and premium features.
6. Rebuild feed/moderation.
7. Reconcile users.
8. Switch individual live features only after testing.
9. Disable AtomChat only after messaging is approved.
10. Retire legacy shortcodes/snippets only after their replacement is confirmed.

No existing working social feature is removed before its replacement is available and tested.

## 16. Phase 1 Scope — ASN Core v0.1

The first implementation milestone is intentionally small and safe.

ASN Core v0.1 will:
- Install/activate beside the current system
- Define plugin constants/versioning
- Create the plugin bootstrap/container
- Add activation/deactivation handling
- Create initial ASN database tables safely and idempotently
- Add database version tracking
- Read existing WordPress users/profile metadata without changing them
- Provide centralized PMPro membership helpers
- Provide centralized WooCommerce integration helpers where needed
- Add an ASN admin diagnostics/status screen
- Add internal/test-only profile/member data services
- Add security and coding foundations
- Add automated tests for critical foundation behavior
- Add README/CHANGELOG/development documentation

ASN Core v0.1 will **not**:
- Replace Explore
- Replace Profile
- Replace Feed
- Change registration
- Change login
- Change billing
- Disable AtomChat
- Change Tun SSO
- Modify existing member data in bulk

## 17. Testing Requirements

Before any live feature cutover:
- Plugin activation/deactivation test
- Database migration/idempotency test
- Existing-user read test
- Free membership detection test
- Premium membership detection test
- Admin capability/security test
- Nonce validation tests for mutations
- Regression check that current Explore/Profile/Feed/registration/AtomChat still work
- PHP compatibility check against the current WordPress environment

Every later feature module must add its own focused tests.

## 18. Error Handling and Observability

ASN Core must fail safely when optional integrations are unavailable.

Examples:
- PMPro unavailable → admin warning; do not fatal
- WooCommerce unavailable → billing helpers return unavailable state
- Better Messages unavailable → social pages still load; messaging actions are disabled with a controlled message
- Database migration failure → record/log error and avoid partially switching live features

Sensitive credentials must never be committed to GitHub.

## 19. Definition of Success

The rebuild is successful when:
- WordPress remains the single user identity source.
- Existing members do not need new accounts.
- Billing/membership access remains connected.
- Explore/Profile/Feed no longer depend on the legacy TunApp social code.
- AtomChat can be disabled without losing the core community experience.
- New features can be added as isolated ASN Core modules.
- The code can be versioned, reviewed, deployed, and rolled back from GitHub.
- The client experiences a faster, more reliable, easier-to-maintain community platform.
