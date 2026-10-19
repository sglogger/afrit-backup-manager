<?php
/**
 * Settings, one tab per area.
 *
 * @package AfritBackupManager
 */

declare( strict_types = 1 );

namespace AfritBackupManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$afbm_tab  = Admin::current_tab();
$afbm_tabs = [
	'general'    => __( 'General', 'afrit-backup-manager' ),
	'schedule'   => __( 'Schedule', 'afrit-backup-manager' ),
	'storage'    => __( 'Storage', 'afrit-backup-manager' ),
	'files'      => __( 'Files', 'afrit-backup-manager' ),
	'database'   => __( 'Database', 'afrit-backup-manager' ),
	'encryption' => __( 'Encryption', 'afrit-backup-manager' ),
	'retention'  => __( 'Retention', 'afrit-backup-manager' ),
	'security'   => __( 'Security', 'afrit-backup-manager' ),
];
?>
<div class="wrap afbm-wrap">
	<?php Admin::header( __( 'Settings', 'afrit-backup-manager' ) ); ?>

	<h2 class="nav-tab-wrapper">
		<?php foreach ( $afbm_tabs as $afbm_slug => $afbm_label ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Admin::MENU_SETTINGS . '&tab=' . $afbm_slug ) ); ?>" class="nav-tab <?php echo $afbm_slug === $afbm_tab ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $afbm_label ); ?></a>
		<?php endforeach; ?>
	</h2>

	<?php require AFBM_DIR . 'admin/views/settings/tab-' . $afbm_tab . '.php'; ?>
</div>
