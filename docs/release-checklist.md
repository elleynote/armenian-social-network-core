# ASN Core Release Checklist

## Automated checks

- [ ] `composer lint` passes
- [ ] `composer test` passes
- [ ] GitHub Actions is green on `develop`
- [ ] plugin version and changelog agree
- [ ] no credentials or secrets are present in the diff

## WordPress candidate check

Activate ASN Core beside the existing system and confirm:

- [ ] ASN Core activates without a PHP fatal error
- [ ] all six ASN tables exist with the site's real table prefix
- [ ] ASN Core → diagnostics opens for an administrator
- [ ] WordPress user count is displayed
- [ ] PMPro detection is correct
- [ ] WooCommerce detection is correct
- [ ] WooCommerce Subscriptions detection is correct
- [ ] AtomChat is only reported; it is not changed
- [ ] Tun SSO / miniOrange is only reported; it is not changed

## Regression check before main/tag

- [ ] current Explore still works
- [ ] current Profile still works
- [ ] current Feed still works
- [ ] current registration still works
- [ ] current PMPro login still works
- [ ] WooCommerce membership checkout/subscription behavior is unchanged
- [ ] AtomChat still works
- [ ] Tun SSO / miniOrange behavior is unchanged

Only after these checks pass should `develop` be merged to `main` and tagged `v0.1.0`.
