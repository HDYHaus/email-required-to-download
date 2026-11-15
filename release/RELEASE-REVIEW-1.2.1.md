# Email Required to Download 1.2.1 — release review

Reviewed 15 November 2026. This document is outside the installable ZIP.

## Release identity

- Plugin: Email Required to Download
- Folder and text domain: `email-required-to-download`
- Author: HDY Haus
- Plugin and author link: https://hdyhaus.com/wp-plugins/email-required-to-download
- WordPress.org contributors currently declared: `hdyhaus, mariaojob`
- Version and stable tag: 1.2.1
- Database version: 1.2.0 (no new migration)
- Minimum WordPress: 6.3 (the existing block uses Block API 3)
- Minimum PHP: 7.4
- Tested-up-to WordPress: 7.1
- License: GPL-2.0-or-later
- Source: https://github.com/HDYHaus/email-required-to-download

## Review changes

1. Keep a shared protected copy while another offer references it. Removing the last referencing offer removes the copy.
2. Escape potentially executable spreadsheet formulas in CSV text. Normal consent wording and dates remain unchanged.
3. Export in batches of 500 instead of loading up to 100,000 entries into memory. Offer filters still apply; the admin's selected page does not limit the export.
4. Correct the minimum WordPress version from 6.0 to 6.3 to match Block API 3.
5. Document deactivation, destructive plugin deletion, mailing-list behavior, hosting requirements, readable JavaScript source, and single-site scope.

## Data and delivery behavior

- Deactivation keeps data.
- WordPress plugin deletion removes the three plugin tables, two settings/version options, and files in `wp-content/erd-protected`, including direct protected uploads. It preserves original Media Library files.
- Offer deletion preserves lead records. Leads can therefore refer to a deleted offer.
- Public URL delivery is the default and does not make the underlying file private.
- Protected delivery requires server access rules. Server detection alone does not prove that files are protected; Media Library originals may remain public.
- All lead records stay in the WordPress database. Confirmation email uses the site's `wp_mail` delivery configuration.
- No remote telemetry, external JavaScript, bundled third-party runtime, or build step was found in the shipped code.
- This release targets single-site WordPress. Network activation and network-wide cleanup remain unverified.

## Verification

- PHP syntax: all shipped PHP files passed using PHP 8.4.4.
- JavaScript syntax: both shipped JavaScript files passed `node --check`.
- Standalone release regressions passed: shared-file deletion, path boundaries, CSV formula handling, ordinary consent/date values, uninstall tables/options/protected files, and preservation of original media.
- Local WordPress 7.1.4 integration tests passed for empty, 25-, 26-, and 1,000-entry lists; page boundaries, stable order, filters, totals, consent, and invalid page inputs.
- Actual filtered CSV export returned all 1,000 entries across multiple batches with consent wording and dates intact, despite page 2 being selected.
- The ZIP uses an explicit file allowlist, has one plugin root, and matches the reviewed source byte for byte. Tests, release notes, ZIPs, and the standalone HTML draft are excluded.
- Final ZIP installed on dev and Local. Dev Plugin Check completed with all categories and Error/Warning types selected: “Checks complete. No errors found.” Screenshot: `/private/tmp/erd-1.2.1-plugin-check.png`.

Runtime compatibility testing does not cover every combination of the declared PHP/WordPress minimums. Uninstall checks use an isolated database double and temporary files; the test sites' real lead data was not deleted.

## WordPress.org handoff

The installable artifact is `email-required-to-download-1.2.1.zip`. Its SHA-256 and per-file hashes are recorded in `manifest-1.2.1.json`.

Before submitting:

- Confirm which WordPress.org account should own the plugin and that the contributor usernames are correct.
- Choose the final display name and requested slug (currently the existing name and `email-required-to-download`). WordPress.org determines slug availability during review.
- Prepare listing screenshots using example data, avoiding real lead emails. Suggested views: front-end download form, offer editor, Leads & Analytics, General settings, and Advanced hosting guidance. Banner/icon artwork can be added to directory assets later.
- Submit the final ZIP through https://wordpress.org/plugins/developers/add/ and follow reviewer feedback. This review does not constitute WordPress.org approval; no WordPress.org submission was made. GitHub release publication is tracked separately.

References checked:

- https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
- https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/
- https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/
- https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/
