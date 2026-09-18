<?php
/**
 * Kontaktformular.
 * Shortcode: [jap_kontakt]
 *
 * Die Nachrichten gehen an die WordPress-Adminadresse.
 * Aendern unter: Settings -> General -> Administration Email Address
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* An welche Adresse die Nachrichten gehen. */
function jap_kontakt_empfaenger() {
	$adresse = get_option( 'admin_email' );
	return apply_filters( 'jap_kontakt_empfaenger', $adresse );
}

function jap_handle_kontakt() {
	if ( ! isset( $_POST['jap_kontakt_submit'] ) ) {
		return '';
	}

	// Unsichtbares Feld: nur Maschinen fuellen es aus.
	if ( ! empty( $_POST['jap_website'] ) ) {
		return '<p style="color:green;">Thanks, your message was sent.</p>';
	}

	if ( empty( $_POST['jap_kontakt_nonce'] ) || ! wp_verify_nonce( $_POST['jap_kontakt_nonce'], 'jap_kontakt_action' ) ) {
		return '<p style="color:red;">Security token expired. Please reload the page and try again.</p>';
	}

	// Einfache Bremse gegen Massenversand: eine Nachricht pro Minute und IP.
	$ip     = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : 'unknown';
	$schluessel = 'jap_kontakt_' . md5( $ip );
	if ( get_transient( $schluessel ) ) {
		return '<p style="color:red;">You just sent a message. Please wait a minute before sending another.</p>';
	}

	$name    = sanitize_text_field( $_POST['jap_kontakt_name'] ?? '' );
	$email   = sanitize_email( $_POST['jap_kontakt_email'] ?? '' );
	$betreff = sanitize_text_field( $_POST['jap_kontakt_betreff'] ?? '' );
	$text    = sanitize_textarea_field( $_POST['jap_kontakt_text'] ?? '' );

	if ( ! $name || ! $email || ! $text ) {
		return '<p style="color:red;">Please fill in your name, your email and a message.</p>';
	}
	if ( ! is_email( $email ) ) {
		return '<p style="color:red;">That email address does not look right.</p>';
	}

	$an      = jap_kontakt_empfaenger();
	$subject = 'Contact form: ' . ( $betreff ? $betreff : 'New message' );

	$body   = array();
	$body[] = 'New message from the website.';
	$body[] = '';
	$body[] = 'Name:    ' . $name;
	$body[] = 'Email:   ' . $email;
	if ( $betreff ) { $body[] = 'Subject: ' . $betreff; }
	$body[] = '';
	$body[] = 'Message:';
	$body[] = $text;
	$body[] = '';
	$body[] = '---';
	$body[] = 'Sent from ' . home_url( '/' );

	// Reply-To auf den Absender, damit eine Antwort direkt beim Nutzer landet.
	$headers = array(
		'Reply-To: ' . $name . ' <' . $email . '>',
	);

	$ok = wp_mail( $an, $subject, implode( "\n", $body ), $headers );

	if ( ! $ok ) {
		return '<p style="color:red;">The message could not be sent. Please write directly to '
			. esc_html( $an ) . '.</p>';
	}

	set_transient( $schluessel, 1, MINUTE_IN_SECONDS );

	return '<p style="color:green;">Thanks, your message is on its way. You will hear back at '
		. esc_html( $email ) . '.</p>';
}

add_shortcode( 'jap_kontakt', function () {
	$meldung = jap_handle_kontakt();

	$user  = wp_get_current_user();
	$vName = ( $user && $user->ID ) ? $user->display_name : '';
	$vMail = ( $user && $user->ID ) ? $user->user_email : '';

	ob_start();
	?>
	<div class="jap-form-wrapper">
		<?php if ( $meldung ) : ?>
			<div class="jap-message"><?php echo wp_kses_post( $meldung ); ?></div>
		<?php endif; ?>

		<p class="jap-hint">
			Something not working, an idea, or just a question? Write below and it lands
			straight in my inbox. I read everything myself.
		</p>

		<form method="post" action="<?php echo esc_url( get_permalink() ); ?>">
			<?php wp_nonce_field( 'jap_kontakt_action', 'jap_kontakt_nonce' ); ?>

			<div class="jap-field">
				<label for="jap_kontakt_name">Your name</label>
				<input type="text" id="jap_kontakt_name" name="jap_kontakt_name"
					value="<?php echo esc_attr( $vName ); ?>" required>
			</div>

			<div class="jap-field">
				<label for="jap_kontakt_email">Your email</label>
				<input type="email" id="jap_kontakt_email" name="jap_kontakt_email"
					value="<?php echo esc_attr( $vMail ); ?>" required>
				<p class="jap-hint">So I can reply. Nothing else happens with it.</p>
			</div>

			<div class="jap-field">
				<label for="jap_kontakt_betreff">Subject</label>
				<input type="text" id="jap_kontakt_betreff" name="jap_kontakt_betreff"
					placeholder="Optional">
			</div>

			<div class="jap-field">
				<label for="jap_kontakt_text">Message</label>
				<textarea id="jap_kontakt_text" name="jap_kontakt_text" rows="7" required></textarea>
			</div>

			<!-- Spamfalle: fuer Menschen unsichtbar. -->
			<div class="jap-trap" aria-hidden="true">
				<label for="jap_website">Leave this field empty</label>
				<input type="text" id="jap_website" name="jap_website" tabindex="-1" autocomplete="off">
			</div>

			<button type="submit" name="jap_kontakt_submit" value="1">Send message</button>
		</form>
	</div>
	<?php
	return ob_get_clean();
} );
