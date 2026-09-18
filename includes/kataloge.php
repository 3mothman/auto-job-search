<?php
/**
 * Studienbereiche und Staedte.
 *
 * Werden ueber die Einstellungsseite gepflegt (Job Portal -> Fields & cities).
 * Ist dort nichts hinterlegt, gelten die Standardwerte aus diesem File.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ---------- Standardwerte ---------- */

// Aktueller Start-Fokus: nur die zwei Regionen, die die gemeinsame Suche
// tatsaechlich abdeckt (siehe jap_such_staedte(): Leipzig 50km-Umkreis
// schliesst Halle mit ein). Der volle Katalog steht unten fuer die spaetere
// Erweiterung bereit, ohne Codeverlust.
function jap_staedte_standard() {
	return array(
		'leipzig' => 'Leipzig / Halle (Saale)',
		'berlin'  => 'Berlin',
	);
}

function jap_staedte_standard_alle() {
	return array(
		'halle'       => 'Halle (Saale)',
		'leipzig'     => 'Leipzig',
		'berlin'      => 'Berlin',
		'dresden'     => 'Dresden',
		'magdeburg'   => 'Magdeburg',
		'muenchen'    => 'Muenchen',
		'hamburg'     => 'Hamburg',
		'koeln'       => 'Koeln',
		'frankfurt'   => 'Frankfurt am Main',
		'stuttgart'   => 'Stuttgart',
		'duesseldorf' => 'Duesseldorf',
		'hannover'    => 'Hannover',
		'nuernberg'   => 'Nuernberg',
		'dortmund'    => 'Dortmund',
		'bremen'      => 'Bremen',
		'essen'       => 'Essen',
		'karlsruhe'   => 'Karlsruhe',
	);
}

// Aktueller Start-Fokus: nur IT/Informatik. Sobald das zuverlaessig laeuft,
// kann jap_bereiche_standard() unten wieder auf jap_bereiche_standard_alle()
// umgestellt werden, um alle Studiengaenge zuzulassen - ohne Codeverlust.
function jap_bereiche_standard() {
	return array(
		'informatik' => array(
			'label'    => 'Computer Science & IT',
			'keywords' => array(
				'Softwareentwicklung', 'Webentwicklung', 'IT-Support', 'Data Science',
				'DevOps', 'Datenbank', 'Programmierung', 'IT-Sicherheit',
				'Systemadministration', 'Frontend', 'Backend', 'Full-Stack',
				'Anwendungsentwicklung', 'Software Engineer', 'Cloud', 'IT-Consultant',
				'Testautomatisierung', 'Netzwerktechnik', 'Machine Learning', 'KI',
			),
		),
	);
}

// Der volle Katalog von vorher, fuer die spaetere Erweiterung auf andere
// Studiengaenge. Wird aktuell nirgends aufgerufen.
function jap_bereiche_standard_alle() {
	return array(
		'informatik'        => array( 'label' => 'Computer Science & IT',      'keywords' => array( 'Softwareentwicklung', 'Webentwicklung', 'IT-Support', 'Data Science', 'DevOps', 'Datenbank', 'Programmierung', 'IT-Sicherheit', 'Systemadministration', 'Frontend', 'Backend' ) ),
		'wirtschaft'        => array( 'label' => 'Business & Economics',       'keywords' => array( 'Betriebswirtschaft', 'Controlling', 'Buchhaltung', 'Finanzen', 'Business Analyst', 'Einkauf', 'Personal', 'Unternehmensberatung' ) ),
		'ingenieur'         => array( 'label' => 'Engineering & Technology',   'keywords' => array( 'Maschinenbau', 'Elektrotechnik', 'Automatisierung', 'Konstruktion', 'Qualitaetssicherung', 'Produktion', 'Mechatronik' ) ),
		'naturwissenschaft' => array( 'label' => 'Natural Sciences',           'keywords' => array( 'Labor', 'Chemie', 'Biologie', 'Physik', 'Forschung', 'Biotechnologie', 'Pharma' ) ),
		'marketing'         => array( 'label' => 'Marketing & Media',          'keywords' => array( 'Marketing', 'Social Media', 'Content', 'Grafikdesign', 'Online-Marketing', 'Redaktion', 'SEO' ) ),
		'vertrieb'          => array( 'label' => 'Sales & Customer Service',   'keywords' => array( 'Vertrieb', 'Sales', 'Kundenservice', 'Kundenbetreuung', 'Account Management' ) ),
		'recht'             => array( 'label' => 'Law & Administration',       'keywords' => array( 'Jura', 'Legal', 'Compliance', 'Datenschutz', 'Verwaltung', 'Sachbearbeitung' ) ),
		'logistik'          => array( 'label' => 'Logistics & Procurement',    'keywords' => array( 'Logistik', 'Supply Chain', 'Lager', 'Disposition', 'Einkauf' ) ),
		'gesundheit'        => array( 'label' => 'Healthcare & Social Work',   'keywords' => array( 'Pflege', 'Gesundheitswesen', 'Sozialarbeit', 'Betreuung', 'Medizin' ) ),
		'bildung'           => array( 'label' => 'Education & Tutoring',       'keywords' => array( 'Nachhilfe', 'Tutor', 'Lehre', 'Bildung', 'Werkstudent Hochschule' ) ),
	);
}

