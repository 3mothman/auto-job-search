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
```

## Autor

Mohammed Othman
