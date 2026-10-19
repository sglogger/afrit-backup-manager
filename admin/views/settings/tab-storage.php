<?php
/**
 * Settings → Storage: where backups are copied to.
 *
 * @package AfritBackupManager
 */

declare( strict_types = 1 );

namespace AfritBackupManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$afbm_types        = Destinations::types();
$afbm_destinations = Destinations::all();

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- selects what to display.
$afbm_edit_id = isset( $_GET['edit'] ) ? sanitize_key( wp_unslash( (string) $_GET['edit'] ) ) : '';
$afbm_new     = isset( $_GET['add'] ) ? sanitize_key( wp_unslash( (string) $_GET['add'] ) ) : '';
$afbm_connect = isset( $_GET['connect'] ) ? sanitize_key( wp_unslash( (string) $_GET['connect'] ) ) : '';
// phpcs:enable

$afbm_has_plugin_app = '' !== Storage_Dropbox::plugin_app_key();

$afbm_edit = '' !== $afbm_edit_id ? Destinations::get( $afbm_edit_id ) : null;
if ( null === $afbm_edit && isset( $afbm_types[ $afbm_new ] ) ) {
	$afbm_edit = [
		'id'             => '',
		'type'           => $afbm_new,
		'name'           => $afbm_types[ $afbm_new ]['label'],
		'enabled'        => true,
		'retention_days' => 0,
		'config'         => [
			'port'    => 'sftp' === $afbm_new ? 22 : 21,
			'passive' => true,
			'tenant'  => 'common',
			'own_app' => ! $afbm_has_plugin_app,
			'path'    => 'ftp' === $afbm_new || 'sftp' === $afbm_new ? 'backups/' . wp_parse_url( home_url(), PHP_URL_HOST ) : wp_parse_url( home_url(), PHP_URL_HOST ),
		],
	];
}

$afbm_status_cell = static function ( array $destination ): string {
	if ( ! Destinations::is_connected( $destination ) ) {
		return Admin::badge( __( 'Not connected', 'afrit-backup-manager' ), '#dba617' );
	}
	return ! empty( $destination['enabled'] )
		? Admin::badge( __( 'Active', 'afrit-backup-manager' ), '#00a32a' )
		: Admin::badge( __( 'Paused', 'afrit-backup-manager' ), '#646970' );
};
?>
<?php
$afbm_paste_url = '' !== $afbm_connect ? OAuth::pending_url( $afbm_connect ) : '';
if ( '' !== $afbm_paste_url ) :
	$afbm_paste_dest = Destinations::get( OAuth::pending_destination( $afbm_connect ) );
	?>
	<div class="afbm-card afbm-connect">
		<h2>
			<?php
			/* translators: %s: destination name */
			echo esc_html( sprintf( __( 'Connect "%s" to Dropbox', 'afrit-backup-manager' ), null !== $afbm_paste_dest ? $afbm_paste_dest['name'] : 'Dropbox' ) );
			?>
		</h2>
		<ol>
			<li>
				<a class="button button-primary" href="<?php echo esc_url( $afbm_paste_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open Dropbox', 'afrit-backup-manager' ); ?> ↗</a>
				<?php esc_html_e( 'Sign in if asked and press "Allow". Afrit Backup Manager only gets access to its own folder (Apps/Afrit Backup Manager), nothing else in your Dropbox.', 'afrit-backup-manager' ); ?>
			</li>
			<li><?php esc_html_e( 'Dropbox then shows an access code. Copy it and paste it here:', 'afrit-backup-manager' ); ?></li>
		</ol>
		<?php Admin::form_open( 'oauth_paste' ); ?>
			<input type="hidden" name="state" value="<?php echo esc_attr( $afbm_connect ); ?>">
			<p>
				<input type="text" name="code" class="large-text code" autocomplete="off" required placeholder="<?php esc_attr_e( 'Access code from Dropbox', 'afrit-backup-manager' ); ?>">
			</p>
			<?php submit_button( __( 'Finish connecting', 'afrit-backup-manager' ), 'primary', 'submit', false ); ?>
		</form>
		<p class="description"><?php esc_html_e( 'The code works once and expires after a few minutes. The connection is stored only on this site; it can be removed at any time under Dropbox → Settings → Apps → Connected apps.', 'afrit-backup-manager' ); ?></p>
	</div>
<?php endif; ?>