function jap_stellenarten_standard() {
	return array(
		'werkstudent' => 'Working student',
		'teilzeit'    => 'Part-time',
		'praktikum'   => 'Internship',
		'minijob'     => 'Mini-job / casual work',
		'abschluss'   => 'Thesis project (Bachelor/Master)',
	);
}

/* ---------- Textformat <-> Array ---------- */

/** Eine Zeile je Eintrag:  schluessel | Anzeigename | Suchwort1, Suchwort2 */
function jap_bereiche_aus_text( $text ) {
	$raus = array();
	foreach ( preg_split( "/\r\n|\r|\n/", (string) $text ) as $zeile ) {
		$zeile = trim( $zeile );
		if ( '' === $zeile || 0 === strpos( $zeile, '#' ) ) { continue; }

		$teile = array_map( 'trim', explode( '|', $zeile ) );
		if ( count( $teile ) < 3 ) { continue; }

		$key = sanitize_key( $teile[0] );
		if ( ! $key ) { continue; }

		$woerter = array_values( array_filter( array_map( 'trim', explode( ',', $teile[2] ) ) ) );
		if ( ! $woerter ) { continue; }

		$raus[ $key ] = array( 'label' => $teile[1], 'keywords' => $woerter );
	}
	return $raus;
}

function jap_bereiche_als_text( $bereiche ) {
	$zeilen = array();
	foreach ( $bereiche as $key => $d ) {
		$zeilen[] = $key . ' | ' . $d['label'] . ' | ' . implode( ', ', $d['keywords'] );
	}
	return implode( "\n", $zeilen );
}

/** Eine Zeile je Eintrag:  schluessel | Anzeigename */
function jap_liste_aus_text( $text ) {
	$raus = array();
	foreach ( preg_split( "/\r\n|\r|\n/", (string) $text ) as $zeile ) {
		$zeile = trim( $zeile );
		if ( '' === $zeile || 0 === strpos( $zeile, '#' ) ) { continue; }

		$teile = array_map( 'trim', explode( '|', $zeile ) );
		if ( count( $teile ) < 2 ) { continue; }

		$key = sanitize_key( $teile[0] );
		if ( ! $key ) { continue; }

		$raus[ $key ] = $teile[1];
	}
	return $raus;
}

function jap_liste_als_text( $liste ) {
	$zeilen = array();
	foreach ( $liste as $key => $label ) {
		$zeilen[] = $key . ' | ' . $label;
	}
	return implode( "\n", $zeilen );
}

/* ---------- Was das Plugin tatsaechlich benutzt ---------- */

function jap_staedte() {
	$eigene = jap_liste_aus_text( get_option( 'jap_staedte_text', '' ) );
	return $eigene ? $eigene : jap_staedte_standard();
}

function jap_bereiche() {
	$eigene = jap_bereiche_aus_text( get_option( 'jap_bereiche_text', '' ) );
	return $eigene ? $eigene : jap_bereiche_standard();
}

function jap_stellenarten() {
	$eigene = jap_liste_aus_text( get_option( 'jap_arten_text', '' ) );
	return $eigene ? $eigene : jap_stellenarten_standard();
}


/* Wie weit gesucht werden soll. Loest die alte Vermischung von
 * "Remote" und "Stadt" auf: Ort und Arbeitsform sind zwei Fragen. */
function jap_suchweite_standard() {
	return array(
		'stadt'        => 'In and around my city',
		'stadt_remote' => 'My city plus remote roles',
		'bundesweit'   => 'Anywhere in Germany (on-site or remote)',
		'nur_remote'   => 'Remote only, anywhere',
	);
}

function jap_suchweite() {
	return jap_suchweite_standard();
}

function jap_keywords_aus_bereichen( $bereiche ) {
	$alle    = jap_bereiche();
	$woerter = array();
	foreach ( (array) $bereiche as $key ) {
		if ( isset( $alle[ $key ] ) ) {
			$woerter = array_merge( $woerter, $alle[ $key ]['keywords'] );
		}
	}
	return array_values( array_unique( $woerter ) );
}
