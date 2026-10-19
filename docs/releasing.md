# Releasing

1. **Version** in three places that must agree (both the script and CI refuse
   otherwise): `Version:` header and `AFBM_VERSION` in
   `afrit-backup-manager.php`, `Stable tag:` in `readme.txt`. Bump
   `AFBM_DATA_VERSION` only when options or the schema need migrating
   (`Installer::maybe_migrate()`); bump `AFBM_FORMAT_VERSION` only when the
   on-disk backup format changes incompatibly.
2. **Changelog**: `CHANGELOG.md` (Keep a Changelog) and mirror into readme.txt
   `== Changelog ==`. Update `Tested up to`.
3. **QA**: PHPUnit, PHPCS, the end-to-end checklist in [testing](testing.md),
   Plugin Check on the built ZIP.
4. **Build** locally: `./package-plugin.sh [-o DIR] [--keep-stage]`.
   - allow-list staging (`afrit-backup-manager.php uninstall.php index.php readme.txt LICENSE.txt composer.json includes admin assets`)
   - `composer install --no-dev` in the stage (local Composer or `composer:2` in Docker)
   - prunes vendor tests/docs, `.po/.pot/.mo`, the 1024 px icon
   - fails on any dev file (`docker-compose`, `local_wp_*`, `db_data`, `tests`, `dev`, `docs`, …) or missing runtime dependency (phpseclib)
5. **Publish**: push to `main`. `auto-tag.yml` creates tag `v<version>` and a
   GitHub release with the same ZIP; `deploy-to-wordpress-org.yml` syncs trunk,
   the tag and `.wordpress-org/` assets (icons, banners, screenshots) to SVN
   (`SVN_USERNAME` / `SVN_PASSWORD` secrets; supports a dry run).

Store assets in `.wordpress-org/` are regenerated with `dev/screenshots/run.sh`.
