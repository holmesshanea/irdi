# Member privacy and account records

Updated 23 September 2026. This document describes the supplied implementation. It is not a promise of indefinite retention or a determination of legal retention requirements.

## Current product policy

Self-service account closure, account deletion and Member Profile deletion are unavailable. The former account-deletion route, profile DELETE route and retained Livewire deleteAccount action reject deletion. The previous 12-month close/restore proposal and Hide Profile package are superseded.

Members with a profile can use Account → Personal Information Privacy → Hide My Personal Information. This setting retains the same account/profile identity, membership history, reviews and enforcement. It is reversible and is not data erasure or anonymity. Separate personal-information requests and alias-change requests go through Contact for staff review.

## Public behavior

| Surface | Privacy enabled |
| --- | --- |
| Directory and public profile | Display public username instead of profile name; suppress personal name, photograph, location, biography and website. |
| Directory searches and sorting | Hidden names and locations cannot be used to identify the profile through search/filters; name sorting uses the username. |
| Member card and profile metadata | Use the public username and omit hidden personal fields. |
| Published rating summaries | Remain subject to existing review moderation, membership-status rules and minimum-review threshold. |
| Country statistics | Exclude privacy-enabled locations; percentages use the remaining shared countries. Overall active-member totals still include those members. |
| Messages | Existing conversations remain readable; identity labels use the public alias. New incoming messages and replies are blocked. The member may still choose to send messages. |
| Review invitations | Creating/resending invitations is blocked. Unused invitations are cancelled and verification secrets cleared. Existing completed reviews remain. Cancelled invitations stay cancelled after privacy is disabled. |
| Owner and staff views | Original account/profile information remains available for administration. Archived images have an authenticated owner/staff viewing route. |

Directory visibility is a separate saved preference. When privacy is enabled, active profiles remain discoverable by alias even if directory_visible is false. When privacy is disabled, the original preference applies again. With privacy off and directory visibility off, ordinary public visitors receive 404 for the profile/card; the owner can preview them if membership status permits. This is existing behavior, not a new privacy rule.

Public profiles/cards still require active membership under the supplied code. Privacy does not override bans, suspensions, staff roles or the rules determining good standing. Active membership is not synonymous with good standing.

The current public profile shows aggregate feedback, not individual review comments. Original review comments, private concerns, and message bodies are not automatically rewritten by privacy. Any separate redaction workflow requires its own review.

## Images and storage

Enabling privacy archives the current photo as Laravel-encrypted data and removes the original public object. Disabling privacy decrypts the archive and writes a new public image URL. Preserve APP_KEY and any required previous decryption keys with secure backups; retain the archive objects alongside the database. Losing keys prevents image recovery. Moving profile-image disks requires moving both images and archives.

The authenticated private-image route enforces owner or moderator/administrator access and returns non-cacheable content. Public profile/directory/statistics responses use no-store headers for future requests. Previously delivered emails, downloaded images, existing browser pages and external caches cannot be recalled. Production CDN invalidation, object-version retention and backups are deployment responsibilities; deleting the origin object does not invalidate every copy.

The service attempts storage compensation if a database transaction fails. A second storage failure can require staff recovery from an encrypted archive. Do not describe database and object-storage work as a single atomic operation.

## Text and translations

Shared privacy copy is in lang/{en,es,fr,de,it,nl}/privacy.php. The same keys serve Account settings, FAQ, Member Manual, Privacy Policy, Terms, directory guidance, statistics and message-notification wording. Update every supported locale when changing those statements. Keep placeholders and meaning consistent.

No self-service deletion claim should be reintroduced. Do not promise all personal information disappears everywhere: the public alias remains, original account records remain, and previously shared content may persist. Do not describe the privacy setting as closing a membership or removing a negative rating.

## Verification

The user previously reported 417 passing tests (2,410 assertions) before this wording-only update. That result does not verify this update. In the local project run:

```powershell
php artisan optimize:clear
composer lint
composer ci:check
```

Check all six languages on Account settings, FAQ, Member Manual section 12, Privacy Policy, Terms, Member Directory and Statistics. Check links and narrow-screen wrapping. The message notification should say reply availability depends on privacy and messaging settings.

This wording update changes no migrations, authorization rules, storage logic, membership statuses, directory queries or privacy behavior.
