# 📄 Assignment Cover Generator

A free, open-source, self-hosted tool that lets students design and export a
print-ready **assignment cover page PDF** — university name, department,
student & course details, topic, submission date — with full control over
fonts, colors, and layout.

Fill in a form, click **Generate**, get a print-ready PDF.

![Example cover](docs/screenshot-cover.png)

## ✨ Features

- **Twelve cover designs** — a horizontally scrollable picker (with an
  **Expand all** button) and live previews that follow your colors.
  *Classic* (the original) · *Professional:* Double Frame, Corner Brackets,
  Official Form, Heritage, Executive · *Modern:* Modern Bands, Side Panel,
  Minimal Lines, Hero Block, Mosaic, Duo Tone. All use the same form data,
  fonts, colors and toggles. Text on a design's own colored areas is always
  given a readable color derived from that background.
- **More cover fields** — university logo upload, faculty/school line, cover
  title (e.g. "Lab Report No. 3"), session, student email, and group
  assignments (group name + up to 12 members).
- **Optional Google sign-in** — signed-in users can save their details and
  have them pre-filled next time. Only a **name and email** are taken from
  Google; no passwords, no email verification, no SMTP.
- **Admin portal** at `/admin/` — turn designs on/off, see user and PDF
  statistics, configure Google OAuth and AdSense (publisher ID, ad units,
  `ads.txt`), manage users and shared fonts, and edit site settings.
- **Ads (optional)** — Google AdSense in three placements, loaded only after
  cookie consent by default. Cookie banner + Cookie Policy included.
- **Toggle any field on/off** — show or hide the University name, Bismillah,
  each student/course detail row, the topic, the submission date, and the
  decorative border independently.
- **Adjustable font sizes** — the Bismillah, University name, Department
  name, Topic, and Submission date each have their own font-size field, and
  the whole Student Details group (or Course Details group) can be resized
  together with a single field, all pre-filled with sensible defaults.
- **Custom fonts** — pick from the built-in fonts, or upload your own
  `.ttf`/`.otf` files right from the app. Uploaded fonts are saved on the
  server and become available to everyone using the app.
- **Rich text** — bold/italic formatting for the Course Title and Topic fields.
- **Full color control** — separate primary/secondary colors, plus an optional
  distinct color for the title and border.
