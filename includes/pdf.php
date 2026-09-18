<?php
/**
 * Erzeugt Lebenslauf und Anschreiben als PDF direkt auf dem eigenen Server.
 * Kein Google-Konto, kein Drive, keine fremden Dienste.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* FPDF arbeitet mit Latin-1. Deutsche Umlaute muessen umgewandelt werden. */
function jap_pdf_text( $text ) {
	$text = (string) $text;
	$text = str_replace(
		array( '—', '–', '„', '“', '”', '‚', '‘', '’', '·', '•', "\xc2\xa0" ),
		array( '-', '-', '"', '"', '"', "'", "'", "'", '-', '-', ' ' ),
		$text
	);
	if ( function_exists( 'iconv' ) ) {
		$out = @iconv( 'UTF-8', 'ISO-8859-1//TRANSLIT', $text );
		if ( false !== $out ) { return $out; }
	}
	return utf8_decode( $text );
}

/**
 * FPDF und die Dokumentklasse erst laden, wenn wirklich ein PDF gebaut wird.
 * Wichtig: Die Klasse darf NICHT beim Start des Plugins definiert werden,
 * sonst sucht PHP nach FPDF, bevor es geladen ist - und die Seite bricht ab.
 */
function jap_pdf_bereitstellen() {
	if ( ! class_exists( 'FPDF' ) ) {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'lib/fpdf/fpdf.php';
	}
	if ( ! class_exists( 'JAP_Document' ) ) {
		require_once plugin_dir_path( __FILE__ ) . 'pdf-document.php';
	}
}

/**
 * Lebenslauf erzeugen - einspaltig, klassisch deutsch.
 *
 * Erwartet strukturierte Daten:
 *   name, titel, geburtsdatum, adresse, kontakt, ort
 *   profil        (string)
 *   erfahrung[]   { position, firma, ort, zeitraum, punkte[] }
 *   bildung[]     { abschluss, institution, ort, zeitraum, zusatz }
 *   kenntnisse[]  { kategorie, eintraege }
 *   sprachen[]    { sprache, niveau }
 */
