<?php
/**
 * Einstellungsseite im WordPress-Menü.
 * Damit lassen sich die Texte der Startseite und die Impressumsdaten
 * aendern, ohne den Code anzufassen.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function jap_felder() {
	return array(
		'hero_badge' => array(
			'label' => 'Badge above the headline',
			'typ'   => 'text',
			'std'   => 'Free. Actually free, no card, no trial, no catch.',
		),
		'hero_titel' => array(
			'label' => 'Main headline',
			'typ'   => 'text',
			'hilfe' => 'Use | for a line break.',
			'std'   => 'Job hunting is|exhausting. So we|did it for you.',
		),
		'hero_text' => array(
			'label' => 'Intro paragraph',
			'typ'   => 'textarea',
			'std'   => 'Built by a student who got tired of refreshing job boards between lectures. Every two hours we search for working student, part-time and internship roles that fit your field and your city, and write the CV and cover letter for each one.',
		),
		'shot_text' => array(
			'label' => 'Caption under the dashboard screenshot',
			'typ'   => 'textarea',
			'std'   => 'A real match: scored 78 out of 100, with the reasoning, both documents, and a prepared application email, found while the student was asleep.',
		),
		'frei_text' => array(
			'label' => 'The "Why this is free" section',
			'typ'   => 'textarea',
			'std'   => 'Because it was built for me first, and it felt wrong to charge other students for it. There is no paid tier hiding behind this page and no plan to add one. It is not entirely free to run: I pay a small amount for the web hosting. What you never pay for is the searching itself.',
		),
		'adzuna_id' => array(
			'label' => 'Adzuna App ID (yours)',
			'typ'   => 'text',
			'hilfe' => 'Used for the shared job search. Customers no longer need their own Adzuna account.',
			'std'   => '',
		),
		'adzuna_key' => array(
			'label' => 'Adzuna App Key (yours)',
			'typ'   => 'text',
			'hilfe' => 'The 32-character key from developer.adzuna.com.',
			'std'   => '',
		),
		'such_staedte' => array(
			'label' => 'Cities to search',
			'typ'   => 'textarea',
			'hilfe' => 'One per line: City | radius in km. Example: Leipzig | 50',
			'std'   => "Leipzig | 50\nBerlin | 30",
		),
		'mail_von_name' => array(
			'label' => 'Sender name for emails',
			'typ'   => 'text',
			'hilfe' => 'Shown as the sender in your users inbox. Leave empty to use the site title.',
			'std'   => '',
		),
		'mail_von_adresse' => array(
			'label' => 'Sender address for emails',
			'typ'   => 'text',
			'hilfe' => 'Use an address on your own domain, e.g. hello@yourdomain.de. Addresses at gmail.com or similar are often rejected or marked as spam.',
			'std'   => '',
		),
		'imp_name' => array(
			'label' => 'Imprint: full name',
			'typ'   => 'text',
			'hilfe' => 'Required by German law.',
			'std'   => '',
		),
		'imp_strasse' => array(
			'label' => 'Imprint: street and number',
			'typ'   => 'text',
			'std'   => '',
		),
		'imp_ort' => array(
			'label' => 'Imprint: postcode and city',
			'typ'   => 'text',
			'std'   => '',
		),
		'imp_telefon' => array(
			'label' => 'Imprint: phone (optional)',
			'typ'   => 'text',
			'std'   => '',
		),
	);
}

function jap_text( $key ) {
	$felder = jap_felder();
	$std    = isset( $felder[ $key ] ) ? $felder[ $key ]['std'] : '';
	$wert   = get_option( 'jap_' . $key, '' );
	return ( '' !== $wert ) ? $wert : $std;
}

add_action( 'admin_menu', function () {
	add_menu_page(
		'Job Portal',
		'Job Portal',
		'manage_options',
		'jap-einstellungen',
		'jap_admin_seite',
		'dashicons-search',
		30
	);

	add_submenu_page(
		'jap-einstellungen',
		'Texts & details',
		'Texts & details',
		'manage_options',
		'jap-einstellungen',
		'jap_admin_seite'
	);

	add_submenu_page(
		'jap-einstellungen',
		'Fields & cities',
		'Fields & cities',
		'manage_options',
		'jap-kataloge',
		'jap_admin_kataloge'
	);
} );

function jap_admin_kataloge() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }

	$bereiche = get_option( 'jap_bereiche_text', '' );
	$staedte  = get_option( 'jap_staedte_text', '' );
	$arten    = get_option( 'jap_arten_text', '' );
	?>
	<div class="wrap">
		<h1>Fields of study, cities and role types</h1>
		<p style="max-width:700px;">
			These lists are what people pick from when they sign up. Add, change or remove
			entries here, no code needed. Leave a box empty to fall back to the built-in list.
		</p>

		<div class="notice notice-info inline" style="max-width:700px;padding:10px 12px;">
			<p style="margin:0;">
				<strong>One entry per line.</strong> Lines starting with <code>#</code> are ignored.
				Never change a key that people already selected, their choice would be lost.
				Adding new lines is always safe.
			</p>
		</div>

		<form method="post" action="options.php">
			<?php settings_fields( 'jap_kataloge' ); ?>

			<h2>Fields of study</h2>
			<p class="description" style="max-width:700px;">
				Format: <code>key | Name shown to the user | search word, search word, ...</code><br>
				The search words must be <strong>German</strong>, they go straight to the German
				job boards. The name on the left can be in any language.
			</p>
			<textarea name="jap_bereiche_text" rows="14" class="large-text code"
				placeholder="<?php echo esc_attr( jap_bereiche_als_text( jap_bereiche_standard() ) ); ?>"><?php echo esc_textarea( $bereiche ); ?></textarea>

			<h2>Cities</h2>
			<p class="description" style="max-width:700px;">
				Format: <code>key | City name</code>, the city name is sent to the job boards,
				so write it as Germans write it (Muenchen, not Munich).
			</p>
			<textarea name="jap_staedte_text" rows="12" class="large-text code"
				placeholder="<?php echo esc_attr( jap_liste_als_text( jap_staedte_standard() ) ); ?>"><?php echo esc_textarea( $staedte ); ?></textarea>

			<h2>Role types</h2>
			<p class="description" style="max-width:700px;">
				Format: <code>key | Name shown to the user</code>. Changing these keys affects the
				search wording built into the workflow, add carefully.
			</p>
			<textarea name="jap_arten_text" rows="7" class="large-text code"
				placeholder="<?php echo esc_attr( jap_liste_als_text( jap_stellenarten_standard() ) ); ?>"><?php echo esc_textarea( $arten ); ?></textarea>

			<?php submit_button( 'Save lists' ); ?>
		</form>

		<hr>
		<h2>Currently in use</h2>
		<p class="description">What people see on the sign-up form right now:</p>
		<table class="widefat striped" style="max-width:760px;">
			<thead><tr><th style="width:180px;">Field of study</th><th>Search words sent to the job boards</th></tr></thead>
			<tbody>
			<?php foreach ( jap_bereiche() as $key => $d ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $d['label'] ); ?></strong><br><code><?php echo esc_html( $key ); ?></code></td>
					<td><?php echo esc_html( implode( ' · ', $d['keywords'] ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

add_action( 'admin_init', function () {
	foreach ( array( 'jap_bereiche_text', 'jap_staedte_text', 'jap_arten_text' ) as $name ) {
		register_setting( 'jap_kataloge', $name, array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => '',
		) );
	}

	foreach ( array_keys( jap_felder() ) as $key ) {
		register_setting( 'jap_optionen', 'jap_' . $key, array(
			'type'              => 'string',
			'sanitize_callback' => 'wp_kses_post',
			'default'           => '',
		) );
	}
} );

function jap_admin_seite() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	?>
	<div class="wrap">
		<h1>Job Portal, texts and details</h1>
		<p style="max-width:620px;">
			Everything here can be changed without touching any code.
			Leave a field empty to fall back to the built-in text.
		</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'jap_optionen' ); ?>
			<table class="form-table" role="presentation">
				<?php foreach ( jap_felder() as $key => $f ) :
					$name = 'jap_' . $key;
					$wert = get_option( $name, '' );
					?>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $f['label'] ); ?></label>
						</th>
						<td>
							<?php if ( 'textarea' === $f['typ'] ) : ?>
								<textarea id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>"
									rows="4" class="large-text"
									placeholder="<?php echo esc_attr( $f['std'] ); ?>"><?php echo esc_textarea( $wert ); ?></textarea>
							<?php else : ?>
								<input type="text" id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>"
									value="<?php echo esc_attr( $wert ); ?>" class="regular-text"
									placeholder="<?php echo esc_attr( $f['std'] ); ?>">
							<?php endif; ?>
							<?php if ( ! empty( $f['hilfe'] ) ) : ?>
								<p class="description"><?php echo esc_html( $f['hilfe'] ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button( 'Save texts' ); ?>
		</form>

		<hr>
		<h2>Shortcodes</h2>
		<table class="widefat striped" style="max-width:640px;">
			<tbody>
				<tr><td><code>[jap_startseite]</code></td><td>Landing page</td></tr>
				<tr><td><code>[jap_register]</code></td><td>Sign-up form</td></tr>
				<tr><td><code>[jap_login]</code></td><td>Sign-in form</td></tr>
				<tr><td><code>[jap_dashboard]</code></td><td>Customer dashboard</td></tr>
				<tr><td><code>[jap_einstellungen]</code></td><td>Customer settings (API keys)</td></tr>
				<tr><td><code>[jap_kontakt]</code></td><td>Contact form</td></tr>
				<tr><td><code>[jap_ueber]</code></td><td>About page</td></tr>
				<tr><td><code>[jap_agb]</code></td><td>Terms of Use</td></tr>
				<tr><td><code>[jap_datenschutz]</code></td><td>Privacy Policy</td></tr>
				<tr><td><code>[jap_impressum]</code></td><td>Imprint</td></tr>
			</tbody>
		</table>
	</div>
	<?php
}

/* Impressumsdaten aus den Einstellungen ziehen. */
add_filter( 'jap_impressum_daten', function ( $d ) {
	if ( jap_text( 'imp_name' ) )    { $d['name']    = jap_text( 'imp_name' ); }
	if ( jap_text( 'imp_strasse' ) ) { $d['strasse'] = jap_text( 'imp_strasse' ); }
	if ( jap_text( 'imp_ort' ) )     { $d['ort']     = jap_text( 'imp_ort' ); }
	if ( jap_text( 'imp_telefon' ) ) { $d['telefon'] = jap_text( 'imp_telefon' ); }
	return $d;
} );

/* -------------------------------------------------------------------------
 * Absender der E-Mails.
 * Ohne diese Filter verschickt WordPress alles als "WordPress"
 * <wordpress@deine-domain> - das landet oft im Spam.
 * ---------------------------------------------------------------------- */
add_filter( 'wp_mail_from', function ( $adresse ) {
	$eigene = jap_text( 'mail_von_adresse' );
	return $eigene ? $eigene : $adresse;
} );

add_filter( 'wp_mail_from_name', function ( $name ) {
	$eigener = jap_text( 'mail_von_name' );
	if ( $eigener ) { return $eigener; }

	$titel = get_bloginfo( 'name' );
	return $titel ? $titel : $name;
} );

/* Fehlgeschlagene Mail-Versuche protokollieren, statt sie stillschweigend zu
 * verschlucken - sichtbar im Server-Errorlog (falls WP_DEBUG_LOG aktiv ist). */
add_action( 'wp_mail_failed', function ( $error ) {
	error_log( 'JAP wp_mail failed: ' . $error->get_error_message() );
} );
