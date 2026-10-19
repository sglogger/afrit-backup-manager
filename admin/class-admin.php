<?php
/**
 * Admin surface: menus, screens and form handling.
 *
 * Everything here is gated on `manage_options` and, when configured, on the
 * plugin password. Forms post back to admin.php and are handled on
 * `admin_init` with a single nonce, then redirect (post/redirect/get) with a
 * `afbm_notice` key that render_notice() turns into a banner.
 *
 * @package AfritBackupManager
 */

declare( strict_types = 1 );

namespace AfritBackupManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin {

	/** Capability required for every screen and action in this plugin. */
	public const CAP = 'manage_options';

	public const MENU_DASHBOARD = 'afrit-backup-manager';
	public const MENU_BACKUPS   = 'afrit-backup-manager-backups';
	public const MENU_RESTORE   = 'afrit-backup-manager-restore';
	public const MENU_MIGRATION = 'afrit-backup-manager-migration';
	public const MENU_REPORTS   = 'afrit-backup-manager-reports';
	public const MENU_SETTINGS  = 'afrit-backup-manager-settings';

	public const TABS = [ 'general', 'schedule', 'storage', 'files', 'database', 'encryption', 'retention', 'security' ];

	private const NONCE = 'afbm_admin';

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'handle_post' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
		add_action( 'admin_notices', [ $this, 'notices' ] );
		add_action( 'admin_post', [ OAuth::class, 'callback' ] );
		add_action( 'admin_post_afbm_download', [ $this, 'download' ] );
		add_filter( 'plugin_action_links_' . AFBM_BASENAME, [ $this, 'action_links' ] );

