=== Email Required to Download ===
Contributors: hdyhaus, mariaojob
Tags: downloads, lead capture, email, media
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.1.3
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Require an email address before visitors can download a file.

== Description ==

Email Required to Download lets site owners create gated download offers, collect email addresses with consent, and track lightweight download analytics locally in WordPress.

== Features ==

* Create downloadable offers in WordPress admin.
* Optionally show the offer title and description on the download form.
* Select or upload one or more files from the Media Library.
* Embed offers with `[email_required_download id="123"]`.
* Collect email, optional name, and required consent.
* Support immediate downloads or email-confirmed download links per offer.
* Set optional per-offer button colors.
* Show helpful form hints for admins and visitors.
* Unlock multiple files from a single email submission.
* Return visitors to the submitted form after lead capture.
* Store leads locally in WordPress.
* Track submissions, unique emails, and downloads.
* Export lead and download data as CSV.

== Installation ==

1. Upload the `email-required-to-download` folder to `/wp-content/plugins/`.
2. Activate the plugin from the Plugins screen.
3. Open Email Downloads to create your first offer.

== Changelog ==

= 1.1.3 =
* Add optional per-offer button background and text colors.
* Add admin field hints and visitor-facing confirmation link expiry hints.
* Validate ready links before showing download buttons and count only the first download per lead.
* Return lead capture redirects to the submitted shortcode.

= 1.1.2 =
* Refresh admin asset version for field styling updates.

= 1.1.1 =
* Replace the separate public download title field with an option to show the offer title on the form.

= 1.1.0 =
* Add support for multiple files per gated download offer.
* Add optional public descriptions for download forms.
* Keep download tracking at the offer/lead level for lightweight bundled offers.
* Show confirmation email send status in Leads & Analytics and CSV exports.

= 1.0.2 =
* Add WordPress.org-compatible license and readme metadata.
* Add Plugin Check testing support.

= 1.0.1 =
* Add plugin row Settings link and contributor display metadata.

= 1.0.0 =
* Initial release.
