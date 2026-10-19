# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses
[Semantic Versioning](https://semver.org/). Mirror every entry into
`readme.txt` → `== Changelog ==`.

## [1.0.1] — 2026-10-19

The name: *afrit* is Icelandic for "copy" — the root of *öryggisafrit*,
"safety copy", the Icelandic word for a backup. The plugin Description and
readme.txt now say so, as in the sibling plugin Vokull Security Center.

### Security
- Strict allow-list for SQL statements during restore; manipulated dumps abort before the live database is touched.
- Custom backup directories are validated; protection files never overwrite foreign files; uninstall deletes only marked directories.
- FTP/FTPS over cURL with certificate verification, no plain-text fallback, optional key pinning.
- Migration keys can no longer reach internal addresses unless explicitly allowed; HTTPS required otherwise.
- Ceiling for key-derivation cost read from encrypted files.
- Stricter, multi-file-type exposure self-test.

### Added
- Files tab with a clickable folder tree: explicit include/exclude per folder, most specific rule wins; patterns and size limit moved here from General.
- Privacy policy (PRIVACY.md), linked from readme.txt.
- WordPress Playground is detected: remote uploads are skipped with a clear explanation instead of failing (Playground cannot send binary data).

### Changed
- FTP/FTPS destination uses cURL instead of the PHP ftp extension.
- Delete, Remove, Cancel and Revoke are real buttons; removing a destination asks for confirmation.
- Admin assets are cache-busted by file time while SCRIPT_DEBUG is on.

## [1.0.0] — 2026-10-19

### Added
- Backups of the whole installation: core, plugins, themes, uploads, other wp-content files, database.
- Incremental file backups (full + incremental chains); selectable database tables.
- Destinations: this server, FTP/FTPS, SFTP (host-key pinning), Dropbox, Microsoft OneDrive — simultaneously, resumable.
- Dropbox without a developer app: built-in app with PKCE and code paste; own app optional.
- Schedules for files and database; automatic backups before core, plugin and theme updates.
- Retention by count, GFS rotation, maximum age, and days per location with per-destination override.
- "Keep" marks: chosen backups (and the chains they depend on) are exempt from all automatic deletion.
- Restore of individual components and tables, search & replace (serialisation-safe), safety backup, atomic database swap.
- One-click migration with migration keys; import through shared storage or upload; table prefix rewriting.
- Encryption (XChaCha20-Poly1305, PBKDF2-SHA256).
- Backup directory protection with daily HTTP exposure self-test; authenticated downloads only.
- Optional plugin password; sealed credentials.
- Reports with full logs; e-mail reports.
- One-click setup; WP-CLI commands.
