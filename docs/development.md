# ASN Core Development Workflow

## Branches

- `develop` — active implementation and integration testing
- `main` — deployable, approved releases only

## Normal workflow

1. Build on `develop`.
2. Run `composer lint` and `composer test`.
3. GitHub Actions must be green.
4. Build the versioned ZIP:
   `pwsh -File scripts/build-release.ps1 -Version 0.2.0`
5. Verify the archive contains one `asn-core/` root and no development files.
6. Upload the ZIP manually through WordPress Admin and replace the existing ASN Core version.
7. Test the parallel Profile/Explore pages and regress the existing live site.
8. Only after approval, promote the candidate to `main` and create the semantic release tag.

Production is **not** connected to arbitrary `develop` commits.

## Parallel v0.2 test pages

Create unlinked WordPress pages containing:

```
[asn_profile]
```

and:

```
[asn_explore]
```

Recommended slugs:

- `/asn-profile-test/`
- `/asn-explore-test/`

Do not replace the live legacy shortcodes until the v0.2 test gate passes.

## Source-of-truth discipline

- WordPress users/user meta remain authoritative.
- `asn_profiles` is rebuildable index data.
- Profile edits write approved WordPress fields first, then refresh the index.
- Never edit generated release files as the source of truth.

## Secrets

Never commit service/API credentials, database credentials, WordPress salts, access tokens, SMTP/app passwords, private keys, or webhook secrets.
