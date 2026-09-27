# ASN Core v0.2 Release Checklist

## Automated checks

- [ ] `composer lint` passes
- [ ] `composer test` passes
- [ ] GitHub Actions is green on `develop`
- [ ] `scripts/build-release.ps1 -Version 0.2.0` succeeds
- [ ] ZIP entries use forward slashes and one `asn-core/` root
- [ ] development-only paths are absent from the ZIP
- [ ] plugin version = 0.2.0 and database schema = 1.1.0
- [ ] no credentials or secrets are present in the diff

## WordPress candidate installation

- [ ] Upload `asn-core-v0.2.0.zip` through Plugins → Add New → Upload Plugin
- [ ] replace the current ASN Core plugin
- [ ] activation/update completes without a PHP fatal
- [ ] ASN Core diagnostics opens
- [ ] database schema reports 1.1.0
- [ ] PMPro/WooCommerce/Subscriptions/AtomChat/Tun SSO detection remains correct

## Profile index

- [ ] run administrator profile sync in 50-member batches
- [ ] all valid existing members can be indexed
- [ ] source WordPress profile data is unchanged by backfill
- [ ] failures are controlled and do not abort the full batch

## Parallel Profile test

- [ ] `[asn_profile]` displays existing member information
- [ ] current profile photo behavior/fallback is correct
- [ ] owner can edit approved fields
- [ ] another member cannot edit the profile
- [ ] invalid nonce/input is rejected
- [ ] no email/password/billing/admin data appears in rendered HTML
- [ ] mobile profile layout is usable

## Parallel Explore test

- [ ] `[asn_explore]` lists indexed members
- [ ] search works
- [ ] dialect filter works
- [ ] proficiency filter works
- [ ] country filter works
- [ ] combined filters work
- [ ] pagination uses 20 members per page
- [ ] profile links target the correct WordPress member
- [ ] AtomChat message action targets the selected member
- [ ] Explore remains usable when the AtomChat browser launcher is unavailable
- [ ] mobile Explore layout is usable

## Existing live-site regression

- [ ] current live Explore still works
- [ ] current live Profile still works
- [ ] current Feed still works
- [ ] current registration still works
- [ ] current PMPro login still works
- [ ] WooCommerce membership checkout/subscription behavior is unchanged
- [ ] AtomChat still works
- [ ] Tun SSO / miniOrange behavior is unchanged

Do not change the live Profile/Explore shortcodes until all applicable checks above pass.
