<?php
/**
 * Plugin Name: Job Automation Portal
 * Description: Kundenregistrierung mit CV-Upload, Praeferenzen, Webhook-Anbindung an n8n und Dashboard mit den gefundenen Job-Ergebnissen.
 * Version: 5.0.5
 * Author: Mohammed Othman
 * Text Domain: jap
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'JAP_VERSION', '5.0.5' );

/* Basis-URL des Plugins. Wird fuer Bilder und Dateien gebraucht.
 * Muss hier stehen, weil plugins_url() aus Unterordnern falsche Pfade liefert. */
define( 'JAP_URL', plugin_dir_url( __FILE__ ) );

/* -------------------------------------------------------------------------
 * Kataloge: feste Auswahl statt Freitext.
 * Grund: Die Werte gehen als Suchanfragen an die Jobboersen. Ein Tippfehler
 * bedeutet null Treffer — und der Kunde denkt, das Portal funktioniert nicht.
 * ---------------------------------------------------------------------- */


/* -------------------------------------------------------------------------
 * 1. Custom Post Type: "bewerbung" (Application) - eine Zeile pro Job-Treffer
 * ---------------------------------------------------------------------- */
add_action( 'init', function () {
	register_post_type( 'jap_bewerbung', array(
		'label'        => 'Applications',
		'labels'       => array(
			'name'          => 'Applications',
			'singular_name' => 'Application',
			'add_new_item'  => 'New application',
		),
		'public'       => false,
		'show_ui'      => true,
		'show_in_rest' => true,
		'rest_base'    => 'jap_bewerbung',
		'supports'     => array( 'title' ),
		'menu_icon'    => 'dashicons-portfolio',
	) );

	$meta_fields = array(
		'jap_user_id'        => 'integer',
		'jap_company'        => 'string',
		'jap_location'       => 'string',
		'jap_work_model'     => 'string',
		'jap_score'          => 'integer',
		'jap_reason'         => 'string',
		'jap_cv_link'        => 'string',
		'jap_cover_link'     => 'string',
		'jap_job_url'        => 'string',
		'jap_date_found'     => 'string',
	);
	foreach ( $meta_fields as $key => $type ) {
		register_post_meta( 'jap_bewerbung', $key, array(
			'type'         => $type,
			'single'       => true,
			'show_in_rest' => true,
			'auth_callback'=> function () { return current_user_can( 'edit_posts' ); },
		) );
	}
} );

/* -------------------------------------------------------------------------
 * 2. Eigene Nutzer-Profilfelder (CV-Link + Praeferenzen)
 * ---------------------------------------------------------------------- */
function jap_user_meta_keys() {
	return array( 'jap_cv_url', 'jap_standort', 'jap_arbeitszeit', 'jap_branche', 'jap_status' );
}

/* -------------------------------------------------------------------------
 * 3. Settings-Seite: n8n Webhook-URL eintragen
 * ---------------------------------------------------------------------- */
add_action( 'admin_menu', function () {
	add_options_page( 'Job Automation Portal', 'Job Automation Portal', 'manage_options', 'jap-settings', 'jap_render_settings_page' );
} );

add_action( 'admin_init', function () {
	register_setting( 'jap_settings_group', 'jap_n8n_webhook_url' );
} );

