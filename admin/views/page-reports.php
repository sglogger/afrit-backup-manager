<?php
/**
 * Reports: when backups ran, what they contained, whether they succeeded.
 *
 * @package AfritBackupManager
 */

declare( strict_types = 1 );

namespace AfritBackupManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- selects what to display.
$afbm_run_id = isset( $_GET['run'] ) ? (int) $_GET['run'] : 0;
$afbm_run    = $afbm_run_id > 0 ? Run_Repository::get( $afbm_run_id ) : null;
?>
<div class="wrap afbm-wrap">
	<?php Admin::header( __( 'Reports', 'afrit-backup-manager' ) ); ?>

	<?php if ( null !== $afbm_run ) : ?>
		<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Admin::MENU_REPORTS ) ); ?>">← <?php esc_html_e( 'All reports', 'afrit-backup-manager' ); ?></a></p>

		<div class="afbm-card">
			<table class="widefat striped afbm-facts">
				<tbody>
					<tr><th><?php esc_html_e( 'Job', 'afrit-backup-manager' ); ?></th><td><?php echo esc_html( Run_Repository::kind_label( (string) $afbm_run['kind'] ) . ' · ' . Run_Repository::trigger_label( (string) $afbm_run['run_trigger'] ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Result', 'afrit-backup-manager' ); ?></th><td><?php echo Admin::badge( Run_Repository::status_label( (string) $afbm_run['status'] ), Run_Repository::status_color( (string) $afbm_run['status'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- badge() escapes. ?></td></tr>
					<tr><th><?php esc_html_e( 'Started', 'afrit-backup-manager' ); ?></th><td><?php echo esc_html( Util::datetime( (int) strtotime( $afbm_run['started_at'] . ' UTC' ) ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Finished', 'afrit-backup-manager' ); ?></th><td><?php echo esc_html( empty( $afbm_run['finished_at'] ) ? '—' : Util::datetime( (int) strtotime( $afbm_run['finished_at'] . ' UTC' ) ) ); ?></td></tr>
					<?php if ( '' !== (string) $afbm_run['backup_id'] ) : ?>
						<tr><th><?php esc_html_e( 'Backup', 'afrit-backup-manager' ); ?></th><td><code><?php echo esc_html( (string) $afbm_run['backup_id'] ); ?></code> <?php echo '' !== (string) $afbm_run['backup_type'] ? Admin::badge( Admin::type_label( (string) $afbm_run['backup_type'] ), Admin::type_color( (string) $afbm_run['backup_type'] ) ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- badge() escapes. ?></td></tr>
					<?php endif; ?>
					<tr><th><?php esc_html_e( 'Included', 'afrit-backup-manager' ); ?></th><td><?php echo esc_html( implode( ', ', array_map( [ Components::class, 'label' ], array_filter( explode( ',', (string) $afbm_run['components'] ) ) ) ) ?: '—' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Files / tables', 'afrit-backup-manager' ); ?></th><td><?php echo esc_html( number_format_i18n( (int) $afbm_run['file_count'] ) . ' / ' . number_format_i18n( (int) $afbm_run['table_count'] ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Size', 'afrit-backup-manager' ); ?></th><td><?php echo esc_html( (int) $afbm_run['size_bytes'] > 0 ? Util::bytes( (int) $afbm_run['size_bytes'] ) : '—' ); ?></td></tr>
					<?php
					$afbm_dest = json_decode( (string) $afbm_run['destinations'], true );
					if ( is_array( $afbm_dest ) && [] !== $afbm_dest ) :
						?>
						<tr><th><?php esc_html_e( 'Stored on', 'afrit-backup-manager' ); ?></th><td>
							<?php foreach ( $afbm_dest as $afbm_dest_id => $afbm_outcome ) : ?>
								<span style="color:<?php echo esc_attr( 'done' === $afbm_outcome ? '#00a32a' : '#d63638' ); ?>;"><?php echo esc_html( Destinations::name( (string) $afbm_dest_id ) . ( 'done' === $afbm_outcome ? ' ✓' : ' ✗' ) ); ?></span>&nbsp;
							<?php endforeach; ?>
						</td></tr>
					<?php endif; ?>
					<?php if ( '' !== trim( (string) $afbm_run['summary'] ) ) : ?>
						<tr><th><?php esc_html_e( 'Summary', 'afrit-backup-manager' ); ?></th><td><?php echo nl2br( esc_html( (string) $afbm_run['summary'] ) ); ?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<h2><?php esc_html_e( 'Log', 'afrit-backup-manager' ); ?></h2>
		<pre class="afbm-log"><?php echo esc_html( (string) $afbm_run['log'] ); ?></pre>
	<?php else : ?>
		<?php
		$afbm_table = new Runs_List_Table();
		$afbm_table->prepare_items();
		$afbm_table->views();
		$afbm_table->display();
		?>
	<?php endif; ?>
</div>
