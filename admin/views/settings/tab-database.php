<?php
/**
 * Settings → Database: which tables are backed up.
 *
 * @package AfritBackupManager
 */

declare( strict_types = 1 );

namespace AfritBackupManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$afbm_db     = Settings::get( Installer::OPTION_DATABASE );
$afbm_tables = Database_Dumper::site_tables();

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- table sizes for display.
$afbm_status = (array) $wpdb->get_results( $wpdb->prepare( 'SHOW TABLE STATUS WHERE `Name` LIKE %s', $wpdb->esc_like( $wpdb->prefix ) . '%' ), ARRAY_A );
$afbm_sizes  = [];
foreach ( $afbm_status as $afbm_row ) {
	$afbm_sizes[ (string) $afbm_row['Name'] ] = [ (int) $afbm_row['Data_length'] + (int) $afbm_row['Index_length'], (int) $afbm_row['Rows'] ];
}
?>
<?php Admin::form_open( 'save_database' ); ?>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Tables', 'afrit-backup-manager' ); ?></th>
			<td>
				<label><input type="radio" name="mode" value="all" <?php checked( 'all', $afbm_db['mode'] ); ?>> <?php esc_html_e( 'All tables of this site (recommended)', 'afrit-backup-manager' ); ?></label><br>
				<label><input type="radio" name="mode" value="selected" <?php checked( 'selected', $afbm_db['mode'] ); ?>> <?php esc_html_e( 'Only the tables selected below', 'afrit-backup-manager' ); ?></label>
				<p class="description">
					<?php
					/* translators: %s: table prefix */
					echo esc_html( sprintf( __( 'Tables with the prefix %s. New tables created by plugins later are included automatically with "All tables".', 'afrit-backup-manager' ), $wpdb->prefix ) );
					?>
				</p>
			</td>
		</tr>
	</table>

	<p><button type="button" class="button-link afbm-toggle-all" data-target=".afbm-table-list"><?php esc_html_e( 'Select all / none', 'afrit-backup-manager' ); ?></button></p>
	<div class="afbm-table-list afbm-table-list-wide">
		<?php foreach ( $afbm_tables as $afbm_table ) : ?>
			<?php $afbm_info = $afbm_sizes[ $afbm_table ] ?? [ 0, 0 ]; ?>
			<label>
				<input type="checkbox" name="tables[]" value="<?php echo esc_attr( $afbm_table ); ?>" <?php checked( 'all' === $afbm_db['mode'] || in_array( $afbm_table, (array) $afbm_db['tables'], true ) ); ?>>
				<code><?php echo esc_html( $afbm_table ); ?></code>
				<span class="afbm-muted"><?php echo esc_html( Util::bytes( $afbm_info[0] ) . ' · ' . number_format_i18n( $afbm_info[1] ) ); ?></span>
			</label>
		<?php endforeach; ?>
	</div>
	<?php submit_button(); ?>
</form>
