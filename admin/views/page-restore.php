<?php
/**
 * Restore one backup: choose parts, tables, search/replace, options.
 *
 * @package AfritBackupManager
 */

declare( strict_types = 1 );

namespace AfritBackupManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- selects what to display; the restore itself is a nonce-checked POST.
$afbm_id       = isset( $_GET['backup'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['backup'] ) ) : '';
$afbm_manifest = Catalog::get( $afbm_id );
?>
<div class="wrap afbm-wrap">
	<?php Admin::header( __( 'Restore', 'afrit-backup-manager' ) ); ?>

	<?php if ( null === $afbm_manifest ) : ?>
		<p><?php esc_html_e( 'Choose a backup on the Backups screen.', 'afrit-backup-manager' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Admin::MENU_BACKUPS ) ); ?>"><?php esc_html_e( 'Backups', 'afrit-backup-manager' ); ?></a></p>
	</div>
		<?php
		return;
	endif;

	$afbm_chain     = Catalog::chain_for( $afbm_id );
	$afbm_locations = Catalog::locations( $afbm_id );
	$afbm_migrating = untrailingslashit( $afbm_manifest->site( 'home' ) ) !== untrailingslashit( home_url() );

	// Pre-filled replacements when the backup comes from another address or path.
	$afbm_pairs = [];
	$afbm_add   = static function ( string $from, string $to ) use ( &$afbm_pairs ): void {
		$from = untrailingslashit( $from );
		$to   = untrailingslashit( $to );
		if ( '' !== $from && $from !== $to && ! in_array( [ $from, $to ], $afbm_pairs, true ) ) {
			$afbm_pairs[] = [ $from, $to ];
		}
	};
	$afbm_add( $afbm_manifest->site( 'home' ), home_url() );
	$afbm_add( $afbm_manifest->site( 'siteurl' ), site_url() );
	$afbm_add( $afbm_manifest->site( 'abspath' ), wp_normalize_path( ABSPATH ) );
	$afbm_add( $afbm_manifest->site( 'content_dir' ), wp_normalize_path( WP_CONTENT_DIR ) );
	?>

	<div class="afbm-card">
		<table class="widefat striped afbm-facts">
			<tbody>
				<tr><th><?php esc_html_e( 'Backup', 'afrit-backup-manager' ); ?></th><td><code><?php echo esc_html( $afbm_id ); ?></code></td></tr>
				<tr><th><?php esc_html_e( 'Created', 'afrit-backup-manager' ); ?></th><td><?php echo esc_html( Util::datetime( $afbm_manifest->created() ) ); ?> — <?php echo Admin::badge( Admin::type_label( $afbm_manifest->type() ), Admin::type_color( $afbm_manifest->type() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- badge() escapes. ?></td></tr>
				<tr><th><?php esc_html_e( 'Source site', 'afrit-backup-manager' ); ?></th><td><?php echo esc_html( $afbm_manifest->site( 'home' ) ); ?> (WordPress <?php echo esc_html( $afbm_manifest->site( 'wp_version' ) ); ?>, <?php esc_html_e( 'table prefix', 'afrit-backup-manager' ); ?> <code><?php echo esc_html( $afbm_manifest->site( 'table_prefix' ) ); ?></code>)</td></tr>
				<tr><th><?php esc_html_e( 'Size', 'afrit-backup-manager' ); ?></th><td><?php echo esc_html( Util::bytes( $afbm_manifest->total_size() ) ); ?></td></tr>
				<?php if ( Manifest::TYPE_INCREMENTAL === $afbm_manifest->type() ) : ?>
					<tr><th><?php esc_html_e( 'Chain', 'afrit-backup-manager' ); ?></th><td>
						<?php
						echo [] === $afbm_chain
							? '<strong style="color:#d63638;">' . esc_html__( 'Incomplete: an earlier backup of this chain is missing. Files cannot be restored; the database can.', 'afrit-backup-manager' ) . '</strong>'
							/* translators: %d: number of backups */
							: esc_html( sprintf( __( 'Uses %d backups (one full backup and the incrementals up to this one).', 'afrit-backup-manager' ), count( $afbm_chain ) ) );
						?>
					</td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<?php if ( $afbm_migrating ) : ?>
		<div class="notice notice-info inline"><p><?php esc_html_e( 'This backup comes from a different address. The addresses and paths below are replaced during the restore so the site works at its new location.', 'afrit-backup-manager' ); ?></p></div>
	<?php endif; ?>

	<?php Admin::form_open( 'start_restore', Admin::MENU_RESTORE, [ 'class' => 'afbm-restore-form' ] ); ?>
		<input type="hidden" name="backup_id" value="<?php echo esc_attr( $afbm_id ); ?>">

		<h2><?php esc_html_e( 'What to restore', 'afrit-backup-manager' ); ?></h2>
		<fieldset class="afbm-components">
			<?php foreach ( $afbm_manifest->components() as $afbm_component ) : ?>
				<label><input type="checkbox" name="components[]" value="<?php echo esc_attr( $afbm_component ); ?>" checked> <?php echo esc_html( Components::label( $afbm_component ) ); ?></label>
			<?php endforeach; ?>
		</fieldset>

		<?php if ( [] !== $afbm_manifest->tables() ) : ?>
			<details class="afbm-tables">
				<summary><?php esc_html_e( 'Choose database tables', 'afrit-backup-manager' ); ?> (<?php echo esc_html( number_format_i18n( count( $afbm_manifest->tables() ) ) ); ?>)</summary>
				<p><button type="button" class="button-link afbm-toggle-all" data-target=".afbm-table-list"><?php esc_html_e( 'Select all / none', 'afrit-backup-manager' ); ?></button></p>
				<div class="afbm-table-list">
					<?php foreach ( $afbm_manifest->tables() as $afbm_table_name ) : ?>
						<label><input type="checkbox" name="tables[]" value="<?php echo esc_attr( $afbm_table_name ); ?>" checked> <code><?php echo esc_html( $afbm_table_name ); ?></code></label>
					<?php endforeach; ?>
				</div>
			</details>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Search and replace', 'afrit-backup-manager' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Applied to the restored database, including serialised and JSON data. Use it to move the site to a new domain, a new path, or from http to https.', 'afrit-backup-manager' ); ?></p>
		<table class="widefat afbm-replace" id="afbm-replace">
			<thead><tr><th><?php esc_html_e( 'Search for', 'afrit-backup-manager' ); ?></th><th><?php esc_html_e( 'Replace with', 'afrit-backup-manager' ); ?></th><th></th></tr></thead>
			<tbody>
				<?php foreach ( array_merge( $afbm_pairs, [ [ '', '' ] ] ) as $afbm_pair ) : ?>
					<tr>
						<td><input type="text" name="replace_from[]" class="large-text code" value="<?php echo esc_attr( $afbm_pair[0] ); ?>"></td>
						<td><input type="text" name="replace_to[]" class="large-text code" value="<?php echo esc_attr( $afbm_pair[1] ); ?>"></td>
						<td><button type="button" class="button-link afbm-remove-row" aria-label="<?php esc_attr_e( 'Remove', 'afrit-backup-manager' ); ?>">×</button></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p><button type="button" class="button" id="afbm-add-row"><?php esc_html_e( 'Add a replacement', 'afrit-backup-manager' ); ?></button></p>

		<h2><?php esc_html_e( 'Options', 'afrit-backup-manager' ); ?></h2>
		<table class="form-table" role="presentation">
			<?php if ( $afbm_manifest->is_encrypted() ) : ?>
				<tr>
					<th scope="row"><label for="afbm-passphrase"><?php esc_html_e( 'Passphrase', 'afrit-backup-manager' ); ?></label></th>
					<td>
						<input type="password" id="afbm-passphrase" name="passphrase" class="regular-text" autocomplete="off">
						<p class="description"><?php esc_html_e( 'This backup is encrypted. Leave empty to use the passphrase stored on this site.', 'afrit-backup-manager' ); ?></p>
					</td>
				</tr>
			<?php endif; ?>
			<?php if ( count( $afbm_locations ) > 1 || ! in_array( Storage_Local::ID, $afbm_locations, true ) ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Restore from', 'afrit-backup-manager' ); ?></th>
					<td>
						<?php foreach ( array_values( array_diff( $afbm_locations, [ Storage_Local::ID ] ) ) as $afbm_i => $afbm_location ) : ?>
							<label><input type="radio" name="source" value="<?php echo esc_attr( $afbm_location ); ?>" <?php checked( 0, $afbm_i ); ?>> <?php echo esc_html( Destinations::name( $afbm_location ) ); ?></label><br>
						<?php endforeach; ?>
						<p class="description"><?php esc_html_e( 'Files already on this server are used directly; the rest is downloaded from here.', 'afrit-backup-manager' ); ?></p>
					</td>
				</tr>
			<?php endif; ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Safety', 'afrit-backup-manager' ); ?></th>
				<td>
					<label><input type="checkbox" name="safety" value="1" <?php checked( ! $afbm_migrating ); ?>> <?php esc_html_e( 'Back up the current state of the selected parts first', 'afrit-backup-manager' ); ?></label><br>
					<label><input type="checkbox" name="delete_extra" value="1"> <?php esc_html_e( 'Delete files that were not part of the backup (exact rollback of the selected parts)', 'afrit-backup-manager' ); ?></label><br>
					<label><input type="checkbox" name="wp_config" value="1"> <?php esc_html_e( 'Also restore wp-config.php', 'afrit-backup-manager' ); ?></label>
					<p class="description"><?php esc_html_e( 'wp-config.php holds this server\'s database credentials and is left alone unless ticked. This plugin\'s own files and settings are never overwritten by a restore.', 'afrit-backup-manager' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Restore now', 'afrit-backup-manager' ), 'primary', 'submit', true, Job_Runner::has_work() ? [ 'disabled' => 'disabled' ] : [] ); ?>
	</form>
</div>
