# Changelog

## 0.8.2 - Performance and infinite Explore loading

- Removes Better Messages private-thread lookup/creation from Explore and profile page rendering; the exact conversation is now resolved only after the member clicks Message.
- Replaces heavy full-profile hydration on Explore cards with the indexed directory row plus only the small amount of member data needed by the card.
- Batches blocked-member and saved-profile state for Explore instead of repeating the same lookups per card.
- Avoids repeated PMPro entitlement checks where one membership-level lookup is sufficient.
- Skips Suggested Members work on page 2+ requests used by infinite scrolling.
- Defers WooCommerce paid-plan variation resolution until the onboarding plan screen instead of running it on every registration step.
- Removes redundant profile-index rebuilds from onboarding and skips index work entirely for prompt-only saves.
- Replaces visible numbered Explore pagination with progressive infinite loading: 20 members initially, then another 20 automatically as the member approaches the lower part of the current results.
- Keeps server-rendered numbered pagination as a no-JavaScript/error fallback.
- Preserves PMPro/WooCommerce rules, Better Messages permission checks, AtomChat fallback, and the parallel test-page rollout.

## 0.8.1 - Explore discovery visibility and usability

- Makes the selected discovery features obvious on Explore instead of leaving most of them hidden behind conditional states.
- Adds an Explore tools bar with one-click access to Recently Active, Saved Profiles, Saved Searches, and Who Viewed Me.
- Keeps Advanced Filters expanded by default so age, gender, job title, I'm Here For, recently-active, and saved-profile filters are immediately visible.
- Shows the Saved Searches area even before the member has saved one, with clear guidance on how to create the first saved search.
- Shows the Suggested Members area on the unfiltered Explore view even when no suggestions are available yet, with a useful empty state.
- Shows I'm Here For interests directly on member cards when members have supplied them.
- Keeps all existing membership, billing, Better Messages, AtomChat fallback, and parallel test-page behavior unchanged.

## 0.8.0 - Elly shared Level 1 + Level 2 feature pack

- Adds Save / Favorite Profiles from Explore and member profiles, with a Saved Profiles Explore filter.
- Adds Who Viewed My Profile using ASN Core's existing profile-view table, with recent unique member viewers shown to the profile owner.
- Adds Advanced Search Filters for age range, gender, job title, "I'm Here For", recently active members, and saved profiles while keeping country, dialect, proficiency, and keyword search.
- Adds Recently Active member badges and filtering backed by a lightweight member activity timestamp.
- Adds Suggested Members using shared country, Armenian dialect/proficiency, "I'm Here For" interests, and recent activity signals.
- Adds Saved Searches so members can save, reuse, and delete useful Explore filter combinations.
- Implements Elly's default-free rule: after onboarding reaches the final profile stage, a member with no active ASN membership is automatically assigned PMPro Level 1. Existing Level 2 members are never downgraded.
- Extends the rebuildable profile index to schema 1.2.0 for discovery interests and recent activity.
- Keeps Pinned Conversations, Message Search, Favorite/Starred Messages, and Message Reactions inside Better Messages rather than duplicating chat data in ASN Core.
- No new paid service is required, and WooCommerce remains the billing source for later Level 2 upgrades.
- AtomChat remains installed during the parallel test phase.

## 0.7.0 - Elly Level 1 feature pack

- Adds the new "I'm Here For" profile field with Friendship, Armenian practice, Networking, Business connections, and Community choices.
- Shows a real profile-completion percentage on the member's own profile and a Resume Profile Setup action when profile cards are still incomplete.
- Adds conversation-starter actions beside completed profile answers so another member can open the correct Better Messages conversation directly from the topic.
- Adds Report Profile and Block Profile actions directly on member profiles.
- ASN profile blocks now prevent direct Better Messages access, message sending, audio calls, and video calls between the blocked pair.
- Adds an Unblock Profile action for the member who created the block.
- Adds a short-lived New Member badge to Explore and the member profile.
- Adds open profile-report visibility to ASN Core admin diagnostics so reports can be reviewed by administrators.
- Keeps the selected green features available without any additional paid service.
- Better Messages message reactions remain a Better Messages setting and are enabled separately in its Messaging settings; no paid add-on is required.
- No billing, paid Level 2 entitlement, WooCommerce subscription, or AtomChat cutover logic changed.

## 0.6.0 - Elly V2 member profile

- Rebuilds the parallel ASN member profile to match Elly's supplied profile layout.
- Adds the left identity card with member name and age, circular profile photo, username, job title, country, speaker level, and a full-width Message action.
- Groups onboarding answers into the five client profile sections: "What makes me, me.", "What gets me out of bed in the morning?", "What I’m planning next?", "How I became me.", and "What I can share."
- Adds the "A glimpse into my world." gallery using the four photo slots from the new onboarding flow.
- Keeps the owner Edit Profile path available while normal profile viewing is read-only.
- Removes the temporary "Test Better Messages" wording from the parallel profile and uses the client-facing "Message" label.
- The profile UI no longer displays member email addresses.
- No membership, billing, registration, Explore filtering, or Better Messages entitlement logic changed.

## 0.5.5 - Client Explore review adjustments

- The Explore country filter now recognizes common United States aliases such as `usa`, `US`, `U.S.A.`, and `United States` and returns members whose saved country is United States / United States of America.
- Explore member cards now span the full results width so the four-card row lines up with the filter panel above it.
- View Profile and Message actions now use equal-width, equal-height buttons on every member card instead of a large profile button with a tiny message icon.
- Responsive three-column, two-column, and one-column card layouts keep the same aligned proportions on smaller screens.
- No membership, registration, payment, profile-data, or messaging permission logic changed.

## 0.5.4 - Single-page profile cards and real completion score

- Combines all of Elly's profile prompt cards onto one onboarding page instead of sending the member through five separate prompt pages.
- Keeps the prompt sections and copy from the supplied UI, but saves all answers in one submission before the photo step.
- Profile completion is now based on how many profile cards actually contain answers. Clicking Next alone no longer increases the score.
- The profile reaches 100% only when all 17 profile cards are completed.
- The completion bar updates live while the member types and keeps the real score on the photo, membership, and completion screens.
- Adds Back navigation from the combined profile-card page, photo page, and membership page.
- Existing PMPro, WooCommerce, Better Messages, photo upload, and membership logic remains unchanged.

## 0.5.3 - WP Fastest Cache registration exclusion

- Explicitly calls WP Fastest Cache's current-page exclusion hook whenever the ASN registration/onboarding shortcode renders.
- Keeps the existing WordPress no-cache guard as a fallback.
- Prevents the clean `/asn-register-test/` URL from being cached again after the stale cache entry is cleared once.
- No registration fields, membership, payment, profile, or messaging behavior changed.

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
