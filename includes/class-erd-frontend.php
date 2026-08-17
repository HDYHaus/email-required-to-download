<?php
/**
 * Shortcode, submissions, and downloads.
 *
 * @package EmailRequiredDownload
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ERD_Frontend {
	private $repository;

	public function __construct( ERD_Repository $repository ) {
		$this->repository = $repository;
	}

	public function init() {
		add_shortcode( 'email_required_download', array( $this, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_post_nopriv_erd_submit_lead', array( $this, 'submit_lead' ) );
		add_action( 'admin_post_erd_submit_lead', array( $this, 'submit_lead' ) );
		add_action( 'admin_post_nopriv_erd_download', array( $this, 'download' ) );
		add_action( 'admin_post_erd_download', array( $this, 'download' ) );
	}

	public function enqueue() {
		wp_enqueue_style( 'erd-frontend', ERD_PLUGIN_URL . 'assets/css/frontend.css', array(), ERD_VERSION );
	}

	public function shortcode( $atts ) {
		$atts  = shortcode_atts( array( 'id' => 0 ), $atts, 'email_required_download' );
		$offer = $this->repository->get_offer( absint( $atts['id'] ) );

		if ( ! $offer || empty( $offer->file_url ) ) {
			return '<div class="erd-message erd-error">' . esc_html__( 'This download is not available.', 'email-required-to-download' ) . '</div>';
		}

		$message = '';
		if ( isset( $_GET['erd_status'], $_GET['erd_offer'] ) && absint( $_GET['erd_offer'] ) === absint( $offer->id ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$status = sanitize_key( wp_unslash( $_GET['erd_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 'invalid' === $status ) {
				$message = '<div class="erd-message erd-error">' . esc_html__( 'Please enter a valid email address and accept the consent checkbox.', 'email-required-to-download' ) . '</div>';
			} elseif ( 'sent' === $status ) {
				$message = '<div class="erd-message erd-success">' . esc_html__( 'Thanks. Check your inbox for the download link.', 'email-required-to-download' ) . '</div>';
			} elseif ( 'ready' === $status && ! empty( $_GET['erd_token'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$url     = $this->download_url( sanitize_text_field( wp_unslash( $_GET['erd_token'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$message = '<div class="erd-message erd-success">' . wp_kses_post( $offer->success_message ) . ' <a class="erd-download-button" href="' . esc_url( $url ) . '">' . esc_html( $offer->button_text ) . '</a></div>';
			}
		}

		ob_start();
		?>
		<div class="erd-download" id="erd-download-<?php echo esc_attr( $offer->id ); ?>">
			<?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<form class="erd-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="erd_submit_lead">
				<input type="hidden" name="offer_id" value="<?php echo esc_attr( $offer->id ); ?>">
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( $this->current_url() ); ?>">
				<?php wp_nonce_field( 'erd_submit_lead_' . absint( $offer->id ) ); ?>
				<label>
					<span><?php esc_html_e( 'Email', 'email-required-to-download' ); ?></span>
					<input type="email" name="email" required autocomplete="email">
				</label>
				<?php if ( $offer->name_required ) : ?>
					<label>
						<span><?php esc_html_e( 'Name', 'email-required-to-download' ); ?></span>
						<input type="text" name="name" required autocomplete="name">
					</label>
				<?php endif; ?>
				<label class="erd-consent">
					<input type="checkbox" name="consent" value="1" required>
					<span><?php echo wp_kses_post( $offer->consent_text ); ?></span>
				</label>
				<button type="submit"><?php echo esc_html( $offer->button_text ); ?></button>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	public function submit_lead() {
		$offer_id = isset( $_POST['offer_id'] ) ? absint( $_POST['offer_id'] ) : 0;
		$offer    = $this->repository->get_offer( $offer_id );
		$redirect = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : home_url( '/' );

		if ( ! $offer || ! wp_verify_nonce( isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '', 'erd_submit_lead_' . $offer_id ) ) {
			wp_safe_redirect( add_query_arg( array( 'erd_status' => 'invalid', 'erd_offer' => $offer_id ), $redirect ) );
			exit;
		}

		$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$consent = ! empty( $_POST['consent'] );

		if ( ! is_email( $email ) || ! $consent || ( $offer->name_required && '' === $name ) ) {
			wp_safe_redirect( add_query_arg( array( 'erd_status' => 'invalid', 'erd_offer' => $offer_id ), $redirect ) );
			exit;
		}

		$settings = $this->repository->get_settings();
		$expires  = time() + ( max( 1, absint( $settings['token_expiry_hours'] ) ) * HOUR_IN_SECONDS );
		$lead_id  = $this->repository->create_lead(
			array(
				'offer_id'         => $offer_id,
				'email'            => $email,
				'name'             => $name,
				'token'            => 'pending',
				'token_expires_at' => gmdate( 'Y-m-d H:i:s', $expires ),
				'consent_text'     => $offer->consent_text,
				'referrer'         => wp_get_referer() ? wp_get_referer() : '',
				'ip_hash'          => $this->hash_ip(),
				'user_agent'       => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_textarea_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
			)
		);

		$token = ERD_Tokens::create( $lead_id, $offer_id, $expires );
		$this->repository->update_lead_token( $lead_id, $token );

		if ( $offer->require_confirmation ) {
			$this->send_confirmation_email( $email, $offer, $token );
			wp_safe_redirect( add_query_arg( array( 'erd_status' => 'sent', 'erd_offer' => $offer_id ), $redirect ) );
			exit;
		}

		wp_safe_redirect( add_query_arg( array( 'erd_status' => 'ready', 'erd_offer' => $offer_id, 'erd_token' => $token ), $redirect ) );
		exit;
	}

	public function download() {
		$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$parts = ERD_Tokens::parse( $token );

		if ( ! $parts ) {
			wp_die( esc_html__( 'This download link is invalid or expired.', 'email-required-to-download' ), 403 );
		}

		$lead  = $this->repository->get_lead_by_token( $token );
		$offer = $this->repository->get_offer( $parts['offer_id'] );

		if ( ! $lead || ! $offer || absint( $lead->id ) !== $parts['lead_id'] || absint( $lead->offer_id ) !== $parts['offer_id'] ) {
			wp_die( esc_html__( 'This download link is invalid.', 'email-required-to-download' ), 403 );
		}

		$this->repository->mark_downloaded( $lead->id );
		wp_safe_redirect( esc_url_raw( $offer->file_url ) );
		exit;
	}

	private function send_confirmation_email( $email, $offer, $token ) {
		$subject = sprintf(
			/* translators: %s: offer title */
			__( 'Your download: %s', 'email-required-to-download' ),
			$offer->title
		);
		$message = sprintf(
			/* translators: 1: offer title, 2: download URL */
			__( "Thanks for requesting %1\$s.\n\nDownload it here:\n%2\$s", 'email-required-to-download' ),
			$offer->title,
			$this->download_url( $token )
		);

		wp_mail( $email, $subject, $message );
	}

	private function download_url( $token ) {
		return add_query_arg(
			array(
				'action' => 'erd_download',
				'token'  => $token,
			),
			admin_url( 'admin-post.php' )
		);
	}

	private function current_url() {
		$scheme = is_ssl() ? 'https://' : 'http://';
		$host   = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : wp_parse_url( home_url(), PHP_URL_HOST );
		$uri    = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
		return esc_url_raw( $scheme . $host . $uri );
	}

	private function hash_ip() {
		$settings = $this->repository->get_settings();
		$ip       = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return $ip ? hash_hmac( 'sha256', $ip, $settings['ip_hash_salt'] ) : '';
	}
}
