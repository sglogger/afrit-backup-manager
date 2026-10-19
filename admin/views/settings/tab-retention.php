<?php
/**
 * Settings → Retention: how many backups to keep, and for how long where.
 *
 * @package AfritBackupManager
 */

declare( strict_types = 1 );

namespace AfritBackupManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$afbm_r = Settings::get( Installer::OPTION_RETENTION );

/**
 * Number input.
 *
 * @param string $name  Field.
 * @param int    $min   Minimum.
 * @param int    $max   Maximum.
 */
$afbm_num = static function ( string $name, int $min, int $max ) use ( $afbm_r ): void {
	printf(
		'<input type="number" name="%1$s" id="afbm-%1$s" min="%2$d" max="%3$d" class="small-text" value="%4$d">',
		esc_attr( $name ),
		(int) $min,
		(int) $max,
		(int) $afbm_r[ $name ]
	);
};
?>
<?php Admin::form_open( 'save_retention' ); ?>
	<p class="description"><?php esc_html_e( 'Old backups are removed automatically after each backup. A full backup is only ever removed together with the incremental backups built on it, and the newest backup of each kind is always kept.', 'afrit-backup-manager' ); ?></p>
	<p class="description"><?php esc_html_e( 'Backups marked "Keep" on the Backups screen are exempt from every rule on this page and do not count towards the limits. Marking an incremental backup also keeps the full backup and the incrementals it builds on.', 'afrit-backup-manager' ); ?></p>

	<h2><?php esc_html_e( 'How many to keep', 'afrit-backup-manager' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="afbm-keep_chains"><?php esc_html_e( 'File backups', 'afrit-backup-manager' ); ?></label></th>
			<td><?php $afbm_num( 'keep_chains', 1, 365 ); ?> <?php esc_html_e( 'full backups, each with its incremental backups', 'afrit-backup-manager' ); ?></td>
		</tr>
		<tr>
			<th scope="row"><label for="afbm-keep_database"><?php esc_html_e( 'Database backups', 'afrit-backup-manager' ); ?></label></th>
			<td><?php $afbm_num( 'keep_database', 1, 3650 ); ?></td>
		</tr>
		<tr>
			<th scope="row"><label for="afbm-keep_partial"><?php esc_html_e( 'Partial backups', 'afrit-backup-manager' ); ?></label></th>
			<td><?php $afbm_num( 'keep_partial', 1, 365 ); ?> <span class="description"><?php esc_html_e( 'manual selections and backups before updates', 'afrit-backup-manager' ); ?></span></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Rotation (GFS)', 'afrit-backup-manager' ); ?></th>
			<td>
				<label><input type="checkbox" name="gfs_enabled" value="1" <?php checked( ! empty( $afbm_r['gfs_enabled'] ) ); ?>> <?php esc_html_e( 'Instead of a fixed number of file backups, keep one per day, week and month:', 'afrit-backup-manager' ); ?></label>
				<p>
					<?php $afbm_num( 'gfs_daily', 0, 365 ); ?> <?php esc_html_e( 'daily', 'afrit-backup-manager' ); ?> ·
					<?php $afbm_num( 'gfs_weekly', 0, 260 ); ?> <?php esc_html_e( 'weekly', 'afrit-backup-manager' ); ?> ·
					<?php $afbm_num( 'gfs_monthly', 0, 120 ); ?> <?php esc_html_e( 'monthly', 'afrit-backup-manager' ); ?>
				</p>
			</td>
		</tr>
	</table>

	<h2><?php esc_html_e( 'How long to keep them, per location', 'afrit-backup-manager' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="afbm-local_days"><?php esc_html_e( 'On this server', 'afrit-backup-manager' ); ?></label></th>
			<td><?php $afbm_num( 'local_days', 0, 3650 ); ?> <?php esc_html_e( 'days', 'afrit-backup-manager' ); ?>
				<p class="description"><?php esc_html_e( 'Backups older than this are removed from this server. Copies on remote destinations are not affected.', 'afrit-backup-manager' ); ?></p></td>
		</tr>
		<tr>
			<th scope="row"><label for="afbm-remote_days"><?php esc_html_e( 'On remote / cloud destinations', 'afrit-backup-manager' ); ?></label></th>
			<td><?php $afbm_num( 'remote_days', 0, 3650 ); ?> <?php esc_html_e( 'days', 'afrit-backup-manager' ); ?>
				<p class="description"><?php esc_html_e( 'Default for every remote destination. Each destination can override it on the Storage tab.', 'afrit-backup-manager' ); ?></p></td>
		</tr>
		<tr>
			<th scope="row"><label for="afbm-max_age_days"><?php esc_html_e( 'Everywhere', 'afrit-backup-manager' ); ?></label></th>
			<td><?php $afbm_num( 'max_age_days', 0, 3650 ); ?> <?php esc_html_e( 'days maximum age', 'afrit-backup-manager' ); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Fewer copies on this server', 'afrit-backup-manager' ); ?></th>
			<td>
				<?php $afbm_num( 'local_chains', 0, 365 ); ?> <?php esc_html_e( 'file backups', 'afrit-backup-manager' ); ?> ·
				<?php $afbm_num( 'local_database', 0, 3650 ); ?> <?php esc_html_e( 'database backups', 'afrit-backup-manager' ); ?>
				<p class="description"><?php esc_html_e( 'Keep only the newest ones locally to save disk space; older copies stay on the remote destinations. Applies only to backups that also exist remotely.', 'afrit-backup-manager' ); ?></p>
			</td>
		</tr>
	</table>
	<p class="description"><?php esc_html_e( '0 means no limit.', 'afrit-backup-manager' ); ?></p>

	<p><label><input type="checkbox" name="apply_now" value="1"> <?php esc_html_e( 'Apply the rules right away', 'afrit-backup-manager' ); ?></label></p>
	<?php submit_button(); ?>
</form>
