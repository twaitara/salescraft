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
public/                    Web root — everything the server needs lives here
  index.html               The scorecard
  submit.php               Saves submission + emails the consultant
  admin/                   Consultant dashboard (password-gated)
  includes/                DB, mailer, config, PHPMailer (blocked via .htaccess)
    config.example.php
    config.php             (you create this; gitignored)
sql/                       Database schema (imported manually, not deployed)
.cpanel.yml                cPanel Git deploy: copies public/ → public_html/
```

## Local setup

1. Copy `public/includes/config.example.php` to `public/includes/config.php` and
   fill in your MySQL and SMTP details.
2. Create the database and import `sql/schema.sql`.
3. Serve `public/` with PHP:
   ```
   php -S 127.0.0.1:8000 -t public
   ```
4. Open http://127.0.0.1:8000

## Deploying via cPanel Git Version Control (Hostinger)

`.cpanel.yml` copies the contents of `public/` into `public_html/` on every
deploy. The whole app runs from the web root; `includes/` is blocked from direct
web access by `public/includes/.htaccess`, and `config.php` only ever `return`s
an array (it prints nothing even if hit directly). PHPMailer is vendored — no
Composer needed on the server.

**One-time setup on the server:**

1. In cPanel → **Git Version Control**, clone/select this repo and **Pull** the
   latest `main` (which now contains `.cpanel.yml`).
2. Create the database (cPanel → MySQL Databases) and import `sql/schema.sql`
   via phpMyAdmin.
3. Create `public_html/includes/config.php` (copy from `config.example.php`) with
   your DB, SMTP, consultant email and admin password. This file lives only on
   the server — it is never in the repo, so deploys won't overwrite it.
4. Back in Git Version Control, click **Deploy HEAD Commit**.

> If deployment reports it can't expand `$HOME`, edit `.cpanel.yml` and replace
> `$HOME` with your absolute home path (e.g. `/home/u123456789`).

## Notes

`public/includes/config.php` holds credentials and is **not** committed — see
`.gitignore`.
