<?php
/**
 * Rechtstexte.
 * Shortcodes: [jap_datenschutz] und [jap_impressum]
 *
 * Hinweis: Vorlage, keine Rechtsberatung. Vor dem Livegang bitte pruefen lassen.
 * Die Angaben im Impressum musst du in den WordPress-Einstellungen eintragen,
 * siehe unten (jap_impressum_daten).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* Deine Angaben fuer das Impressum.
 * Trage sie hier ein oder ueberschreibe sie per Filter. */
function jap_impressum_daten() {
	return apply_filters( 'jap_impressum_daten', array(
		'name'     => '',            // Vor- und Nachname
		'strasse'  => '',            // Strasse und Hausnummer
		'ort'      => '',            // PLZ und Ort
		'land'     => 'Germany',
		'email'    => get_option( 'admin_email' ),
		'telefon'  => '',            // optional
	) );
}

add_shortcode( 'jap_impressum', function () {
	$d    = jap_impressum_daten();
	$luecke = ( ! $d['name'] || ! $d['strasse'] || ! $d['ort'] );

	ob_start();
	?>
	<div class="jap-legal">
		<?php if ( $luecke && current_user_can( 'manage_options' ) ) : ?>
			<div class="jap-status-box jap-status-box--warn">
				<strong>Only you can see this notice.</strong>
				<p>
					The imprint is still missing your details. German law requires a full name and
					a postal address here. Add them via the <code>jap_impressum_daten</code> filter
					in your theme functions file, or ask for them to be filled in.
				</p>
			</div>
		<?php endif; ?>

		<h2>Imprint</h2>
		<p class="jap-legal-sub">Information according to &sect;&nbsp;5 DDG (German Digital Services Act)</p>

		<h3>Responsible for this website</h3>
		<p>
			<?php echo esc_html( $d['name'] ? $d['name'] : '[Your full name]' ); ?><br>
			<?php echo esc_html( $d['strasse'] ? $d['strasse'] : '[Street and number]' ); ?><br>
			<?php echo esc_html( $d['ort'] ? $d['ort'] : '[Postcode and city]' ); ?><br>
			<?php echo esc_html( $d['land'] ); ?>
		</p>

		<h3>Contact</h3>
		<p>
			Email: <?php echo esc_html( $d['email'] ); ?>
			<?php if ( $d['telefon'] ) : ?><br>Phone: <?php echo esc_html( $d['telefon'] ); ?><?php endif; ?>
		</p>

		<h3>Nature of this website</h3>
		<p>
			This is a privately run, non-commercial project. It is offered free of charge,
			there is no company behind it, and nothing is sold here.
		</p>

		<h3>Liability for links</h3>
		<p>
			This site links to job postings on external websites. Their content is the sole
			responsibility of the respective operators. At the time of linking no unlawful
			content was apparent. Permanent monitoring of linked pages is not reasonable
			without concrete evidence of an infringement.
		</p>

		<h3>Dispute resolution</h3>
		<p>
			We are neither obliged nor willing to take part in dispute resolution proceedings
			before a consumer arbitration board.
		</p>
	</div>
	<?php
	return ob_get_clean();
} );

add_shortcode( 'jap_datenschutz', function () {
	$d = jap_impressum_daten();

	ob_start();
	?>
	<div class="jap-legal">
		<h2>Privacy Policy</h2>
		<p class="jap-legal-sub">How your data is handled here, in plain words.</p>

		<h3>The short version</h3>
		<p>
			Your CV and your details stay on this website. They are never sold, never shared
			with employers, and never used for advertising. No applications are sent in your
			name without you pressing send yourself.
		</p>

		<h3>Who is responsible</h3>
		<p>
			<?php echo esc_html( $d['name'] ? $d['name'] : '[Your full name]' ); ?>,
			<?php echo esc_html( $d['ort'] ? $d['ort'] : '[Postcode and city]' ); ?>.
			Contact: <?php echo esc_html( $d['email'] ); ?>
		</p>

		<h3>What is stored, and why</h3>
		<p>
			<strong>Account data</strong>: your name, email address and password.
			Needed to give you an account. Legal basis: performance of a contract,
			Art.&nbsp;6(1)(b) GDPR.
		</p>
		<p>
			<strong>Your CV (PDF)</strong>: uploaded by you. It is read once to extract
			your details (name, contact, studies, experience, skills) so they can be placed into
			the documents written for each role. The file stays on this server.
		</p>
		<p>
			<strong>Your preferences</strong>: field of study, city, type of role.
			Used to build the search queries.
		</p>
		<p>
			<strong>Your API keys</strong>: the keys you paste in your settings.
			Stored so the search can run on your own accounts. They are shown only in
			shortened form and are never displayed in full again.
		</p>
		<p>
			<strong>Found roles and documents</strong>: the matches and the generated
			PDFs, so you can find them again in your dashboard.
		</p>

		<h3>Who else sees your data</h3>
		<p>
			To find and assess roles, the following services are contacted, using
			<em>your</em> own accounts:
		</p>
		<ul>
			<li>
				<strong>Bundesagentur f&uuml;r Arbeit</strong>: searched for job postings.
				Only search terms are sent, never your personal data.
			</li>
			<li>
				<strong>Adzuna</strong>: searched for job postings.
				Only search terms are sent, never your personal data.
			</li>
			<li>
				<strong>Groq</strong>: used to assess roles and draft your documents.
				Here parts of your CV are transmitted, because that is what the assessment
				and the cover letter are based on.
			</li>
		</ul>
		<p>
			Beyond these, your data is not passed on to anyone.
		</p>

		<h3>Data transfer outside the EU</h3>
		<p>
			Groq, used to assess roles and draft your documents, is a company based in the
			United States. Sending parts of your CV to Groq therefore means a transfer of
			personal data outside the EU/EEA, based on Groq's standard contractual clauses
			(Art.&nbsp;46 GDPR). If you would rather your CV data not be sent to a US-based
			service at all, please get in touch using the contact details above.
		</p>

		<h3>Hosting</h3>
		<p>
			This website is hosted by Hostinger. The server records the usual access data
			(IP address, time, page requested) for security and operation.
		</p>

		<h3>Contact form</h3>
		<p>
			What you write in the contact form is sent by email to the address above and kept
			only as long as needed to answer you.
		</p>

		<h3>How long data is kept</h3>
		<p>
			As long as your account exists. Ask to have it deleted and everything goes:
			account, CV, preferences, keys, documents.
		</p>

		<h3>Your rights</h3>
		<p>
			You can ask what is stored about you, have it corrected or deleted, restrict its
			use, receive a copy, or object to processing. One email to the address above is
			enough. You may also complain to a data protection authority.
		</p>

		<h3>Cookies</h3>
		<p>
			Only the cookies WordPress needs to keep you signed in. No tracking, no analytics,
			no advertising cookies.
		</p>
	</div>
	<?php
	return ob_get_clean();
} );
