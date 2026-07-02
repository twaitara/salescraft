# SalesCraft — Sales Diagnostic Scorecard

A lightweight tool for sales consultants. A consultant sends a client a link to an
interactive **Sales Diagnostic Scorecard** (10 areas, 40 questions, scored 1–5).
When the client submits, the consultant gets an **email notification** and the
results are stored for review.

## How it works

1. The consultant shares the scorecard link with a client.
2. The client enters their name + work email and fills in all 10 sections.
3. On completion the results are POSTed to `public/submit.php`, which saves them
   to MySQL and **emails the consultant** a summary with a link to the full report.
4. The consultant reviews every submission at `/admin/`.

## Status

- [x] Interactive scorecard front-end (`public/index.html`) — section-by-section
      scoring, behavioral rubric (what a "5" means), radar chart, priority fixes.
- [x] PHP submission handler (`public/submit.php`) → recomputes scores server-side,
      saves to MySQL, emails the consultant via SMTP (PHPMailer).
- [x] Admin dashboard (`admin/`) — password-gated submissions list + detail view
      with radar chart and every answer.
- [ ] Branded PDF report.
- [ ] Per-client invite links / tokens (currently a single shared link).

## Stack

Vanilla **PHP + MySQL** (no framework). Front-end is a self-contained HTML/CSS/JS
file — no build step.

## Structure

```
public/          Web root (point your vhost/docroot here)
  index.html     The scorecard (standalone for now; becomes the PHP form)
  assets/
includes/        DB connection, mailer, shared config (config.php is gitignored)
  config.example.php
admin/           Consultant dashboard (submissions list)
sql/             Database schema
```

## Local setup

1. Copy `includes/config.example.php` to `includes/config.php` and fill in your
   MySQL and SMTP details.
2. Create the database and import `sql/schema.sql`.
3. Serve `public/` with PHP:
   ```
   php -S 127.0.0.1:8000 -t public
   ```
4. Open http://127.0.0.1:8000

## Deploying to shared hosting (e.g. Hostinger)

Keep `includes/` **outside** the web root so config/credentials aren't served:

```
account_root/
├─ includes/          ← upload here (NOT web-accessible)
├─ admin/             ← upload here (or protect separately)
├─ sql/
└─ public_html/       ← contents of public/ go here (this is the web root)
   ├─ index.html
   └─ submit.php
```

`submit.php` and the admin pages reference `../includes/...`, so `includes/`
must sit one level above the web root, as shown. PHPMailer is vendored in
`includes/PHPMailer/` — no Composer needed on the server.

## Notes

`includes/config.php` holds credentials and is **not** committed — see
`.gitignore`.