- **Bismillah support** — renders the ﷽ ligature correctly using the bundled,
  open-source [Amiri](https://github.com/aliftype/amiri) font (SIL OFL),
  regardless of which fonts you pick elsewhere.
- **Private by default** — without signing in, nothing about your cover is
  stored; a PDF is generated on demand and never saved, and no cookie is set.
  Only visitors who sign in *and* press "Save my details" have anything
  stored, and they can delete it (or their whole account) at any time.
- **Self-hosted & open source** — AGPL-3.0 licensed, runs entirely on your
  own server via Docker.

## 🏗️ How it's built

- **Backend:** plain PHP (no framework) + [mPDF](https://mpdf.github.io/) for PDF rendering.
- **Frontend:** vanilla HTML/CSS/JS — no build step, no framework, no tracking.
- **Packaging:** a single Docker image with **nginx + PHP-FPM baked in** (via supervisord), deployable as a one-container Compose stack — no separate web server setup needed.

> **Design note:** the reference design uses CSS Grid for its label/value
> rows. mPDF's HTML/CSS engine doesn't support Grid, so the PDF is built from
> an equivalent HTML `<table>` layout with the same column widths, fonts,
> sizes, and colors — producing the same visual result. The decorative page
> border is drawn as a separate, absolutely-positioned element so it never
> constrains how the content itself flows or paginates.

## 🚀 Full setup guide: GitHub → Debian server → Portainer

This walks through everything from an empty GitHub account to a running app
at `http://<your-server-ip>:1025`. It assumes:

- GitHub username: **TechZeeLand**
- Repository name: **Assignment-Cover-Generator**
- A Debian 12 home server already running Docker + Portainer.

### Part 1 — Push this project to GitHub

1. **Create the repository on GitHub:**
   - Go to [github.com/new](https://github.com/new).
   - Owner: `TechZeeLand`. Repository name: `Assignment-Cover-Generator`.
   - Leave it **empty** (don't check "Add a README" — you already have one).
   - Visibility: Public (needed for GHCR images to be pullable without login;
     Private also works, see note in Part 3).
   - Click **Create repository**. Keep the page open — GitHub shows you the
     commands you need, which match what's below.

2. **On your own computer** (not necessarily the server — anywhere with git),
   in the folder containing all these project files:

   ```bash
   cd assignment-cover-generator
   git init -b main
   git add .
   git commit -m "Initial commit: Assignment Cover Generator"
   git remote add origin https://github.com/TechZeeLand/Assignment-Cover-Generator.git
   git push -u origin main
   ```

   If prompted for credentials, GitHub no longer accepts your account
   password over HTTPS — use a
   [Personal Access Token](https://github.com/settings/tokens) as the
   password instead, or push over SSH if you have a key set up
   (`git remote set-url origin git@github.com:TechZeeLand/Assignment-Cover-Generator.git`).

3. **Check the Actions tab** on GitHub after pushing. The included workflow
   (`.github/workflows/docker-publish.yml`) will automatically lint the PHP
   code and — since you pushed to `main` — build and publish a Docker image
   to GitHub Container Registry at
   `ghcr.io/techzeeland/assignment-cover-generator:latest`. This takes a
   couple of minutes the first time. You don't strictly need this step
   (Portainer can build the image itself, see Part 3 Option A), but it means
   your home server never has to compile anything.

4. **Make the package public** (only needed if the repo itself is public and
   you want the image pullable without logging in): go to your GitHub
   profile → **Packages** tab → `assignment-cover-generator` → **Package
   settings** → change visibility to **Public**.

### Part 2 — Prepare your Debian 12 server

You said Docker + Portainer are already set up, so this is just a sanity check:

```bash
docker --version
docker compose version
```

If either command fails, install Docker first:

```bash
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker $USER
# log out and back in for the group change to apply
```

### Part 3 — Deploy the stack in Portainer

Open Portainer in your browser (usually `https://<server-ip>:9443`), then
**Stacks → Add stack**, name it `assignment-cover-generator`, and choose one
of these two build methods:

**Option A — Build on the server from your GitHub repo (recommended to start with)**

1. Build method: **Repository**.
2. Repository URL: `https://github.com/TechZeeLand/Assignment-Cover-Generator`
3. Repository reference: `refs/heads/main`
4. Compose path: `docker-compose.yml`
5. Deploy the stack. Portainer clones the repo and runs
   `docker compose up -d --build` on your server — the first build takes a
   few minutes (installing PHP extensions + Composer packages); later
   redeploys are much faster thanks to Docker's layer cache.

**Option B — Web editor, pulling the pre-built image from GHCR**

Use this once the GitHub Action from Part 1 has published an image at least
once. It's faster since your server just downloads a ready-made image
instead of building it.

1. Build method: **Web editor**.
2. Paste this in:

   ```yaml
   services:
     assignment-cover-generator:
       image: ghcr.io/techzeeland/assignment-cover-generator:latest
       container_name: assignment-cover-generator
       restart: unless-stopped
       ports:
         - "1025:80"
       volumes:
         - acg_fonts:/var/www/html/storage/fonts
         - acg_data:/var/www/html/storage/data
       environment:
         - TZ=UTC
         - ADMIN_PASSWORD=choose-a-long-random-password

   volumes:
     acg_fonts:
     acg_data:
   ```

3. Deploy the stack.

Either way, once it's running, visit **`http://<your-server-ip>:1025`** —
you should see the form.

### Part 4 — Updating later

- **Option A stacks:** in Portainer, open the stack and click **Pull and
  redeploy** (or **Update the stack** → check "Re-pull image and
  redeploy") after pushing new commits to `main`.
- **Option B stacks:** same, but it re-pulls the `:latest` tag from GHCR —
  make sure the GitHub Action has finished building first.
- Either way, your uploaded custom fonts are safe: they live in the
  `acg_fonts` Docker volume, completely separate from the app code/image.

### Command-line alternative (skip Portainer entirely)

If you'd rather SSH in and run it directly instead of using the Portainer UI:

```bash
ssh you@your-server
git clone https://github.com/TechZeeLand/Assignment-Cover-Generator.git
cd Assignment-Cover-Generator
docker compose up -d --build
```

Then visit `http://<your-server-ip>:1025`.

## 🔐 Admin portal, Google sign-in & ads

### 1. Set the admin password (first run)

Set the `ADMIN_PASSWORD` environment variable (Portainer: *Environment
variables*; Compose: put `ADMIN_PASSWORD=...` in a `.env` file next to
`docker-compose.yml`, see `.env.example`). Then open
**`https://your-site/admin/`** and log in with it. The password is stored
hashed on first login and can be changed under *Admin password*. If you lose
it, set `ADMIN_PASSWORD` plus `ADMIN_PASSWORD_RESET=1`, restart, log in, then
remove the reset flag. After 5 wrong attempts an IP is locked out for 15
minutes. Put the site behind HTTPS (e.g. a reverse proxy) before using it on
the internet.

### 2. Google sign-in

Admin → **Sign-in (Google)** shows the exact *redirect URI* to register. In
the [Google Cloud Console](https://console.cloud.google.com/apis/credentials)
create an *OAuth client ID → Web application*, add that URI, paste the client
ID and secret into the admin panel and save. Set **Site URL** (Site settings)
to your public `https://…` address so the redirect URI is exact.
Sign-in is optional for visitors and uses the default `openid email profile`
scopes; only name and email are kept.

### 3. Ads

Admin → **Ads & ads.txt**: enter your AdSense publisher ID
(`ca-pub-…`), the numeric ad unit IDs for the top / middle / bottom
placements and switch ads on. `ads.txt` is served at `/ads.txt` (generated
from the publisher ID, or paste your own lines). By default ads load only
after the visitor accepts the cookie banner. If you serve the EEA, UK or
Switzerland, Google additionally requires a certified consent platform for
personalised ads — enable Google's own consent message in AdSense.

### Where data lives

A single SQLite file and the PHP sessions live in `storage/data` (the
`acg_data` volume): settings (including the Google client secret — keep this
volume private and back it up), signed-in users (name + email), saved
details, and anonymous daily counters. Fonts live in `acg_fonts`.

## 🖥️ Local development (without Docker)

Requirements: PHP 8.1+, Composer, and the `mbstring`, `gd`, `zip`, `curl`
and `pdo_sqlite` PHP extensions.

```bash
composer install
ADMIN_PASSWORD='dev-password-123' php -S localhost:1025 -t public
```

(`/ads.txt` is routed by nginx in Docker; with the built-in server open
`/ads-txt.php` instead.)

Then open `http://localhost:1025`.

## 📁 Project structure

```
├── composer.json          # PHP dependencies (mpdf/mpdf)
├── Dockerfile              # Multi-stage build: Composer -> php:8.2-fpm + nginx + supervisord
├── docker-compose.yml       # Portainer / docker compose stack definition
├── public/                  # Web root
│   ├── index.php             # The form page
│   ├── generate.php          # Handles POST -> builds & streams the PDF
│   ├── auth/                  # Google sign-in start / callback / sign-out / delete account
│   ├── api/profile.php        # Save / load / delete a signed-in user's saved details
│   ├── admin/                 # Admin portal (dashboard, designs, sign-in, ads, users, ...)
│   ├── cookie-policy.php      # Cookie policy (plus privacy-policy.php, terms-and-conditions.php)
│   ├── ads-txt.php            # Serves /ads.txt from the admin settings
│   ├── fonts.php              # Returns the current font list as JSON
│   ├── upload_font.php        # Handles custom font uploads
│   ├── font_file.php           # Streams a stored font file (for the font-select dropdown preview)
│   └── assets/
│       ├── css/style.css        # All styling (responsive)
│       ├── js/app.js             # Rich text, font upload, and field-toggle logic
│       └── fonts/Amiri-Regular.ttf  # Bundled font for the Bismillah glyph
├── src/                      # PHP application code (PSR-4: App\)
│   ├── Config.php              # Central constants (paths, built-in fonts, limits)
│   ├── FontManager.php          # Built-in + custom font registry, mPDF font config
│   ├── Sanitize.php              # Input sanitization (text, color, rich text)
│   ├── Templates/                 # One class per cover design + TemplateRegistry
│   ├── Db.php, Settings.php       # SQLite storage and admin-editable settings
│   ├── Auth.php, GoogleOAuth.php  # Optional Google sign-in (OAuth code flow + PKCE)
│   ├── AdminAuth.php              # Password-only admin login with throttling
│   ├── Users.php, ProfileStore.php, Stats.php, Ads.php, Logo.php, Color.php
│   ├── CoverData.php              # Validated data object built from the form POST
│   ├── CoverBuilder.php            # Renders CoverData -> mPDF-ready HTML (tables)
│   └── PdfService.php              # Wires it all together into an mPDF instance
├── storage/fonts/             # Persisted custom font uploads (Docker volume)
└── storage/data/              # SQLite database + sessions (Docker volume, keep private)
```

## 🔒 Security & privacy notes

- Without signing in, no data from the form is written anywhere — PDFs are
  generated in memory and streamed straight to your browser. Signed-in users'
  saved details are whitelisted and sanitised before they are stored.
- Admin and user forms are CSRF-protected, sessions use `HttpOnly` +
  `SameSite=Lax` cookies (and `Secure` over HTTPS), the Google flow uses
  `state`, `nonce` and PKCE, and uploaded logos are re-encoded through GD.
- Font uploads are validated by file extension **and** a binary signature
  check (so a renamed `.exe` won't pass as a `.ttf`), size-limited to 2 MB
  per file, and stored with sanitized, generated filenames.
- Font uploads are open to anyone who can reach the app, by design (per the
  project's goal of a shared, growing font library). If you're deploying
  this somewhere more exposed than a home server/LAN, switch uploads off in
  the admin panel (Site settings) and remove unwanted fonts under
  *Custom fonts*.
- All user-supplied text is HTML-escaped; the Course Title and Topic rich
  text fields only ever allow `<b>`, `<i>`, and `<br>` — every other tag and
  every attribute is stripped server-side, regardless of what the browser
  sends.

## 🎨 Customizing further

- **Add a new cover design:** create a class in `src/Templates/` extending
  `BaseTemplate` (implement `key()`, `label()`, `description()`,
  `thumbnailSvg()`, `bottomMarginPt()` and `buildHtml()`), then add it to
  `TemplateRegistry::all()`. The picker, validation and PDF output pick it up
  automatically. Follow the mPDF rules documented at the top of
  `BaseTemplate.php` (no Grid/Flex, one details table, fixed boxes for
  decoration).

- **Colors on coloured areas:** designs that draw their own fills (bands,
  panels, tinted rows) must derive text colors from that fill with
  `Color::contrastText()` / `Color::ensureContrast()` and apply them as
  **inline** styles — never rely on a stylesheet rule that might not match.

- **Change the default colors/fonts:** edit the `value=""` attributes in the
  Design section of `public/index.php`.
- **Change a default font size:** edit both the matching `value=""` in
  `public/index.php` and the matching `DEFAULT_*_FONT_SIZE` constant in
  `src/CoverData.php` (they should stay in sync).
- **Add a built-in font:** add an entry to `Config::builtInFonts()` — but
  note built-ins are expected to map to mPDF's core font aliases (`sans`,
  `serif`, `mono`); for anything else, use the in-app font uploader instead.
- **Change the page size/margins:** see `PdfService::render()`.

## 🙏 Credits

- [mPDF](https://mpdf.github.io/) — GPL-2.0-or-later.
- [Amiri font](https://github.com/aliftype/amiri) by Khaled Hosny — SIL Open Font License 1.1.

## 📜 License

Licensed under the **GNU Affero General Public License v3.0** — see
[`LICENSE`](LICENSE). In short: you're free to use, modify, and
self-host this, but if you run a modified version as a network service,
you must make your modified source available to its users too.

## 🤝 Contributing

Issues and pull requests are welcome. This project intentionally stays
dependency-light (plain PHP, vanilla JS) — please keep contributions in that
spirit unless there's a strong reason not to.