function jap_build_cv_pdf( $d, $pfad ) {
	jap_pdf_bereitstellen();

	$pdf = new JAP_Document( 'P', 'mm', 'A4' );
	$pdf->AliasNbPages();
	$pdf->SetMargins( 22, 18, 22 );
	$pdf->SetAutoPageBreak( true, 18 );
	$pdf->SetTitle( 'Lebenslauf' );
	$pdf->AddPage();

	/* ---------- Kopf ---------- */
	$pdf->SetFont( 'Helvetica', 'B', 21 );
	$pdf->SetTextColor( 18, 32, 58 );
	$pdf->Cell( 0, 9, jap_pdf_text( $d['name'] ), 0, 1, 'L' );

	if ( ! empty( $d['titel'] ) ) {
		$pdf->SetFont( 'Helvetica', '', 11.5 );
		$pdf->SetTextColor( 90, 104, 128 );
		$pdf->Cell( 0, 6, jap_pdf_text( $d['titel'] ), 0, 1, 'L' );
	}

	$pdf->Ln( 1 );
	$pdf->SetDrawColor( 18, 32, 58 );
	$pdf->SetLineWidth( 0.8 );
	$y = $pdf->GetY();
	$pdf->Line( 22, $y, 60, $y );
	$pdf->Ln( 4 );

	// Kontaktzeile
	$kontakt = array();
	if ( ! empty( $d['adresse'] ) ) { $kontakt[] = $d['adresse']; }
	if ( ! empty( $d['kontakt'] ) ) { $kontakt[] = $d['kontakt']; }
	if ( $kontakt ) {
		$pdf->SetFont( 'Helvetica', '', 9.5 );
		$pdf->SetTextColor( 90, 104, 128 );
		$pdf->MultiCell( 0, 4.6, jap_pdf_text( implode( '  |  ', $kontakt ) ), 0, 'L' );
	}
	if ( ! empty( $d['geburtsdatum'] ) ) {
		$pdf->SetFont( 'Helvetica', '', 9.5 );
		$pdf->SetTextColor( 90, 104, 128 );
		$pdf->Cell( 0, 4.6, jap_pdf_text( 'Geburtsdatum: ' . $d['geburtsdatum'] ), 0, 1, 'L' );
	}

	/* ---------- Kurzprofil ---------- */
	if ( ! empty( $d['profil'] ) ) {
		$pdf->heading( 'Kurzprofil' );
		$pdf->absatz( $d['profil'] );
	}

	/* ---------- Berufserfahrung ---------- */
	if ( ! empty( $d['erfahrung'] ) && is_array( $d['erfahrung'] ) ) {
		$pdf->heading( 'Berufserfahrung' );
		foreach ( $d['erfahrung'] as $e ) {
			if ( empty( $e['position'] ) ) { continue; }
			$pdf->eintrag_kopf( $e['position'], $e['zeitraum'] ?? '' );

			$zweite = array();
			if ( ! empty( $e['firma'] ) ) { $zweite[] = $e['firma']; }
			if ( ! empty( $e['ort'] ) )   { $zweite[] = $e['ort']; }
			$pdf->eintrag_zeile( implode( ', ', $zweite ) );

			if ( ! empty( $e['punkte'] ) ) {
				$pdf->punkte( $e['punkte'] );
			}
			$pdf->Ln( 2.2 );
		}
	}

	/* ---------- Projekte ----------
	 * Bei Studierenden oft der aussagekraeftigste Teil: hier stehen die
	 * Technologien, die im Berufsalltag noch fehlen. */
	if ( ! empty( $d['projekte'] ) && is_array( $d['projekte'] ) ) {
		$pdf->heading( 'Projekte' );
		foreach ( $d['projekte'] as $pr ) {
			if ( empty( $pr['name'] ) ) { continue; }
			$pdf->eintrag_kopf( $pr['name'], '' );
			if ( ! empty( $pr['zusatz'] ) ) {
				$pdf->eintrag_zeile( $pr['zusatz'] );
			}
			if ( ! empty( $pr['punkte'] ) ) {
				$pdf->punkte( $pr['punkte'] );
			}
			$pdf->Ln( 2.2 );
		}
	}

	/* ---------- Bildungsweg ---------- */
	if ( ! empty( $d['bildung'] ) && is_array( $d['bildung'] ) ) {
		$pdf->heading( 'Bildungsweg' );
		foreach ( $d['bildung'] as $b ) {
			if ( empty( $b['abschluss'] ) ) { continue; }
			$pdf->eintrag_kopf( $b['abschluss'], $b['zeitraum'] ?? '' );

			$zweite = array();
			if ( ! empty( $b['institution'] ) ) { $zweite[] = $b['institution']; }
			if ( ! empty( $b['ort'] ) )         { $zweite[] = $b['ort']; }
			$pdf->eintrag_zeile( implode( ', ', $zweite ) );

			if ( ! empty( $b['zusatz'] ) ) {
				$pdf->SetFont( 'Helvetica', '', 9.5 );
				$pdf->SetTextColor( 90, 104, 128 );
				$pdf->MultiCell( 0, 4.6, jap_pdf_text( $b['zusatz'] ), 0, 'L' );
			}
			$pdf->Ln( 2.2 );
		}
	}

	/* ---------- Kenntnisse ---------- */
	if ( ! empty( $d['kenntnisse'] ) && is_array( $d['kenntnisse'] ) ) {
		$pdf->heading( 'Kenntnisse' );
		foreach ( $d['kenntnisse'] as $k ) {
			if ( empty( $k['kategorie'] ) || empty( $k['eintraege'] ) ) { continue; }
			$eintraege = is_array( $k['eintraege'] ) ? implode( ', ', $k['eintraege'] ) : $k['eintraege'];
			$pdf->kenntnis_zeile( $k['kategorie'], $eintraege );
			$pdf->Ln( 0.8 );
		}
	}

	/* ---------- Sprachen ---------- */
	if ( ! empty( $d['sprachen'] ) && is_array( $d['sprachen'] ) ) {
		$pdf->heading( 'Sprachen' );
		foreach ( $d['sprachen'] as $sp ) {
			if ( empty( $sp['sprache'] ) ) { continue; }
			$pdf->kenntnis_zeile( $sp['sprache'], $sp['niveau'] ?? '' );
			$pdf->Ln( 0.8 );
		}
	}

	/* ---------- Ort und Datum ---------- */
	$pdf->Ln( 7 );
	$pdf->SetFont( 'Helvetica', '', 9.5 );
	$pdf->SetTextColor( 90, 104, 128 );
	$ort = ! empty( $d['ort'] ) ? $d['ort'] . ', ' : '';
	$pdf->Cell( 0, 5, jap_pdf_text( $ort . date_i18n( 'd.m.Y' ) ), 0, 1, 'L' );

	$pdf->Output( 'F', $pfad );
	return $pfad;
}

