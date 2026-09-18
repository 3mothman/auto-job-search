<?php
/**
 * Weitere Seiten.
 * Shortcodes: [jap_ueber] (About) und [jap_agb] (Terms of Use)
 *
 * Hinweis zu den Nutzungsbedingungen: Vorlage, keine Rechtsberatung.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_shortcode( 'jap_ueber', function () {
	$register = home_url( '/register/' );
	$pages    = get_pages();
	if ( $pages ) {
		foreach ( $pages as $p ) {
			if ( has_shortcode( $p->post_content, 'jap_register' ) ) { $register = get_permalink( $p->ID ); }
		}
	}

	ob_start();
	?>
	<div class="jap-legal jap-about">
		<h2>About this project</h2>
		<p class="jap-legal-sub">Who is behind it, and why it exists.</p>

		<p class="jap-about-lead">
			There is no team here. No company, no investors, no office.
			Just one student in Halle who kept losing evenings to job boards.
		</p>

		<h3>How it started</h3>
		<p>
			Looking for a working student role in Germany means opening the same handful of
			sites over and over, reading through listings that turn out to require a finished
			degree, and rewriting the same cover letter for the twentieth time. It eats hours
			that should go into studying, and the roles that actually fit are usually
			gone by the time you find them.
		</p>
		<p>
			So instead of searching harder, I built something that searches for me. It checks
			the job boards every two hours, throws out everything that is not genuinely open to
			students, and writes the documents for the ones that are. It worked, so it seemed
			wrong to keep it to myself.
		</p>

		<h3>Why it stays free</h3>
		<p>
			Because the people who need it most are the ones with the least to spare.
			It also costs almost nothing to run: the searches happen on your own free accounts
			at the job boards, not on a server I pay for. There is no paid tier waiting behind
			this page.
		</p>

		<h3>What it is not</h3>
		<p>
			It is not a recruitment agency and it has no relationship with any employer.
			It does not apply on your behalf, you read every document and press send
			yourself. And it will not flood you: some days there is simply nothing worth
			showing you, and on those days you hear nothing.
		</p>

		<h3>What comes next</h3>
		<p>
			More job sources, better matching, and whatever the people using it ask for.
			If something is broken or missing, tell me, the contact form goes straight
			to my inbox and I read everything myself.
		</p>

		<p class="jap-cta-row" style="margin-top:26px;">
			<a class="jap-cta" href="<?php echo esc_url( $register ); ?>">Create a free account</a>
		</p>
	</div>
	<?php
	return ob_get_clean();
} );

add_shortcode( 'jap_agb', function () {
	$d = function_exists( 'jap_impressum_daten' ) ? jap_impressum_daten() : array( 'email' => get_option( 'admin_email' ) );

	ob_start();
	?>
	<div class="jap-legal">
		<h2>Terms of Use</h2>
		<p class="jap-legal-sub">Last updated: <?php echo esc_html( date_i18n( 'F Y' ) ); ?></p>

		<h3>1. What this service is</h3>
		<p>
			This website searches public job boards for roles that match the preferences you
			enter, scores them, and prepares a CV and a cover letter for each match. It is a
			privately run, non-commercial project offered free of charge.
		</p>

		<h3>2. What it is not</h3>
		<p>
			It is not a recruitment agency, not an employment service and not a legal or career
			advisor. There is no relationship with the companies whose postings appear here.
			No application is ever sent in your name, you send every application yourself.
		</p>

		<h3>3. Your account</h3>
		<p>
			You need an account to use the service. Keep your password to yourself and give
			accurate information. You may only upload a CV that is your own. You can delete
			your account at any time by writing to
			<?php echo esc_html( $d['email'] ); ?>.
		</p>

		<h3>4. Your own API keys</h3>
		<p>
			The search runs on accounts you create yourself at third-party services. Their terms
			apply to those accounts, and any costs you incur there are yours. You remain
			responsible for how those accounts are used.
		</p>

		<h3>5. Documents are drafts, check them</h3>
		<p>
			The CV and cover letter are written automatically from the data in your uploaded CV.
			Automated text can contain mistakes, awkward phrasing, or claims that do not match
			what you meant. <strong>Read every document before you send it.</strong> Once you
			press send, the application is yours, including anything wrong in it.
		</p>

		<h3>6. Matches are suggestions</h3>
		<p>
			The scoring is an estimate, not a judgement. A high score does not mean you will be
			invited, and a role that is filtered out might still have suited you. Job postings
			may also be outdated or removed by the time you see them.
		</p>

		<h3>7. Availability</h3>
		<p>
			The service runs when it runs. There is no guaranteed uptime, no guaranteed number
			of matches, and it may be paused or discontinued at any time. Since nothing is paid
			for, nothing is owed.
		</p>

		<h3>8. Liability</h3>
		<p>
			Liability is limited to intent and gross negligence. There is no liability for
			missed opportunities, applications that fail, roles that disappear, or damages
			arising from mistakes in generated documents, you are asked to check them
			precisely for this reason. Liability for injury to life, body or health, and under
			the German Product Liability Act, remains unaffected.
		</p>

		<h3>9. Fair use</h3>
		<p>
			Do not use the service to spam employers, to upload someone else's CV, or to
			overload the system. Accounts doing so can be closed without notice.
		</p>

		<h3>10. Changes</h3>
		<p>
			These terms may change as the project develops. Significant changes will be
			announced by email to registered users.
		</p>

		<h3>11. Applicable law</h3>
		<p>
			German law applies. If you are a consumer, mandatory consumer protection rules of
			your country of residence remain unaffected.
		</p>

		<h3>Questions</h3>
		<p>
			Anything unclear? Write to <?php echo esc_html( $d['email'] ); ?> and ask.
		</p>
	</div>
	<?php
	return ob_get_clean();
} );
