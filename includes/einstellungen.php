<?php
/**
 * Customer settings: CV, preferences and personal API keys.
 * Shortcode: [jap_einstellungen]
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* Welche Schluessel der Kunde hinterlegen kann. */
function jap_api_felder() {
	return array(
		'groq_key' => array(
			'label'   => 'Groq API key',
			'hilfe'   => 'Free. This is what reads your CV, scores each role and writes your documents.',
			'link'    => 'https://console.groq.com/keys',
			'linktext'=> 'Get a Groq key',
			'schritte'=> array(
				'Open the link and sign in with Google or GitHub.',
				'Click "Create API Key" and give it any name.',
				'Copy the key immediately, it is shown only once.',
				'Paste it here and save.',
			),
		),
	);
}

/* Zeigt nur die letzten vier Zeichen, damit der Kunde erkennt, ob etwas hinterlegt ist. */
function jap_key_maskieren( $wert ) {
	$wert = (string) $wert;
	if ( strlen( $wert ) < 6 ) {
		return str_repeat( '•', max( strlen( $wert ), 4 ) );
	}
	return str_repeat( '•', 12 ) . substr( $wert, -4 );
}

function jap_handle_einstellungen() {
	if ( ! isset( $_POST['jap_settings_submit'] ) || ! is_user_logged_in() ) {
		return '';
	}
	if ( empty( $_POST['jap_settings_nonce'] ) || ! wp_verify_nonce( $_POST['jap_settings_nonce'], 'jap_settings_action' ) ) {
		return '<p style="color:red;">Security token expired. Please reload the page.</p>';
	}

	$user_id  = get_current_user_id();
	$meldungen = array();

	// --- Lebenslauf loeschen ---
	if ( ! empty( $_POST['jap_cv_loeschen'] ) ) {
		$alt_id = (int) get_user_meta( $user_id, 'jap_cv_id', true );
		if ( $alt_id ) { wp_delete_attachment( $alt_id, true ); }
		delete_user_meta( $user_id, 'jap_cv_url' );
		delete_user_meta( $user_id, 'jap_cv_id' );
		return '<p style="color:green;">Your CV has been deleted. The search is paused until you upload a new one.</p>';
	}

	// --- Lebenslauf ersetzen ---
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	if ( ! empty( $_FILES['jap_cv_neu'] ) && UPLOAD_ERR_NO_FILE !== $_FILES['jap_cv_neu']['error'] ) {
		$typ = wp_check_filetype( $_FILES['jap_cv_neu']['name'] );
		if ( 'pdf' !== strtolower( (string) $typ['ext'] ) ) {
			$meldungen[] = 'CV: only PDF files are allowed.';
		} else {
			$att_id = media_handle_upload( 'jap_cv_neu', 0 );
			if ( is_wp_error( $att_id ) ) {
				$meldungen[] = 'CV: ' . $att_id->get_error_message();
			} else {
				$alt_id = (int) get_user_meta( $user_id, 'jap_cv_id', true );
				if ( $alt_id ) { wp_delete_attachment( $alt_id, true ); }
				update_user_meta( $user_id, 'jap_cv_url', wp_get_attachment_url( $att_id ) );
				update_user_meta( $user_id, 'jap_cv_id', $att_id );
				$meldungen[] = 'New CV saved.';
			}
		}
	}

	// --- API-Schluessel ---
	foreach ( jap_api_felder() as $key => $data ) {
		$feld = 'jap_' . $key;
		if ( ! isset( $_POST[ $feld ] ) ) {
			continue;
		}
		$wert = trim( sanitize_text_field( $_POST[ $feld ] ) );

		// Leeres Feld bedeutet: unveraendert lassen (der Wert wird ja maskiert angezeigt).
		if ( '' === $wert ) {
			continue;
		}
		if ( 'delete' === strtolower( $wert ) || 'loeschen' === strtolower( $wert ) ) {
			delete_user_meta( $user_id, $feld );
			$meldungen[] = $data['label'] . ' removed.';
			continue;
		}
		jap_api_key_speichern( $user_id, $feld, $wert );
		$meldungen[] = $data['label'] . ' saved.';

		// Neuer Schluessel: die Problemmarkierung verfaellt, damit die Suche
		// beim naechsten Durchlauf ohne Zutun wieder anlaeuft.
		delete_user_meta( $user_id, 'jap_key_problem' );
		delete_user_meta( $user_id, 'jap_key_problem_zeit' );
		delete_user_meta( $user_id, 'jap_key_problem_mail' );
	}


	if ( ! $meldungen ) {
		return '';
	}
	return '<p style="color:green;">' . esc_html( implode( ' ', $meldungen ) ) . '</p>';
}