<?php if ( Util::is_playground() ) : ?>
	<div class="notice notice-warning inline"><p><?php echo esc_html( Util::playground_notice() ); ?></p></div>
<?php endif; ?>

<p class="description"><?php esc_html_e( 'Every backup is written to this server first and then copied to all active destinations at the same time. If one destination is unavailable, the others still receive their copy.', 'afrit-backup-manager' ); ?></p>

<table class="widefat striped afbm-destinations">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Destination', 'afrit-backup-manager' ); ?></th>
			<th><?php esc_html_e( 'Type', 'afrit-backup-manager' ); ?></th>
			<th><?php esc_html_e( 'Status', 'afrit-backup-manager' ); ?></th>
			<th><?php esc_html_e( 'Keep for', 'afrit-backup-manager' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'afrit-backup-manager' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><strong><?php esc_html_e( 'This server', 'afrit-backup-manager' ); ?></strong><br><code class="afbm-muted"><?php echo esc_html( Paths::backup_dir() ); ?></code></td>
			<td><?php esc_html_e( 'Local', 'afrit-backup-manager' ); ?></td>
			<td><?php echo Admin::badge( __( 'Active', 'afrit-backup-manager' ), '#00a32a' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- badge() escapes. ?></td>
			<td><?php $afbm_ld = (int) Settings::get( Installer::OPTION_RETENTION )['local_days'];
echo esc_html( $afbm_ld > 0 ? sprintf( /* translators: %d: days */ _n( '%d day', '%d days', $afbm_ld, 'afrit-backup-manager' ), $afbm_ld ) : __( 'Retention rules', 'afrit-backup-manager' ) ); ?></td>
			<td class="afbm-actions"><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . Admin::MENU_SETTINGS . '&tab=general' ) ); ?>"><?php esc_html_e( 'Settings', 'afrit-backup-manager' ); ?></a></td>
		</tr>
		<?php foreach ( $afbm_destinations as $afbm_id => $afbm_destination ) : ?>
			<tr>
				<td><strong><?php echo esc_html( (string) $afbm_destination['name'] ); ?></strong></td>
				<td><?php echo esc_html( $afbm_types[ $afbm_destination['type'] ]['label'] ); ?></td>
				<td><?php echo $afbm_status_cell( $afbm_destination ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- badge() escapes. ?></td>
				<td>
					<?php
					$afbm_days = (int) ( $afbm_destination['retention_days'] ?? 0 );
					$afbm_rd   = (int) Settings::get( Installer::OPTION_RETENTION )['remote_days'];
					$afbm_eff  = $afbm_days > 0 ? $afbm_days : $afbm_rd;
					/* translators: %d: days */
					echo esc_html( $afbm_eff > 0 ? sprintf( _n( '%d day', '%d days', $afbm_eff, 'afrit-backup-manager' ), $afbm_eff ) : __( 'Retention rules', 'afrit-backup-manager' ) );
					?>
				</td>
				<td class="afbm-actions">
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . Admin::MENU_SETTINGS . '&tab=storage&edit=' . $afbm_id ) ); ?>"><?php esc_html_e( 'Edit', 'afrit-backup-manager' ); ?></a>
					<?php if ( ! empty( $afbm_types[ $afbm_destination['type'] ]['oauth'] ) ) : ?>
						<?php Admin::form_open( 'oauth_connect', Admin::MENU_SETTINGS, [ 'class' => 'afbm-inline' ] ); ?>
							<input type="hidden" name="destination" value="<?php echo esc_attr( (string) $afbm_id ); ?>">
							<button type="submit" class="button"><?php echo esc_html( Destinations::is_connected( $afbm_destination ) ? __( 'Reconnect', 'afrit-backup-manager' ) : __( 'Connect', 'afrit-backup-manager' ) ); ?></button>
						</form>
					<?php endif; ?>
					<?php Admin::form_open( 'test_destination', Admin::MENU_SETTINGS, [ 'class' => 'afbm-inline' ] ); ?>
						<input type="hidden" name="destination" value="<?php echo esc_attr( (string) $afbm_id ); ?>">
						<button type="submit" class="button"><?php esc_html_e( 'Test', 'afrit-backup-manager' ); ?></button>
					</form>
					<?php Admin::form_open( 'delete_destination', Admin::MENU_SETTINGS, [ 'class' => 'afbm-inline afbm-confirm-remove' ] ); ?>
						<input type="hidden" name="destination" value="<?php echo esc_attr( (string) $afbm_id ); ?>">
						<button type="submit" class="button afbm-button-delete"><?php esc_html_e( 'Remove', 'afrit-backup-manager' ); ?></button>
					</form>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<p class="afbm-add">
	<?php esc_html_e( 'Add a destination:', 'afrit-backup-manager' ); ?>
	<?php foreach ( $afbm_types as $afbm_type => $afbm_def ) : ?>
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . Admin::MENU_SETTINGS . '&tab=storage&add=' . $afbm_type ) ); ?>"><?php echo esc_html( $afbm_def['label'] ); ?></a>
	<?php endforeach; ?>
