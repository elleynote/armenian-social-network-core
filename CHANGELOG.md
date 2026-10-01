# Changelog

## 0.5.2 - Registration cache guard

- Prevents the dynamic ASN registration/onboarding shortcode from being page-cached.
- Sends no-cache headers and marks registration pages with the standard WordPress `DONOTCACHEPAGE` flag.
- This prevents logged-out visitors from being served an older cached registration screen after UI updates.
- No registration fields, membership, payment, profile, or messaging logic changed.

## 0.5.1 - Elly V2 registration and Explore polish

- Restyled the guest account-entry screen so it uses the same two-column Elly V2 onboarding layout, soft-gray panels, pill inputs, blue Next action, 0% profile-completion sidebar, and help card as the supplied design.
- Updated Explore filters to the Elly V2 navy / soft-gray / coral palette with rounded pill controls and a coral Search button.
- Reworked Explore member cards to match the supplied page 9 direction: soft-gray rounded cards, circular portraits, compact member details, and coral View Profile / chat actions.
- Updated Explore pagination so current/hover states use the coral client accent instead of the old blue treatment.
- No registration, membership, payment, profile-data, or messaging entitlement logic changed in this release.

## 0.5.0 - Elly V2 multi-step onboarding

- Implements the supplied Illustrator onboarding sequence on the parallel ASN registration page with the exact client headings, prompt groups, progress values, photo step, completion screen, sidebar preview, and help card.
- The basic profile screen now follows the client field set: first name, last name, public display name, email, country, age, gender, job title, and spoken proficiency.
- Profile answers are saved between steps using the existing ASN profile fields so they appear on member profiles later.
- Adds the four photo slots shown in the supplied design and stores successful WordPress media uploads against the matching profile prompts.
- Free and paid membership logic is preserved. After membership activation, members now see the 100% "All done!" client screen before continuing automatically to ASN Explore.
- The Better Messages floating interface is suppressed while the onboarding UI is open so it does not cover the client design.
- Explore cards remain on the compact client V2 treatment introduced in 0.4.0.

## 0.4.0 - Client UI V2 foundation

- Added the first client-design visual pass for the parallel registration experience using the new two-column onboarding layout, profile-completion sidebar, progress treatment, and updated form styling.
- Updated the parallel Explore member cards to the new client direction with image-first cards, name and age, public username, job title, country, speaker level, View Profile action, and compact chat action.
- Better Messages is now the primary chat action on the parallel ASN Explore cards when available; the legacy message action remains as fallback while live AtomChat stays untouched.
- No membership, payment, subscription, or live registration logic was replaced in this release.
- This release is the UI foundation only; the remaining client onboarding screens and richer profile-completion steps will be layered onto this structure next.

## 0.3.9 - Faster signup step transition

- Moved WordPress new-user notification email sending out of the synchronous registration request and into a short-lived WP-Cron event.
- Account creation now redirects to the profile step immediately instead of waiting for the site's outbound mail transport.
- The same admin/user new-account notifications are still sent asynchronously.
- No signup fields, PMPro entitlements, WooCommerce billing, or legacy registration behavior were changed.

## 0.3.8 - Explore active members only

- The ASN Explore directory now includes only users with an active PMPro Level 1 or Level 2 membership.
- Legacy WordPress profiles without an active ASN chat membership are excluded from Explore results and pagination.
- This keeps Explore aligned with the Better Messages entitlement rules while AtomChat remains installed as the fallback transport during the remaining cutover work.

## 0.3.7 - Paid checkout success redirect

- Successful paid orders containing WooCommerce product 152 now return the member directly to `/asn-explore-test/` instead of the WooCommerce order-received page.
- The redirect only applies to paid orders for the logged-in customer and leaves other WooCommerce orders unchanged.
- Billing, subscription creation, WooPayments processing, and PMPro Level 2 mapping remain owned by the existing WooCommerce/PMPro setup.

## 0.3.6 - Monthly Level 2 checkout selection

- Product 152 currently offers Monthly and Annually subscription variations.
- The ASN Level 2 paid signup now automatically selects the Monthly variation, matching the current monthly signup offer, and sends the member directly to WooCommerce checkout.
- If the variation setup becomes ambiguous in the future, ASN Core falls back to the product page instead of guessing.
- WooCommerce Subscriptions and WooPayments remain the billing source of truth.

## 0.3.5 - Paid subscription variation auto-selection

- Detects WooCommerce product 152 at runtime when the Level 2 paid signup button is rendered.
- If product 152 has exactly one available purchasable variation, ASN Core automatically includes that variation and its attributes in the checkout URL so the member does not have to choose product options manually.
- If multiple paid variations exist, the member is sent to the product page instead of an invalid empty-cart checkout.
- WooCommerce Subscriptions and WooPayments remain the billing source of truth.

