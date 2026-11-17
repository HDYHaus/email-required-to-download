# Email Required to Download

Require an email address before visitors can download a file.

Version: 1.2.8
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.2.8
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

## Contributors

- HDY Haus
- MariaOJob

## Features

- Create downloadable offers in WordPress admin.
- Optionally show the offer title and description on the download form.
- Select or upload one or more files from the Media Library.
- Embed offers with `[email_required_download id="123"]`.
- Insert offers with the Email Required Download block and adjust display settings in the editor.
- Collect email, optional name, and required consent.
- Support immediate downloads or email-confirmed download links per offer.
- Choose public URL delivery or protected file delivery with direct protected uploads.
- Optionally force protected files to download instead of opening in the browser.
- Set optional per-offer button colors.
- Show helpful form hints for admins and visitors.
- Unlock multiple files from a single email submission.
- Return visitors to the submitted form after lead capture.
- Store leads locally in WordPress.
- Track submissions, unique emails, and downloads.
- Show confirmation email send status for leads.
- Resend a fresh, expiring download link from a lead's admin row.
- Reuse active download access when a visitor submits the same offer again.
- Validate ready links before showing download buttons.
- Browse leads in pages of 25, with row numbers and recorded consent status.
- Export lead and download data as CSV.

## Development

Install the plugin folder into:

```text
wp-content/plugins/email-required-to-download
```

Activate it from WordPress admin, then open **Email Downloads**.

## Protected download hosting

Protected files stay in `wp-content/erd-protected` (or the equivalent custom content folder). Apache and LiteSpeed can honor the plugin's `.htaccess` rules when permitted by the host. Nginx/OpenResty require hosting configuration. The plugin shows server detection, a site-specific rule, and verification steps under **Email Downloads → Settings → Advanced**. The **General** tab retains everyday settings, and the protected-delivery option links to Advanced in a new tab. Detection alone does not verify protection; proxies can hide the file-serving server. Have the host verify a real protected file returns **403 Forbidden** on direct access before storing confidential files. Media Library originals can remain public; use protected uploads for private files.

## Privacy

Email Required to Download stores submitted email addresses, optional names, consent details, hashed IP addresses, user agents, referrers, email delivery status, and download activity in the site's local WordPress database. The plugin does not send lead data to a third-party service by default. Site owners are responsible for describing their own download forms, consent language, retention practices, and email delivery provider in their privacy policy.

## Deactivation and deletion

Deactivation preserves plugin data. Deleting the plugin through WordPress permanently removes offers, leads, settings, and plugin-managed files in `erd-protected`, including direct protected uploads. Export leads and back up protected files first. Media Library originals are retained. This release targets single-site WordPress; network activation and network-wide deletion are not verified.

## Source and compatibility

The JavaScript in `assets/js` and `blocks/email-required-download` is the shipped, human-readable source; no build step or external runtime is needed. WordPress 6.3 or later is required because the editor block uses Block API version 3. Runtime testing has used WordPress 7.1.4; PHP 7.4 remains the declared minimum, with runtime verification on PHP 8.4.

## Release checks

Run `php tests/release-regressions.php` for the isolated file-lifecycle and CSV safety regressions. Run `python3 tools/build-release.py` to build the installable ZIP and verify its manifest. GitHub Actions checks PHP/JavaScript syntax, these regressions, and package reproducibility for pull requests and updates to `main`.

Future enhancements remain tracked in [GitHub issues](https://github.com/HDYHaus/email-required-to-download/issues), including optional per-file analytics. The current release tracks downloads per lead/offer.
