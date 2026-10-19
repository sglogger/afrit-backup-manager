<?php
/**
 * Dashboard: status at a glance, one-click setup, backup now, live progress.
 *
 * @package AfritBackupManager
 */

declare( strict_types = 1 );

namespace AfritBackupManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$afbm_schedule   = Settings::get( Installer::OPTION_SCHEDULE );
$afbm_status     = Job_Runner::status();
$afbm_latest     = Catalog::latest();
$afbm_last_run   = Run_Repository::last( 'backup' );
$afbm_exposure   = Paths::exposure();
$afbm_sets       = Catalog::all();
$afbm_remote     = Destinations::enabled();
$afbm_next_files = Scheduler::next( 'files' );
$afbm_next_db    = Scheduler::next( 'database' );

/**
 * One status tile.
 *
 * @param string $label  Caption.
 * @param string $value  Main value.
 * @param string $detail Second line.
 * @param string $state  ok|warn|bad|info.
 * @param string $icon   Dashicon name.
 */
$afbm_tile = static function ( string $label, string $value, string $detail, string $state, string $icon ): void {
	printf(
		'<div class="afbm-tile afbm-tile-%1$s"><span class="dashicons dashicons-%2$s"></span><div><div class="afbm-tile-label">%3$s</div><div class="afbm-tile-value">%4$s</div><div class="afbm-tile-detail">%5$s</div></div></div>',
		esc_attr( $state ),
		esc_attr( $icon ),
		esc_html( $label ),
		esc_html( $value ),
		esc_html( $detail )
	);
};
?>
<div class="wrap afbm-wrap">
	<?php Admin::header( __( 'Backup Manager', 'afrit-backup-manager' ) ); ?>

	<?php if ( ! Settings::is_configured() ) : ?>
		<div class="afbm-card afbm-setup">
			<h2><?php esc_html_e( 'Set up backups in one click', 'afrit-backup-manager' ); ?></h2>
			<p><?php esc_html_e( 'This applies safe defaults and starts your first backup right away. Everything can be changed later under Settings.', 'afrit-backup-manager' ); ?></p>
			<ul class="afbm-checklist">
				<li><?php esc_html_e( 'Files: daily at 02:00, incremental — only changed files are saved; a new full backup every 7th day', 'afrit-backup-manager' ); ?></li>
				<li><?php esc_html_e( 'Database: daily at 03:00', 'afrit-backup-manager' ); ?></li>
				<li><?php esc_html_e( 'Automatic backup before every WordPress, plugin and theme update', 'afrit-backup-manager' ); ?></li>
				<li><?php esc_html_e( 'Keep 4 weeks of file backups and 14 database backups', 'afrit-backup-manager' ); ?></li>
				<li><?php esc_html_e( 'Stored in a protected directory on this server — add Dropbox, OneDrive, FTP or SFTP at any time', 'afrit-backup-manager' ); ?></li>
				<li><?php esc_html_e( 'E-mail to the site administrator if a backup fails', 'afrit-backup-manager' ); ?></li>
			</ul>
			<?php Admin::form_open( 'run_setup', Admin::MENU_DASHBOARD ); ?>
				<?php submit_button( __( 'Set up and run the first backup', 'afrit-backup-manager' ), 'primary hero', 'submit', false ); ?>
			</form>
		</div>
	<?php endif; ?>

	<div id="afbm-progress" class="afbm-card afbm-progress" <?php echo $afbm_status['running'] || $afbm_status['queued'] ? '' : 'hidden'; ?>>
		<div class="afbm-progress-head">
			<strong id="afbm-progress-title"><?php esc_html_e( 'Running', 'afrit-backup-manager' ); ?></strong>
			<span id="afbm-progress-message" class="afbm-muted"><?php echo esc_html( (string) ( $afbm_status['job']['message'] ?? '' ) ); ?></span>
		</div>
		<div class="afbm-bar"><div id="afbm-progress-bar" style="width:<?php echo (int) ( $afbm_status['job']['progress'] ?? 0 ); ?>%;"></div></div>
		<?php Admin::form_open( 'cancel_job', Admin::MENU_DASHBOARD, [ 'class' => 'afbm-inline' ] ); ?>
			<button type="submit" class="button afbm-button-delete"><?php esc_html_e( 'Cancel', 'afrit-backup-manager' ); ?></button>
		</form>
	</div>

	<?php if ( ! $afbm_status['running'] && null !== $afbm_status['last'] && $afbm_status['last']['finished'] > time() - HOUR_IN_SECONDS ) : ?>
		<?php
		$afbm_last_status = (string) $afbm_status['last']['status'];
		$afbm_class       = Run_Repository::STATUS_SUCCESS === $afbm_last_status ? 'success' : ( Run_Repository::STATUS_WARNING === $afbm_last_status ? 'warning' : 'error' );
		?>
		<div class="notice notice-<?php echo esc_attr( $afbm_class ); ?> inline">
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: job kind, 2: result */
						__( 'Last job (%1$s): %2$s.', 'afrit-backup-manager' ),
						Run_Repository::kind_label( (string) $afbm_status['last']['type'] ),
						Run_Repository::status_label( $afbm_last_status )
					)
				);
				echo ' ' . esc_html( (string) ( $afbm_status['last']['error'] ?: $afbm_status['last']['message'] ) );
				?>
				<a href="<?php echo esc_url( add_query_arg( 'run', (int) $afbm_status['last']['run_id'], admin_url( 'admin.php?page=' . Admin::MENU_REPORTS ) ) ); ?>"><?php esc_html_e( 'Report', 'afrit-backup-manager' ); ?></a>
			</p>
		</div>
	<?php endif; ?>

	<div class="afbm-tiles">
		<?php
		if ( null === $afbm_latest ) {
			$afbm_tile( __( 'Last backup', 'afrit-backup-manager' ), __( 'None yet', 'afrit-backup-manager' ), __( 'Run your first backup now.', 'afrit-backup-manager' ), 'bad', 'backup' );
		} else {
			$afbm_age = time() - $afbm_latest->created();
			$afbm_tile(
				__( 'Last backup', 'afrit-backup-manager' ),
				/* translators: %s: human time difference */
				sprintf( __( '%s ago', 'afrit-backup-manager' ), human_time_diff( $afbm_latest->created() ) ),
				Admin::type_label( $afbm_latest->type() ) . ' · ' . Util::bytes( $afbm_latest->total_size() ),
				$afbm_age > 3 * DAY_IN_SECONDS ? 'warn' : 'ok',
				'backup'
			);
		}

		if ( null !== $afbm_last_run && Run_Repository::STATUS_FAILED === $afbm_last_run['status'] ) {
			$afbm_tile( __( 'Last run', 'afrit-backup-manager' ), __( 'Failed', 'afrit-backup-manager' ), (string) $afbm_last_run['summary'], 'bad', 'warning' );
		}

		$afbm_tile(
			__( 'Next file backup', 'afrit-backup-manager' ),
			null !== $afbm_next_files ? Util::datetime( $afbm_next_files ) : __( 'Not scheduled', 'afrit-backup-manager' ),
			Scheduler::describe( (array) $afbm_schedule['files'] ),
			null !== $afbm_next_files ? 'info' : 'warn',
			'calendar-alt'
		);

		$afbm_tile(
			__( 'Next database backup', 'afrit-backup-manager' ),
			null !== $afbm_next_db ? Util::datetime( $afbm_next_db ) : __( 'Not scheduled', 'afrit-backup-manager' ),
			Scheduler::describe( (array) $afbm_schedule['database'] ),
			null !== $afbm_next_db ? 'info' : 'warn',
			'database'
		);

		$afbm_tile(
			__( 'Stored backups', 'afrit-backup-manager' ),
			number_format_i18n( count( $afbm_sets ) ),
			/* translators: %s: disk space */
			sprintf( __( '%s on this server', 'afrit-backup-manager' ), Util::bytes( Catalog::local_usage() ) ),
			'info',
			'portfolio'
		);

		$afbm_tile(
			__( 'Destinations', 'afrit-backup-manager' ),
			/* translators: %d: number of remote destinations */
			sprintf( _n( 'This server + %d', 'This server + %d', count( $afbm_remote ), 'afrit-backup-manager' ), count( $afbm_remote ) ),
			[] === $afbm_remote ? __( 'Add an off-site copy for real safety.', 'afrit-backup-manager' ) : implode( ', ', array_column( $afbm_remote, 'name' ) ),
			[] === $afbm_remote ? 'warn' : 'ok',
			'cloud'
		);

		$afbm_exposure_labels = [
			'outside'   => [ __( 'Outside the web root', 'afrit-backup-manager' ), 'ok' ],
			'protected' => [ __( 'Protected', 'afrit-backup-manager' ), 'ok' ],
			'exposed'   => [ __( 'EXPOSED', 'afrit-backup-manager' ), 'bad' ],
			'unknown'   => [ __( 'Not verified', 'afrit-backup-manager' ), 'warn' ],
		];
		$afbm_exp             = $afbm_exposure_labels[ $afbm_exposure['status'] ] ?? $afbm_exposure_labels['unknown'];
		$afbm_tile(
			__( 'Backup directory', 'afrit-backup-manager' ),
			$afbm_exp[0],
			$afbm_exposure['checked'] > 0
				/* translators: %s: human time difference */
				? sprintf( __( 'Tested %s ago', 'afrit-backup-manager' ), human_time_diff( $afbm_exposure['checked'] ) )
				: __( 'Test it on the Security tab.', 'afrit-backup-manager' ),
			$afbm_exp[1],
			'shield'
		);
		?>
	</div>

	<div class="afbm-columns">
		<div class="afbm-card">
			<h2><?php esc_html_e( 'Back up now', 'afrit-backup-manager' ); ?></h2>
			<?php Admin::form_open( 'backup_now', Admin::MENU_DASHBOARD ); ?>
				<fieldset class="afbm-components">
					<?php foreach ( Components::ALL as $afbm_component ) : ?>
						<label><input type="checkbox" name="components[]" value="<?php echo esc_attr( $afbm_component ); ?>" checked> <?php echo esc_html( Components::label( $afbm_component ) ); ?></label>
					<?php endforeach; ?>
				</fieldset>
				<p>
					<label for="afbm-label"><?php esc_html_e( 'Note (optional)', 'afrit-backup-manager' ); ?></label><br>
					<input type="text" id="afbm-label" name="label" class="regular-text" maxlength="120">
				</p>
				<p class="description"><?php esc_html_e( 'Everything selected: a complete backup that also serves as a migration package. Only the file parts selected: continues the incremental chain. Anything else: a partial backup.', 'afrit-backup-manager' ); ?></p>
				<?php submit_button( __( 'Start backup', 'afrit-backup-manager' ), 'primary', 'submit', false, $afbm_status['running'] ? [ 'disabled' => 'disabled' ] : [] ); ?>
			</form>
		</div>

		<div class="afbm-card">
			<h2><?php esc_html_e( 'Recent activity', 'afrit-backup-manager' ); ?></h2>
			<?php $afbm_recent = Run_Repository::query( 6, 1 )['rows']; ?>
			<?php if ( [] === $afbm_recent ) : ?>
				<p class="afbm-muted"><?php esc_html_e( 'Nothing has run yet.', 'afrit-backup-manager' ); ?></p>
			<?php else : ?>
				<table class="widefat striped afbm-compact">
					<tbody>
					<?php foreach ( $afbm_recent as $afbm_run ) : ?>
						<tr>
							<td><?php echo esc_html( Util::datetime( (int) strtotime( $afbm_run['started_at'] . ' UTC' ) ) ); ?></td>
							<td><?php echo esc_html( Run_Repository::kind_label( (string) $afbm_run['kind'] ) ); ?><br><span class="afbm-muted"><?php echo esc_html( Run_Repository::trigger_label( (string) $afbm_run['run_trigger'] ) ); ?></span></td>
							<td><?php echo Admin::badge( Run_Repository::status_label( (string) $afbm_run['status'] ), Run_Repository::status_color( (string) $afbm_run['status'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- badge() escapes. ?></td>
							<td><a href="<?php echo esc_url( add_query_arg( 'run', (int) $afbm_run['id'], admin_url( 'admin.php?page=' . Admin::MENU_REPORTS ) ) ); ?>"><?php esc_html_e( 'Details', 'afrit-backup-manager' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Admin::MENU_REPORTS ) ); ?>"><?php esc_html_e( 'All reports', 'afrit-backup-manager' ); ?> →</a></p>
			<?php endif; ?>
		</div>
	</div>
</div>