## 0.3.4 - Paid signup checkout routing

- Changed the parallel Level 2 paid signup button from the missing `/upgrade/` page to the site's existing WooCommerce subscription checkout for product 152.
- The paid path now uses `/checkout/?add-to-cart=152&quantity=1`, matching the existing TunApp paid-plan flow.
- WooCommerce Subscriptions and WooPayments remain the billing source of truth; ASN Core does not create billing records itself.

## 0.3.3 - Legacy profile metadata compatibility

- New ASN registrations now save the legacy `dob_date` and `dob` user-meta keys in addition to ASN profile data.
- This prevents the still-active TunApp profile-completion guard from forcing newly registered Level 1 members back to `/register/?step=2`.
- The live legacy plugin remains untouched.

## 0.3.2 - Robust free signup redirect

- Added a short-lived onboarding redirect marker before PMPro Level 1 assignment.
- If legacy code sends the new free member to `/register/?step=2`, ASN Core now catches that next request and redirects directly to the configured ASN Explore page.
- The redirect only applies to the user who just completed the ASN free onboarding flow and expires automatically.

## 0.3.1 - Free plan Explore redirect fix

- Scoped the parallel free-plan signup request so any legacy redirect to `/register/?step=2` is rewritten to the configured ASN Explore test page.
- Kept PMPro Level 1 assignment as the entitlement source and left the live legacy registration page unchanged.

## 0.3.0 - Parallel registration and Level 1 onboarding

- Added a parallel `[asn_register]` flow for testing signup without replacing the live legacy `[tac_reg_form]` page.
- Added account creation, core profile details, plan selection, and automatic PMPro Level 1 assignment for the free path.
- Free members are redirected to the parallel ASN Explore page after Level 1 activation so text and live voice entitlements are immediately available.
- The paid choice continues through the existing WooCommerce upgrade/subscription flow; ASN Core does not create or modify paid billing records.
- Kept the legacy registration page, AtomChat, WooCommerce billing, PMPro paid mapping, and Tun SSO unchanged during parallel testing.

## 0.2.9 - Better Messages WebSocket call gating

- Added Better Messages WebSocket audio-call permission checks backed by PMPro: Level 1 and Level 2 members can use live audio calls.
- Added video-call permission checks so every participant in a video call must have PMPro Level 2.
- Added server-side create/join errors so call permissions cannot be bypassed by opening Better Messages directly.
- Kept recorded voice/video messages disabled and left AtomChat active during parallel testing.

## 0.2.8 - Better Messages membership gating

- Added explicit ASN chat entitlements for the current PMPro plan contract: levels 1 and 2 can use text/voice, while video is premium level 2 only.
- Better Messages test actions now require both the current member and the target member to have an active chat-enabled PMPro level.
- Added a Better Messages send-permission filter so users without a chat-enabled membership cannot bypass ASN Core by opening the Better Messages page directly.
- Kept AtomChat behavior unchanged during the parallel test phase.


## 0.2.7 - Better Messages direct-thread fix

- Replaced the initial Better Messages conversation-link shortcut with an explicit private-thread lookup/create flow.
- The ASN test action now resolves the exact private thread first, then generates the current user's inbox URL for that thread.
- This prevents the test action from landing on the generic `/messages/` inbox without selecting the intended member conversation.
- AtomChat remains unchanged and active in parallel.


## 0.2.6 - Parallel Better Messages evaluation

- Added a Better Messages adapter behind the ASN messaging boundary using the plugin's public conversation-link API.
- Added a separate `Test Better Messages` action on the parallel ASN Explore and Profile views while keeping the existing AtomChat Message action unchanged.
- Kept WordPress user IDs as the member identity used for the Better Messages test link.
- Kept AtomChat active and untouched so Better Messages can be verified safely in parallel before any transport cutover.

## 0.2.5 - AJAX Explore

- Made the Explore Search button explicitly clickable and handled by the ASN directory controller.
- Added AJAX search/filter submission so results update in place without a full page reload.
- Added AJAX pagination while keeping normal GET-form behavior as a fallback when JavaScript is unavailable.
- Added loading and live-result states for clearer interaction feedback.


## 0.2.4 - Explore filter form fix

- Fixed the Explore filter form so Search submits GET parameters explicitly to the current Explore page.
- Preserved the working server-side search, country, dialect, proficiency, and pagination filtering logic.


## 0.2.3 - Profile editing patch

- Added the member email as the second profile-summary row for logged-in members.
- Reordered the profile summary to name, email, age, gender, job title, spoken proficiency, then country.
- Made all 23 profile cards editable inline for the profile owner using the existing secure profile update flow.
- Kept profile cards read-only for other members while preserving saved card text and legacy card images.


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
