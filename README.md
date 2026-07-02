# SalesCraft — Sales Diagnostic Scorecard

A lightweight tool for sales consultants. A consultant sends a client a link to an
interactive **Sales Diagnostic Scorecard** (10 areas, 40 questions, scored 1–5).
When the client submits, the consultant gets an **email notification** and the
results are stored for review.

## Status

- [x] Interactive scorecard front-end (`public/index.html`) — section-by-section
      scoring, behavioral rubric (what a "5" means), radar chart, priority fixes.
- [ ] PHP submission handler → save to MySQL + email the consultant.
- [ ] Admin view of submissions.
- [ ] Branded PDF report.

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

## Notes

`includes/config.php` holds credentials and is **not** committed — see
`.gitignore`.