function jap_render_settings_page() {
	?>
	<div class="wrap">
		<h1>Job Automation Portal, settings</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'jap_settings_group' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="jap_n8n_webhook_url">n8n webhook URL</label></th>
					<td>
						<input type="url" id="jap_n8n_webhook_url" name="jap_n8n_webhook_url"
							value="<?php echo esc_attr( get_option( 'jap_n8n_webhook_url' ) ); ?>"
							class="regular-text" placeholder="https://othman5911.app.n8n.cloud/webhook/..." />
						<p class="description">The webhook URL n8n gives you for new sign-ups. Optional, leave empty if you do not use it.</p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>

		<h2>Available shortcodes</h2>
		<ul style="list-style:disc;margin-left:20px;">
			<li><code>[jap_register]</code>: sign-up form with CV upload and preferences</li>
			<li><code>[jap_login]</code>: sign-in form</li>
			<li><code>[jap_dashboard]</code>: the customer dashboard (signed-in users only)</li>
		</ul>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * 4. Registrierungsformular (Shortcode)
 * ---------------------------------------------------------------------- */
add_shortcode( 'jap_register', 'jap_render_register_form' );

/* Seiten mit diesen Shortcodes duerfen niemals zwischengespeichert werden,
 * sonst laufen die Sicherheitstoken (Nonces) ab und Formulare tun nichts. */
require_once plugin_dir_path( __FILE__ ) . 'includes/verschluesselung.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/kataloge.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/einstellungen.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/startseite.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/kontakt.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/rechtstexte.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/seiten.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/admin.php';
if ( file_exists( plugin_dir_path( __FILE__ ) . 'includes/pdf.php' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/pdf.php';
}

function jap_page_has_shortcode() {
	if ( ! is_singular() ) { return false; }
	global $post;
	if ( ! $post ) { return false; }
	return has_shortcode( $post->post_content, 'jap_register' )
		|| has_shortcode( $post->post_content, 'jap_login' )
		|| has_shortcode( $post->post_content, 'jap_einstellungen' )
		|| has_shortcode( $post->post_content, 'jap_startseite' )
		|| has_shortcode( $post->post_content, 'jap_kontakt' )
		|| has_shortcode( $post->post_content, 'jap_impressum' )
		|| has_shortcode( $post->post_content, 'jap_datenschutz' )
		|| has_shortcode( $post->post_content, 'jap_ueber' )
		|| has_shortcode( $post->post_content, 'jap_agb' )
		|| has_shortcode( $post->post_content, 'jap_dashboard' );
}

/* Nur Seiten mit Formularen duerfen nicht gecacht werden.
 * Die Startseite darf gecacht bleiben, das macht sie schneller. */
function jap_page_is_formular() {
	if ( ! is_singular() ) { return false; }
	global $post;
	if ( ! $post ) { return false; }
	return has_shortcode( $post->post_content, 'jap_register' )
		|| has_shortcode( $post->post_content, 'jap_login' )
		|| has_shortcode( $post->post_content, 'jap_einstellungen' )
		|| has_shortcode( $post->post_content, 'jap_kontakt' )
		|| has_shortcode( $post->post_content, 'jap_dashboard' );
}

add_action( 'template_redirect', function () {
	if ( ! jap_page_is_formular() ) { return; }
	if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
	do_action( 'litespeed_control_set_nocache', 'JAP Formularseite' );
	nocache_headers();
}, 1 );

add_action( 'wp_enqueue_scripts', function () {
	if ( ! jap_page_has_shortcode() ) { return; }
	wp_enqueue_style(
		'jap-style',
		plugin_dir_url( __FILE__ ) . 'assets/jap.css',
		array(),
		JAP_VERSION
	);

	// Auf der Landing Page stoert der Seitentitel des Themes ("Home").
	global $post;
	if ( $post && has_shortcode( $post->post_content, 'jap_startseite' ) ) {
		wp_add_inline_style( 'jap-style',
			'.wp-block-post-title, .entry-title, .page-title { display: none !important; }'
			. ' .wp-site-blocks > * > .entry-content, .entry-content { margin-block-start: 0 !important; }'
		);
	}
} );

function jap_render_register_form() {
	if ( is_user_logged_in() ) {
		return '<p>You already have an account and are signed in. <a href="' . esc_url( jap_dashboard_url() ) . '">Open dashboard</a></p>';
	}

	$message = '';
	if ( isset( $_POST['jap_register_submit'] ) ) {
		if ( empty( $_POST['jap_register_nonce'] ) ) {
			$message = '<p style="color:red;">Security check failed (token missing). Please reload the page (Ctrl+F5) and try again.</p>';
		} elseif ( ! wp_verify_nonce( $_POST['jap_register_nonce'], 'jap_register_action' ) ) {
			$message = '<p style="color:red;">Security token expired (the page was cached). Please reload (Ctrl+F5) and try again.</p>';
		} else {
			$message = jap_handle_registration();
		}
	}

	ob_start();
	?>
	<div class="jap-form-wrapper">
		<div class="jap-scope-notice">
			<strong>Right now, this is for Computer Science / Informatik students in the Leipzig/Halle
			or Berlin area only.</strong> We search local job boards for those two regions and match roles
			to a Computer Science background. If that is not you yet, more fields of study and cities are
			planned, but signing up today will not get you matches outside IT or outside these two areas.
		</div>
		<?php if ( $message ) : ?>
			<div class="jap-message"><?php echo wp_kses_post( $message ); ?></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( get_permalink() ); ?>" enctype="multipart/form-data" class="jap-register-form">
			<?php wp_nonce_field( 'jap_register_action', 'jap_register_nonce' ); ?>

			<div class="jap-field">
				<label for="jap_name">Name</label>
				<input type="text" id="jap_name" name="jap_name" required>
			</div>
			<div class="jap-field">
				<label for="jap_email">Email</label>
				<input type="email" id="jap_email" name="jap_email" required>
				<p class="jap-hint">This is where we send your job matches.</p>
			</div>
			<div class="jap-field">
				<label for="jap_password">Password</label>
				<input type="password" id="jap_password" name="jap_password" required>
			</div>
			<div class="jap-field">
				<label for="jap_cv">Your CV as a PDF</label>
				<input type="file" id="jap_cv" name="jap_cv" accept=".pdf" required>
				<p class="jap-hint">We read it once and build a tailored CV for every role.</p>
			</div>
			<div class="jap-field">
				<label>City: pick Leipzig, Berlin, or both</label>
				<div class="jap-checks">
					<?php foreach ( jap_staedte() as $key => $label ) : ?>
						<label class="jap-check">
							<input type="checkbox" name="jap_standort[]" value="<?php echo esc_attr( $key ); ?>"<?php echo 'leipzig' === $key ? ' checked' : ''; ?>>
							<span><?php echo esc_html( $label ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
				<p class="jap-hint">These are the only two areas we currently search. Picking both is fine.</p>
			</div>
			<div class="jap-field">
				<label>How far should we search? Pick as many as you like.</label>
				<div class="jap-checks">
					<?php foreach ( jap_suchweite() as $wkey => $wlabel ) : ?>
						<label class="jap-check">
							<input type="checkbox" name="jap_suchweite[]" value="<?php echo esc_attr( $wkey ); ?>"<?php echo 'stadt_remote' === $wkey ? ' checked' : ''; ?>>
							<span><?php echo esc_html( $wlabel ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
				<p class="jap-hint">
					Place and working style are two separate questions. Pick "Remote only" and the city
					above is ignored; pick "In and around my city" and remote roles are left out. Pick both
					to get both.
				</p>
			</div>
			<div class="jap-field">
				<label>Type of role: pick as many as you like</label>
				<div class="jap-checks">
					<?php foreach ( jap_stellenarten() as $key => $label ) : ?>
						<label class="jap-check">
							<input type="checkbox" name="jap_arbeitszeit[]" value="<?php echo esc_attr( $key ); ?>"<?php echo 'werkstudent' === $key ? ' checked' : ''; ?>>
							<span><?php echo esc_html( $label ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="jap-field">
				<label>What kind of work are you looking for? Pick as many as you like.</label>
				<div class="jap-checks">
					<?php foreach ( jap_bereiche() as $key => $data ) : ?>
						<label class="jap-check">
							<input type="checkbox" name="jap_bereiche[]" value="<?php echo esc_attr( $key ); ?>">
							<span><?php echo esc_html( $data['label'] ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
				<p class="jap-hint">
					This is what we search for. It does <strong>not</strong> have to match your degree.
					Studying cyber security but want to work in sales? Tick sales.
				</p>
			</div>

			<div class="jap-field">
				<label for="jap_studienfach">What are you studying? (optional)</label>
				<input type="text" id="jap_studienfach" name="jap_studienfach" placeholder="e.g. Cyber Security, Business Administration">
				<p class="jap-hint">Only used so your documents mention it correctly. It does not steer the search.</p>
			</div>

			<div class="jap-field jap-field--consent">
				<label class="jap-check">
					<input type="checkbox" id="jap_consent" name="jap_consent" value="1" required>
					<span>
						I have read the <a href="<?php echo esc_url( jap_legal_url( 'jap_datenschutz' ) ); ?>" target="_blank" rel="noopener">privacy policy</a>
						and agree that my CV and details are processed as described there.
					</span>
				</label>
			</div>

			<button type="submit" name="jap_register_submit" value="1">Create my account</button>
		</form>

		<p class="jap-version">Form version <?php echo esc_html( JAP_VERSION ); ?></p>
	</div>
	<?php
	return ob_get_clean();
}

function jap_handle_registration() {
	if ( ! isset( $_POST['jap_register_submit'] ) ) {
		return '';
	}

	$name  = sanitize_text_field( $_POST['jap_name'] ?? '' );
	$email = sanitize_email( $_POST['jap_email'] ?? '' );
	$pass  = $_POST['jap_password'] ?? '';

	if ( ! $name || ! $email || ! $pass ) {
		return '<p style="color:red;">Please fill in all required fields.</p>';
	}
	if ( empty( $_POST['jap_consent'] ) ) {
		return '<p style="color:red;">Please confirm you have read the privacy policy to continue.</p>';
	}
	if ( email_exists( $email ) ) {
		return '<p style="color:red;">That email address is already registered.</p>';
	}
	if ( empty( $_FILES['jap_cv'] ) || $_FILES['jap_cv']['error'] !== UPLOAD_ERR_OK ) {
		return '<p style="color:red;">Please upload your CV as a PDF.</p>';
	}

	// Nutzer anlegen
	$username = sanitize_user( current( explode( '@', $email ) ) . '_' . wp_generate_password( 4, false ) );
	$user_id  = wp_create_user( $username, $pass, $email );
	if ( is_wp_error( $user_id ) ) {
		return '<p style="color:red;">Sign-up failed: ' . esc_html( $user_id->get_error_message() ) . '</p>';
	}
	wp_update_user( array( 'ID' => $user_id, 'display_name' => $name, 'first_name' => $name ) );

	// CV hochladen in die Mediathek
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$filetype = wp_check_filetype( $_FILES['jap_cv']['name'] );
	if ( $filetype['ext'] !== 'pdf' ) {
		return '<p style="color:red;">Only PDF files are allowed.</p>';
	}

	$attachment_id = media_handle_upload( 'jap_cv', 0 );
	if ( is_wp_error( $attachment_id ) ) {
		return '<p style="color:red;">Could not upload your CV: ' . esc_html( $attachment_id->get_error_message() ) . '</p>';
	}
	$cv_url = wp_get_attachment_url( $attachment_id );

	// Praeferenzen speichern
	$suchweite_roh = isset( $_POST['jap_suchweite'] ) ? (array) $_POST['jap_suchweite'] : array();
	$suchweite     = array();
	foreach ( $suchweite_roh as $s ) {
		$s = sanitize_text_field( $s );
		if ( array_key_exists( $s, jap_suchweite() ) ) { $suchweite[] = $s; }
	}
	if ( ! $suchweite ) { $suchweite = array( 'stadt_remote' ); }
	$studienfach  = sanitize_text_field( $_POST['jap_studienfach'] ?? '' );
	$staedte      = jap_staedte();
	$standort_roh = isset( $_POST['jap_standort'] ) ? (array) $_POST['jap_standort'] : array();
	$standort_keys = array();
	foreach ( $standort_roh as $sk ) {
		$sk = sanitize_text_field( $sk );
		if ( isset( $staedte[ $sk ] ) ) { $standort_keys[] = $sk; }
	}
	if ( ! $standort_keys ) { $standort_keys = array( 'leipzig' ); }

	$standort_labels = array();
	foreach ( $standort_keys as $sk ) { $standort_labels[] = $staedte[ $sk ]; }
	$standort     = implode( ', ', $standort_labels );
	// Fuer Rueckwaertskompatibilitaet (Alt-Code, das jap_standort_key liest):
	// erste gewaehlte Stadt.
	$standort_key = $standort_keys[0];

	$arten_roh   = isset( $_POST['jap_arbeitszeit'] ) ? (array) $_POST['jap_arbeitszeit'] : array();
	$arten_ok    = array_keys( jap_stellenarten() );
	$arbeitszeit = array();
	foreach ( $arten_roh as $a ) {
		$a = sanitize_text_field( $a );
		if ( in_array( $a, $arten_ok, true ) ) {
			$arbeitszeit[] = $a;
		}
	}
	if ( ! $arbeitszeit ) {
		$arbeitszeit = array( 'werkstudent' );
	}
	$arbeitszeit_labels = array();
	$arten_alle         = jap_stellenarten();
	foreach ( $arbeitszeit as $a ) {
		$arbeitszeit_labels[] = $arten_alle[ $a ];
	}

	$bereiche_roh = isset( $_POST['jap_bereiche'] ) ? (array) $_POST['jap_bereiche'] : array();
	$erlaubt      = array_keys( jap_bereiche() );
	$bereiche     = array();
	foreach ( $bereiche_roh as $b ) {
		$b = sanitize_text_field( $b );
		if ( in_array( $b, $erlaubt, true ) ) {
			$bereiche[] = $b;
		}
	}

	$keywords = jap_keywords_aus_bereichen( $bereiche );
	$branche  = implode( ', ', $keywords );

	update_user_meta( $user_id, 'jap_cv_url', $cv_url );
	update_user_meta( $user_id, 'jap_standort', $standort );
	update_user_meta( $user_id, 'jap_standort_key', $standort_key );
	update_user_meta( $user_id, 'jap_suchweite', $suchweite );
	update_user_meta( $user_id, 'jap_studienfach', $studienfach );
	update_user_meta( $user_id, 'jap_arbeitszeit', $arbeitszeit );
	update_user_meta( $user_id, 'jap_arbeitszeit_labels', $arbeitszeit_labels );
	update_user_meta( $user_id, 'jap_bereiche', $bereiche );
	update_user_meta( $user_id, 'jap_keywords', $keywords );
	update_user_meta( $user_id, 'jap_branche', $branche );
	update_user_meta( $user_id, 'jap_status', 'aktiv' );
	update_user_meta( $user_id, 'jap_consent_zeit', current_time( 'mysql' ) );

	// An n8n senden
	$webhook_url = get_option( 'jap_n8n_webhook_url' );
	if ( $webhook_url ) {
		wp_remote_post( $webhook_url, array(
			'timeout'  => 15,
			'blocking' => false,
			'headers'  => array( 'Content-Type' => 'application/json' ),
			'body'     => wp_json_encode( array(
				'event'        => 'new_customer',
				'user_id'      => $user_id,
				'name'         => $name,
				'email'        => $email,
				'cv_url'       => $cv_url,
				'standort'     => $standort,
				'arbeitszeit'  => $arbeitszeit,
				'stellenarten' => $arbeitszeit_labels,
				'bereiche'     => $bereiche,
				'keywords'     => $keywords,
				'branche'      => $branche,
				'site_url'     => home_url(),
			) ),
		) );
	}

	// Automatisch einloggen
	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id );

	jap_send_welcome_email( $user_id );

	return '<p style="color:green;">Welcome, ' . esc_html( $name ) . '! Your account is ready. Check your inbox, we sent you the two setup steps.</p>';
}

/* -------------------------------------------------------------------------
 * 5. Login-Formular (Shortcode)
 * ---------------------------------------------------------------------- */
/* Ziel-URL des Kunden-Dashboards ermitteln (Seite mit [jap_dashboard]). */
/* Findet die URL der Seite mit einem bestimmten Rechtstext-Shortcode (z.B. Datenschutz). */
function jap_legal_url( $shortcode ) {
	$pages = get_pages();
	if ( $pages ) {
		foreach ( $pages as $p ) {
			if ( has_shortcode( $p->post_content, $shortcode ) ) {
				return get_permalink( $p->ID );
			}
		}
	}
	return home_url( '/' );
}

function jap_dashboard_url() {
	$pages = get_pages();
	if ( $pages ) {
		foreach ( $pages as $p ) {
			if ( has_shortcode( $p->post_content, 'jap_dashboard' ) ) {
				return get_permalink( $p->ID );
			}
		}
	}
	return home_url( '/' );
}

add_shortcode( 'jap_login', function () {
	if ( is_user_logged_in() ) {
		return '<div class="jap-form-wrapper"><p class="jap-hint">You are signed in. <a href="' . esc_url( jap_dashboard_url() ) . '">Open dashboard</a> &middot; <a href="' . esc_url( wp_logout_url( home_url( '/' ) ) ) . '">Sign out</a></p></div>';
	}
	ob_start();
	echo '<div class="jap-form-wrapper">';
	wp_login_form( array(
		'redirect'       => jap_dashboard_url(),
		'label_username' => 'Email or username',
		'label_password' => 'Password',
		'label_remember' => 'Keep me signed in',
		'label_log_in'   => 'Sign in',
	) );
	echo '<p class="jap-hint"><a href="' . esc_url( wp_lostpassword_url( jap_dashboard_url() ) ) . '">Forgot your password?</a></p>';
	echo '</div>';
	return ob_get_clean();
} );

/* -------------------------------------------------------------------------
 * 6. Dashboard: "Meine Bewerbungen" (Shortcode)
 * ---------------------------------------------------------------------- */
/* Status eines Job-Eintrags aendern (nur eigener Eintrag). */
function jap_handle_status_change() {
	if ( ! isset( $_POST['jap_new_status'] ) || ! is_user_logged_in() ) {
		return '';
	}
	if ( empty( $_POST['jap_status_nonce'] ) || ! wp_verify_nonce( $_POST['jap_status_nonce'], 'jap_status_action' ) ) {
		return '<p style="color:red;">Security token expired. Please reload the page.</p>';
	}

	$post_id = isset( $_POST['jap_post_id'] ) ? absint( $_POST['jap_post_id'] ) : 0;
	$status  = isset( $_POST['jap_new_status'] ) ? sanitize_text_field( $_POST['jap_new_status'] ) : '';
	$allowed = array( 'neu', 'beworben', 'abgelehnt' );

	if ( ! $post_id || ! in_array( $status, $allowed, true ) ) {
		return '';
	}
	if ( (int) get_post_meta( $post_id, 'jap_user_id', true ) !== get_current_user_id() ) {
		return '<p style="color:red;">You do not have access to this entry.</p>';
	}

	update_post_meta( $post_id, 'jap_status', $status );
	return '<p style="color:green;">Status updated.</p>';
}

add_shortcode( 'jap_dashboard', function () {
	if ( ! is_user_logged_in() ) {
		return '<p>Please sign in to see your dashboard.</p>';
	}

	$user_id     = get_current_user_id();
	$status_note = jap_handle_status_change();
	if ( isset( $_POST['jap_filter'] ) ) {
		$filter = sanitize_text_field( $_POST['jap_filter'] );
	} elseif ( isset( $_GET['jap_filter'] ) ) {
		$filter = sanitize_text_field( $_GET['jap_filter'] );
	} else {
		$filter = 'offen';
	}

	$meta_query = array(
		array( 'key' => 'jap_user_id', 'value' => $user_id, 'compare' => '=' ),
	);

	if ( 'beworben' === $filter ) {
		$meta_query[] = array( 'key' => 'jap_status', 'value' => 'beworben', 'compare' => '=' );
	} elseif ( 'abgelehnt' === $filter ) {
		$meta_query[] = array( 'key' => 'jap_status', 'value' => 'abgelehnt', 'compare' => '=' );
	} elseif ( 'offen' === $filter ) {
		$meta_query[] = array(
			'relation' => 'OR',
			array( 'key' => 'jap_status', 'value' => 'neu', 'compare' => '=' ),
			array( 'key' => 'jap_status', 'compare' => 'NOT EXISTS' ),
		);
	}

	$query = new WP_Query( array(
		'post_type'      => 'jap_bewerbung',
		'posts_per_page' => 50,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'meta_query'     => $meta_query,
	) );

	ob_start();
	?>
	<div class="jap-dashboard">
		<div class="jap-userbar">
			<span>Signed in as <strong><?php echo esc_html( wp_get_current_user()->display_name ); ?></strong></span>
			<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">Sign out</a>
		</div>

		<h2>Your matches</h2>
		<p class="jap-count"><?php echo esc_html( $query->found_posts ); ?> <?php echo 1 === (int) $query->found_posts ? 'match' : 'matches'; ?> in this view</p>

		<?php if ( $status_note ) { echo '<div class="jap-message">' . wp_kses_post( $status_note ) . '</div>'; } ?>

		<?php
		$base          = get_permalink();
		$jap_dash_page = $base;
		$tabs          = array( 'offen' => 'Open', 'beworben' => 'Applied', 'abgelehnt' => 'Not for me', 'alle' => 'All' );
		?>
		<details class="jap-help">
			<summary>What do these buttons do?</summary>
			<ul>
				<li><strong>Open</strong>: roles you have not decided on yet. This is your to-do list.</li>
				<li><strong>Applied</strong>: roles you marked as applied, so they stop cluttering the list.</li>
				<li><strong>Not for me</strong>: roles you dismissed. Hidden, but never deleted.</li>
				<li><strong>All</strong>: everything we ever found for you.</li>
			</ul>
			<p>
				Marking a role changes nothing on the employer's side, it is only your own
				bookkeeping, so you still know where you stand a month from now. You can undo any mark.
			</p>
		</details>

		<nav class="jap-filters">
			<?php foreach ( $tabs as $key => $label ) :
				$is_active = ( $filter === $key );
				$url       = add_query_arg( 'jap_filter', $key, $base );
				?>
				<a href="<?php echo esc_url( $url ); ?>"<?php echo $is_active ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>

		<?php if ( ! $query->have_posts() ) : ?>
			<div class="jap-empty">
				<p>Nothing here yet. As soon as a role fits, it lands here, and you get an email.</p>
			</div>
		<?php else : ?>
			<?php while ( $query->have_posts() ) : $query->the_post();
				$post_id    = get_the_ID();
				$company    = get_post_meta( $post_id, 'jap_company', true );
				$location   = get_post_meta( $post_id, 'jap_location', true );
				$score      = (int) get_post_meta( $post_id, 'jap_score', true );
				$reason     = get_post_meta( $post_id, 'jap_reason', true );
				$cv_link    = get_post_meta( $post_id, 'jap_cv_link', true );
				$cover_link = get_post_meta( $post_id, 'jap_cover_link', true );
				$job_url    = get_post_meta( $post_id, 'jap_job_url', true );
				$date_found = get_post_meta( $post_id, 'jap_date_found', true );
				$bew_email  = get_post_meta( $post_id, 'jap_bewerbungs_email', true );
				$betreff    = get_post_meta( $post_id, 'jap_betreff', true );
				$brieftext  = get_post_meta( $post_id, 'jap_brieftext', true );
				$status     = get_post_meta( $post_id, 'jap_status', true );
				if ( ! $status ) { $status = 'neu'; }

				if ( $score >= 70 )      { $band = 'strong'; $band_word = 'Strong'; }
				elseif ( $score >= 40 )  { $band = 'mid';    $band_word = 'Fair'; }
				else                     { $band = 'weak';   $band_word = 'Weak'; }
				?>
				<article class="jap-job-card">
					<div class="jap-score-tab jap-score-tab--<?php echo esc_attr( $band ); ?>">
						<span class="jap-score-num"><?php echo esc_html( $score ); ?></span>
						<span class="jap-score-cap"><?php echo esc_html( $band_word ); ?></span>
					</div>

					<div class="jap-job-body">
						<h3><?php the_title(); ?></h3>

						<p class="jap-job-meta">
							<?php if ( $company )  : ?><span><?php echo esc_html( $company ); ?></span><?php endif; ?>
							<?php if ( $location ) : ?><span><?php echo esc_html( $location ); ?></span><?php endif; ?>
						</p>

						<?php if ( $reason ) : ?>
							<p class="jap-job-reason"><?php echo esc_html( $reason ); ?></p>
						<?php endif; ?>

						<?php if ( $bew_email ) :
							$mail_betreff = $betreff ? $betreff : ( 'Bewerbung als ' . get_the_title() );
							$mail_body    = ( $brieftext ? $brieftext : 'Sehr geehrte Damen und Herren,' )
								. "\r\n\r\nMit freundlichen Gruessen\r\n"
								. wp_get_current_user()->display_name
								. "\r\n\r\n---\r\nBitte Lebenslauf und Anschreiben als PDF anhaengen.";
							$mailto = 'mailto:' . rawurlencode( $bew_email )
								. '?subject=' . rawurlencode( $mail_betreff )
								. '&body=' . rawurlencode( $mail_body );
							?>
							<p class="jap-mail-row">
								<a class="jap-btn jap-mail-btn" href="<?php echo esc_attr( $mailto ); ?>">Write application email</a>
								<span class="jap-mail-hint">Opens your mail app with the text ready. Just attach the two PDFs below.</span>
							</p>
						<?php else : ?>
							<p class="jap-mail-row">
								<span class="jap-btn jap-mail-btn jap-mail-btn--off" aria-disabled="true">Write application email</span>
								<span class="jap-mail-hint">This company did not list an email. Apply through the job posting below.</span>
							</p>
						<?php endif; ?>

						<div class="jap-job-links">
							<?php if ( $cv_link )    : ?><a href="<?php echo esc_url( $cv_link ); ?>" target="_blank" rel="noopener">View CV</a><?php endif; ?>
							<?php if ( $cover_link ) : ?><a href="<?php echo esc_url( $cover_link ); ?>" target="_blank" rel="noopener">View cover letter</a><?php endif; ?>
							<?php if ( $job_url )    : ?><a href="<?php echo esc_url( $job_url ); ?>" target="_blank" rel="noopener">View job posting</a><?php endif; ?>
						</div>

						<div class="jap-job-foot">
							<form method="post" action="<?php echo esc_url( $jap_dash_page ); ?>" class="jap-status-form">
								<?php wp_nonce_field( 'jap_status_action', 'jap_status_nonce' ); ?>
								<input type="hidden" name="jap_post_id" value="<?php echo esc_attr( $post_id ); ?>">
								<input type="hidden" name="jap_filter" value="<?php echo esc_attr( $filter ); ?>">

								<?php if ( 'beworben' === $status ) : ?>
									<span class="jap-status-mark jap-status-mark--go">Applied</span>
									<button type="submit" name="jap_new_status" value="neu" class="jap-btn jap-btn--tiny">Undo</button>
								<?php elseif ( 'abgelehnt' === $status ) : ?>
									<span class="jap-status-mark jap-status-mark--off">Not for me</span>
									<button type="submit" name="jap_new_status" value="neu" class="jap-btn jap-btn--tiny">Undo</button>
								<?php else : ?>
									<button type="submit" name="jap_new_status" value="beworben" class="jap-btn jap-btn--quiet">Mark as applied</button>
									<button type="submit" name="jap_new_status" value="abgelehnt" class="jap-btn jap-btn--tiny">Not for me</button>
								<?php endif; ?>
							</form>

							<?php if ( $date_found ) : ?>
								<span class="jap-date">Found on <?php echo esc_html( $date_found ); ?></span>
							<?php endif; ?>
						</div>
					</div>
				</article>
		<?php endwhile; wp_reset_postdata(); ?>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
} );

/* -------------------------------------------------------------------------
 * 7. REST-API-Endpunkt: n8n schreibt hier Ergebnisse zurueck
 *    Aufruf per Application Password (Basic Auth) eines Admin-Kontos.
 * ---------------------------------------------------------------------- */
add_action( 'rest_api_init', function () {
	register_rest_route( 'jap/v1', '/bewerbung', array(
		'methods'             => 'POST',
		'callback'            => 'jap_rest_create_bewerbung',
		'permission_callback' => function () {
			return current_user_can( 'edit_posts' );
		},
	) );
} );

function jap_rest_create_bewerbung( WP_REST_Request $request ) {
	$data = $request->get_json_params();

	$target_user_id = isset( $data['user_id'] ) ? intval( $data['user_id'] ) : 0;
	if ( ! $target_user_id && ! empty( $data['email'] ) ) {
		$user = get_user_by( 'email', sanitize_email( $data['email'] ) );
		if ( $user ) { $target_user_id = $user->ID; }
	}
	if ( ! $target_user_id ) {
		return new WP_Error( 'jap_missing_user', 'user_id or email required', array( 'status' => 400 ) );
	}

	// Doppelte Eintraege verhindern.
	// Der Workflow laeuft alle zwei Stunden und sucht in einem Zeitfenster von
	// mehreren Tagen - dieselbe Stelle taucht also immer wieder auf.
	// Ohne diese Pruefung wuerde sie dem Kunden dutzendfach angezeigt.
	$job_url = isset( $data['job_url'] ) ? esc_url_raw( $data['job_url'] ) : '';
	if ( $job_url ) {
		$schon_da = get_posts( array(
			'post_type'      => 'jap_bewerbung',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array(
				'relation' => 'AND',
				array( 'key' => 'jap_user_id', 'value' => $target_user_id, 'compare' => '=' ),
				array( 'key' => 'jap_job_url', 'value' => $job_url, 'compare' => '=' ),
			),
		) );
		if ( ! empty( $schon_da ) ) {
			return array(
				'success'   => true,
				'post_id'   => (int) $schon_da[0],
				'duplicate' => true,
				'hinweis'   => 'This role was already saved for this customer.',
			);
		}
	}

	$post_id = wp_insert_post( array(
		'post_type'   => 'jap_bewerbung',
		'post_title'  => sanitize_text_field( $data['job_title'] ?? 'Untitled role' ),
		'post_status' => 'publish',
	) );

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	$meta_map = array(
		'jap_company'    => $data['company']     ?? '',
		'jap_location'   => $data['location']    ?? '',
		'jap_work_model' => $data['work_model']  ?? '',
		'jap_score'      => $data['score']       ?? 0,
		'jap_reason'     => $data['reason']      ?? '',
		'jap_cv_link'    => $data['cv_link']     ?? '',
		'jap_cover_link' => $data['cover_link']  ?? '',
		'jap_job_url'    => $data['job_url']     ?? '',
		'jap_date_found' => $data['date_found']  ?? current_time( 'Y-m-d' ),
		'jap_bewerbungs_email' => $data['application_email'] ?? '',
		'jap_betreff'          => $data['betreff']           ?? '',
	);
	foreach ( $meta_map as $key => $value ) {
		update_post_meta( $post_id, $key, sanitize_text_field( is_scalar( $value ) ? $value : '' ) );
	}
	update_post_meta( $post_id, 'jap_brieftext', sanitize_textarea_field( $data['brieftext'] ?? '' ) );
	update_post_meta( $post_id, 'jap_user_id', $target_user_id );
	update_post_meta( $post_id, 'jap_status', 'neu' );

	// Dokumente direkt hier erzeugen, sofern n8n die noetigen Angaben mitliefert.
	// Bewusst abgesichert: schlaegt die PDF-Erzeugung fehl, wird der Job trotzdem
	// gespeichert und im Dashboard angezeigt - nur ohne Anhaenge.
	$erzeugt = array( 'cv_link' => '', 'cover_link' => '' );
	try {
		$erzeugt = jap_erzeuge_dokumente( $target_user_id, $post_id, $data );
	} catch ( Throwable $e ) {
		error_log( 'JAP: document generation failed: ' . $e->getMessage() );
	}
	if ( $erzeugt['cv_link'] ) {
		update_post_meta( $post_id, 'jap_cv_link', $erzeugt['cv_link'] );
	}
	if ( $erzeugt['cover_link'] ) {
		update_post_meta( $post_id, 'jap_cover_link', $erzeugt['cover_link'] );
	}

	jap_send_new_job_email( $target_user_id, $post_id );

	return array( 'success' => true, 'post_id' => $post_id );
}

/* -------------------------------------------------------------------------
 * 8. E-Mail-Benachrichtigung bei neuem Job-Treffer
 * ---------------------------------------------------------------------- */
function jap_send_new_job_email( $user_id, $post_id ) {
	$user = get_userdata( $user_id );
	if ( ! $user || ! is_email( $user->user_email ) ) {
		return false;
	}

	$title    = get_the_title( $post_id );
	$company  = get_post_meta( $post_id, 'jap_company', true );
	$location = get_post_meta( $post_id, 'jap_location', true );
	$score    = get_post_meta( $post_id, 'jap_score', true );
	$reason   = get_post_meta( $post_id, 'jap_reason', true );
	$job_url  = get_post_meta( $post_id, 'jap_job_url', true );
	$dash_url = jap_dashboard_url();

	$name    = $user->display_name ? $user->display_name : $user->user_login;
	$subject = 'New match: ' . $title;

	$lines   = array();
	$lines[] = 'Hi ' . $name . ',';
	$lines[] = '';
	$lines[] = 'we found a role that fits you:';
	$lines[] = '';
	$lines[] = $title;
	if ( $company )  { $lines[] = 'Company: ' . $company; }
	if ( $location ) { $lines[] = 'Location: ' . $location; }
	if ( $score )    { $lines[] = 'Match score: ' . $score . '/100'; }
	if ( $reason )   { $lines[] = ''; $lines[] = $reason; }
	$lines[] = '';

	// Nur behaupten, was auch wirklich erzeugt wurde.
	$cv_link    = get_post_meta( $post_id, 'jap_cv_link', true );
	$cover_link = get_post_meta( $post_id, 'jap_cover_link', true );

	if ( $cv_link && $cover_link ) {
		$lines[] = 'Your CV and cover letter for this role are written and waiting.';
	} elseif ( $cv_link ) {
		$lines[] = 'Your CV for this role is ready. The cover letter could not be written this time.';
	} else {
		$lines[] = 'The documents could not be generated this time - the role itself is still worth a look.';
	}

	$lines[] = 'Open your dashboard: ' . $dash_url;
	if ( $job_url ) { $lines[] = 'The job posting: ' . $job_url; }
	$lines[] = '';
	$lines[] = 'Read them over before you send anything - they are drafts, not finished work.';
	$lines[] = '';
	$lines[] = 'Good luck out there.';

	return wp_mail( $user->user_email, $subject, implode( "\n", $lines ) );
}

/* -------------------------------------------------------------------------
 * 9. REST-Endpunkt: Kundenliste fuer n8n
 *    n8n holt sich hier alle aktiven Kunden mit ihren Suchpraeferenzen,
 *    um pro Kunde eine eigene Suche zu fahren.
 *    GET /wp-json/jap/v1/kunden   (Basic Auth mit Application Password)
 * ---------------------------------------------------------------------- */
add_action( 'rest_api_init', function () {
	register_rest_route( 'jap/v1', '/kunden', array(
		'methods'             => 'GET',
		'callback'            => 'jap_rest_list_kunden',
		'permission_callback' => function () {
			return current_user_can( 'edit_posts' );
		},
	) );
} );

function jap_rest_list_kunden( WP_REST_Request $request ) {
	$users = get_users( array(
		'meta_key'   => 'jap_status',
		'meta_value' => 'aktiv',
		'number'     => 200,
		'orderby'    => 'ID',
		'order'      => 'ASC',
	) );

	$staedte  = jap_staedte();
	$bereiche = jap_bereiche();
	$out      = array();

	foreach ( $users as $u ) {
		$keywords = get_user_meta( $u->ID, 'jap_keywords', true );
		if ( ! is_array( $keywords ) || ! $keywords ) {
			$roh      = get_user_meta( $u->ID, 'jap_branche', true );
			$keywords = $roh ? array_map( 'trim', explode( ',', $roh ) ) : array();
		}

		$arten = get_user_meta( $u->ID, 'jap_arbeitszeit', true );
		if ( ! is_array( $arten ) ) {
			$arten = $arten ? array( $arten ) : array( 'werkstudent' );
		}

		$arten_labels = get_user_meta( $u->ID, 'jap_arbeitszeit_labels', true );
		if ( ! is_array( $arten_labels ) || ! $arten_labels ) {
			$alle_arten   = jap_stellenarten();
			$arten_labels = array();
			foreach ( $arten as $a ) {
				if ( isset( $alle_arten[ $a ] ) ) {
					$arten_labels[] = $alle_arten[ $a ];
				}
			}
		}

		$bereich_keys = get_user_meta( $u->ID, 'jap_bereiche', true );
		if ( ! is_array( $bereich_keys ) ) {
			$bereich_keys = array();
		}
		$bereich_labels = array();
		foreach ( $bereich_keys as $bk ) {
			if ( isset( $bereiche[ $bk ] ) ) {
				$bereich_labels[] = $bereiche[ $bk ]['label'];
			}
		}

		$standort     = get_user_meta( $u->ID, 'jap_standort', true );
		$standort_key = get_user_meta( $u->ID, 'jap_standort_key', true );
		$suchweite_roh = get_user_meta( $u->ID, 'jap_suchweite', true );
		if ( is_array( $suchweite_roh ) ) {
			$suchweite = $suchweite_roh;
		} elseif ( $suchweite_roh ) {
			// Altbestand: frueher wurde nur ein einzelner Text gespeichert.
			$suchweite = array( $suchweite_roh );
		} else {
			$suchweite = array();
		}
		if ( ! $suchweite ) {
			// Noch aelterer Bestand: kannte nur Stadt oder Remote.
			$suchweite = ( 'remote' === $standort_key ) ? array( 'nur_remote' ) : array( 'stadt_remote' );
		}

		$keys = array();
		foreach ( array_keys( jap_api_felder() ) as $k ) {
			$keys[ $k ] = jap_api_key_lesen( $u->ID, 'jap_' . $k );
		}


		// Startklar = beide Vorlagen vorhanden + Suchquelle + Bewertung.
		// Gesucht wird ueber Adzuna, SerpApi ist derzeit nicht im Einsatz.
		$fehlt = array();
		if ( ! get_user_meta( $u->ID, 'jap_cv_url', true ) ) { $fehlt[] = 'CV (PDF)'; }

		// Bekanntes Schluesselproblem: Kunde wird uebersprungen, bis er einen
		// neuen Schluessel speichert. Sonst scheitert jeder Durchlauf erneut daran.
		$key_problem = get_user_meta( $u->ID, 'jap_key_problem', true );
		if ( $key_problem ) { $fehlt[] = $key_problem . ' key rejected'; }

		if ( ! $keys['groq_key'] )   { $fehlt[] = 'Groq API key'; }

		$bereit = empty( $fehlt );

		$out[] = array(
			'user_id'         => $u->ID,
			'bereit'          => (bool) $bereit,
			'fehlt'           => $fehlt,
			'api'             => $keys,
			'name'            => $u->display_name,
			'email'           => $u->user_email,
			'cv_url'          => get_user_meta( $u->ID, 'jap_cv_url', true ),
			'standort'        => $standort ? $standort : 'Deutschland',
			'suchweite'       => $suchweite,
			'nur_remote'      => in_array( 'nur_remote', $suchweite, true ),
			'bundesweit'      => (bool) array_intersect( array( 'bundesweit', 'nur_remote' ), $suchweite ),
			'remote_ok'       => (bool) array_intersect( array( 'stadt_remote', 'bundesweit', 'nur_remote' ), $suchweite ),
			'studienfach'     => (string) get_user_meta( $u->ID, 'jap_studienfach', true ),
			'ist_remote'      => in_array( 'nur_remote', $suchweite, true ),
			'stellenarten'    => array_values( $arten ),
			'stellenart_text' => implode( ', ', $arten_labels ),
			'bereiche'        => array_values( $bereich_keys ),
			'bereich_text'    => implode( ', ', $bereich_labels ),
			'keywords'        => array_values( $keywords ),
			'registriert_am'  => $u->user_registered,
		);
	}

	return array(
		'anzahl' => count( $out ),
		'kunden' => $out,
		// Betriebsdaten fuer die gemeinsame Suche: ein Adzuna-Zugang fuer alle,
		// damit sich Kunden dort nicht mehr selbst anmelden muessen.
		'system' => array(
			'adzuna_id'  => function_exists( 'jap_text' ) ? jap_text( 'adzuna_id' ) : '',
			'adzuna_key' => function_exists( 'jap_text' ) ? jap_text( 'adzuna_key' ) : '',
			'staedte'    => jap_such_staedte(),
		),
	);
}

/* WordPress erlaubt .docx-Uploads nicht in jeder Konfiguration.
 * Fuer unsere Vorlagen-Upload-Seite schalten wir den Typ gezielt frei. */
add_filter( 'upload_mimes', function ( $mimes ) {
	$mimes['docx'] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
	return $mimes;
} );

/* -------------------------------------------------------------------------
 * 10. Dokumente erzeugen
 *     Wird vom REST-Endpunkt aufgerufen, sobald n8n einen Treffer meldet.
 *     Alles laeuft auf diesem Server - kein Google, kein fremdes Konto.
 * ---------------------------------------------------------------------- */
function jap_erzeuge_dokumente( $user_id, $post_id, $data ) {
	$ergebnis = array( 'cv_link' => '', 'cover_link' => '' );

	$cv = isset( $data['cv_daten'] ) && is_array( $data['cv_daten'] ) ? $data['cv_daten'] : array();
	if ( empty( $cv['name'] ) ) {
		$user = get_userdata( $user_id );
		$cv['name'] = $user ? $user->display_name : '';
	}
	if ( empty( $cv['name'] ) ) {
		return $ergebnis; // Ohne Namen kein sinnvolles Dokument.
	}

	if ( ! class_exists( 'FPDF' ) ) {
		require_once plugin_dir_path( __FILE__ ) . 'lib/fpdf/fpdf.php';
	}

	$ziel = jap_docs_dir( $user_id );
	$slug = sanitize_title( ( $data['company'] ?? 'firma' ) . '-' . ( $data['job_title'] ?? 'stelle' ) );
	$slug = substr( $slug, 0, 60 ) . '-' . $post_id;

	/* ---------- Lebenslauf ---------- */
	$cv_felder = array(
		'name'         => $cv['name'],
		'titel'        => $cv['titel']        ?? '',
		'geburtsdatum' => $cv['geburtsdatum'] ?? '',
		'adresse'      => $cv['adresse']      ?? '',
		'kontakt'      => $cv['kontakt']      ?? '',
		'ort'          => $cv['ort']          ?? '',
		'profil'       => $cv['profil']       ?? '',
		'erfahrung'    => ( isset( $cv['erfahrung'] )  && is_array( $cv['erfahrung'] ) )  ? $cv['erfahrung']  : array(),
		'projekte'     => ( isset( $cv['projekte'] )   && is_array( $cv['projekte'] ) )   ? $cv['projekte']   : array(),
		'bildung'      => ( isset( $cv['bildung'] )    && is_array( $cv['bildung'] ) )    ? $cv['bildung']    : array(),
		'kenntnisse'   => ( isset( $cv['kenntnisse'] ) && is_array( $cv['kenntnisse'] ) ) ? $cv['kenntnisse'] : array(),
		'sprachen'     => ( isset( $cv['sprachen'] )   && is_array( $cv['sprachen'] ) )   ? $cv['sprachen']   : array(),
	);

	try {
		$cv_datei = trailingslashit( $ziel['dir'] ) . 'CV-' . $slug . '.pdf';
		jap_build_cv_pdf( $cv_felder, $cv_datei );
		$ergebnis['cv_link'] = trailingslashit( $ziel['url'] ) . basename( $cv_datei );
	} catch ( Exception $e ) {
		error_log( 'JAP: could not build CV PDF: ' . $e->getMessage() );
	}

	/* ---------- Anschreiben ---------- */
	$brief_felder = array(
		'name'            => $cv['name'],
		'adresse'         => $cv['adresse'] ?? '',
		'kontakt'         => $cv['kontakt'] ?? '',
		'ort'             => $cv['ort']     ?? '',
		'firma_name'      => $data['firma_name']      ?? ( $data['company'] ?? '' ),
		'firma_anschrift' => $data['firma_anschrift'] ?? '',
		'firma_ort'       => $data['firma_ort']       ?? '',
		'betreff'         => $data['betreff']         ?? ( 'Bewerbung als ' . ( $data['job_title'] ?? '' ) ),
		'anrede'          => $data['anrede']          ?? 'Sehr geehrte Damen und Herren,',
		'brieftext'       => $data['brieftext']       ?? '',
	);

	if ( ! empty( $brief_felder['brieftext'] ) ) {
		try {
			$brief_datei = trailingslashit( $ziel['dir'] ) . 'Cover-Letter-' . $slug . '.pdf';
			jap_build_cover_pdf( $brief_felder, $brief_datei );
			$ergebnis['cover_link'] = trailingslashit( $ziel['url'] ) . basename( $brief_datei );
		} catch ( Exception $e ) {
			error_log( 'JAP: could not build cover letter PDF: ' . $e->getMessage() );
		}
	}

	return $ergebnis;
}

/* -------------------------------------------------------------------------
 * 11. Schluesselprobleme melden
 *     n8n ruft diesen Endpunkt auf, wenn der Groq- oder Adzuna-Schluessel
 *     eines Kunden abgelehnt wurde. Der Kunde bekommt dann eine E-Mail und
 *     sieht einen Hinweis in seinen Einstellungen.
 *     POST /wp-json/jap/v1/schluessel-problem
 * ---------------------------------------------------------------------- */
add_action( 'rest_api_init', function () {
	register_rest_route( 'jap/v1', '/schluessel-problem', array(
		'methods'             => 'POST',
		'callback'            => 'jap_rest_schluessel_problem',
		'permission_callback' => function () {
			return current_user_can( 'edit_posts' );
		},
	) );
} );

function jap_rest_schluessel_problem( WP_REST_Request $request ) {
	$data = $request->get_json_params();

	$user_id = isset( $data['user_id'] ) ? intval( $data['user_id'] ) : 0;
	if ( ! $user_id && ! empty( $data['email'] ) ) {
		$user = get_user_by( 'email', sanitize_email( $data['email'] ) );
		if ( $user ) { $user_id = $user->ID; }
	}
	if ( ! $user_id ) {
		return new WP_Error( 'jap_missing_user', 'user_id or email required', array( 'status' => 400 ) );
	}

	$dienst = sanitize_text_field( $data['dienst'] ?? 'Groq' );
	$grund  = sanitize_text_field( $data['grund'] ?? 'The key was rejected.' );

	update_user_meta( $user_id, 'jap_key_problem', $dienst );
	update_user_meta( $user_id, 'jap_key_problem_zeit', current_time( 'mysql' ) );

	// Hoechstens eine E-Mail pro Tag, damit niemand mit Meldungen zugeschuettet wird.
	$zuletzt = (int) get_user_meta( $user_id, 'jap_key_problem_mail', true );
	$mail_versendet = false;

	if ( time() - $zuletzt > DAY_IN_SECONDS ) {
		$mail_versendet = jap_send_key_problem_email( $user_id, $dienst, $grund );
		update_user_meta( $user_id, 'jap_key_problem_mail', time() );
	}

	return array(
		'success'        => true,
		'user_id'        => $user_id,
		'email_gesendet' => (bool) $mail_versendet,
	);
}

function jap_send_key_problem_email( $user_id, $dienst, $grund ) {
	$user = get_userdata( $user_id );
	if ( ! $user || ! is_email( $user->user_email ) ) { return false; }

	$name       = $user->display_name ? $user->display_name : $user->user_login;
	$settings   = jap_einstellungen_url();

	$subject = 'Your ' . $dienst . ' key needs attention';

	$lines   = array();
	$lines[] = 'Hi ' . $name . ',';
	$lines[] = '';
	$lines[] = 'your search is paused. The ' . $dienst . ' key in your settings was rejected,';
	$lines[] = 'so we cannot look for roles on your behalf right now.';
	$lines[] = '';
	$lines[] = 'What the service told us: ' . $grund;
	$lines[] = '';
	$lines[] = 'This usually means the key was deleted, regenerated, or the free quota ran out.';
	$lines[] = 'Creating a new key takes about two minutes:';
	$lines[] = '';
	$lines[] = '1. Open ' . ( 'Groq' === $dienst ? 'https://console.groq.com/keys' : 'https://developer.adzuna.com/' );
	$lines[] = '2. Create a new key and copy it.';
	$lines[] = '3. Paste it here: ' . $settings;
	$lines[] = '';
	$lines[] = 'The search starts again by itself once the new key is saved.';
	$lines[] = '';
	$lines[] = 'Nothing else is lost - your CV and your past matches are still there.';

	return wp_mail( $user->user_email, $subject, implode( "\n", $lines ) );
}

/** Adresse der Kunden-Einstellungsseite finden. */
function jap_einstellungen_url() {
	$pages = get_pages();
	if ( $pages ) {
		foreach ( $pages as $p ) {
			if ( has_shortcode( $p->post_content, 'jap_einstellungen' ) ) {
				return get_permalink( $p->ID );
			}
		}
	}
	return home_url( '/settings/' );
}

/* -------------------------------------------------------------------------
 * 12. Willkommens-E-Mail
 *     Wird direkt nach der Registrierung verschickt. Sie erklaert die
 *     beiden Schritte, die noch fehlen - ohne die passiert naemlich nichts.
 * ---------------------------------------------------------------------- */
function jap_send_welcome_email( $user_id ) {
	$user = get_userdata( $user_id );
	if ( ! $user || ! is_email( $user->user_email ) ) { return false; }

	$name      = $user->display_name ? $user->display_name : $user->user_login;
	$settings  = jap_einstellungen_url();
	$dashboard = jap_dashboard_url();

	// Was fehlt diesem Kunden noch, damit die Suche laufen kann?
	// Adzuna laeuft inzwischen ueber ein gemeinsames System-Konto (siehe
	// jap_such_staedte() / REST /kunden) - der Kunde hinterlegt dafuer nichts
	// mehr selbst. Nur der persoenliche Groq-Schluessel wird noch gebraucht.
	$fehlt = array();
	if ( ! jap_api_key_lesen( $user_id, 'jap_groq_key' ) ) { $fehlt[] = 'Groq API key'; }

	$subject = $fehlt ? 'Welcome, one thing left before we can start' : 'Welcome, you are all set';

	$lines   = array();
	$lines[] = 'Hi ' . $name . ',';
	$lines[] = '';
	$lines[] = 'your account is ready and your CV is saved.';
	$lines[] = '';

	if ( $fehlt ) {
		$lines[] = 'One thing is still missing before the search can run: your own Groq key.';
		$lines[] = 'It takes about two minutes, once, and then never again.';
		$lines[] = '';
		$lines[] = 'Groq (free)';
		$lines[] = '  Sign in at https://console.groq.com/keys with Google or GitHub';
		$lines[] = '  Click "Create API Key" and copy it right away - it is shown only once.';
		$lines[] = '';
		$lines[] = 'Then paste it here: ' . $settings;
		$lines[] = '';
		$lines[] = 'Why your own key? Because that is what keeps this free. Groq reads your CV';
		$lines[] = 'and writes your documents - on your own free account that costs nothing.';
	} else {
		$lines[] = 'Everything is set up. We are already searching for you.';
	}

	$lines[] = '';
	$lines[] = 'Once that is done, we check the job boards several times a day and email you';
	$lines[] = 'whenever something fits - with a CV and cover letter already written for it.';
	$lines[] = '';
	$lines[] = 'Your dashboard: ' . $dashboard;
	$lines[] = '';
	$lines[] = 'One more thing: nothing is ever sent in your name. You read every document';
	$lines[] = 'first and press send yourself.';
	$lines[] = '';
	$lines[] = 'Good luck out there.';

	return wp_mail( $user->user_email, $subject, implode( "\n", $lines ) );
}

/* Staedte fuer die gemeinsame Suche.
 * Format je Zeile: "Leipzig | 50" (Stadt | Umkreis in km). */
function jap_such_staedte() {
	$text = function_exists( 'jap_text' ) ? jap_text( 'such_staedte' ) : '';
	if ( ! $text ) { $text = "Leipzig | 50\nBerlin | 30"; }

	$raus = array();
	foreach ( preg_split( "/\r\n|\r|\n/", $text ) as $zeile ) {
		$zeile = trim( $zeile );
		if ( '' === $zeile || 0 === strpos( $zeile, '#' ) ) { continue; }
		$teile   = array_map( 'trim', explode( '|', $zeile ) );
		$stadt   = $teile[0];
		$umkreis = isset( $teile[1] ) ? intval( $teile[1] ) : 30;
		if ( '' === $stadt ) { continue; }
		$raus[] = array( 'stadt' => $stadt, 'umkreis' => $umkreis > 0 ? $umkreis : 30 );
	}
	return $raus;
}
