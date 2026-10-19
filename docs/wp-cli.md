# WP-CLI

All commands run jobs synchronously, without the web time budget.

```sh
wp afbm backup [--components=database,core,plugins,themes,uploads,content] [--type=full|auto|partial|database] [--label=<text>]
wp afbm list [--format=table|csv|json|yaml]
wp afbm restore <id> [--components=…] [--tables=a,b] [--replace='old=>new|old2=>new2'] [--passphrase=…] [--delete-extra] [--wp-config] [--yes]
wp afbm migrate <AFBM1-key>
wp afbm keep <id> [--remove]
wp afbm prune
wp afbm rescan
wp afbm status
```

Examples:

```sh
# Nightly from system cron instead of WP-Cron:
wp afbm backup --components=core,plugins,themes,uploads,content --type=auto
wp afbm backup --components=database --type=database

# Move a site:
wp afbm migrate AFBM1-eyJ1Ijoi…
wp afbm restore example-com_20261019-020000_f_3fa9c2d81b07 --replace='https://old.example=>https://new.example' --yes
```

`wp afbm keep` marks a backup (and thereby its chain) so retention never
deletes it; `wp afbm list` shows the mark in the `keep` column.

`wp afbm status` re-runs the exposure self-test and warns if backups are
downloadable over HTTP.
