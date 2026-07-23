# SalesCraft Scorecard — cPanel Quickstart

Host the SalesCraft Scorecard on any cPanel server in a few minutes. No terminal,
no editing config files — a web installer does it for you.

## What you need
- A cPanel hosting account with **PHP 7.4+** and **MySQL**.
- The **`salescraft-quickstart.zip`** package.

## Install (5 steps)

1. **Create a database + user**
   cPanel → **MySQL Databases** → create a database, create a user (set a
   password), and **add the user to the database** with *All Privileges*.
   Note the **database name**, **user**, and **password**.

2. **Upload the files**
   cPanel → **File Manager** → go to where you want the app (e.g. your domain's
   `public_html`, or a subfolder like `public_html/salescraft`). Upload
   **`salescraft-quickstart.zip`** and **Extract** it there.

3. **Run the installer**
   Visit **`https://your-domain/…/install.php`** in a browser. Fill in the
   database details from step 1 and choose an **admin password**. Click
   **Install now** — it creates the tables and writes the config automatically.

4. **Delete the installer**
   Back in File Manager, **delete `install.php`** (important for security).

5. **Finish setup**
   Log in at **`/admin/`** with your admin password, then open **Settings** to
   add your **SMTP** details (so result emails send), notification email, brand,
   and CAPTCHA choice. Use **Send test email** to confirm mail works.

That's it — share your scorecard link (the base URL) with clients.

## Folder layout (already arranged for you)
Everything runs from one web root:
```
index.php        the client scorecard
install.php       the installer (delete after install)
admin/            the admin dashboard (password-protected)
includes/         config, mailer, PDF, PHPMailer, FPDF (blocked from web access)
assets/           theme + favicon
```

## Notes
- `includes/config.php` is created by the installer and holds your DB password —
  it is protected from direct web access by `includes/.htaccess`.
- To move the app, just update **Base URL** in Admin → Settings.
- To reinstall from scratch, delete `includes/config.php` and re-upload
  `install.php`.
