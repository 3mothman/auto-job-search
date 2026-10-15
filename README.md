# Job Automation Portal

A WordPress plugin that provides customers with a self-service portal for automated job searches. Version 5.0.5.

For the full software architecture (components, data flows, data model, and security), see [ARCHITECTURE.md](ARCHITECTURE.md).

## What it does

Customers register on the WordPress site, upload their CV, and set their preferences (location, work schedule, and industry). The plugin sends this data via webhook to an [n8n](https://n8n.io/) automation, which searches job boards in the background, evaluates matches against the CV (score and explanation), and prepares application documents. Results are sent back to the portal through the WordPress REST API and displayed to the customer in a dashboard.

## Features

- **Registration & login** (`[jap_register]`, `[jap_login]`): Create an account, upload a CV, and set search preferences.
- **Customer settings** (`[jap_einstellungen]`): Manage the CV, preferences, and personal API keys (such as Groq). Keys are encrypted using the WordPress salts and are never stored in plain text in the database.
- **Dashboard** (`[jap_dashboard]`): Shows matching jobs with their scores, explanations, and generated application documents.
- **n8n integration**: Configure a webhook URL for new registrations (on the *Job Automation Portal* admin settings page). The plugin provides REST endpoints under `jap/v1/*` for n8n to retrieve customer lists and send search results back.
- **PDF generation**: CVs and cover letters are generated directly on your own server using the included FPDF library under `lib/fpdf`—without a Google account or external services.
- **Additional pages**: Contact form, imprint and privacy policy templates, About Us and Terms and Conditions shortcodes, and an admin settings page for landing page text.

## Goal

Customers should be able to automate their job search completely: upload a CV and set preferences once, then let the background process search, evaluate jobs, and prepare documents without further input. Results are collected in their dashboard.

## Project structure

```
job-automation-portal.php   Main plugin file: post type, shortcodes, REST endpoints, n8n webhook
includes/
  startseite.php            Landing page shortcode
  einstellungen.php         Customer settings: CV, preferences, API keys
  kataloge.php               Fixed selection lists (fields of study, cities)
  kontakt.php                Contact form
  rechtstexte.php            Privacy policy and imprint shortcodes
  seiten.php                  About Us and Terms and Conditions shortcodes
  admin.php                   Admin settings page for landing page text
  verschluesselung.php        Encryption of customer API keys
  pdf.php, pdf-document.php   PDF generation for CVs and cover letters
lib/fpdf/                    FPDF library for PDF generation
assets/                       CSS and images
n8n/apify.json                n8n workflow: the actual search, evaluation, and document generation
```

## n8n workflow (`n8n/apify.json`)

This is the background automation that communicates with the plugin through its REST API (`jap/v1/*`) and webhook. Import it into n8n using *Import from File*.

Workflow:

1. **Schedule**: Runs daily at 8:00, 11:00, 14:00, and 18:00.
2. **Load customers**: Retrieves active customers, their preferences, and CV links from `jap/v1/kunden` on the portal (Basic Auth credential `WP Portal Auth`).
3. **Build a job pool**: Searches two sources once for all customers—the Federal Employment Agency job listings API and Adzuna—and combines the results into one pool. This saves API calls compared with searching separately for each customer.
4. **Select jobs for each customer** (`Select Freshest & Best Match`): Uses text statistics only (TF-IDF/Cosine Similarity, no AI and no token costs) to sort jobs by recency and relevance to keywords, location, remote-work preference, and CV content. It automatically excludes jobs with strict requirements (such as “several years of professional experience” or “senior”).
5. **Evaluate matches** (`Groq - Bewertung`, `Groq - Anforderungen`, `Groq - Anschreiben`): For the remaining top matches, an LLM uses each customer's personal Groq API key to score the fit, extract requirements, and draft cover letter text.
6. **Create documents and return results**: Assembles the data and sends it through `Send To WordPress Portal` to `jap/v1/bewerbung`. The plugin creates the application PDFs and displays them in the customer's dashboard.
7. **Handle errors**: If a customer API key is missing or invalid, the workflow reports it to the portal through `jap/v1/schluessel-problem` instead of failing silently.

Before importing, create the `WP Portal Auth` credential in n8n (Basic Auth for the portal) and update the portal URL, Adzuna keys, and other access details for your installation. The export contains credential references (IDs/names), not secrets.

## Author

Mohammed Othman
