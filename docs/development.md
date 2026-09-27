# ASN Core Development Workflow

## Branches

- `develop` — active implementation and integration testing
- `main` — deployable, approved releases only

Use feature branches later when a change is large enough to need isolated review.

## Normal workflow

1. Build and test on `develop`.
2. Run `composer lint` and `composer test`.
3. Confirm GitHub Actions is green.
4. Test the candidate in WordPress without replacing current live features.
5. Merge the approved candidate to `main`.
6. Tag a semantic version such as `v0.1.0`.

## Production discipline

Once GitHub deployment is connected, do not routinely edit ASN Core directly in WP File Manager. GitHub is the source of truth so every change remains reviewable and reversible.

Do not connect production to `develop`. Production should track approved `main` releases.

## Secrets

Never commit service/API credentials, database credentials, WordPress salts, access tokens, SMTP/app passwords, private keys, or webhook secrets.
