<?php
/**
 * Settings → General.
 *
 * @package AfritBackupManager
 */

declare( strict_types = 1 );

namespace AfritBackupManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$afbm_settings = Settings::general();
?>
<?php Admin::form_open( 'save_general' ); ?>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="afbm-path"><?php esc_html_e( 'Backup directory', 'afrit-backup-manager' ); ?></label></th>
			<td>
				<input type="text" id="afbm-path" name="backup_path" class="large-text code" value="<?php echo esc_attr( (string) $afbm_settings['backup_path'] ); ?>" placeholder="<?php echo esc_attr( Paths::default_dir() ); ?>">
				<p class="description">
					<?php esc_html_e( 'In use:', 'afrit-backup-manager' ); ?> <code><?php echo esc_html( Paths::backup_dir() ); ?></code><br>
					<?php esc_html_e( 'Leave empty for the automatic choice: outside the web root when the server allows it, otherwise a protected, randomly named folder. A directory outside the web root is the safest place.', 'afrit-backup-manager' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="afbm-part"><?php esc_html_e( 'Archive part size', 'afrit-backup-manager' ); ?></label></th>
			<td><input type="number" id="afbm-part" name="part_size_mb" min="10" max="4000" class="small-text" value="<?php echo esc_attr( (string) $afbm_settings['part_size_mb'] ); ?>"> MB
				<p class="description"><?php esc_html_e( 'Large backups are split into parts of this size, which upload and download more reliably.', 'afrit-backup-manager' ); ?></p></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Copy on this server', 'afrit-backup-manager' ); ?></th>
			<td><label><input type="checkbox" name="keep_local" value="1" <?php checked( ! empty( $afbm_settings['keep_local'] ) ); ?>> <?php esc_html_e( 'Keep a copy of every backup on this server', 'afrit-backup-manager' ); ?></label>
				<p class="description"><?php esc_html_e( 'When off, the local copy is deleted once at least one remote destination has received the backup.', 'afrit-backup-manager' ); ?></p></td>
		</tr>
		<tr>
			<th scope="row"><label for="afbm-budget"><?php esc_html_e( 'Work per request', 'afrit-backup-manager' ); ?></label></th>
			<td><input type="number" id="afbm-budget" name="time_budget" min="5" max="300" class="small-text" value="<?php echo esc_attr( (string) $afbm_settings['time_budget'] ); ?>"> <?php esc_html_e( 'seconds', 'afrit-backup-manager' ); ?>
				<p class="description"><?php esc_html_e( 'Backups run in short steps so they never hit the server\'s time limit. Raise this on a dedicated server, lower it if backups stall.', 'afrit-backup-manager' ); ?></p></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'E-mail reports', 'afrit-backup-manager' ); ?></th>
			<td>
				<select name="email_mode">
					<option value="failures" <?php selected( 'failures', $afbm_settings['email_mode'] ); ?>><?php esc_html_e( 'Only when something went wrong', 'afrit-backup-manager' ); ?></option>
					<option value="always" <?php selected( 'always', $afbm_settings['email_mode'] ); ?>><?php esc_html_e( 'After every backup and restore', 'afrit-backup-manager' ); ?></option>
					<option value="never" <?php selected( 'never', $afbm_settings['email_mode'] ); ?>><?php esc_html_e( 'Never', 'afrit-backup-manager' ); ?></option>
				</select>
				<p><textarea name="email_recipients" rows="2" class="large-text" placeholder="<?php echo esc_attr( (string) get_option( 'admin_email' ) ); ?>"><?php echo esc_textarea( (string) $afbm_settings['email_recipients'] ); ?></textarea></p>
				<p class="description"><?php esc_html_e( 'Recipients, separated by commas. Empty: the site administrator address.', 'afrit-backup-manager' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Uninstall', 'afrit-backup-manager' ); ?></th>
			<td>
				<label><input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked( ! empty( $afbm_settings['delete_data_on_uninstall'] ) ); ?>> <?php esc_html_e( 'Delete settings and reports when the plugin is deleted', 'afrit-backup-manager' ); ?></label><br>
				<label><input type="checkbox" name="delete_backups_on_uninstall" value="1" <?php checked( ! empty( $afbm_settings['delete_backups_on_uninstall'] ) ); ?>> <?php esc_html_e( 'Also delete the backups stored on this server', 'afrit-backup-manager' ); ?></label>
			</td>
		</tr>
	</table>
	<?php submit_button(); ?>
</form>

<?php Admin::form_open( 'test_email' ); ?>
	<?php submit_button( __( 'Send a test e-mail', 'afrit-backup-manager' ), 'secondary', 'submit', false ); ?>
</form>
