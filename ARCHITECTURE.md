# Software Architecture

Version 5.0.5 · Author: Mohammed Othman

## Purpose and scope

The project has two main runtime components:

- **WordPress plugin**: Customer portal, profile and CV storage, REST API, match records, and PDF generation.
- **Scheduled n8n workflow** (`n8n/apify.json`): Shared job search, per-customer matching, Groq processing, and delivery of results to WordPress.

The scheduled workflow pulls customer data from WordPress through the REST API and posts matches back through that API. It also downloads CV files from their WordPress URLs. An optional registration webhook can notify a separate n8n workflow, but that separate workflow is not included in this repository export.

## System context

```mermaid
flowchart LR
    Customer["Customer<br/>browser"]
    Portal["WordPress plugin<br/>registration · settings · dashboard"]
    DB["WordPress database<br/>user meta · jap_bewerbung posts"]
    REST["WordPress REST API<br/>jap/v1"]
    Media["WordPress media<br/>uploaded CV files"]
    N8N["Scheduled n8n workflow<br/>n8n/apify.json"]
    BA["Federal Employment Agency API"]
    ADZ["Adzuna API"]
    GROQ["Groq API"]
    Hook["Separate registration<br/>webhook workflow<br/>(optional)"]

    Customer <-->|"Register · preferences · results"| Portal
    Portal <--> REST
    Portal <--> DB
    Portal -->|"Store uploaded CV"| Media
    N8N <-->|"GET /kunden<br/>POST /bewerbung<br/>POST /schluessel-problem"| REST
    N8N <-->|"GET CV by URL"| Media
    N8N <-->|"Search requests · job listings"| BA
    N8N <-->|"Search requests · job listings"| ADZ
    N8N <-->|"CV/profile data · AI responses"| GROQ
    Portal -.->|"Optional signup event"| Hook
```

The portal and the scheduled workflow are independently deployed. A WordPress or n8n outage interrupts communication between them, but the n8n workflow is not required to render the portal. The separate registration webhook is an optional integration path and is not part of the scheduled job-search workflow.

## Runtime interaction

```mermaid
sequenceDiagram
    actor Customer
    participant WP as WordPress plugin
    participant DB as WordPress database
    participant Media as WordPress media
    participant N8N as Scheduled n8n workflow
    participant REST as WordPress REST API
    participant Jobs as Federal Employment Agency + Adzuna
    participant Groq
    participant Hook as Separate n8n webhook workflow

    Customer->>WP: Register, upload CV, save preferences
    WP->>DB: Store account and profile
    WP->>Media: Store uploaded CV

    opt Separate registration webhook is configured
        WP-->>Hook: Send registration event
    end

    loop Four scheduled runs per day (n8n instance timezone)
        N8N->>REST: GET /kunden
        REST-->>N8N: Up to 200 active profiles and CV URLs
        N8N->>Jobs: Search one shared job pool
        Jobs-->>N8N: Job listings

        loop Each customer returned by /kunden
            N8N->>Media: Download CV by URL
            Media-->>N8N: CV file
            N8N->>Groq: Parse up to 7,000 characters of extracted CV text
            Groq-->>N8N: Structured CV fields
            alt CV data is usable and the customer's key is valid
                N8N->>N8N: Rank locally and select up to six matches
                N8N->>Groq: Score matches, summarize requirements, draft cover letter
                Groq-->>N8N: Match details and application draft
                N8N->>REST: POST /bewerbung
                REST->>DB: Check duplicate and store match
            else CV data is unusable or the key is rejected
                N8N->>REST: POST /schluessel-problem
            end
        end
    end

    Customer->>WP: View matches and download generated PDFs
```

The optional webhook participant is separate from the scheduled workflow; configure it only if the site has a corresponding n8n webhook workflow.

## Scheduled automation pipeline

```mermaid
flowchart TD
    S["Schedule trigger<br/>08:00 · 11:00 · 14:00 · 18:00"]
    C["GET /kunden<br/>up to 200 active customers"]
    P["Build shared job pool<br/>Federal Employment Agency + Adzuna"]
    L["For each customer<br/>download CV and extract PDF text"]
    X["Groq CV parsing<br/>up to 7,000 extracted characters"]
    R["Rank locally<br/>TF-IDF · cosine similarity · preferences"]
    F["Filter strict requirements<br/>select up to 6 matches"]
    G["Groq job enrichment<br/>score · requirements · cover-letter draft"]
    W["POST /bewerbung"]
    D["WordPress duplicate check<br/>store match · generate PDFs"]
    V["Customer dashboard"]
    E["Key problem<br/>POST /schluessel-problem"]

    S --> C --> P --> L --> X --> R --> F --> G --> W --> D --> V
    X -. "CV data unusable or Groq key rejected" .-> E
```

