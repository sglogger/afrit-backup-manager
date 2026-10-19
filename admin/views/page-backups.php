<?php
/**
 * Backups: list, restore, download, delete, rescan, upload.
 *
 * @package AfritBackupManager
 */

declare( strict_types = 1 );

namespace AfritBackupManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$afbm_table = new Backups_List_Table();
$afbm_table->prepare_items();
?>
<div class="wrap afbm-wrap">
	<?php Admin::header( __( 'Backups', 'afrit-backup-manager' ) ); ?>

	<div class="afbm-toolbar">
		<?php Admin::form_open( 'rescan', Admin::MENU_BACKUPS, [ 'class' => 'afbm-inline' ] ); ?>
			<button type="submit" class="button"><span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Rescan all locations', 'afrit-backup-manager' ); ?></button>
		</form>
		<details class="afbm-upload">
			<summary class="button"><span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Upload a backup', 'afrit-backup-manager' ); ?></summary>
			<div class="afbm-card">
				<p><?php esc_html_e( 'Select all files of one backup set (the .manifest.json and every file starting with the same name). They are uploaded in chunks, so size limits of the server do not apply.', 'afrit-backup-manager' ); ?></p>
				<input type="file" id="afbm-upload-files" multiple>
				<p id="afbm-upload-status" class="afbm-muted" aria-live="polite"></p>
			</div>
		</details>
	</div>

	<p class="description">
		<?php esc_html_e( 'Incremental backups only contain files that changed; restoring one automatically uses the full backup and every incremental before it. Downloads are only possible while signed in here — backup files are never reachable by URL.', 'afrit-backup-manager' ); ?>
	</p>

	<?php $afbm_table->views(); ?>
	<?php $afbm_table->display(); ?>
</div>
