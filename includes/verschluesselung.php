<?php
/**
 * Verschluesselung fuer die API-Schluessel der Kunden.
 *
 * Die Schluessel werden nie im Klartext in der Datenbank gespeichert.
 * Der Verschluesselungsschluessel kommt aus WordPress' eigenen Salts
 * (wp-config.php) - die liegen nicht in der Datenbank und sind pro
 * Installation einzigartig.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function jap_verschluesselungsschluessel() {
	$basis = '';
	foreach ( array( 'AUTH_KEY', 'AUTH_SALT', 'SECURE_AUTH_KEY', 'SECURE_AUTH_SALT' ) as $k ) {
		if ( defined( $k ) ) { $basis .= constant( $k ); }
	}
	if ( '' === $basis ) {
		// Absicherung falls die Konstanten fehlen sollten (untypisch, aber moeglich).
		$basis = wp_salt( 'auth' ) . wp_salt( 'secure_auth' );
	}
	return hash( 'sha256', $basis, true );
}

/**
 * Schluessel verschluesseln. Gibt eine Zeichenkette zurueck, die sicher
 * in einem Textfeld der Datenbank gespeichert werden kann.
 */
function jap_verschluesseln( $klartext ) {
	if ( '' === (string) $klartext ) { return ''; }
	if ( ! function_exists( 'openssl_encrypt' ) ) {
		// Ohne OpenSSL koennen wir nicht sicher verschluesseln - lieber
		// sichtbar machen, dass etwas fehlt, als unverschluesselt zu speichern.
		error_log( 'JAP: OpenSSL fehlt, Schluessel kann nicht sicher gespeichert werden.' );
		return '';
	}
	$schluessel = jap_verschluesselungsschluessel();
	$iv         = openssl_random_pseudo_bytes( 16 );
	$verschluesselt = openssl_encrypt( (string) $klartext, 'aes-256-cbc', $schluessel, OPENSSL_RAW_DATA, $iv );
	if ( false === $verschluesselt ) { return ''; }
	// IV wird mit abgespeichert, sie muss nicht geheim sein.
	return 'jap_enc:' . base64_encode( $iv . $verschluesselt );
}

/**
 * Schluessel entschluesseln. Gibt bei Fehlern einen leeren String zurueck,
 * niemals eine Fehlermeldung mit dem (teilweise) entschluesselten Inhalt.
 */
function jap_entschluesseln( $gespeichert ) {
	$gespeichert = (string) $gespeichert;
	if ( '' === $gespeichert ) { return ''; }

	// Altbestand aus der Zeit vor der Verschluesselung: als Klartext behandeln,
	// aber beim naechsten Speichern automatisch verschluesselt (siehe unten).
	if ( 0 !== strpos( $gespeichert, 'jap_enc:' ) ) {
		return $gespeichert;
	}

	if ( ! function_exists( 'openssl_decrypt' ) ) { return ''; }

	$roh = base64_decode( substr( $gespeichert, 8 ) );
	if ( false === $roh || strlen( $roh ) < 17 ) { return ''; }

	$iv             = substr( $roh, 0, 16 );
	$verschluesselt = substr( $roh, 16 );
	$schluessel     = jap_verschluesselungsschluessel();

	$klartext = openssl_decrypt( $verschluesselt, 'aes-256-cbc', $schluessel, OPENSSL_RAW_DATA, $iv );
	return ( false === $klartext ) ? '' : $klartext;
}

/** Kurzform zum Speichern: verschluesselt direkt in die user_meta schreiben. */
function jap_api_key_speichern( $user_id, $feldname, $klartext ) {
	if ( '' === (string) $klartext ) {
		delete_user_meta( $user_id, $feldname );
		return;
	}
	update_user_meta( $user_id, $feldname, jap_verschluesseln( $klartext ) );
}

/** Kurzform zum Lesen: user_meta holen und entschluesseln. */
function jap_api_key_lesen( $user_id, $feldname ) {
	return jap_entschluesseln( get_user_meta( $user_id, $feldname, true ) );
}

/**
 * Einmalige Umstellung: Schluessel, die noch unverschluesselt aus der Zeit
 * vor diesem Update in der Datenbank liegen, automatisch verschluesseln.
 * Laeuft nur einmal (ueber eine Option abgesichert), danach nie wieder.
 */
add_action( 'admin_init', function () {
	if ( get_option( 'jap_keys_verschluesselt_v1' ) ) { return; }

	$felder = array( 'jap_adzuna_id', 'jap_adzuna_key', 'jap_groq_key' );
	$users  = get_users( array( 'fields' => array( 'ID' ) ) );

	foreach ( $users as $u ) {
		foreach ( $felder as $feld ) {
			$wert = get_user_meta( $u->ID, $feld, true );
			if ( '' === (string) $wert || 0 === strpos( $wert, 'jap_enc:' ) ) {
				continue; // leer oder schon verschluesselt
			}
			update_user_meta( $u->ID, $feld, jap_verschluesseln( $wert ) );
		}
	}

	update_option( 'jap_keys_verschluesselt_v1', 1 );
} );
