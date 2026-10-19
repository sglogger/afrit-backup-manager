<?php
/**
 * Migration: move or copy this site to another domain or host.
 *
 * @package AfritBackupManager
 */

declare( strict_types = 1 );

namespace AfritBackupManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$afbm_new_key = get_transient( 'afbm_new_key_' . get_current_user_id() );
if ( is_string( $afbm_new_key ) ) {
	delete_transient( 'afbm_new_key_' . get_current_user_id() );
	$afbm_new_key = Secret_Cipher::decrypt( $afbm_new_key );
}

$afbm_packages = array_values(
	array_filter(
		Catalog::all(),
		static fn( Manifest $m ) => $m->is_complete() && Catalog::local_files_present( $m ) && $m->site( 'home' ) === home_url()
	)
);
$afbm_keys     = Migration::keys();
?>
<div class="wrap afbm-wrap">
	<?php Admin::header( __( 'Migration', 'afrit-backup-manager' ) ); ?>

	<p class="description"><?php esc_html_e( 'Move or copy this site to a new domain or host — completely, or only selected parts such as the uploads or the database. Install Afrit Backup Manager on the target site, then follow the steps below.', 'afrit-backup-manager' ); ?></p>

	<?php if ( is_string( $afbm_new_key ) && '' !== $afbm_new_key ) : ?>
		<div class="afbm-card afbm-key">
			<h2><?php esc_html_e( 'Your migration key', 'afrit-backup-manager' ); ?></h2>
			<p><?php esc_html_e( 'Paste it into Migration → Import on the target site within 24 hours. Anyone holding this key can download this backup, so treat it like a password.', 'afrit-backup-manager' ); ?></p>
			<textarea class="large-text code" rows="3" readonly id="afbm-key-text"><?php echo esc_textarea( $afbm_new_key ); ?></textarea>
			<p><button type="button" class="button afbm-copy" data-target="#afbm-key-text"><?php esc_html_e( 'Copy', 'afrit-backup-manager' ); ?></button></p>
		</div>
	<?php endif; ?>

	<div class="afbm-columns">
		<div class="afbm-card">
			<h2><span class="afbm-step">1</span> <?php esc_html_e( 'Export from this site', 'afrit-backup-manager' ); ?></h2>
			<p><?php esc_html_e( 'Create a complete backup (files and database), then a migration key for it.', 'afrit-backup-manager' ); ?></p>
			<?php Admin::form_open( 'migration_backup', Admin::MENU_MIGRATION ); ?>
				<?php submit_button( __( 'Create migration package', 'afrit-backup-manager' ), 'secondary', 'submit', false, Job_Runner::has_work() ? [ 'disabled' => 'disabled' ] : [] ); ?>
			</form>

			<?php if ( [] !== $afbm_packages ) : ?>
				<h3><?php esc_html_e( 'Create a key for a backup', 'afrit-backup-manager' ); ?></h3>
				<?php Admin::form_open( 'create_key', Admin::MENU_MIGRATION ); ?>
					<select name="backup_id" class="afbm-wide">
						<?php foreach ( $afbm_packages as $afbm_package ) : ?>
							<option value="<?php echo esc_attr( $afbm_package->id() ); ?>">
								<?php
								echo esc_html(
									sprintf(
										'%s — %s — %s — %s',
										Util::datetime( $afbm_package->created() ),
										Admin::type_label( $afbm_package->type() ),
										implode( ', ', array_map( [ Components::class, 'short_label' ], $afbm_package->components() ) ),
										Util::bytes( $afbm_package->total_size() )
									)
								);
								?>
							</option>
						<?php endforeach; ?>
					</select>
					<?php submit_button( __( 'Create migration key', 'afrit-backup-manager' ), 'primary', 'submit', false ); ?>
				</form>
			<?php endif; ?>

			<p class="afbm-muted">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: number of active keys */
						_n( '%d migration key is active.', '%d migration keys are active.', count( $afbm_keys ), 'afrit-backup-manager' ),
						count( $afbm_keys )
					)
				);
				?>
			</p>
			<?php if ( [] !== $afbm_keys ) : ?>
				<?php Admin::form_open( 'revoke_keys', Admin::MENU_MIGRATION, [ 'class' => 'afbm-inline' ] ); ?>
					<button type="submit" class="button afbm-button-delete"><?php esc_html_e( 'Revoke all keys', 'afrit-backup-manager' ); ?></button>
				</form>
			<?php endif; ?>
		</div>

		<div class="afbm-card">
			<h2><span class="afbm-step">2</span> <?php esc_html_e( 'Import on the target site', 'afrit-backup-manager' ); ?></h2>
			<p><?php esc_html_e( 'Do this on the new site. Paste the key from the source site; the backup is copied over directly, server to server.', 'afrit-backup-manager' ); ?></p>
			<?php Admin::form_open( 'import_key', Admin::MENU_MIGRATION ); ?>
				<textarea name="migration_key" class="large-text code" rows="3" placeholder="AFBM1-…" required></textarea>
				<p><label><input type="checkbox" name="allow_private" value="1"> <?php esc_html_e( 'The source site is on my private network (internal address, non-standard port or plain http)', 'afrit-backup-manager' ); ?></label></p>
				<p class="description"><?php esc_html_e( 'Only import keys you created yourself. Without this option the target only connects to public HTTPS addresses, which keeps a manipulated key from reaching internal services.', 'afrit-backup-manager' ); ?></p>
				<?php submit_button( __( 'Import backup', 'afrit-backup-manager' ), 'primary', 'submit', false, Job_Runner::has_work() ? [ 'disabled' => 'disabled' ] : [] ); ?>
			</form>
			<h3><?php esc_html_e( 'Other ways to bring a backup over', 'afrit-backup-manager' ); ?></h3>
			<ul class="afbm-checklist">
				<li><?php esc_html_e( 'Shared storage: add the same Dropbox, OneDrive, FTP or SFTP destination on both sites, then use "Rescan all locations" on the Backups screen of the target.', 'afrit-backup-manager' ); ?></li>
				<li><?php esc_html_e( 'Upload: download the backup files from the source and upload them on the Backups screen of the target.', 'afrit-backup-manager' ); ?></li>
			</ul>
		</div>

		<div class="afbm-card">
			<h2><span class="afbm-step">3</span> <?php esc_html_e( 'Restore with the new address', 'afrit-backup-manager' ); ?></h2>
			<p><?php esc_html_e( 'On the target, open the imported backup on the Backups screen and choose Restore. The old address and paths are pre-filled for replacement with the new ones; add more pairs if needed. Choose only the parts you want for a selective migration — for example only Uploads, or only the database.', 'afrit-backup-manager' ); ?></p>
			<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . Admin::MENU_BACKUPS ) ); ?>"><?php esc_html_e( 'Go to Backups', 'afrit-backup-manager' ); ?></a></p>
		</div>
	</div>
</div>