add_shortcode( 'jap_einstellungen', function () {
	if ( ! is_user_logged_in() ) {
		return '<div class="jap-form-wrapper"><p>Please sign in first.</p></div>';
	}

	$meldung = jap_handle_einstellungen();
	$user_id = get_current_user_id();


	// Was fehlt noch, damit die Suche fuer diesen Kunden laufen kann?
	$fehlt = array();
	if ( ! get_user_meta( $user_id, 'jap_cv_url', true ) ) { $fehlt[] = 'CV (PDF)'; }

	if ( ! jap_api_key_lesen( $user_id, 'jap_groq_key' ) )   { $fehlt[] = 'Groq API key'; }

	ob_start();
	?>
	<div class="jap-form-wrapper">
		<?php if ( $meldung ) : ?>
			<div class="jap-message"><?php echo wp_kses_post( $meldung ); ?></div>
		<?php endif; ?>

		<?php $key_problem = get_user_meta( $user_id, 'jap_key_problem', true ); ?>
		<?php if ( $key_problem ) : ?>
			<div class="jap-status-box jap-status-box--alarm">
				<strong>Your search is paused, the <?php echo esc_html( $key_problem ); ?> key was rejected.</strong>
				<p>
					This usually means the key was deleted, regenerated, or its free quota ran out.
					Create a new one and paste it below, the search restarts by itself.
					Your CV and your past matches are untouched.
				</p>
			</div>
		<?php endif; ?>

		<?php if ( $fehlt ) : ?>
			<div class="jap-status-box jap-status-box--warn">
				<strong>Your search is not running yet.</strong>
				<p>Still missing: <?php echo esc_html( implode( ', ', $fehlt ) ); ?></p>
			</div>
		<?php elseif ( ! $key_problem ) : ?>
			<div class="jap-status-box jap-status-box--ok">
				<strong>You are all set.</strong>
				<p>We check the job boards for you every two hours.</p>
			</div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( get_permalink() ); ?>" enctype="multipart/form-data">
			<?php wp_nonce_field( 'jap_settings_action', 'jap_settings_nonce' ); ?>

			<h3 class="jap-section-title">Your CV</h3>
			<?php $cv_url = get_user_meta( $user_id, 'jap_cv_url', true ); ?>

			<?php if ( $cv_url ) : ?>
				<p class="jap-hint jap-ok">
					On file, <a href="<?php echo esc_url( $cv_url ); ?>" target="_blank" rel="noopener">view your CV</a>.
					We read your details from it and place them into a ready-made layout for every role,
					so you never have to build a template yourself.
				</p>
			<?php else : ?>
				<p class="jap-hint jap-warn">
					No CV on file. Nothing can be searched or written until you upload one.
				</p>
			<?php endif; ?>

			<div class="jap-field">
				<label for="jap_cv_neu"><?php echo $cv_url ? 'Replace it with a new PDF' : 'Upload your CV (PDF)'; ?></label>
				<input type="file" id="jap_cv_neu" name="jap_cv_neu" accept=".pdf">
				<p class="jap-hint">The old file is deleted from the server when you upload a new one.</p>
			</div>

			<?php if ( $cv_url ) : ?>
				<div class="jap-field">
					<label class="jap-check jap-check--danger">
						<input type="checkbox" name="jap_cv_loeschen" value="1">
						<span>Delete my CV from this site</span>
					</label>
					<p class="jap-hint">
						The file is removed for good. Your account and past matches stay,
						but the search pauses until you upload a new CV.
					</p>
				</div>
			<?php endif; ?>

			<h3 class="jap-section-title">Your API keys</h3>
			<p class="jap-hint">
				The search runs on your own free accounts at the job boards. Create them once and
				paste the keys here. A stored key is shown shortened, leave the field empty
				to keep it, or type "delete" to remove it.
			</p>

			<?php foreach ( jap_api_felder() as $key => $data ) :
				$feld      = 'jap_' . $key;
				$vorhanden = jap_api_key_lesen( $user_id, $feld );
				?>
				<div class="jap-field">
					<label for="<?php echo esc_attr( $feld ); ?>"><?php echo esc_html( $data['label'] ); ?></label>
					<input type="text" id="<?php echo esc_attr( $feld ); ?>" name="<?php echo esc_attr( $feld ); ?>"
						autocomplete="off" spellcheck="false"
						placeholder="<?php echo $vorhanden ? esc_attr( jap_key_maskieren( $vorhanden ) ) : 'Not set yet'; ?>">
					<p class="jap-hint">
						<?php echo esc_html( $data['hilfe'] ); ?>
					</p>

					<?php if ( ! empty( $data['schritte'] ) ) : ?>
						<div class="jap-steps-box">
							<a class="jap-key-link" href="<?php echo esc_url( $data['link'] ); ?>"
								target="_blank" rel="noopener noreferrer">
								<?php echo esc_html( $data['linktext'] ); ?> &rarr;
							</a>
							<ol>
								<?php foreach ( $data['schritte'] as $schritt ) : ?>
									<li><?php echo wp_kses_post( $schritt ); ?></li>
								<?php endforeach; ?>
							</ol>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>

			<button type="submit" name="jap_settings_submit" value="1">Save changes</button>
		</form>

		<p class="jap-version">Form version <?php echo esc_html( JAP_VERSION ); ?></p>
	</div>
	<?php
	return ob_get_clean();
} );
