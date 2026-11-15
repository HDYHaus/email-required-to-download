=== Email Required to Download ===
Contributors: hdyhaus, mariaojob
Tags: downloads, lead capture, email, media
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.2.3
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
* Insert offers with the Email Required Download block.
* Collect email, optional name, and required consent.
* Support immediate downloads or email-confirmed download links per offer.
* Choose public URL delivery or protected file delivery with direct protected uploads.
* Optionally force protected files to download instead of opening in the browser.
* Set optional per-offer button colors.
* Show helpful form hints for admins and visitors.
* Unlock multiple files from a single email submission.
* Return visitors to the submitted form after lead capture.
* Store leads locally in WordPress.
* Track submissions, unique emails, and downloads.
* Browse leads in pages of 25, with row numbers and recorded consent status.
* Export lead and download data as CSV.

== Installation ==

1. Upload the `email-required-to-download` folder to `/wp-content/plugins/`.
2. Activate the plugin from the Plugins screen.
3. Open Email Downloads to create your first offer.

== Privacy ==

Email Required to Download stores submitted email addresses, optional names, consent details, hashed IP addresses, user agents, referrers, email delivery status, and download activity in the site's local WordPress database. The plugin does not send lead data to a third-party service by default. Site owners are responsible for describing their own download forms, consent language, retention practices, and email delivery provider in their privacy policy.

== Frequently Asked Questions ==

= Does protected delivery need hosting setup? =

Protected files are stored in `erd-protected` inside the WordPress content folder. Your server must block direct access to this folder. Apache and LiteSpeed can use the plugin's `.htaccess` file when permitted by the host. Nginx and OpenResty need a server configuration rule from your hosting provider.

Open Email Downloads > Settings > Advanced for server detection, a rule based on your site's content URL, and verification instructions. General settings stay focused on everyday options. Selecting Protected delivery on an offer shows a short link to Advanced settings. Server detection does not verify protection, especially behind proxies. Ask your host to confirm an actual protected file returns 403 Forbidden when opened directly before uploading confidential files. Media Library originals may remain publicly accessible; use protected uploads for files that must not be public.

= What happens when I deactivate or delete the plugin? =

Deactivation keeps offers, leads, settings, and protected files. Deleting the plugin through WordPress permanently removes its offers, leads, settings, and files in the plugin-managed `erd-protected` directory. Export your leads and back up protected uploads first. Original Media Library attachments are not deleted.

= Does this subscribe people to a mailing list? =

No. The plugin records the consent wording shown on the download form and delivers the requested download. It does not connect to a mailing list service or create a newsletter subscription. Choose consent wording appropriate to your own use of the collected data.

= Why does the plugin require WordPress 6.3? =

The editor block uses Block API version 3, introduced in WordPress 6.3.

= Is a build step required? =

No. All shipped JavaScript is readable source and uses WordPress-provided libraries. The ZIP installs directly.

= Is WordPress Multisite supported? =

This release is intended for single-site installations. Network activation and network-wide uninstall have not been verified.

== Changelog ==

= 1.2.3 =
* Link the author names to the plugin landing page and use the HDY Haus plugins directory for Visit plugin site.


= 1.2.2 =
* Display HDY Haus and Mary Mojisola Job as the plugin authors.
* Use maryjob.com for the author link and keep the plugin landing page separate for WordPress.org submission.


= 1.2.1 =
* Preserve protected files still referenced by another offer when deleting an offer.
* Export leads in batches and protect CSV text cells against spreadsheet formula execution.
* Align the minimum WordPress version with Block API version 3 and clarify deletion, consent, and hosting behavior.


= 1.2.0 =
* Add consent status, row numbers, and offer-aware pagination to Leads & Analytics.
* Add General and Advanced settings tabs with server-specific hosting setup instructions and a short protected-delivery hint.
* Keep file extensions when protected download labels differ from the original filename.
* Add a dynamic Email Required Download block with offer selection and display controls.
* Add protected delivery mode that copies Media Library files into plugin-managed storage that requires server access rules and streams them after token validation.
* Add optional force-download behavior for protected files.
* Show a visitor-facing error when confirmation email delivery fails.

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
