# DOable Website — PHP Package

This folder is a complete, self-contained PHP version of the DOable website **and**
its admin editor. It is meant to be uploaded to a standard PHP + MySQL web host
(for example an AWS EC2 server running Apache/PHP, or AWS Lightsail, with an RDS
MySQL database).

There is **no framework and no build step required** — it is plain PHP. Everything
you need to change lives in **one clearly-labeled file: `config.php`**.

---

## What's inside

| Path | What it is |
|------|------------|
| **`config.php`** | **THE settings file.** Database, email, site URL, and the owner admin account. This is the only file you normally edit. |
| `database.sql` | The database structure + starter content and 2 sample blog posts. Import this once. |
| `index.php` | The public homepage (hero, features, industries, pricing, testimonials, contact/free-trial form). |
| `privacy.php`, `terms.php` | Privacy Policy and Terms of Use pages. |
| `blog.php`, `blog-post.php` | Public blog list and single-article pages. |
| `contact-submit.php` | Receives the contact / free-trial form, saves it, and emails you. |
| `admin/` | The password-protected editor (see below). |
| `includes/` | Shared code (database connection, helpers, header/footer). You don't edit these. |
| `assets/` | CSS, JavaScript, and images (logo). |

### The admin editor (`/admin/`)

After logging in at **`/admin/login.php`** the owner can:

- **Dashboard** — quick counts of leads, demo requests, and blog posts.
- **Page Content** — edit every piece of text on the homepage: headline, features,
  industries, pricing, testimonials, the contact section, etc. No coding needed.
- **Blog Posts** — write, edit, publish/unpublish, and delete blog articles.
- **Leads** — view everyone who submitted the contact / free-trial form and any
  demo requests.

---

## Setup — step by step (for the programmer)

### 1. Create the database
Create an empty MySQL (or MariaDB) database on your host, e.g. named `doable`,
and a database user that can access it.

### 2. Import the tables and starter content
Import `database.sql` into that database. From a shell:

```bash
mysql -h YOUR_DB_HOST -u YOUR_DB_USER -p doable < database.sql
```

(or import it through phpMyAdmin / your DB tool). This creates all the tables and
loads the starter homepage content and two sample blog posts.

### 3. Fill in `config.php`
Open **`config.php`** and set the values in the four labeled sections:

1. **Database** — host, database name, user, password, port.
2. **Site** — public URL, the email address that should receive leads, and
   (optionally) the "Enroll" button link.
3. **Email (SMTP)** — recommended: your **AWS SES SMTP** credentials so lead
   notifications send reliably. If you leave `SMTP_HOST` blank, the site falls
   back to PHP's built-in `mail()`.
4. **Owner admin account** — set `ADMIN_EMAIL` and `ADMIN_PASSWORD`. **The first
   time** you visit `/admin/login.php` and log in with these, an owner account is
   created automatically. Use a strong password.

That is the only file you need to edit.

### 4. Point the web server at this folder
Set your Apache/Nginx document root to this folder so that `index.php` is served
as the homepage. PHP 8.0+ is recommended (developed and tested on PHP 8.2).
The only PHP extensions needed are **PDO / pdo_mysql** (standard on almost every host).

### 5. Log in and take over
Visit `https://YOUR-SITE/admin/login.php`, log in with the `ADMIN_EMAIL` /
`ADMIN_PASSWORD` from `config.php`, and you're in. From there everything is
editable through the admin screens.

---

## Email / AWS SES notes

- The contact and demo forms email the address in `LEAD_NOTIFICATION_EMAIL`.
- For AWS, the simplest reliable option is **SES SMTP**: create SMTP credentials
  in the SES console, verify your "from" address/domain, and put the host
  (e.g. `email-smtp.us-east-1.amazonaws.com`), port `587`, `tls`, and the
  username/password into the EMAIL section of `config.php`.
- Make sure `MAIL_FROM` is an address/domain you've verified in SES.

---

## Styling note (optional, for production polish)

The pages load Tailwind CSS from a CDN (`cdn.tailwind...`) for convenience, plus a
small custom stylesheet at `assets/css/styles.css`. This works out of the box.

For a production site you may prefer to compile Tailwind into a single static CSS
file (so it doesn't depend on the CDN and loads faster). That is optional — the
site looks and works the same either way. If you do, replace the CDN `<script>`
tag in `includes/header.php` with a link to your compiled CSS.

---

## Security reminders

- Change `ADMIN_PASSWORD` in `config.php` to a strong value **before** the first login.
- Keep `config.php` private (it holds your database and email credentials). It is
  never shown to visitors, but don't commit it to a public code repository.
- The admin area is protected by login + CSRF tokens and passwords are stored
  hashed. Serve the whole site over HTTPS.

---

Questions about the site content itself (wording, features, blog) can all be
handled from the admin editor without touching code.
