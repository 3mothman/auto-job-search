<?php
/**
 * Landing page.
 * Shortcode: [jap_startseite]
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_shortcode( 'jap_startseite', function () {
	$register = '';
	$login    = '';
	$pages    = get_pages();
	if ( $pages ) {
		foreach ( $pages as $p ) {
			if ( has_shortcode( $p->post_content, 'jap_register' ) ) { $register = get_permalink( $p->ID ); }
			if ( has_shortcode( $p->post_content, 'jap_login' ) )    { $login    = get_permalink( $p->ID ); }
		}
	}
	if ( ! $register ) { $register = home_url( '/register/' ); }
	if ( ! $login )    { $login    = home_url( '/login/' ); }

	$logged_in = is_user_logged_in();
	$dashboard = function_exists( 'jap_dashboard_url' ) ? jap_dashboard_url() : home_url( '/dashboard/' );
	$img       = defined( 'JAP_URL' ) ? JAP_URL . 'assets/img/' : plugins_url( 'assets/img/', __FILE__ );

	ob_start();
	?>
	<div class="jap-home">

		<section class="jap-hero">
			<div class="jap-hero-inner">
				<p class="jap-badge"><span class="jap-dot"></span> <?php echo esc_html( jap_text( 'hero_badge' ) ); ?></p>
				<h1><?php echo wp_kses_post( str_replace( '|', '<br>', esc_html( jap_text( 'hero_titel' ) ) ) ); ?></h1>
				<p class="jap-lead"><?php echo esc_html( jap_text( 'hero_text' ) ); ?></p>
				<p class="jap-scope-notice">
					Right now this is for Computer Science / Informatik students in the Leipzig/Halle
					or Berlin area only. More fields of study and cities may follow later.
				</p>
				<p class="jap-cta-row">
					<?php if ( $logged_in ) : ?>
						<a class="jap-cta" href="<?php echo esc_url( $dashboard ); ?>">Open my dashboard</a>
					<?php else : ?>
						<a class="jap-cta" href="<?php echo esc_url( $register ); ?>">Start free, no payment ever</a>
						<a class="jap-cta jap-cta--ghost" href="<?php echo esc_url( $login ); ?>">Sign in</a>
					<?php endif; ?>
				</p>
			</div>

			<div class="jap-stats">
				<div><strong>&euro;0</strong><span>now and later</span></div>
				<div><strong>Every 2h</strong><span>we check for you</span></div>
				<div><strong>2 files</strong><span>ready per role</span></div>
				<div><strong>2 sources</strong><span>Adzuna &amp; Federal Agency</span></div>
			</div>
		</section>

		<section class="jap-band">
			<h2 class="jap-h2">This is what lands in your inbox</h2>
			<figure class="jap-shot">
				<img src="<?php echo esc_url( $img . 'dashboard.jpg' ); ?>"
					alt="The dashboard showing two matched roles with scores and ready documents" loading="lazy">
				<figcaption><?php echo esc_html( jap_text( 'shot_text' ) ); ?></figcaption>
			</figure>
		</section>

		<section class="jap-band">
			<h2 class="jap-h2">How it works</h2>
			<ol class="jap-step-list">
				<li>
					<span class="jap-step-num">1</span>
					<div>
						<h3>Ten minutes, once</h3>
						<p>Tell us your field and your city, upload your CV as a PDF. That is the only work you will ever do here.</p>
					</div>
				</li>
				<li>
					<span class="jap-step-num">2</span>
					<div>
						<h3>We keep watch</h3>
						<p>While you are in a lecture, at work, or asleep, new listings get checked and scored: does this fit your field, and is it really open to students?</p>
					</div>
				</li>
				<li>
					<span class="jap-step-num">3</span>
					<div>
						<h3>Everything is ready when you are</h3>
						<p>Each match arrives with a CV and cover letter written for that exact role, in German, ready to send. Open your email, check it over, hit send.</p>
					</div>
				</li>
			</ol>
		</section>

		<section class="jap-band">
			<h2 class="jap-h2">Where the jobs come from</h2>
			<div class="jap-sources">
				<div class="jap-source">
					<h3>Bundesagentur f&uuml;r Arbeit</h3>
					<p>
						The German Federal Employment Agency, the largest official job board in the country.
						We pull the full posting text, which is what makes the scoring and the cover letter accurate.
					</p>
				</div>
				<div class="jap-source">
					<h3>Adzuna</h3>
					<p>
						An aggregator that collects listings from company career pages and other boards,
						giving us reach beyond the official register.
					</p>
				</div>
			</div>
			<p class="jap-sources-note">
				No scraping, no grey areas, both are official interfaces meant to be used this way.
				More sources are planned.
			</p>
		</section>

		<section class="jap-band">
			<h2 class="jap-h2">Why it helps</h2>
			<div class="jap-why-grid">
				<div class="jap-why-item">
					<h3>Be early, not lucky</h3>
					<p>For student roles the first few applications often decide it. You hear about a listing the day it appears, not whenever you next remember to look.</p>
				</div>
				<div class="jap-why-item">
					<h3>No more pointless rejections</h3>
					<p>Roles demanding a finished degree or five years of experience never reach you. We read the fine print so you do not have to.</p>
				</div>
				<div class="jap-why-item">
					<h3>Written for that one job</h3>
					<p>Not the same letter sent twenty times with the company name swapped. Each one refers to the actual posting.</p>
				</div>
				<div class="jap-why-item">
					<h3>You stop losing track</h3>
					<p>Mark what you applied to, hide what does not fit. A month in, you still know exactly where you stand.</p>
				</div>
			</div>
		</section>

		<section class="jap-band">
			<div class="jap-note">
				<h2 class="jap-h2">Why this is free</h2>
				<p><?php echo esc_html( jap_text( 'frei_text' ) ); ?></p>
				<p>
					It works because the search runs on <em>your</em> own free Groq account, not on a
					server I have to pay for full price. You create a free account at Groq once, paste the
					key into your settings, and that is it. Roughly two minutes, then never again. The web
					search itself runs on a shared account I manage, so that part needs nothing from you.
				</p>
			</div>
		</section>

		<section class="jap-band">
			<h2 class="jap-h2">Questions people ask</h2>
			<div class="jap-faq">
				<details>
					<summary>What happens to my CV?</summary>
					<p>
						It stays on this site. We read it once to pull out your details, name, studies,
						experience, skills, so they can be placed into every new document.
						It is never sent to employers or shared with anyone.
					</p>
				</details>
				<details>
					<summary>Are the applications sent automatically?</summary>
					<p>
						No. Nothing leaves your name without you. You read every document first, then press send
						yourself from your own email address. We only prepare the work.
					</p>
				</details>
				<details>
					<summary>How many roles will I get?</summary>
					<p>
						Fewer than you expect, and that is deliberate. Only roles that actually fit your field
						and are genuinely open to students make it through. Some days that is none.
						A flood of bad matches would waste more of your time than it saves.
					</p>
				</details>
				<details>
					<summary>Are the documents in German?</summary>
					<p>
						Yes. The site is in English, but your CV and cover letter are written in German,
						following the DIN 5008 layout German employers expect.
					</p>
				</details>
				<details>
					<summary>Why do I need my own API keys?</summary>
					<p>
						Because that is what keeps this free. The job boards charge per search ,
						on your own free account those searches cost nothing. Setting them up takes
						about ten minutes and you never touch them again.
					</p>
				</details>
				<details>
					<summary>Can I stop it?</summary>
					<p>
						Any time. Remove your keys in settings and the search stops immediately.
						No notice period, nothing to cancel.
					</p>
				</details>
			</div>
		</section>

		<?php if ( ! $logged_in ) : ?>
		<section class="jap-final">
			<h2>From one student to another, good luck out there.</h2>
			<p>Two minutes to sign up. Nothing to pay, now or ever.</p>
			<p class="jap-cta-row">
				<a class="jap-cta" href="<?php echo esc_url( $register ); ?>">Create my free account</a>
			</p>
		</section>
		<?php endif; ?>

	</div>
	<?php
	return ob_get_clean();
} );
