<?php
/**
 * Dokumentklasse fuer die PDF-Erzeugung.
 * Wird ausschliesslich ueber jap_pdf_bereitstellen() geladen.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'FPDF' ) ) { return; }

class JAP_Document extends FPDF {

	public $ink    = array( 18, 32, 58 );
	public $grau   = array( 90, 104, 128 );
	public $linie  = array( 200, 206, 216 );

	/** Abschnittsueberschrift mit Linie darunter. */
	public function heading( $text ) {
		if ( $this->GetY() > 250 ) { $this->AddPage(); }
		$this->Ln( 3.5 );
		$this->SetFont( 'Helvetica', 'B', 10 );
		$this->SetTextColor( $this->ink[0], $this->ink[1], $this->ink[2] );
		$gross = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $text, 'UTF-8' ) : strtoupper( $text );
		$this->Cell( 0, 6, jap_pdf_text( $gross ), 0, 1, 'L' );
		$this->SetDrawColor( $this->linie[0], $this->linie[1], $this->linie[2] );
		$this->SetLineWidth( 0.35 );
		$y = $this->GetY();
		$this->Line( $this->lMargin, $y, $this->w - $this->rMargin, $y );
		$this->Ln( 2.5 );
		$this->SetTextColor( 34, 34, 34 );
	}

	/**
	 * Eine Kopfzeile eines Eintrags: links Titel (fett), rechts Zeitraum.
	 * Das rechtsbuendige Datum ist das, was einen Lebenslauf geordnet wirken laesst.
	 */
	public function eintrag_kopf( $titel, $zeitraum ) {
		// Kopfzeile und die zwei folgenden Zeilen sollen zusammenbleiben.
		if ( $this->GetY() + 16 > $this->PageBreakTrigger ) {
			$this->AddPage();
		}

		$breite = $this->w - $this->lMargin - $this->rMargin;
		$rechts = 45;

		$this->SetFont( 'Helvetica', 'B', 10.5 );
		$this->SetTextColor( $this->ink[0], $this->ink[1], $this->ink[2] );
		$this->Cell( $breite - $rechts, 5.5, jap_pdf_text( $titel ), 0, 0, 'L' );

		$this->SetFont( 'Helvetica', 'I', 9.5 );
		$this->SetTextColor( $this->grau[0], $this->grau[1], $this->grau[2] );
		$this->Cell( $rechts, 5.5, jap_pdf_text( $zeitraum ), 0, 1, 'R' );
	}

	/** Zweite Zeile: Firma, Ort. */
	public function eintrag_zeile( $text ) {
		if ( '' === trim( (string) $text ) ) { return; }
		$this->SetFont( 'Helvetica', '', 10 );
		$this->SetTextColor( 60, 70, 90 );
		$this->MultiCell( 0, 5, jap_pdf_text( $text ), 0, 'L' );
	}

	/** Aufzaehlungspunkte einer Station. */
	public function punkte( $liste ) {
		if ( ! is_array( $liste ) || ! $liste ) { return; }
		$this->SetFont( 'Helvetica', '', 9.8 );
		$this->SetTextColor( 45, 55, 75 );
		foreach ( $liste as $punkt ) {
			$punkt = trim( (string) $punkt );
			if ( '' === $punkt ) { continue; }
			$x = $this->GetX();
			$this->Cell( 4, 4.8, jap_pdf_text( '-' ), 0, 0, 'L' );
			$this->SetX( $x + 4 );
			$this->MultiCell( 0, 4.8, jap_pdf_text( $punkt ), 0, 'L' );
		}
	}

	/**
	 * Kenntnisse: Kategorie links fett, Eintraege rechts.
	 *
	 * Wichtig: Die Schreibposition wird NICHT von Hand zurueckgesetzt.
	 * Faellt naemlich ein Seitenumbruch dazwischen, landet die Kategorie
	 * sonst auf der einen und ihre Eintraege auf der naechsten Seite.
	 */
	public function kenntnis_zeile( $kategorie, $eintraege ) {
		$links   = 48;   // breit genug fuer lange Begriffe wie "Testing & Datenbanken"
		$abstand = 3;

		// Passt die Zeile noch auf diese Seite? Sonst vorher umbrechen.
		$breite_rechts = $this->w - $this->rMargin - ( $this->lMargin + $links );
		$zeilen        = max( 1, ceil( $this->GetStringWidth( jap_pdf_text( $eintraege ) ) / max( 1, $breite_rechts ) ) );
		$hoehe         = $zeilen * 5;

		if ( $this->GetY() + $hoehe > $this->PageBreakTrigger ) {
			$this->AddPage();
		}

		$this->SetFont( 'Helvetica', 'B', 9.5 );
		$this->SetTextColor( $this->ink[0], $this->ink[1], $this->ink[2] );
		$this->Cell( $links - $abstand, 5, jap_pdf_text( $kategorie ), 0, 0, 'L' );

		// Nach Cell mit ln=0 steht die Position bereits richtig - einfach weiterschreiben.
		$this->SetX( $this->lMargin + $links );
		$this->SetFont( 'Helvetica', '', 9.5 );
		$this->SetTextColor( 45, 55, 75 );
		$this->MultiCell( $breite_rechts, 5, jap_pdf_text( $eintraege ), 0, 'L' );
	}

	/** Freier Absatz. */
	public function absatz( $text, $size = 10 ) {
		if ( '' === trim( (string) $text ) ) { return; }
		$this->SetFont( 'Helvetica', '', $size );
		$this->SetTextColor( 45, 55, 75 );
		$this->MultiCell( 0, 5, jap_pdf_text( $text ), 0, 'L' );
	}

	public function Footer() {
		$this->SetY( -12 );
		$this->SetFont( 'Helvetica', '', 7.5 );
		$this->SetTextColor( 150, 158, 172 );
		$this->Cell( 0, 6, jap_pdf_text( 'Seite ' . $this->PageNo() . ' / {nb}' ), 0, 0, 'C' );
	}
}
