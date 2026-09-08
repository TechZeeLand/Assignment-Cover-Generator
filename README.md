# 📄 Assignment Cover Generator

A free, open-source, self-hosted tool that lets students design and export a
print-ready **assignment cover page PDF** — university name, department,
student & course details, topic, submission date — with full control over
fonts, colors, and layout.

Fill in a form and click **Generate** to get a print-ready PDF.

![Example cover](docs/screenshot-cover.png)

## ✨ Features

- **Toggle any field on/off** — show or hide the University name, Bismillah,
  each student/course detail row, the topic, the submission date, and the
  decorative border independently.
- **Custom fonts** — pick from the built-in fonts, or upload your own
  `.ttf`/`.otf` files right from the app. Uploaded fonts are saved on the
  server and become available to everyone using the app.
- **Rich text** — bold/italic formatting for the Course Title and Topic fields.
- **Full color control** — separate primary/secondary colors, plus an optional
  distinct color for the title and border.
- **Bismillah support** — renders the ﷽ ligature correctly using the bundled,
  open-source [Amiri](https://github.com/aliftype/amiri) font (SIL OFL),
  regardless of which fonts you pick elsewhere.
- **Stateless** — nothing about your cover is stored on the server; a PDF is
  generated on demand and never saved. (Only the *fonts* you optionally
  upload are persisted, since they're meant to be shared.)
- **Self-hosted & open source** — AGPL-3.0 licensed, runs entirely on your
  own server via Docker.

## 🏗️ How it's built

- **Backend:** plain PHP (no framework) + [mPDF](https://mpdf.github.io/) for PDF rendering.
- **Frontend:** vanilla HTML/CSS/JS — no build step, no framework, no tracking.
- **Packaging:** a single Docker image with **nginx + PHP-FPM baked in** (via supervisord), deployable as a one-container Compose stack — no separate web server setup needed.

> **Design note:** the reference design uses CSS Grid for its label/value
> rows. mPDF's HTML/CSS engine doesn't support Grid, so the PDF is built from
> an equivalent HTML `<table>` layout with the same fonts, sizes, and colors
> — producing the same visual result. Student Details and Course Details
> share a single `<table>` so mPDF sizes the label column once, keeping every
> colon in both sections aligned under the widest label.

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
       environment:
         - TZ=UTC

   volumes:
     acg_fonts:
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

## 🖥️ Local development (without Docker)

Requirements: PHP 8.1+, Composer, and the `mbstring`, `gd`, and `zip` PHP extensions.

```bash
composer install
php -S localhost:1025 -t public
```

Then open `http://localhost:1025`.

## 📁 Project structure

```
├── composer.json          # PHP dependencies (mpdf/mpdf)
├── Dockerfile              # Multi-stage build: Composer -> php:8.2-fpm + nginx + supervisord
├── docker-compose.yml       # Portainer / docker compose stack definition
├── public/                  # Web root
│   ├── index.php             # The form page
│   ├── generate.php          # Handles POST -> builds & streams the PDF
│   ├── fonts.php              # Returns the current font list as JSON
│   ├── upload_font.php        # Handles custom font uploads
│   ├── font_file.php           # Streams a stored font file (so the font picker can preview it)
│   └── assets/
│       ├── css/style.css        # All styling (responsive)
│       ├── js/app.js             # Rich text, font upload, form logic
│       └── fonts/Amiri-Regular.ttf  # Bundled font for the Bismillah glyph
├── src/                      # PHP application code (PSR-4: App\)
│   ├── Config.php              # Central constants (paths, built-in fonts, limits)
│   ├── FontManager.php          # Built-in + custom font registry, mPDF font config
│   ├── Sanitize.php              # Input sanitization (text, color, rich text)
│   ├── CoverData.php              # Validated data object built from the form POST
│   ├── CoverBuilder.php            # Renders CoverData -> mPDF-ready HTML (tables)
│   └── PdfService.php              # Wires it all together into an mPDF instance
└── storage/fonts/             # Persisted custom font uploads (Docker volume)
```

## 🔒 Security & privacy notes

- No data from the form is ever written to disk or a database — PDFs are
  generated in memory and streamed straight to your browser.
- Font uploads are validated by file extension **and** a binary signature
  check (so a renamed `.exe` won't pass as a `.ttf`), size-limited to 2 MB
  per file, and stored with sanitized, generated filenames.
- Font uploads are open to anyone who can reach the app, by design (per the
  project's goal of a shared, growing font library). If you're deploying
  this somewhere more exposed than a home server/LAN, consider putting it
  behind a reverse proxy with basic auth, or fork and add an admin gate
  around `upload_font.php`.
- All user-supplied text is HTML-escaped; the Course Title and Topic rich
  text fields only ever allow `<b>`, `<i>`, and `<br>` — every other tag and
  every attribute is stripped server-side, regardless of what the browser
  sends.

## 🎨 Customizing further

- **Change the default colors/fonts:** edit the `value=""` attributes in the
  Design section of `public/index.php`.
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
