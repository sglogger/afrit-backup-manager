<?php
/**
 * Settings → Schedule.
 *
 * @package AfritBackupManager
 */

declare( strict_types = 1 );

namespace AfritBackupManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wp_locale;

$afbm_schedule = Settings::get( Installer::OPTION_SCHEDULE );

$afbm_frequencies = [
	'off'      => __( 'Off', 'afrit-backup-manager' ),
	'interval' => __( 'Every few hours', 'afrit-backup-manager' ),
	'daily'    => __( 'Daily', 'afrit-backup-manager' ),
	'weekly'   => __( 'Weekly', 'afrit-backup-manager' ),
	'monthly'  => __( 'Monthly', 'afrit-backup-manager' ),
];

/**
 * Frequency/time controls for one schedule.
 *
 * @param string               $kind   files|database.
 * @param array<string, mixed> $config Current config.
 */
$afbm_controls = static function ( string $kind, array $config ) use ( $afbm_frequencies, $wp_locale ): void {
	?>
	<div class="afbm-schedule" data-frequency="<?php echo esc_attr( (string) $config['frequency'] ); ?>">
		<select name="<?php echo esc_attr( $kind ); ?>[frequency]" class="afbm-frequency">
			<?php foreach ( $afbm_frequencies as $afbm_value => $afbm_label ) : ?>
				<option value="<?php echo esc_attr( $afbm_value ); ?>" <?php selected( $afbm_value, $config['frequency'] ); ?>><?php echo esc_html( $afbm_label ); ?></option>
			<?php endforeach; ?>
		</select>
		<span class="afbm-when afbm-when-interval">
			<?php esc_html_e( 'every', 'afrit-backup-manager' ); ?>
			<input type="number" min="1" max="168" class="small-text" name="<?php echo esc_attr( $kind ); ?>[interval_hours]" value="<?php echo esc_attr( (string) $config['interval_hours'] ); ?>">
			<?php esc_html_e( 'hours', 'afrit-backup-manager' ); ?>
		</span>
		<span class="afbm-when afbm-when-weekly">
			<?php esc_html_e( 'on', 'afrit-backup-manager' ); ?>
			<select name="<?php echo esc_attr( $kind ); ?>[weekday]">
				<?php for ( $afbm_d = 1; $afbm_d <= 7; $afbm_d++ ) : ?>
					<option value="<?php echo esc_attr( (string) $afbm_d ); ?>" <?php selected( $afbm_d, (int) $config['weekday'] ); ?>><?php echo esc_html( $wp_locale->get_weekday( $afbm_d % 7 ) ); ?></option>
				<?php endfor; ?>
			</select>
		</span>
		<span class="afbm-when afbm-when-monthly">
			<?php esc_html_e( 'on day', 'afrit-backup-manager' ); ?>
			<input type="number" min="1" max="31" class="small-text" name="<?php echo esc_attr( $kind ); ?>[monthday]" value="<?php echo esc_attr( (string) $config['monthday'] ); ?>">
		</span>
		<span class="afbm-when afbm-when-daily afbm-when-weekly afbm-when-monthly">
			<?php esc_html_e( 'at', 'afrit-backup-manager' ); ?>
			<input type="time" name="<?php echo esc_attr( $kind ); ?>[time]" value="<?php echo esc_attr( (string) $config['time'] ); ?>">
		</span>
	</div>
	<?php
};
?>
<?php Admin::form_open( 'save_schedule' ); ?>
	<p class="description">
		<?php
		/* translators: %s: timezone name */
		echo esc_html( sprintf( __( 'Times are in the site\'s timezone (%s). WordPress runs scheduled tasks when the site receives visits; on a very quiet site, set up a real cron job for wp-cron.php for exact timing.', 'afrit-backup-manager' ), wp_timezone_string() ) );
		?>
	</p>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'File backups', 'afrit-backup-manager' ); ?></th>
			<td>
				<?php $afbm_controls( 'files', (array) $afbm_schedule['files'] ); ?>
				<p>
					<?php esc_html_e( 'A full backup every', 'afrit-backup-manager' ); ?>
					<input type="number" min="1" max="100" class="small-text" name="files[full_every]" value="<?php echo esc_attr( (string) $afbm_schedule['files']['full_every'] ); ?>">
					<?php esc_html_e( 'runs; incremental in between (only changed files).', 'afrit-backup-manager' ); ?>
				</p>
				<fieldset class="afbm-components">
					<?php foreach ( Components::FILE_COMPONENTS as $afbm_component ) : ?>
						<label><input type="checkbox" name="files[components][]" value="<?php echo esc_attr( $afbm_component ); ?>" <?php checked( in_array( $afbm_component, (array) $afbm_schedule['files']['components'], true ) ); ?>> <?php echo esc_html( Components::label( $afbm_component ) ); ?></label>
					<?php endforeach; ?>
				</fieldset>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Database backups', 'afrit-backup-manager' ); ?></th>
			<td>
				<?php $afbm_controls( 'database', (array) $afbm_schedule['database'] ); ?>
				<p class="description"><?php esc_html_e( 'Which tables are included is set on the Database tab.', 'afrit-backup-manager' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Before updates', 'afrit-backup-manager' ); ?></th>
			<td>
				<label><input type="checkbox" name="pre_update[enabled]" value="1" <?php checked( ! empty( $afbm_schedule['pre_update']['enabled'] ) ); ?>> <?php esc_html_e( 'Back up automatically before WordPress, a plugin or a theme is updated', 'afrit-backup-manager' ); ?></label><br>
				<label><input type="checkbox" name="pre_update[database]" value="1" <?php checked( ! empty( $afbm_schedule['pre_update']['database'] ) ); ?>> <?php esc_html_e( 'Include the database', 'afrit-backup-manager' ); ?></label>
				<p class="description"><?php esc_html_e( 'Applies to manual, bulk and automatic background updates. Only what is about to change is archived, so the update is delayed as little as possible.', 'afrit-backup-manager' ); ?></p>
			</td>
		</tr>
	</table>
	<?php submit_button(); ?>
</form>
