<?php
/**
 * Admin screens and exports.
 *
 * @package EmailRequiredDownload
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ERD_Admin {
	private $repository;

	public function __construct( ERD_Repository $repository ) {
		$this->repository = $repository;
	}

	public function init() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_post_erd_save_offer', array( $this, 'save_offer' ) );
		add_action( 'admin_post_erd_delete_offer', array( $this, 'delete_offer' ) );
		add_action( 'admin_post_erd_save_settings', array( $this, 'save_settings' ) );
		add_action( 'admin_post_erd_export_leads', array( $this, 'export_leads' ) );
	}

	public function admin_menu() {
		add_menu_page(
			__( 'Email Downloads', 'email-required-to-download' ),
			__( 'Email Downloads', 'email-required-to-download' ),
			'manage_options',
			'erd-offers',
			array( $this, 'render_offers_page' ),
			'dashicons-download',
			58
		);
		add_submenu_page( 'erd-offers', __( 'Offers', 'email-required-to-download' ), __( 'Offers', 'email-required-to-download' ), 'manage_options', 'erd-offers', array( $this, 'render_offers_page' ) );
		add_submenu_page( 'erd-offers', __( 'Add/Edit Offer', 'email-required-to-download' ), __( 'Add New', 'email-required-to-download' ), 'manage_options', 'erd-edit-offer', array( $this, 'render_edit_offer_page' ) );
		add_submenu_page( 'erd-offers', __( 'Leads & Analytics', 'email-required-to-download' ), __( 'Leads & Analytics', 'email-required-to-download' ), 'manage_options', 'erd-leads', array( $this, 'render_leads_page' ) );
		add_submenu_page( 'erd-offers', __( 'Settings', 'email-required-to-download' ), __( 'Settings', 'email-required-to-download' ), 'manage_options', 'erd-settings', array( $this, 'render_settings_page' ) );
	}

	public function enqueue( $hook ) {
		if ( false === strpos( $hook, 'erd-' ) ) {
			return;
		}

		wp_enqueue_style( 'erd-admin', ERD_PLUGIN_URL . 'assets/css/admin.css', array(), ERD_VERSION );
		wp_enqueue_media();
		wp_enqueue_script( 'erd-admin', ERD_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), ERD_VERSION, true );
	}

	private function admin_url( $page, array $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) );
	}

	private function notice() {
		if ( empty( $_GET['erd_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$messages = array(
			'saved'    => __( 'Saved.', 'email-required-to-download' ),
			'deleted'  => __( 'Deleted.', 'email-required-to-download' ),
			'settings' => __( 'Settings saved.', 'email-required-to-download' ),
		);
		$key      = sanitize_key( wp_unslash( $_GET['erd_notice'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( isset( $messages[ $key ] ) ) {
			printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $messages[ $key ] ) );
		}
	}

	public function render_offers_page() {
		$this->notice();
		$offers = $this->repository->get_offers();
		?>
		<div class="wrap erd-admin erd-admin-form-page erd-admin-edit">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Email Downloads', 'email-required-to-download' ); ?></h1>
			<a class="page-title-action" href="<?php echo esc_url( $this->admin_url( 'erd-edit-offer' ) ); ?>"><?php esc_html_e( 'Add New', 'email-required-to-download' ); ?></a>
			<hr class="wp-header-end">
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Title', 'email-required-to-download' ); ?></th>
						<th><?php esc_html_e( 'Shortcode', 'email-required-to-download' ); ?></th>
						<th><?php esc_html_e( 'Submissions', 'email-required-to-download' ); ?></th>
						<th><?php esc_html_e( 'Unique Emails', 'email-required-to-download' ); ?></th>
						<th><?php esc_html_e( 'Downloads', 'email-required-to-download' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'email-required-to-download' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( $offers ) : ?>
						<?php foreach ( $offers as $offer ) : ?>
							<?php $stats = $this->repository->get_offer_stats( $offer->id ); ?>
							<tr>
								<td><strong><?php echo esc_html( $offer->title ); ?></strong></td>
								<td><code>[email_required_download id="<?php echo esc_attr( $offer->id ); ?>"]</code></td>
								<td><?php echo esc_html( $stats['submissions'] ); ?></td>
								<td><?php echo esc_html( $stats['unique_emails'] ); ?></td>
								<td><?php echo esc_html( $stats['downloads'] ); ?></td>
								<td>
									<a href="<?php echo esc_url( $this->admin_url( 'erd-edit-offer', array( 'offer_id' => $offer->id ) ) ); ?>"><?php esc_html_e( 'Edit', 'email-required-to-download' ); ?></a>
									|
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=erd_delete_offer&offer_id=' . absint( $offer->id ) ), 'erd_delete_offer_' . absint( $offer->id ) ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this offer?', 'email-required-to-download' ) ); ?>');"><?php esc_html_e( 'Delete', 'email-required-to-download' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr><td colspan="6"><?php esc_html_e( 'No offers yet.', 'email-required-to-download' ); ?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function render_edit_offer_page() {
		$offer_id = isset( $_GET['offer_id'] ) ? absint( $_GET['offer_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$offer    = $offer_id ? $this->repository->get_offer( $offer_id ) : null;
		$files    = $offer_id ? $this->repository->get_offer_files( $offer_id ) : array();
		$settings = $this->repository->get_settings();
		$defaults = array(
			'id'                   => 0,
			'title'                => '',
			'show_title'           => 0,
			'description'          => '',
			'file_id'              => 0,
			'file_url'             => '',
			'button_text'          => $settings['default_button_text'],
			'delivery_mode'        => 'public',
			'force_download'       => 0,
			'use_button_colors'    => 0,
			'button_bg_color'      => '',
			'button_text_color'    => '',
			'success_message'      => __( 'Thanks. Your download is ready.', 'email-required-to-download' ),
			'name_required'        => 0,
			'require_confirmation' => 0,
			'consent_text'         => __( 'I agree to be contacted about this download and understand my information will be stored according to the site privacy policy.', 'email-required-to-download' ),
		);
		$data     = $offer ? wp_parse_args( (array) $offer, $defaults ) : $defaults;
		if ( ! $files ) {
			$files = array(
				(object) array(
					'file_id'    => $data['file_id'],
					'file_url'   => $data['file_url'],
					'file_label' => $data['title'],
				),
			);
		}
		?>
		<div class="wrap erd-admin">
			<?php $this->notice(); ?>
			<h1><?php echo $offer ? esc_html__( 'Edit Offer', 'email-required-to-download' ) : esc_html__( 'Add Offer', 'email-required-to-download' ); ?></h1>
			<?php if ( $offer ) : ?>
				<p class="erd-shortcode">
					<strong><?php esc_html_e( 'Shortcode:', 'email-required-to-download' ); ?></strong>
					<code>[email_required_download id="<?php echo esc_attr( $offer->id ); ?>"]</code>
				</p>
			<?php endif; ?>
			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="erd_save_offer">
				<input type="hidden" name="offer_id" value="<?php echo esc_attr( $data['id'] ); ?>">
				<?php wp_nonce_field( 'erd_save_offer' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="erd-title"><?php esc_html_e( 'Offer title', 'email-required-to-download' ); ?></label></th>
						<td>
							<input class="regular-text" id="erd-title" name="title" required value="<?php echo esc_attr( $data['title'] ); ?>">
							<p class="description"><?php esc_html_e( 'Used to identify this offer in admin. You can also show it above the public form.', 'email-required-to-download' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Display options', 'email-required-to-download' ); ?></th>
						<td>
							<label><input type="checkbox" name="show_title" value="1" <?php checked( $data['show_title'] ); ?>> <?php esc_html_e( 'Show offer title on the download form', 'email-required-to-download' ); ?></label>
							<p class="description"><?php esc_html_e( 'Leave unchecked if your page already introduces the download.', 'email-required-to-download' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="erd-description"><?php esc_html_e( 'Description', 'email-required-to-download' ); ?></label></th>
						<td>
							<?php
							wp_editor(
								$data['description'],
								'erd_description',
								array(
									'media_buttons' => false,
									'quicktags'     => true,
									'textarea_name' => 'description',
									'textarea_rows' => 4,
									'teeny'         => true,
								)
							);
							?>
							<p class="description"><?php esc_html_e( 'Optional text shown above the email form.', 'email-required-to-download' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Files', 'email-required-to-download' ); ?></th>
						<td>
							<div class="erd-files" data-empty-label="<?php echo esc_attr__( 'File label', 'email-required-to-download' ); ?>">
								<?php foreach ( $files as $file ) : ?>
									<div class="erd-file-row">
										<input class="erd-file-id" type="hidden" name="file_ids[]" value="<?php echo esc_attr( $file->file_id ); ?>">
										<input class="erd-protected-path" type="hidden" name="protected_paths[]" value="<?php echo esc_attr( isset( $file->protected_path ) ? $file->protected_path : '' ); ?>">
										<input class="regular-text erd-file-url" type="url" name="file_urls[]" placeholder="<?php echo esc_attr__( 'File URL', 'email-required-to-download' ); ?>" value="<?php echo esc_attr( $file->file_url ); ?>">
										<input class="regular-text erd-file-label" type="text" name="file_labels[]" placeholder="<?php echo esc_attr__( 'File label', 'email-required-to-download' ); ?>" value="<?php echo esc_attr( $file->file_label ); ?>">
										<button type="button" class="button erd-select-file"><?php esc_html_e( 'Select File', 'email-required-to-download' ); ?></button>
										<button type="button" class="button erd-remove-file"><?php esc_html_e( 'Remove', 'email-required-to-download' ); ?></button>
										<label class="erd-protected-upload">
											<span><?php esc_html_e( 'Protected upload', 'email-required-to-download' ); ?></span>
											<input type="file" name="protected_uploads[]">
										</label>
									</div>
								<?php endforeach; ?>
							</div>
							<p><button type="button" class="button erd-add-file"><?php esc_html_e( 'Add File', 'email-required-to-download' ); ?></button></p>
							<p class="description"><?php esc_html_e( 'Visitors submit the form once to unlock every file in this offer.', 'email-required-to-download' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Delivery', 'email-required-to-download' ); ?></th>
						<td>
							<fieldset class="erd-delivery-options">
								<label><input type="radio" name="delivery_mode" value="public" <?php checked( $data['delivery_mode'], 'public' ); ?>> <?php esc_html_e( 'Public URL delivery', 'email-required-to-download' ); ?></label>
								<p class="description"><?php esc_html_e( 'Collect an email before showing links, but files remain accessible anywhere their public URL is known.', 'email-required-to-download' ); ?></p>
								<label><input type="radio" name="delivery_mode" value="protected" <?php checked( $data['delivery_mode'], 'protected' ); ?>> <?php esc_html_e( 'Protected delivery', 'email-required-to-download' ); ?></label>
								<p class="description"><?php esc_html_e( 'Use protected uploads for truly private files, or copy selected Media Library files into private plugin storage. Existing public media URLs may remain accessible if already known.', 'email-required-to-download' ); ?></p>
								<label class="erd-force-download"><input type="checkbox" name="force_download" value="1" <?php checked( $data['force_download'] ); ?>> <?php esc_html_e( 'Force files to download instead of opening in the browser', 'email-required-to-download' ); ?></label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="erd-button-text"><?php esc_html_e( 'Button text', 'email-required-to-download' ); ?></label></th>
						<td>
							<input class="regular-text" id="erd-button-text" name="button_text" value="<?php echo esc_attr( $data['button_text'] ); ?>">
							<p class="description"><?php esc_html_e( 'Shown on the form submit button and single-file download button.', 'email-required-to-download' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Button colors', 'email-required-to-download' ); ?></th>
						<td>
							<div class="erd-color-fields">
								<label class="erd-color-toggle">
									<input type="checkbox" name="use_button_colors" value="1" <?php checked( $data['use_button_colors'] ); ?>>
									<span><?php esc_html_e( 'Use custom colors', 'email-required-to-download' ); ?></span>
								</label>
								<label>
									<span><?php esc_html_e( 'Background', 'email-required-to-download' ); ?></span>
									<input type="color" name="button_bg_color" value="<?php echo esc_attr( $data['button_bg_color'] ? $data['button_bg_color'] : '#1d2327' ); ?>">
								</label>
								<label>
									<span><?php esc_html_e( 'Text', 'email-required-to-download' ); ?></span>
									<input type="color" name="button_text_color" value="<?php echo esc_attr( $data['button_text_color'] ? $data['button_text_color'] : '#ffffff' ); ?>">
								</label>
							</div>
							<p class="description"><?php esc_html_e( 'Optional. Leave custom colors off to use the default button style.', 'email-required-to-download' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="erd-success-message"><?php esc_html_e( 'Success message', 'email-required-to-download' ); ?></label></th>
						<td>
							<?php
							wp_editor(
								$data['success_message'],
								'erd_success_message',
								array(
									'media_buttons' => false,
									'quicktags'     => true,
									'textarea_name' => 'success_message',
									'textarea_rows' => 4,
									'teeny'         => true,
								)
							);
							?>
							<p class="description"><?php esc_html_e( 'Shown after a visitor submits the form or opens a valid confirmation link.', 'email-required-to-download' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Form fields', 'email-required-to-download' ); ?></th>
						<td>
							<label><input type="checkbox" name="name_required" value="1" <?php checked( $data['name_required'] ); ?>> <?php esc_html_e( 'Require name field', 'email-required-to-download' ); ?></label><br>
							<label><input type="checkbox" name="require_confirmation" value="1" <?php checked( $data['require_confirmation'] ); ?>> <?php esc_html_e( 'Require email confirmation before download', 'email-required-to-download' ); ?></label>
							<p class="description"><?php esc_html_e( 'Email confirmation sends a link instead of showing downloads immediately.', 'email-required-to-download' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="erd-consent-text"><?php esc_html_e( 'Consent text', 'email-required-to-download' ); ?></label></th>
						<td>
							<textarea class="large-text" rows="3" id="erd-consent-text" name="consent_text" required><?php echo esc_textarea( $data['consent_text'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Required checkbox text shown on the public form.', 'email-required-to-download' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save Offer', 'email-required-to-download' ) ); ?>
			</form>
		</div>
		<?php
	}

	public function render_leads_page() {
		$offer_id = isset( $_GET['offer_id'] ) ? absint( $_GET['offer_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$offers   = $this->repository->get_offers();
		$stats    = $this->repository->get_offer_stats( $offer_id );
		$leads    = $this->repository->get_leads( $offer_id, 100 );
		?>
		<div class="wrap erd-admin erd-admin-form-page">
			<h1><?php esc_html_e( 'Leads & Analytics', 'email-required-to-download' ); ?></h1>
			<div class="erd-stats">
				<div><strong><?php echo esc_html( $stats['submissions'] ); ?></strong><span><?php esc_html_e( 'Submissions', 'email-required-to-download' ); ?></span></div>
				<div><strong><?php echo esc_html( $stats['unique_emails'] ); ?></strong><span><?php esc_html_e( 'Unique Emails', 'email-required-to-download' ); ?></span></div>
				<div><strong><?php echo esc_html( $stats['downloads'] ); ?></strong><span><?php esc_html_e( 'Downloads', 'email-required-to-download' ); ?></span></div>
			</div>
			<form method="get" class="erd-filter">
				<input type="hidden" name="page" value="erd-leads">
				<select name="offer_id">
					<option value="0"><?php esc_html_e( 'All offers', 'email-required-to-download' ); ?></option>
					<?php foreach ( $offers as $offer ) : ?>
						<option value="<?php echo esc_attr( $offer->id ); ?>" <?php selected( $offer_id, $offer->id ); ?>><?php echo esc_html( $offer->title ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php submit_button( __( 'Filter', 'email-required-to-download' ), 'secondary', '', false ); ?>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=erd_export_leads&offer_id=' . absint( $offer_id ) ), 'erd_export_leads' ) ); ?>"><?php esc_html_e( 'Export CSV', 'email-required-to-download' ); ?></a>
			</form>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Offer', 'email-required-to-download' ); ?></th><th><?php esc_html_e( 'Email', 'email-required-to-download' ); ?></th><th><?php esc_html_e( 'Name', 'email-required-to-download' ); ?></th><th><?php esc_html_e( 'Submitted', 'email-required-to-download' ); ?></th><th><?php esc_html_e( 'Email Status', 'email-required-to-download' ); ?></th><th><?php esc_html_e( 'Downloads', 'email-required-to-download' ); ?></th><th><?php esc_html_e( 'Downloaded', 'email-required-to-download' ); ?></th></tr></thead>
				<tbody>
					<?php if ( $leads ) : ?>
						<?php foreach ( $leads as $lead ) : ?>
							<tr>
								<td><?php echo esc_html( $lead->offer_title ); ?></td>
								<td><a href="mailto:<?php echo esc_attr( $lead->email ); ?>"><?php echo esc_html( $lead->email ); ?></a></td>
								<td><?php echo esc_html( $lead->name ); ?></td>
								<td><?php echo esc_html( $lead->submitted_at ); ?></td>
								<td><?php echo esc_html( $this->email_status_label( $lead ) ); ?></td>
								<td><?php echo esc_html( $lead->download_count ); ?></td>
								<td><?php echo esc_html( $lead->downloaded_at ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr><td colspan="7"><?php esc_html_e( 'No leads yet.', 'email-required-to-download' ); ?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function render_settings_page() {
		$this->notice();
		$settings = $this->repository->get_settings();
		?>
		<div class="wrap erd-admin erd-admin-form-page">
			<h1><?php esc_html_e( 'Settings', 'email-required-to-download' ); ?></h1>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="erd_save_settings">
				<?php wp_nonce_field( 'erd_save_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="erd-token-expiry"><?php esc_html_e( 'Token expiry hours', 'email-required-to-download' ); ?></label></th>
						<td>
							<input id="erd-token-expiry" type="number" min="1" max="720" name="token_expiry_hours" value="<?php echo esc_attr( $settings['token_expiry_hours'] ); ?>">
							<p class="description"><?php esc_html_e( 'How long confirmation links stay valid after someone submits the form.', 'email-required-to-download' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="erd-default-button"><?php esc_html_e( 'Default button text', 'email-required-to-download' ); ?></label></th>
						<td>
							<input id="erd-default-button" class="regular-text" name="default_button_text" value="<?php echo esc_attr( $settings['default_button_text'] ); ?>">
							<p class="description"><?php esc_html_e( 'Used when creating new offers. Existing offers keep their own button text.', 'email-required-to-download' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'IP hash salt', 'email-required-to-download' ); ?></th>
						<td>
							<code><?php echo esc_html( substr( $settings['ip_hash_salt'], 0, 8 ) ); ?>...</code><br>
							<label><input type="checkbox" name="regenerate_ip_salt" value="1"> <?php esc_html_e( 'Regenerate salt for future leads', 'email-required-to-download' ); ?></label>
							<p class="description"><?php esc_html_e( 'Used to store privacy-friendlier hashed IP addresses instead of raw IPs.', 'email-required-to-download' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save Settings', 'email-required-to-download' ) ); ?>
			</form>
		</div>
		<?php
	}

	public function save_offer() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'erd_save_offer' ) ) {
			wp_die( esc_html__( 'You are not allowed to save this offer.', 'email-required-to-download' ) );
		}

		$files       = array();
		$file_ids    = isset( $_POST['file_ids'] ) && is_array( $_POST['file_ids'] ) ? array_map( 'absint', wp_unslash( $_POST['file_ids'] ) ) : array();
		$file_urls   = isset( $_POST['file_urls'] ) && is_array( $_POST['file_urls'] ) ? array_map( 'esc_url_raw', wp_unslash( $_POST['file_urls'] ) ) : array();
		$file_labels = isset( $_POST['file_labels'] ) && is_array( $_POST['file_labels'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['file_labels'] ) ) : array();
		$protected_paths = isset( $_POST['protected_paths'] ) && is_array( $_POST['protected_paths'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['protected_paths'] ) ) : array();
		$protected_uploads = $this->protected_uploads();
		foreach ( $file_urls as $index => $file_url ) {
			$file_id = isset( $file_ids[ $index ] ) ? absint( $file_ids[ $index ] ) : 0;
			if ( $file_id ) {
				$attachment_url = wp_get_attachment_url( $file_id );
				if ( $attachment_url ) {
					$file_url = $attachment_url;
				}
			}

			$files[] = array(
				'file_id'    => $file_id,
				'file_url'   => $file_url,
				'file_label' => isset( $file_labels[ $index ] ) ? $file_labels[ $index ] : '',
				'protected_path' => isset( $protected_paths[ $index ] ) ? $protected_paths[ $index ] : '',
				'protected_upload' => isset( $protected_uploads[ $index ] ) ? $protected_uploads[ $index ] : array(),
			);
		}

		$delivery_mode = isset( $_POST['delivery_mode'] ) && 'protected' === sanitize_key( wp_unslash( $_POST['delivery_mode'] ) ) ? 'protected' : 'public';
		if ( 'protected' === $delivery_mode ) {
			$protected_files = $this->repository->prepare_protected_files( $files );
			if ( false === $protected_files ) {
				wp_die( esc_html__( 'Protected delivery requires files selected from the local Media Library so they can be copied into private storage.', 'email-required-to-download' ) );
			}
			$files = $protected_files;
		}

		$data = array(
			'title'                => isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '',
			'show_title'           => ! empty( $_POST['show_title'] ),
			'description'          => isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '',
			'files'                => $files,
			'button_text'          => isset( $_POST['button_text'] ) ? sanitize_text_field( wp_unslash( $_POST['button_text'] ) ) : '',
			'delivery_mode'        => $delivery_mode,
			'force_download'       => ! empty( $_POST['force_download'] ),
			'use_button_colors'    => ! empty( $_POST['use_button_colors'] ),
			'button_bg_color'      => isset( $_POST['button_bg_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['button_bg_color'] ) ) : '',
			'button_text_color'    => isset( $_POST['button_text_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['button_text_color'] ) ) : '',
			'success_message'      => isset( $_POST['success_message'] ) ? wp_kses_post( wp_unslash( $_POST['success_message'] ) ) : '',
			'name_required'        => ! empty( $_POST['name_required'] ),
			'require_confirmation' => ! empty( $_POST['require_confirmation'] ),
			'consent_text'         => isset( $_POST['consent_text'] ) ? wp_kses_post( wp_unslash( $_POST['consent_text'] ) ) : '',
		);

		if ( '' === $data['title'] || ( 'public' === $delivery_mode && ! $this->has_file_url( $files ) ) || '' === $data['consent_text'] ) {
			wp_die( esc_html__( 'Title, at least one file, and consent text are required.', 'email-required-to-download' ) );
		}

		$offer_id = $this->repository->save_offer( $data, isset( $_POST['offer_id'] ) ? absint( $_POST['offer_id'] ) : 0 );
		wp_safe_redirect(
			$this->admin_url(
				'erd-edit-offer',
				array(
					'offer_id'    => $offer_id,
					'erd_notice'  => 'saved',
				)
			)
		);
		exit;
	}

	public function delete_offer() {
		$offer_id = isset( $_GET['offer_id'] ) ? absint( $_GET['offer_id'] ) : 0;
		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '', 'erd_delete_offer_' . $offer_id ) ) {
			wp_die( esc_html__( 'You are not allowed to delete this offer.', 'email-required-to-download' ) );
		}
		$this->repository->delete_offer( $offer_id );
		wp_safe_redirect( $this->admin_url( 'erd-offers', array( 'erd_notice' => 'deleted' ) ) );
		exit;
	}

	public function save_settings() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'erd_save_settings' ) ) {
			wp_die( esc_html__( 'You are not allowed to save settings.', 'email-required-to-download' ) );
		}

		$settings = $this->repository->get_settings();
		$new      = array(
			'token_expiry_hours' => isset( $_POST['token_expiry_hours'] ) ? max( 1, min( 720, absint( $_POST['token_expiry_hours'] ) ) ) : 24,
			'default_button_text' => isset( $_POST['default_button_text'] ) ? sanitize_text_field( wp_unslash( $_POST['default_button_text'] ) ) : __( 'Download', 'email-required-to-download' ),
			'ip_hash_salt'       => ! empty( $_POST['regenerate_ip_salt'] ) ? wp_generate_password( 32, true, true ) : $settings['ip_hash_salt'],
		);

		$this->repository->save_settings( $new );
		wp_safe_redirect( $this->admin_url( 'erd-settings', array( 'erd_notice' => 'settings' ) ) );
		exit;
	}

	public function export_leads() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'erd_export_leads' ) ) {
			wp_die( esc_html__( 'You are not allowed to export leads.', 'email-required-to-download' ) );
		}

		$offer_id = isset( $_GET['offer_id'] ) ? absint( $_GET['offer_id'] ) : 0;
		$leads    = $this->repository->get_leads( $offer_id, 100000 );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=email-required-download-leads.csv' );

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array( 'Offer', 'Email', 'Name', 'Consent Given', 'Consent Text', 'Consent At', 'Submitted At', 'Email Status', 'Email Sent At', 'Downloaded At', 'Download Count', 'Referrer', 'IP Hash', 'User Agent' ) );
		foreach ( $leads as $lead ) {
			fputcsv( $output, array( $lead->offer_title, $lead->email, $lead->name, $lead->consent_given, $lead->consent_text, $lead->consent_at, $lead->submitted_at, $lead->email_status, $lead->email_sent_at, $lead->downloaded_at, $lead->download_count, $lead->referrer, $lead->ip_hash, $lead->user_agent ) );
		}
		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	private function has_file_url( array $files ) {
		foreach ( $files as $file ) {
			if ( ! empty( $file['file_url'] ) ) {
				return true;
			}
		}

		return false;
	}

	private function protected_uploads() {
		// File data is read only after save_offer() has passed check_admin_referer().
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
		if ( empty( $_FILES['protected_uploads'] ) || ! is_array( $_FILES['protected_uploads'] ) ) {
			return array();
		}

		if ( empty( $_FILES['protected_uploads']['name'] ) || ! is_array( $_FILES['protected_uploads']['name'] ) ) {
			return array();
		}

		$uploads = array();
		foreach ( $_FILES['protected_uploads']['name'] as $index => $name ) {
			$uploads[ $index ] = array(
				'name'     => sanitize_file_name( wp_unslash( $name ) ),
				'type'     => isset( $_FILES['protected_uploads']['type'][ $index ] ) ? sanitize_mime_type( $_FILES['protected_uploads']['type'][ $index ] ) : '',
				'tmp_name' => isset( $_FILES['protected_uploads']['tmp_name'][ $index ] ) ? sanitize_text_field( $_FILES['protected_uploads']['tmp_name'][ $index ] ) : '',
				'error'    => isset( $_FILES['protected_uploads']['error'][ $index ] ) ? absint( $_FILES['protected_uploads']['error'][ $index ] ) : UPLOAD_ERR_NO_FILE,
				'size'     => isset( $_FILES['protected_uploads']['size'][ $index ] ) ? absint( $_FILES['protected_uploads']['size'][ $index ] ) : 0,
			);
		}

		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
		return $uploads;
	}

	private function email_status_label( $lead ) {
		$status = isset( $lead->email_status ) ? $lead->email_status : 'not_required';
		if ( 'sent' === $status && ! empty( $lead->email_sent_at ) ) {
			return sprintf(
				/* translators: %s: date and time the email was sent */
				__( 'Sent %s', 'email-required-to-download' ),
				$lead->email_sent_at
			);
		}

		$labels = array(
			'not_required' => __( 'Not required', 'email-required-to-download' ),
			'pending'      => __( 'Pending', 'email-required-to-download' ),
			'sent'         => __( 'Sent', 'email-required-to-download' ),
			'failed'       => __( 'Failed', 'email-required-to-download' ),
		);

		return isset( $labels[ $status ] ) ? $labels[ $status ] : $labels['not_required'];
	}
}
