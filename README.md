# Job Automation Portal

WordPress-Plugin, das eine automatisierte Jobsuche als Self-Service-Portal fuer Kunden anbietet. Version 5.0.5.

## Was es macht

Kunden registrieren sich auf der WordPress-Seite, laden ihren Lebenslauf hoch und hinterlegen ihre Praeferenzen (Standort, Arbeitszeitmodell, Branche). Das Plugin uebergibt diese Daten per Webhook an eine [n8n](https://n8n.io/)-Automatisierung, die im Hintergrund Jobboersen durchsucht, Treffer gegen den Lebenslauf bewertet (Score + Begruendung) und Bewerbungsunterlagen erstellt. Die Ergebnisse laufen ueber die WordPress-REST-API zurueck ins Portal und werden dem Kunden in einem Dashboard angezeigt.

## Funktionsumfang

- **Registrierung & Login** (`[jap_register]`, `[jap_login]`): Kontoerstellung inkl. CV-Upload und Suchpraeferenzen.
- **Kundeneinstellungen** (`[jap_einstellungen]`): CV, Praeferenzen und persoenliche API-Keys (z. B. Groq) verwalten. Die Keys werden verschluesselt gespeichert, abgeleitet aus den WordPress-Salts (nie im Klartext in der Datenbank).
- **Dashboard** (`[jap_dashboard]`): zeigt die gefundenen Job-Treffer inklusive Score, Begruendung und generierten Bewerbungsunterlagen.
- **n8n-Anbindung**: Webhook-URL fuer neue Anmeldungen (Admin-Einstellungsseite unter *Job Automation Portal*), REST-Endpunkte unter `jap/v1/*`, ueber die n8n Kundenlisten abruft und Suchergebnisse zurueckschreibt.
- **PDF-Erzeugung**: Lebenslauf und Anschreiben werden direkt auf dem eigenen Server erzeugt (eigene FPDF-Bibliothek unter `lib/fpdf`) - ohne Google-Konto oder externe Dienste.
- **Weitere Seiten**: Kontaktformular, Impressum/Datenschutz (Vorlagen), Ueber-uns- und AGB-Shortcodes, Admin-Einstellungsseite fuer Landingpage-Texte.

## Ziel

Kunden sollen ihre Jobsuche vollstaendig automatisieren koennen: einmal CV hochladen und Praeferenzen setzen, danach laeuft die Suche, Bewertung und Dokumentenerstellung ohne weiteres Zutun im Hintergrund - Ergebnisse landen gesammelt im eigenen Dashboard.

## Projektstruktur

```
job-automation-portal.php   Haupt-Plugin-Datei: Post-Type, Shortcodes, REST-Endpunkte, n8n-Webhook
includes/
  startseite.php            Landingpage-Shortcode
  einstellungen.php         Kundeneinstellungen: CV, Praeferenzen, API-Keys
  kataloge.php               Feste Auswahllisten (Studienbereiche, Staedte)
  kontakt.php                Kontaktformular
  rechtstexte.php            Datenschutz- und Impressum-Shortcodes
  seiten.php                  Ueber-uns- und AGB-Shortcodes
  admin.php                   Admin-Einstellungsseite fuer Landingpage-Texte
  verschluesselung.php        Verschluesselung der Kunden-API-Keys
  pdf.php, pdf-document.php   PDF-Erzeugung fuer Lebenslauf/Anschreiben
lib/fpdf/                    FPDF-Bibliothek zur PDF-Erzeugung
assets/                       CSS und Bilder
n8n/apify.json                n8n-Workflow: die eigentliche Suche, Bewertung und Dokumentenerstellung
```

## n8n-Workflow (`n8n/apify.json`)

Das ist die Automatisierung, die im Hintergrund laeuft und mit dem Plugin ueber dessen REST-API (`jap/v1/*`) und Webhook kommuniziert. Import in n8n über *Import from File*.

Ablauf:

1. **Zeitplan**: laeuft taeglich um 8, 11, 14 und 18 Uhr.
2. **Kunden laden**: holt aktive Kunden inkl. Praeferenzen und CV-Link ueber `jap/v1/kunden` vom Portal (Basic-Auth-Credential `WP Portal Auth`).
3. **Job-Pool bilden**: sucht einmal gemeinsam fuer alle Kunden bei zwei Quellen - der Arbeitsagentur-Jobboerse-API und Adzuna - und fasst die Treffer zu einem gemeinsamen Pool zusammen (spart API-Aufrufe statt pro Kunde einzeln zu suchen).
4. **Auswahl pro Kunde** (`Select Freshest & Best Match`): filtert reine Textstatistik (TF-IDF/Cosine-Similarity, keine KI, kostet keine Tokens) - sortiert nach Aktualitaet und Relevanz zu Keywords, Standort, Remote-Praeferenz und CV-Inhalt; schliesst Stellen mit harten Anforderungen (z. B. "mehrjaehrige Berufserfahrung", "Senior") automatisch aus.
5. **Bewertung** (`Groq - Bewertung`, `Groq - Anforderungen`, `Groq - Anschreiben`): fuer die uebrig gebliebenen Top-Treffer bewertet ein LLM (ueber den persoenlichen Groq-API-Key des Kunden) die Passung (Score + Begruendung), extrahiert Anforderungen und erstellt Anschreiben-Text.
6. **Dokumente & Rueckmeldung**: baut den Datensatz zusammen und schickt ihn per `Send To WordPress Portal` an `jap/v1/bewerbung`, wo das Plugin daraus die PDF-Bewerbungsunterlagen erzeugt und im Kunden-Dashboard anzeigt.
7. **Fehlerfall**: fehlt oder ist ein Kunden-API-Key ungueltig, meldet der Workflow das ueber `jap/v1/schluessel-problem` zurueck ans Portal, statt stumm zu scheitern.

Vor dem Import muessen in n8n die Credentials `WP Portal Auth` (Basic-Auth fuers Portal) neu angelegt und die Zugangsdaten (Portal-URL, Adzuna-Keys etc.) auf die eigene Installation angepasst werden - im Export sind nur Credential-Referenzen (IDs/Namen) enthalten, keine Geheimnisse.

## Autor

Mohammed Othman
