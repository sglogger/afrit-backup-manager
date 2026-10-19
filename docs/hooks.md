# Hooks

## Actions

| Hook | Arguments | When |
|---|---|---|
| `afbm_loaded` | `Plugin $plugin` | after all components are registered |
| `afbm_job_finished` | `array $job, string $status` | after any job ended (`success`, `warning`, `failed`, `cancelled`) |
| `afbm_restored` | `Manifest $manifest, array $job` | after a successful restore |

## Filters

| Hook | Value | Purpose |
|---|---|---|
| `afbm_time_budget` | `int` seconds (5–300) | work per request |
| `afbm_exclude_patterns` | `string[]` fnmatch globs on archive paths | add/remove exclusions |
| `afbm_use_loopback` | `bool` | disable loopback requests on hosts where they hang |
| `afbm_backup_before_update` | `bool`, upgrader, hook_extra | skip the pre-update backup for some updates |
| `afbm_retention_delete` | `string[]` ids, `Manifest[]` sets | veto or extend retention deletions |
| `afbm_job_handlers` | `array<type, class>` | register a job type |
| `afbm_dropbox_app_key` | `string` | app key of the built-in Dropbox app (`''` = none; also settable with the `AFBM_DROPBOX_APP_KEY` constant) |

## Example: exclude a cache directory and run longer steps

```php
add_filter( 'afbm_exclude_patterns', static function ( array $patterns ): array {
    $patterns[] = 'content/uploads/my-cache/*';
    return $patterns;
} );

add_filter( 'afbm_time_budget', static fn() => 60 );
```

## Example: post to a chat webhook when a backup fails

```php
add_action( 'afbm_job_finished', static function ( array $job, string $status ): void {
    if ( 'backup' === $job['type'] && 'failed' === $status ) {
        wp_remote_post( MY_WEBHOOK_URL, [ 'body' => [ 'text' => 'Backup failed: ' . $job['error'] ] ] );
    }
}, 10, 2 );
```