</p>

<?php if ( null !== $afbm_edit ) : ?>
	<?php
	$afbm_def     = $afbm_types[ $afbm_edit['type'] ];
	$afbm_config  = (array) $afbm_edit['config'];
	$afbm_own_app = 'dropbox' !== $afbm_edit['type'] || ! $afbm_has_plugin_app || ! empty( $afbm_config['own_app'] );
	?>
	<div class="afbm-card">
		<h2>
			<?php
			echo esc_html(
				'' === $afbm_edit['id']
					/* translators: %s: destination type */
					? sprintf( __( 'New %s destination', 'afrit-backup-manager' ), $afbm_def['label'] )
					/* translators: %s: destination name */
					: sprintf( __( 'Edit "%s"', 'afrit-backup-manager' ), $afbm_edit['name'] )
			);
			?>
		</h2>

		<?php if ( 'dropbox' === $afbm_edit['type'] ) : ?>
			<?php if ( $afbm_has_plugin_app ) : ?>
				<div class="afbm-guide afbm-plugin-only" <?php echo $afbm_own_app ? 'hidden' : ''; ?>>
					<h3><?php esc_html_e( 'Connect in three steps', 'afrit-backup-manager' ); ?></h3>
					<ol>
						<li><?php esc_html_e( 'Save this destination.', 'afrit-backup-manager' ); ?></li>
						<li><?php esc_html_e( 'Press "Connect" in the list above and allow access in Dropbox.', 'afrit-backup-manager' ); ?></li>
						<li><?php esc_html_e( 'Paste the code Dropbox shows. Done.', 'afrit-backup-manager' ); ?></li>
					</ol>
					<p class="description"><?php esc_html_e( 'No Dropbox app of your own is needed: Afrit Backup Manager appears under "Connected apps" in your Dropbox and can only access its own folder Apps/Afrit Backup Manager.', 'afrit-backup-manager' ); ?></p>
				</div>
			<?php else : ?>
				<div class="notice notice-info inline"><p><?php esc_html_e( 'This copy of the plugin has no built-in Dropbox app, so connect it with your own app as described below.', 'afrit-backup-manager' ); ?></p></div>
			<?php endif; ?>
			<div class="afbm-guide afbm-own-only" <?php echo $afbm_own_app ? '' : 'hidden'; ?>>
				<h3><?php esc_html_e( 'Create your Dropbox app (once, about 3 minutes)', 'afrit-backup-manager' ); ?></h3>
				<ol>
					<li><?php echo wp_kses(
						__( 'Open <a href="https://www.dropbox.com/developers/apps/create" target="_blank" rel="noopener noreferrer">dropbox.com/developers/apps/create</a> and sign in.', 'afrit-backup-manager' ),
						[
							'a' => [
								'href'   => [],
								'target' => [],
								'rel'    => [],
							],
						]
					); ?></li>
					<li><?php esc_html_e( 'Choose "Scoped access" and "App folder", give the app a name, and create it.', 'afrit-backup-manager' ); ?></li>
					<li><?php esc_html_e( 'On the Permissions tab, tick files.metadata.read, files.metadata.write, files.content.read and files.content.write, then "Submit".', 'afrit-backup-manager' ); ?></li>
					<li><?php esc_html_e( 'On the Settings tab, add this Redirect URI:', 'afrit-backup-manager' ); ?> <code class="afbm-copyable"><?php echo esc_html( OAuth::redirect_uri() ); ?></code></li>
					<li><?php esc_html_e( 'Copy the App key (and App secret) into the fields below, save, then press "Connect".', 'afrit-backup-manager' ); ?></li>
				</ol>
				<p class="description"><?php esc_html_e( 'Dropbox accepts plain http only for localhost; a live site needs https.', 'afrit-backup-manager' ); ?></p>
			</div>
		<?php elseif ( 'onedrive' === $afbm_edit['type'] ) : ?>
			<div class="afbm-guide">
				<h3><?php esc_html_e( 'Register your Microsoft app (once, about 5 minutes)', 'afrit-backup-manager' ); ?></h3>
				<ol>
					<li><?php echo wp_kses(
						__( 'Open <a href="https://entra.microsoft.com/#view/Microsoft_AAD_RegisteredApps/ApplicationsListBlade" target="_blank" rel="noopener noreferrer">Microsoft Entra → App registrations</a> and choose "New registration".', 'afrit-backup-manager' ),
						[
							'a' => [
								'href'   => [],
								'target' => [],
								'rel'    => [],
							],
						]
					); ?></li>
					<li><?php esc_html_e( 'Supported account types: "Accounts in any organizational directory and personal Microsoft accounts".', 'afrit-backup-manager' ); ?></li>
					<li><?php esc_html_e( 'Redirect URI: platform "Web", value:', 'afrit-backup-manager' ); ?> <code class="afbm-copyable"><?php echo esc_html( OAuth::redirect_uri() ); ?></code></li>
					<li><?php esc_html_e( 'Under "Certificates & secrets", create a client secret and copy its value.', 'afrit-backup-manager' ); ?></li>
					<li><?php esc_html_e( 'Under "API permissions", add Microsoft Graph → Delegated → Files.ReadWrite.AppFolder and offline_access.', 'afrit-backup-manager' ); ?></li>
					<li><?php esc_html_e( 'Copy the Application (client) ID and the secret into the fields below, save, then press "Connect".', 'afrit-backup-manager' ); ?></li>
				</ol>
				<p class="description"><?php esc_html_e( 'Tenant: "common" for most cases, "consumers" for personal accounts only, or your organisation\'s tenant ID.', 'afrit-backup-manager' ); ?></p>
			</div>
		<?php elseif ( 'ftp' === $afbm_edit['type'] ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'Without FTPS, the password and the backups travel unencrypted. Use FTPS or SFTP whenever the server supports it, and turn on backup encryption.', 'afrit-backup-manager' ); ?></p></div>
		<?php endif; ?>

		<?php Admin::form_open( 'save_destination', Admin::MENU_SETTINGS, [ 'class' => 'afbm-dest-form' ] ); ?>
			<input type="hidden" name="destination" value="<?php echo esc_attr( (string) $afbm_edit['id'] ); ?>">
			<input type="hidden" name="type" value="<?php echo esc_attr( (string) $afbm_edit['type'] ); ?>">
			<table class="form-table afbm-dest-table" role="presentation" data-own-app="<?php echo $afbm_own_app ? '1' : '0'; ?>">
				<tr>
					<th scope="row"><label for="afbm-dest-name"><?php esc_html_e( 'Name', 'afrit-backup-manager' ); ?></label></th>
					<td><input type="text" id="afbm-dest-name" name="name" class="regular-text" value="<?php echo esc_attr( (string) $afbm_edit['name'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Active', 'afrit-backup-manager' ); ?></th>
					<td><label><input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $afbm_edit['enabled'] ) ); ?>> <?php esc_html_e( 'Copy new backups to this destination', 'afrit-backup-manager' ); ?></label></td>
				</tr>
				<?php foreach ( $afbm_def['fields'] as $afbm_field => $afbm_kind ) : ?>
					<?php
					$afbm_secret = in_array( $afbm_field, $afbm_def['secrets'], true );
					$afbm_value  = $afbm_secret ? '' : ( $afbm_config[ $afbm_field ] ?? '' );
					$afbm_stored = $afbm_secret && '' !== (string) ( $afbm_config[ $afbm_field ] ?? '' );
					$afbm_name   = 'config[' . $afbm_field . ']';
					$afbm_fid    = 'afbm-f-' . $afbm_field;
					$afbm_row    = 'dropbox' === $afbm_edit['type'] && in_array( $afbm_field, [ 'app_key', 'app_secret' ], true ) ? 'afbm-own-only' : '';

					// Without a built-in app the own-app mode is the only one.
					if ( 'own_app' === $afbm_field && ! $afbm_has_plugin_app ) {
						echo '<input type="hidden" name="config[own_app]" value="1">';
						continue;
					}
					?>
					<tr class="<?php echo esc_attr( $afbm_row ); ?>">
						<th scope="row"><label for="<?php echo esc_attr( $afbm_fid ); ?>"><?php echo esc_html( Destinations::field_label( $afbm_field ) ); ?></label></th>
						<td>
							<?php if ( 'checkbox' === $afbm_kind ) : ?>
								<input type="checkbox" id="<?php echo esc_attr( $afbm_fid ); ?>" name="<?php echo esc_attr( $afbm_name ); ?>" value="1" <?php checked( ! empty( $afbm_config[ $afbm_field ] ) ); ?> <?php echo 'own_app' === $afbm_field ? 'class="afbm-own-app-toggle"' : ''; ?>>
								<?php if ( 'own_app' === $afbm_field ) : ?>
									<label for="<?php echo esc_attr( $afbm_fid ); ?>"><?php esc_html_e( 'Advanced: use my own Dropbox app instead of the built-in one', 'afrit-backup-manager' ); ?></label>
								<?php endif; ?>
							<?php elseif ( 'textarea' === $afbm_kind ) : ?>
								<textarea id="<?php echo esc_attr( $afbm_fid ); ?>" name="<?php echo esc_attr( $afbm_name ); ?>" rows="4" class="large-text code" autocomplete="off" placeholder="<?php echo $afbm_stored ? esc_attr__( 'Stored — leave empty to keep', 'afrit-backup-manager' ) : '-----BEGIN OPENSSH PRIVATE KEY-----'; ?>"></textarea>
							<?php else : ?>
								<input type="<?php echo esc_attr( 'password' === $afbm_kind ? 'password' : ( 'number' === $afbm_kind ? 'number' : 'text' ) ); ?>" id="<?php echo esc_attr( $afbm_fid ); ?>" name="<?php echo esc_attr( $afbm_name ); ?>" class="<?php echo 'number' === $afbm_kind ? 'small-text' : 'regular-text'; ?>" value="<?php echo esc_attr( (string) $afbm_value ); ?>" autocomplete="off" <?php echo $afbm_stored ? 'placeholder="' . esc_attr__( 'Stored — leave empty to keep', 'afrit-backup-manager' ) . '"' : ''; ?>>
							<?php endif; ?>
							<?php if ( $afbm_stored ) : ?>
								<label class="afbm-clear"><input type="checkbox" name="config[<?php echo esc_attr( $afbm_field ); ?>_clear]" value="1"> <?php esc_html_e( 'Remove', 'afrit-backup-manager' ); ?></label>
							<?php endif; ?>
							<?php if ( 'path' === $afbm_field ) : ?>
								<p class="description"><?php echo esc_html( in_array( $afbm_edit['type'], [ 'dropbox', 'onedrive' ], true ) ? __( 'Folder inside the app folder. One folder per site keeps backups of several sites apart.', 'afrit-backup-manager' ) : __( 'Created if it does not exist. Relative paths start in the login directory.', 'afrit-backup-manager' ) ); ?></p>
							<?php elseif ( 'tls_pin' === $afbm_field ) : ?>
								<p class="description"><?php esc_html_e( 'Leave empty: the certificate must be valid for the host name. Only for your own server with a self-signed certificate: press "Test", compare the key shown with the one on your server, and enter it here (sha256//…).', 'afrit-backup-manager' ); ?></p>
							<?php elseif ( 'fingerprint' === $afbm_field ) : ?>
								<p class="description"><?php esc_html_e( 'Filled in automatically on the first successful test, then checked on every connection. Clear it only if the server\'s key changed on purpose.', 'afrit-backup-manager' ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<th scope="row"><label for="afbm-dest-days"><?php esc_html_e( 'Keep backups here for', 'afrit-backup-manager' ); ?></label></th>
					<td>
						<input type="number" id="afbm-dest-days" name="retention_days" min="0" max="3650" class="small-text" value="<?php echo esc_attr( (string) (int) ( $afbm_edit['retention_days'] ?? 0 ) ); ?>"> <?php esc_html_e( 'days', 'afrit-backup-manager' ); ?>
						<p class="description"><?php esc_html_e( '0 = use the remote setting on the Retention tab.', 'afrit-backup-manager' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Save destination', 'afrit-backup-manager' ) ); ?>
		</form>
	</div>
<?php endif; ?>
