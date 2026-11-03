# DOable Website — Setup Instructions for Developer

**From:** The DOable team  
**To:** Developer handling the AWS deployment  
**Date:** August 2026  

---

Hi — thanks for taking this on. Everything you need is in this folder. The site is a marketing website for DOable (business management software for dance studios, martial arts schools, yoga studios, gyms, and personal trainers). It includes a public website and a password-protected admin editor.

Here is what you need to do, step by step.

---

## Step 1 — Create a MySQL database

On your AWS setup (RDS, Lightsail, or the server's own MySQL/MariaDB), create an empty database. Name it whatever you like — `doable` works fine. Create a database user with full access to it.

## Step 2 — Import the database file

Import **`database.sql`** into that database. It creates all five tables and loads the starter homepage content plus two sample blog posts. From a terminal:

```
mysql -h YOUR_HOST -u YOUR_USER -p YOUR_DB_NAME < database.sql
```

Or use phpMyAdmin / your preferred DB tool.

## Step 3 — Edit the one settings file

Open **`config.php`** — this is the only file you need to edit. It has four clearly labeled sections:

1. **DATABASE** — Fill in host, database name, username, and password from Step 1.  
2. **SITE** — Set `SITE_URL` to the live domain (e.g. `https://doable.net`). `LEAD_NOTIFICATION_EMAIL` is where form submissions get emailed (currently set to `demo@doable.net`). Leave `ENROLL_URL` empty for now (it makes the Enroll button scroll to the contact form on the homepage).  
3. **EMAIL (SMTP)** — For reliable email delivery, plug in AWS SES SMTP credentials: host (e.g. `email-smtp.us-east-1.amazonaws.com`), port `587`, security `tls`, and the SES SMTP username/password. Make sure the `MAIL_FROM` address is verified in SES. If you leave `SMTP_HOST` blank, it falls back to PHP's built-in `mail()`.  
4. **OWNER ADMIN ACCOUNT** — Set `ADMIN_EMAIL` and `ADMIN_PASSWORD` to whatever the owner wants to log in with. **The first time** someone visits `/admin/login.php` and enters these credentials, an owner account is automatically created. Use a strong password and change it before the site goes live.

## Step 4 — Upload and point the web server

Upload this entire folder to the server. Set your Apache or Nginx document root to point at this folder so `index.php` serves as the homepage.

**Requirements:** PHP 8.0+ with PDO and pdo_mysql (standard on virtually every host). No Composer, no frameworks, no build step.

## Step 5 — Verify it works

Visit the public homepage. Then visit `/admin/login.php`, log in with the email and password from Step 4, and confirm you can see the dashboard.

---

## Quick reference — what's in the folder

| File / Folder | What it is |
|---|---|
| **`config.php`** | **THE settings file** — database, email, site URL, admin login. Only file you edit. |
| `database.sql` | Database tables + starter content. Import once. |
| `index.php` | Public homepage (hero, features, industries, pricing, testimonials, contact form). |
| `privacy.php` / `terms.php` | Legal pages. |
| `blog.php` / `blog-post.php` | Public blog. |
| `contact-submit.php` | Handles the contact/free-trial form → saves to DB + sends email. |
| `admin/` | Password-protected admin area: dashboard, page content editor, blog manager, leads viewer. |
| `includes/` | Shared PHP code (DB connection, helpers, header/footer). Don't need to edit. |
| `assets/` | CSS, JS, logo images. |
| `README.md` | Detailed version of these instructions. |

## Security notes

- Change the admin password in `config.php` before going live.
- Keep `config.php` out of any public code repository (it holds credentials).
- Serve the site over HTTPS.
- Passwords are stored hashed. All forms use CSRF protection.

## Styling note

The pages currently load Tailwind CSS from a CDN for convenience. For production performance, you can optionally compile Tailwind into a static CSS file and replace the CDN script tag in `includes/header.php`. This is optional — it works fine either way.

---

That's it. Five steps and you're live. The `README.md` has more detail if you need it. Questions can go to the DOable team.