The shared pool is searched once for the customer batch rather than once per customer. Groq first structures the extracted CV text. After that step, matching and filtering run locally in n8n; only up to six selected job matches per customer are sent for job-specific Groq evaluation. The workflow prepares documents but does not submit job applications.

## Components

| Component | Responsibility | Implementation |
|---|---|---|
| Portal forms and shortcodes | Registration, login, customer settings, dashboard, contact and legal pages | `includes/startseite.php`, `einstellungen.php`, `kontakt.php`, `rechtstexte.php`, `seiten.php` |
| WordPress storage | User accounts and profile fields in user meta; job matches as `jap_bewerbung` posts | `job-automation-portal.php` |
| REST API | Customer retrieval, match creation, and key-problem reporting for n8n | `jap/v1/kunden`, `jap/v1/bewerbung`, `jap/v1/schluessel-problem` |
| Key encryption | Encrypts each customer's Groq key using a key derived from WordPress salts | `includes/verschluesselung.php` |
| PDF generation | Creates CV and cover-letter PDFs on the WordPress server | `includes/pdf.php`, `includes/pdf-document.php`, `lib/fpdf` |
| Scheduled workflow | Builds a job pool, ranks matches, calls Groq, and writes results | `n8n/apify.json` |
| External services | Job listings and language-model processing | Federal Employment Agency API, Adzuna, and Groq |

## Data model

### Customer profile

Customer fields are stored as WordPress user meta.

| Field | Meaning |
|---|---|
| `jap_cv_url` | URL of the uploaded CV |
| `jap_standort` / `jap_standort_key` | Selected search location or locations |
| `jap_suchweite` | Search radius in kilometres |
| `jap_arbeitszeit` / `jap_arbeitszeit_labels` | Selected work types |
| `jap_bereiche` / `jap_keywords` / `jap_branche` | Selected fields and derived search terms |
| `jap_status` | `aktiv` or `inaktiv`; controls inclusion in a scheduled run |
| `jap_key_problem*` | Key-problem note and notification timestamp |

### Job match

Each match is stored as a custom post of type `jap_bewerbung`, with post meta for:

- Customer ID, company, location, and work model.
- Groq score and explanation.
- Source job URL and discovery date.
- Generated CV and cover-letter PDF links.
- Detected application email, subject, and cover-letter text.

Before creating a match, the plugin checks the customer ID and job URL to avoid storing the same listing repeatedly.

## Security and privacy boundaries

- The three `jap/v1/*` integration endpoints require the WordPress `manage_options` capability. The n8n `WP Portal Auth` credential uses a WordPress administrator application password over Basic Auth; treat it as an administrator credential and keep it private.
- Each customer's Groq API key is encrypted in user meta using WordPress salts. Protect the salts and backups.
- Adzuna App ID and key are shared site-level credentials stored in WordPress options. They are used to build the shared job pool; they are not individual customer keys.
- Groq calls use each customer's personal key. The workflow sends up to 7,000 characters of extracted CV text for CV parsing and sends relevant profile and job details for match evaluation and drafting.
- The WordPress server stores uploaded CVs and generated PDFs. The n8n workflow downloads CVs by URL while processing them.
- Review the site's privacy notice and service settings before using real customer data. The repository's imprint and privacy texts are templates, not legal advice.

## Operations and known limits

- **Schedule**: 08:00, 11:00, 14:00, and 18:00 in the n8n instance timezone. The exported workflow does not set that timezone.
- **Customer batch**: `/kunden` returns up to 200 active customers per request. The workflow does not paginate beyond that limit.
- **Candidate volume**: Local ranking selects up to six matches per customer for Groq processing.
- **Shared job pool**: Job source queries are made for the batch; results are then ranked for each customer.
- **Deployment URL**: The export contains the current WordPress URL in three HTTP Request nodes. Update all three when deploying to another site.
- **Credentials**: Recreate the `WP Portal Auth` Basic Auth credential after importing the workflow. The export contains a credential reference, not the application password.

## Repository map

| Path | Contents |
|---|---|
| `job-automation-portal.php` | Main plugin, custom post type, shortcodes, REST routes, and webhook setting |
| `includes/` | Forms, settings, legal text, encryption, and PDF generation |
| `lib/fpdf/` | Third-party PDF library and license |
| `assets/` | Portal CSS and images |
| `n8n/apify.json` | Exported scheduled n8n workflow |
| `README.md` | Project overview and setup steps |
