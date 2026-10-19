<?php
/**
 * Settings → Files: which folders are backed up.
 *
 * The tree is loaded folder by folder over AJAX (afbm_tree) and edits a
 * set of folder rules kept in a hidden field as JSON; File_Rules applies
 * them during the backup.
 *
 * @package AfritBackupManager
 */

declare( strict_types = 1 );

namespace AfritBackupManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$afbm_settings = Settings::general();
$afbm_rules    = File_Rules::sanitize( (array) ( $afbm_settings['file_rules'] ?? [] ) );
?>
<?php Admin::form_open( 'save_files', Admin::MENU_SETTINGS, [ 'class' => 'afbm-files-form' ] ); ?>
	<p class="description">
		<?php esc_html_e( 'Untick a folder to leave it out of file backups; tick it to include it. A rule applies to everything inside the folder, and the most specific rule wins: you can exclude "uploads" and still include one folder inside it, or include a folder that is skipped by default. Which parts are backed up at all (core, plugins, themes, uploads, other files) is chosen per backup and on the Schedule tab.', 'afrit-backup-manager' ); ?>
	</p>

	<div class="afbm-tree-legend">
		<span><input type="checkbox" checked disabled> <?php esc_html_e( 'included', 'afrit-backup-manager' ); ?></span>
		<span><input type="checkbox" disabled> <?php esc_html_e( 'excluded', 'afrit-backup-manager' ); ?></span>
		<span><?php echo Admin::badge( __( 'rule', 'afrit-backup-manager' ), '#2271b1' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- badge() escapes. ?> <?php esc_html_e( 'your own setting for this folder', 'afrit-backup-manager' ); ?></span>
		<span><?php echo Admin::badge( __( 'default', 'afrit-backup-manager' ), '#646970' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- badge() escapes. ?> <?php esc_html_e( 'skipped by default (cache, other backups)', 'afrit-backup-manager' ); ?></span>
	</div>

	<div id="afbm-tree" class="afbm-tree" aria-live="polite"
		data-rules="<?php echo esc_attr( (string) wp_json_encode( (object) $afbm_rules ) ); ?>">
		<p class="afbm-muted"><?php esc_html_e( 'Loading folders…', 'afrit-backup-manager' ); ?></p>
	</div>
	<input type="hidden" name="file_rules" id="afbm-file-rules" value="<?php echo esc_attr( (string) wp_json_encode( (object) $afbm_rules ) ); ?>">

	<h2><?php esc_html_e( 'Your folder rules', 'afrit-backup-manager' ); ?></h2>
	<div id="afbm-rule-list" class="afbm-rule-list"></div>

	<h2><?php esc_html_e( 'Advanced', 'afrit-backup-manager' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="afbm-excludes"><?php esc_html_e( 'Exclude by pattern', 'afrit-backup-manager' ); ?></label></th>
			<td>
				<textarea id="afbm-excludes" name="excludes" rows="4" class="large-text code"><?php echo esc_textarea( (string) $afbm_settings['excludes'] ); ?></textarea>
				<p class="description"><?php esc_html_e( 'One pattern per line, for files or folders anywhere. Examples: *.log  ·  *.tmp  ·  content/uploads/*/cache/*. Paths start with root/ (the WordPress directory) or content/ (wp-content). Folder rules from the tree take precedence; file-name patterns such as *.log apply everywhere.', 'afrit-backup-manager' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="afbm-maxsize"><?php esc_html_e( 'Skip files larger than', 'afrit-backup-manager' ); ?></label></th>
			<td><input type="number" id="afbm-maxsize" name="exclude_larger_than_mb" min="0" class="small-text" value="<?php echo esc_attr( (string) $afbm_settings['exclude_larger_than_mb'] ); ?>"> MB <span class="description"><?php esc_html_e( '0 = no limit', 'afrit-backup-manager' ); ?></span></td>
		</tr>
	</table>

	<?php submit_button(); ?>
</form>