		// The Restore screen is not in the menu; highlight "Backups" for it.
		add_filter( 'submenu_file', static fn( $file ) => self::on_page( self::MENU_RESTORE ) ? self::MENU_BACKUPS : $file );
	}

	private static function on_page( string $page ): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- screen detection only.
		return isset( $_GET['page'] ) && sanitize_key( wp_unslash( (string) $_GET['page'] ) ) === $page;
	}

	// -------------------------------------------------------------------------
	// Menu
	// -------------------------------------------------------------------------

	public function add_menu(): void {
		add_menu_page(
			__( 'Backup Manager', 'afrit-backup-manager' ),
			__( 'Backup Manager', 'afrit-backup-manager' ),
			self::CAP,
			self::MENU_DASHBOARD,
			[ $this, 'render_dashboard' ],
			'dashicons-backup',
			81
		);

		$pages = [
			self::MENU_DASHBOARD => [ __( 'Dashboard', 'afrit-backup-manager' ), 'render_dashboard' ],
			self::MENU_BACKUPS   => [ __( 'Backups', 'afrit-backup-manager' ), 'render_backups' ],
			self::MENU_MIGRATION => [ __( 'Migration', 'afrit-backup-manager' ), 'render_migration' ],
			self::MENU_REPORTS   => [ __( 'Reports', 'afrit-backup-manager' ), 'render_reports' ],
			self::MENU_SETTINGS  => [ __( 'Settings', 'afrit-backup-manager' ), 'render_settings' ],
		];

		foreach ( $pages as $slug => [ $title, $callback ] ) {
			add_submenu_page( self::MENU_DASHBOARD, $title, $title, self::CAP, $slug, [ $this, $callback ] );
		}

		// Reached from the Backups list only. Registered under the plugin menu
		// so WordPress knows its title, then taken out of the visible menu.
		add_submenu_page( self::MENU_DASHBOARD, __( 'Restore', 'afrit-backup-manager' ), __( 'Restore', 'afrit-backup-manager' ), self::CAP, self::MENU_RESTORE, [ $this, 'render_restore' ] );
		add_action( 'admin_head', static fn() => remove_submenu_page( self::MENU_DASHBOARD, self::MENU_RESTORE ) );
	}

	/**
	 * @param string[] $links Existing action links.
	 * @return string[]
	 */
	public function action_links( $links ): array {
		if ( ! current_user_can( self::CAP ) ) {
			return (array) $links;
		}

		array_unshift(
			$links,
			sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'admin.php?page=' . self::MENU_DASHBOARD ) ),
				esc_html__( 'Dashboard', 'afrit-backup-manager' )
			)
		);

		return (array) $links;
	}

	public static function is_own_screen(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- screen detection only.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : '';
		return str_starts_with( $page, self::MENU_DASHBOARD );
	}

	public function enqueue( string $hook ): void {
		if ( ! self::is_own_screen() || ! current_user_can( self::CAP ) ) {
			return;
		}

		wp_enqueue_style( 'afbm-admin', AFBM_URL . 'assets/css/admin.css', [], self::asset_version( 'assets/css/admin.css' ) );
		wp_enqueue_script( 'afbm-admin', AFBM_URL . 'assets/js/admin.js', [], self::asset_version( 'assets/js/admin.js' ), true );

		wp_localize_script(
			'afbm-admin',
			'afbmAdmin',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( Ajax::NONCE ),
				'chunk'   => 8 * 1048576,
				'i18n'    => [
					'running'       => __( 'Running', 'afrit-backup-manager' ),
					'queued'        => __( 'Queued', 'afrit-backup-manager' ),
					'done'          => __( 'Finished. Reloading…', 'afrit-backup-manager' ),
					'error'         => __( 'The status could not be read. If a database restore just finished, log in again.', 'afrit-backup-manager' ),
					/* translators: 1: file name, 2: percentage */
					'uploading'     => __( 'Uploading %1$s: %2$s%%', 'afrit-backup-manager' ),
					'uploaded'      => __( 'Upload complete. Reloading…', 'afrit-backup-manager' ),
					/* translators: %s: error message */
					'uploadFailed'  => __( 'Upload failed: %s', 'afrit-backup-manager' ),
					'confirmDel'    => __( 'Delete this backup from every location? This cannot be undone.', 'afrit-backup-manager' ),
					'confirmRes'    => __( 'Restore now? The selected parts of this site will be overwritten.', 'afrit-backup-manager' ),
					'confirmRemove' => __( 'Remove this destination? Backups already stored there are not deleted.', 'afrit-backup-manager' ),
					'copied'        => __( 'Copied', 'afrit-backup-manager' ),
					'treeError'     => __( 'The folders could not be loaded.', 'afrit-backup-manager' ),
					'treeMore'      => __( 'Only the first 500 folders are shown.', 'afrit-backup-manager' ),
					'ruleBadge'     => __( 'rule', 'afrit-backup-manager' ),
					'defaultBadge'  => __( 'default', 'afrit-backup-manager' ),
					'reset'         => __( 'Reset to inherited', 'afrit-backup-manager' ),
					'included'      => __( 'included', 'afrit-backup-manager' ),
					'excluded'      => __( 'excluded', 'afrit-backup-manager' ),
					'noRules'       => __( 'No folder rules yet: everything in the selected parts is backed up, except the folders skipped by default.', 'afrit-backup-manager' ),
					'remove'        => __( 'Remove', 'afrit-backup-manager' ),
					'expand'        => __( 'Show subfolders', 'afrit-backup-manager' ),
				],
			]
		);
	}

	/**
	 * Asset version: the plugin version, plus the file's modification time
	 * while SCRIPT_DEBUG is on, so browsers never keep a stale stylesheet
	 * during development.
	 */
	private static function asset_version( string $file ): string {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			return AFBM_VERSION . '.' . (int) filemtime( AFBM_DIR . $file );
		}
		return AFBM_VERSION;
	}

	// -------------------------------------------------------------------------
	// Notices — administrators only
	// -------------------------------------------------------------------------

	public function notices(): void {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}

		// The one notice shown outside our own screens: backups are exposed.
		if ( 'exposed' === Paths::exposure()['status'] ) {
			printf(
				'<div class="notice notice-error"><p><strong>%s</strong> %s <a href="%s">%s</a></p></div>',
				esc_html__( 'Backup Manager:', 'afrit-backup-manager' ),
				esc_html__( 'your backup files can be downloaded over the internet by anyone who knows their address.', 'afrit-backup-manager' ),
				esc_url( admin_url( 'admin.php?page=' . self::MENU_SETTINGS . '&tab=security' ) ),
				esc_html__( 'Fix this now', 'afrit-backup-manager' )
			);
		}

		if ( ! self::is_own_screen() ) {
			return;
		}

		if ( ! Archiver::is_available() ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'The PHP zip extension (ZipArchive) is missing. File backups cannot run until your host enables it.', 'afrit-backup-manager' ) . '</p></div>';
		}

		if ( is_multisite() ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'This is a multisite network. Backups cover the whole installation; restoring single sites of a network is not supported.', 'afrit-backup-manager' ) . '</p></div>';
		}
	}

	// -------------------------------------------------------------------------
	// Screens
	// -------------------------------------------------------------------------

	private function screen( string $view ): void {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}

		if ( ! Password_Gate::is_unlocked() ) {
			Password_Gate::render_form();
			return;
		}

		require AFBM_DIR . 'admin/views/' . $view . '.php';
	}

	public function render_dashboard(): void {
		$this->screen( 'page-dashboard' );
	}

	public function render_backups(): void {
		$this->screen( 'page-backups' );
	}

	public function render_restore(): void {
		$this->screen( 'page-restore' );
	}

	public function render_migration(): void {
		$this->screen( 'page-migration' );
	}

	public function render_reports(): void {
		$this->screen( 'page-reports' );
	}

	public function render_settings(): void {
		$this->screen( 'page-settings' );
	}

	public static function current_tab(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation only.
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( (string) $_GET['tab'] ) ) : 'general';
		return in_array( $tab, self::TABS, true ) ? $tab : 'general';
	}

	/**
	 * Page header with the plugin icon.
	 */
	public static function header( string $title ): void {
		printf(
			'<h1 class="afbm-title"><img src="%s" alt="" width="36" height="36"> %s</h1>',
			esc_url( AFBM_URL . 'assets/logos/afrit-backup-manager-icon.svg' ),
			esc_html( $title )
		);
		self::render_notice();
	}

	// -------------------------------------------------------------------------
	// Form handling
	// -------------------------------------------------------------------------

	public static function form_open( string $action, string $page = self::MENU_SETTINGS, array $extra = [] ): void {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin.php?page=' . $page ) ) . '"' . ( ! empty( $extra['class'] ) ? ' class="' . esc_attr( $extra['class'] ) . '"' : '' ) . '>';
		wp_nonce_field( self::NONCE );
		printf( '<input type="hidden" name="afbm_action" value="%s">', esc_attr( $action ) );
	}

	public function handle_post(): void {
		if ( empty( $_POST['afbm_action'] ) ) {
			return;
		}

		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'afrit-backup-manager' ), '', [ 'response' => 403 ] );
		}

		check_admin_referer( self::NONCE );

		if ( ! Password_Gate::is_unlocked() ) {
			wp_die( esc_html__( 'Backup Manager is locked. Enter its password first.', 'afrit-backup-manager' ), '', [ 'response' => 403 ] );
		}

		$action = sanitize_key( wp_unslash( (string) $_POST['afbm_action'] ) );
		$post   = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each field is sanitised individually by the handlers.

		Admin_Actions::dispatch( $action, (array) $post );
	}

	/**
	 * Redirect back to a plugin screen with a notice key, and stop.
	 *
	 * @param array<string, string|int> $args Extra query arguments.
	 */
	public static function redirect( string $page, string $notice, array $args = [] ): void {
		wp_safe_redirect(
			add_query_arg(
				array_merge(
					[
						'page'        => $page,
						'afbm_notice' => $notice,
					],
					$args
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Render the feedback banner after a redirect.
	 */
	public static function render_notice(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only, no action taken.
		$notice = isset( $_GET['afbm_notice'] ) ? sanitize_key( wp_unslash( (string) $_GET['afbm_notice'] ) ) : '';

		if ( '' === $notice ) {
			return;
		}

		$messages = [
			'saved'              => [ 'success', __( 'Settings saved.', 'afrit-backup-manager' ) ],
			'setup_done'         => [ 'success', __( 'All set. Your first backup is running now; scheduled backups take over from tonight.', 'afrit-backup-manager' ) ],
			'backup_started'     => [ 'success', __( 'Backup started. You can leave this page; it continues in the background.', 'afrit-backup-manager' ) ],
			'restore_started'    => [ 'success', __( 'Restore started. Keep this page open to follow it.', 'afrit-backup-manager' ) ],
			'import_started'     => [ 'success', __( 'Import started. When it has finished, the backup appears in the list and can be restored.', 'afrit-backup-manager' ) ],
			'cancelled'          => [ 'warning', __( 'The running job will stop at its next step.', 'afrit-backup-manager' ) ],
			'deleted'            => [ 'success', __( 'Backup deleted.', 'afrit-backup-manager' ) ],
			'kept'               => [ 'success', __( 'Marked as keep. Automatic retention will never delete this backup or the backups it depends on.', 'afrit-backup-manager' ) ],
			'unkept'             => [ 'success', __( 'Keep mark removed. The backup is subject to the retention rules again.', 'afrit-backup-manager' ) ],
			'keep_blocks_delete' => [ 'error', __( 'This backup, or an incremental backup that depends on it, is marked as keep. Remove the mark first.', 'afrit-backup-manager' ) ],
			'delete_partial'     => [ 'warning', __( 'The backup could not be deleted from every location. It stays listed for the remaining copies.', 'afrit-backup-manager' ) ],
			'rescanned'          => [ 'success', __( 'The backup list has been rebuilt from all locations.', 'afrit-backup-manager' ) ],
			'busy'               => [ 'error', __( 'Another job is already running. Wait for it to finish.', 'afrit-backup-manager' ) ],
			'nothing'            => [ 'error', __( 'Nothing was selected.', 'afrit-backup-manager' ) ],
			'invalid'            => [ 'error', __( 'The request was not valid.', 'afrit-backup-manager' ) ],
			'passphrase'         => [ 'error', __( 'The passphrase is wrong.', 'afrit-backup-manager' ) ],
			'passphrase_short'   => [ 'error', __( 'The passphrase must have at least 12 characters, and both fields must match.', 'afrit-backup-manager' ) ],
			'dest_saved'         => [ 'success', __( 'Destination saved.', 'afrit-backup-manager' ) ],
			'dest_deleted'       => [ 'success', __( 'Destination removed. Backups already stored there were not deleted.', 'afrit-backup-manager' ) ],
			'test_ok'            => [ 'success', __( 'Connection test passed: the destination is reachable and writable.', 'afrit-backup-manager' ) ],
			'test_failed'        => [ 'error', self::flash( __( 'Connection test failed.', 'afrit-backup-manager' ) ) ],
			'oauth_ok'           => [ 'success', __( 'Connected. The destination is ready to use.', 'afrit-backup-manager' ) ],
			'oauth_failed'       => [ 'error', __( 'The connection could not be completed. A code is valid only once and for a few minutes — press "Connect" again for a new one. With your own app, also check its credentials and redirect URI.', 'afrit-backup-manager' ) ],
			'mail_ok'            => [ 'success', __( 'The test e-mail was accepted for delivery.', 'afrit-backup-manager' ) ],
			'mail_failed'        => [ 'error', __( 'WordPress could not send the e-mail. Check the site\'s mail configuration.', 'afrit-backup-manager' ) ],
			'exposure_checked'   => [ 'success', __( 'The backup directory has been tested.', 'afrit-backup-manager' ) ],
			'path_invalid'       => [ 'error', __( 'That backup directory could not be created or is not writable. The previous one is still in use.', 'afrit-backup-manager' ) ],
			'path_forbidden'     => [ 'error', __( 'That folder cannot hold backups: it is WordPress itself, wp-content, uploads, plugins or themes, a parent of one of them, or inside code folders. Choose a separate folder, ideally outside the web root. The previous one is still in use.', 'afrit-backup-manager' ) ],
			'path_not_empty'     => [ 'error', __( 'That folder already contains other files. Choose a new or empty folder used only for backups. The previous one is still in use.', 'afrit-backup-manager' ) ],
			'password_set'       => [ 'success', __( 'The Backup Manager password is set.', 'afrit-backup-manager' ) ],
			'password_off'       => [ 'success', __( 'The Backup Manager password has been removed.', 'afrit-backup-manager' ) ],
			'password_wrong'     => [ 'error', __( 'The current password is wrong.', 'afrit-backup-manager' ) ],
			'password_short'     => [ 'error', __( 'The password must have at least 10 characters, and both fields must match.', 'afrit-backup-manager' ) ],
			'gate_wrong'         => [ 'error', __( 'Wrong password.', 'afrit-backup-manager' ) ],
			'gate_locked'        => [ 'error', __( 'Too many wrong passwords. Try again in 15 minutes.', 'afrit-backup-manager' ) ],
			'key_created'        => [ 'success', __( 'Migration key created. Copy it now; it is shown only once.', 'afrit-backup-manager' ) ],
			'key_failed'         => [ 'error', __( 'A migration key needs a backup whose files are stored on this server.', 'afrit-backup-manager' ) ],
			'key_invalid'        => [ 'error', __( 'That is not a valid migration key.', 'afrit-backup-manager' ) ],
			'keys_revoked'       => [ 'success', __( 'All migration keys have been revoked.', 'afrit-backup-manager' ) ],
		];

		if ( ! isset( $messages[ $notice ] ) ) {
			return;
		}

		printf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			esc_attr( $messages[ $notice ][0] ),
			esc_html( $messages[ $notice ][1] )
		);
	}

	/**
	 * Message plus the detail stored for this user by the last action (e.g.
	 * the error text of a connection test).
	 */
	private static function flash( string $message ): string {
		$detail = get_transient( 'afbm_flash_' . get_current_user_id() );
		delete_transient( 'afbm_flash_' . get_current_user_id() );
		return is_string( $detail ) && '' !== $detail ? $message . ' ' . $detail : $message;
	}

	public static function set_flash( string $detail ): void {
		set_transient( 'afbm_flash_' . get_current_user_id(), $detail, MINUTE_IN_SECONDS * 5 );
	}

	// -------------------------------------------------------------------------
	// Downloads
	// -------------------------------------------------------------------------

	/**
	 * URL of the authenticated download of one set file.
	 */
	public static function download_url( string $backup_id, string $file ): string {
		return wp_nonce_url(
			add_query_arg(
				[
					'action' => 'afbm_download',
					'backup' => $backup_id,
					'file'   => $file,
				],
				admin_url( 'admin-post.php' )
			),
			'afbm_download_' . $backup_id
		);
	}

	/**
	 * Stream one file of a backup set to an authenticated administrator.
	 * This is the only way an archive leaves the server through the web.
	 */
	public function download(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'afrit-backup-manager' ), '', [ 'response' => 403 ] );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- verified right below with check_admin_referer().
		$backup = isset( $_GET['backup'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['backup'] ) ) : '';
		$file   = isset( $_GET['file'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['file'] ) ) : '';
		// phpcs:enable

		check_admin_referer( 'afbm_download_' . $backup );

		if ( ! Password_Gate::is_unlocked() ) {
			wp_die( esc_html__( 'Backup Manager is locked. Enter its password first.', 'afrit-backup-manager' ), '', [ 'response' => 403 ] );
		}

		$manifest = Catalog::get( $backup );
		if ( null === $manifest || ! in_array( $file, $manifest->all_file_names(), true ) || ! Util::is_safe_filename( $file ) ) {
			wp_die( esc_html__( 'Unknown file.', 'afrit-backup-manager' ), '', [ 'response' => 404 ] );
		}

		$path = Paths::backup_dir() . '/' . $file;
		if ( ! is_file( $path ) ) {
			wp_die( esc_html__( 'This file is not stored on this server. Download it from the remote destination, or restore from there directly.', 'afrit-backup-manager' ), '', [ 'response' => 404 ] );
		}

		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		nocache_headers();
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . $file . '"' );
		header( 'Content-Length: ' . (string) filesize( $path ) );
		header( 'X-Content-Type-Options: nosniff' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- streaming a large archive; WP_Filesystem would load it into memory.
		readfile( $path );
		exit;
	}

	// -------------------------------------------------------------------------
	// Small view helpers
	// -------------------------------------------------------------------------

	public static function badge( string $text, string $color ): string {
		return sprintf( '<span class="afbm-badge" style="background:%s;">%s</span>', esc_attr( $color ), esc_html( $text ) );
	}

	public static function type_label( string $type ): string {
		switch ( $type ) {
			case Manifest::TYPE_FULL:
				return __( 'Full', 'afrit-backup-manager' );
			case Manifest::TYPE_INCREMENTAL:
				return __( 'Incremental', 'afrit-backup-manager' );
			case Manifest::TYPE_PARTIAL:
				return __( 'Partial', 'afrit-backup-manager' );
			case Manifest::TYPE_DATABASE:
				return __( 'Database', 'afrit-backup-manager' );
		}
		return $type;
	}

	public static function type_color( string $type ): string {
		return [
			Manifest::TYPE_FULL        => '#2271b1',
			Manifest::TYPE_INCREMENTAL => '#00a32a',
			Manifest::TYPE_PARTIAL     => '#646970',
			Manifest::TYPE_DATABASE    => '#8c5ec4',
		][ $type ] ?? '#646970';
	}
}
