# Legacy Profile Compatibility Contract

Source audited: active `tunapp-customizations.old.php` implementation used by the live Armenian Social Network profile/contact shortcodes.

## Profile photo

- Source type: WordPress user meta containing a URL.
- Meta key: `profile_pic`.
- Legacy fallback when empty: plugin placeholder image (`assets/images/no-pp.jpg`).
- ASN v0.2 may use WordPress avatar as an intermediate fallback before its own neutral placeholder, but must not migrate or rewrite the legacy value.

## Gender values

- `Male`
- `Female`
- `Non Binary`

The legacy `<select>` does not emit explicit `value` attributes for gender, so the submitted/stored values are the visible labels above.

## Spoken proficiency values

- Eastern Armenian - Beginner
- Eastern Armenian - Intermediate
- Eastern Armenian - Advanced
- Eastern Armenian - Fluent
- Western Armenian - Beginner
- Western Armenian - Intermediate
- Western Armenian - Advanced
- Western Armenian - Fluent

## Age / birth-date behavior

The active legacy registration stores a selected `dob_date` and also calculates whole years client-side into hidden user meta key `dob`. The legacy code does not define a minimum/maximum age bound. ASN v0.2 therefore uses a defensive numeric age range of 1 through 120 for the `age` field while preserving legacy `dob` data as read-only compatibility data.

## Messaging launcher

The live legacy profile/directory launches AtomChat/CometChat through the browser function `jqcc.cometchat.launch` and passes the selected WordPress user ID as `uid`.

No credentials belong in ASN Core. The launcher contract is only the public runtime function name and WordPress user ID target.