/**
 * Anschreiben erzeugen (Aufbau nach DIN 5008).
 */
function jap_build_cover_pdf( $d, $pfad ) {
	jap_pdf_bereitstellen();
	$pdf = new JAP_Document( 'P', 'mm', 'A4' );
	$pdf->SetMargins( 25, 20, 20 );
	$pdf->SetAutoPageBreak( true, 20 );
	$pdf->SetTitle( 'Anschreiben' );
	$pdf->AddPage();

	// Absender
	$pdf->SetFont( 'Helvetica', 'B', 10 );
	$pdf->SetTextColor( 18, 32, 58 );
	$pdf->Cell( 0, 5, jap_pdf_text( $d['name'] ), 0, 1 );
	$pdf->SetFont( 'Helvetica', '', 9.5 );
	$pdf->SetTextColor( 34, 34, 34 );
	if ( ! empty( $d['adresse'] ) ) { $pdf->Cell( 0, 5, jap_pdf_text( $d['adresse'] ), 0, 1 ); }
	if ( ! empty( $d['kontakt'] ) ) { $pdf->Cell( 0, 5, jap_pdf_text( $d['kontakt'] ), 0, 1 ); }

	$pdf->Ln( 12 );

	// Empfaenger
	$pdf->SetFont( 'Helvetica', '', 10 );
	if ( ! empty( $d['firma_name'] ) )      { $pdf->Cell( 0, 5, jap_pdf_text( $d['firma_name'] ), 0, 1 ); }
	if ( ! empty( $d['firma_anschrift'] ) ) { $pdf->Cell( 0, 5, jap_pdf_text( $d['firma_anschrift'] ), 0, 1 ); }
	if ( ! empty( $d['firma_ort'] ) )       { $pdf->Cell( 0, 5, jap_pdf_text( $d['firma_ort'] ), 0, 1 ); }

	$pdf->Ln( 10 );

	// Datum rechts
	$pdf->SetFont( 'Helvetica', '', 10 );
	$ort = ! empty( $d['ort'] ) ? $d['ort'] . ', ' : '';
	$pdf->Cell( 0, 5, jap_pdf_text( $ort . date_i18n( 'd.m.Y' ) ), 0, 1, 'R' );

	$pdf->Ln( 8 );

	// Betreff
	$pdf->SetFont( 'Helvetica', 'B', 11 );
	$pdf->SetTextColor( 18, 32, 58 );
	$pdf->MultiCell( 0, 6, jap_pdf_text( $d['betreff'] ), 0, 'L' );
	$pdf->SetTextColor( 34, 34, 34 );

	$pdf->Ln( 6 );

	// Anrede
	$pdf->SetFont( 'Helvetica', '', 10.5 );
	$pdf->MultiCell( 0, 6, jap_pdf_text( $d['anrede'] ), 0, 'L' );
	$pdf->Ln( 3 );

	// Brieftext
	$absaetze = preg_split( "/\n\s*\n/", (string) $d['brieftext'] );
	foreach ( $absaetze as $absatz ) {
		$absatz = trim( preg_replace( "/\s*\n\s*/", ' ', $absatz ) );
		if ( '' === $absatz ) { continue; }
		$pdf->MultiCell( 0, 6, jap_pdf_text( $absatz ), 0, 'J' );
		$pdf->Ln( 3 );
	}

	$pdf->Ln( 6 );
	$pdf->Cell( 0, 6, jap_pdf_text( 'Mit freundlichen Grüßen' ), 0, 1 );
	$pdf->Ln( 12 );
	$pdf->Cell( 0, 6, jap_pdf_text( $d['name'] ), 0, 1 );

	$pdf->Output( 'F', $pfad );
	return $pfad;
}

/**
 * Zielordner fuer die Dokumente eines Kunden.
 * Liegt im Uploads-Verzeichnis unter einem schwer erratbaren Namen.
 */
function jap_docs_dir( $user_id ) {
	$up   = wp_upload_dir();
	$hash = substr( md5( 'jap-' . $user_id . '-' . wp_salt() ), 0, 16 );
	$dir  = trailingslashit( $up['basedir'] ) . 'jap-dokumente/' . $hash;
	$url  = trailingslashit( $up['baseurl'] ) . 'jap-dokumente/' . $hash;

	if ( ! file_exists( $dir ) ) {
		wp_mkdir_p( $dir );
		// Verzeichnisauflistung unterbinden.
		@file_put_contents( trailingslashit( $dir ) . 'index.html', '' );
	}
	return array( 'dir' => $dir, 'url' => $url );
}
