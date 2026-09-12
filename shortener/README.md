# 🧷 NS Link — URL Shortener Web App (Part 2)

> **One short link, an editorial chain of task pages, one final destination.**
> A self-hosted URL shortener + chain generator — **vanilla PHP + SQLite**, no framework, no composer. Drop the folder on any PHP host and it runs.

<p align="center">
  <img src="../assets/brand/ns-link-shortener-flow.svg" alt="NS Link shortener — chain engine flow" width="900">
</p>

**Part of the [NSKWeb/ns](../README.md) repo** (main README → Part 2). The visual voice is the same as the WordPress plugin: *literary neo-brutalism* — paper background, ink lines, vermilion + ochre accents, serif headlines with monospace labels.

---

## 🧪 Live demo

> ⚠️ **The demo runs on a temporary all-hands container URL** — it may not be up when you visit. While it runs it shows the real app against a real SQLite DB, and you can reset it anytime by deleting `shortener/data/`.

**👉 Try it now:** [**work-2 … all-hands.dev**](https://work-2-axtomacioldgqets.prod-runtime.all-hands.dev/)
*(backup mirror: [**work-1 … all-hands.dev**](https://work-1-axtomacioldgqets.prod-runtime.all-hands.dev/))*

- **Chain code** — [`/go/qTqvVFAZ`](https://work-2-axtomacioldgqets.prod-runtime.all-hands.dev/go/qTqvVFAZ) → step-1 task page (timer) → Continue → … → final "Open Your Link"
- **Direct link** — [`/go/fPfMju`](https://work-2-axtomacioldgqets.prod-runtime.all-hands.dev/go/fPfMju) → 302 to `https://example.com/direct`
- **Admin** — [`/admin/login.php`](https://work-2-axtomacioldgqets.prod-runtime.all-hands.dev/admin/login.php) → `admin` / `ns-admin-2026` *(change it once you log in!)*

### Screenshots

<details open>
<summary>📸 Live-demo screen recording stills (open by default)</summary>

| Landing | Task page (step 1) |
|---|---|
| <img src="../assets/screenshots/demo-landing.png" alt="Landing page" width="420"> | <img src="../assets/screenshots/demo-task-page.png" alt="Task page with countdown" width="420"> |

| Final step → "Open Your Link" | Admin — dashboard |
|---|---|
| <img src="../assets/screenshots/demo-final-step.png" alt="Final step" width="420"> | <img src="../assets/screenshots/demo-dashboard.png" alt="Admin dashboard" width="420"> |

| Admin — click stats |
|---|
| <img src="../assets/screenshots/demo-stats.png" alt="Admin stats" width="420"> |

</details>

---

## 🗺️ What this app does

| You get | How it works |
|---|---|
| **Short links** | `/go/<code>` → instant **302 redirect** to any URL, click logged |
| **Chain generator** | Admin form builds **N step URLs + a final destination**; every step becomes a task page |
| **Timed task pages** | Timer starts on first scroll → **Continue** button unlocks → next step (or destination on the last one) |
| **Click stats** | Every step view logged to the `clicks` table; per-chain / per-step totals in admin **Stats** |
| **SQLite, single file** | DB auto-created (`data/ns-link.sqlite`, WAL mode), schema auto-applied on first run |
| **Single admin** | Password-protected panel (`admin/`) — links, chains, stats, password change |

**In one line:** `site.com/go/abc123` → a visitor *reads/browses* your task pages (ad slots here) → the moment the timer ends they can tap **Continue** → eventually lands on the destination. Every step is measured.

---

## ✨ Why it looks editorial

The app inherits the repo's **literary neo-brutalism** house style:

- 🧷 paper `#F7F1E5` canvas, ink `#1C1A17` rules, vermilion `#E4572E` accents, ochre `#C98A00` labels
- 📰 serif headlines (Georgia) + monospace labels (Courier), hard 3px borders, grain & registration-mark flavor
- 🧩 Ad slots are **empty containers** (`.nslink-ad-top`, `.nslink-ad-mid`, `.nslink-ad-foot`) — drop ad codes in via the plugin or cPanel, exactly like Part 1

---

## 🚀 Quick start

### Requirements

- PHP **7.4+** (tested on **8.x**) with `pdo`, `pdo_sqlite`, `session`
- Write access to `shortener/data/`
- Apache (`mod_rewrite`, `.htaccess` included) **or** Nginx — or the PHP built-in server for local work

### 60-second run (local)

```bash
cd shortener
php -S 127.0.0.1:8099 router.php
# open http://127.0.0.1:8099/   →  admin:  http://127.0.0.1:8099/admin/login.php
```

> **Important:** pass **`router.php`** as the router script. That makes `/go/<code>` clean URLs work on the CLI server. (Without a router, `-t` falls back to `index.php` for `/go/*` and the shortener breaks.)

### First-login checklist

| Thing | Default / note |
|---|---|
| Admin user | `admin` (`config.php` → `admin_user`) |
| Admin password | `ns-admin-2026` — **change immediately!** (`config.php` or Settings) |
| Step wait | `8` seconds (`config.php` → `default_wait`) |
| Two security toggles | `cookie_secure = true` over HTTPS; `allowed_hosts` optional host-whitelist |

1. Log in → **Settings → change password**
2. Set `base_url` in `config.php` if you want absolute URLs (else auto-detected)
3. Create a simple link or a chain → test `/go/<code>`

---

## 🔗 How short links work *(the logic, end to end)*

### The three database tables

`schema.sql` creates four tables; three are the heart:

```
links:   id · code · url · title · created_at
chains:  id · code · name · steps_json · created_at · updated_at
clicks:  id · link_id · chain_id · step · ip · user_agent · referer · created_at
```

- **`links`** — every redirect target. A `code` is a random 7-char base-string (`[A-Za-z0-9]`), unique.
- **`chains`** — a chain is just a **name + JSON of steps**. Each step holds `{ url, wait, link_id }`; the admin form builds N steps and stores them as one JSON document.
- **`clicks`** — one row per view of a short link or a step page. The `step` column is the step number within the chain.

### Request lifecycle for `/go/abc123`

```
GET /go/abc123
        │
        ▼
   router.php / .htaccess  →  go.php?code=abc123
        │
        ▼
   go.php  →  sanitize code ([^A-Za-z0-9] stripped)
        │
        ├─ 1. Is it a plain link?   ─► log click → 302 → target URL
        │
        ├─ 2. Is it a chain step link?  ─► log click → render task page (2xx)
        │                              (url contains chain=<c>&step=<n>)
        │
        ├─ 3. Is it a chain code?   ─► log click → 302 → first step's short link
        │
        └─ else  ─► 404 Not found
```

**Order matters.** `go.php` checks in this exact sequence: plain link first, then a chain-step link (URL that carries `chain=…&step=…`), then a chain code (which redirects to step 1). That is why a chain code never shadows a step link, and why `/go/<step-code>` can render a task page *without* a browser-navigation change.

### The chain JSON

When you click **Create chain**, the admin builds:

```json
{
  "steps": [
    { "url": "https://blog.example/task-1", "wait": 8,  "link_id": 41 },
    { "url": "https://blog.example/task-2", "wait": 6,  "link_id": 42 },
    { "url": "https://blog.example/task-3", "wait": 14, "link_id": 43 }
  ],
  "dest": "https://offer.example/checkout"
}
```

- Each step URL gets **its own short link** (`link_id`) — so you can track a step even if the visitor only opens that step's short URL directly.
- The final destination is **not** short-handed; it is the chain's `dest` and is only revealed when the last Continue is tapped.

### Task page mechanics

A task page is rendered by **`chain.php → nslink_chain_render()`** (or `task.php?chain=<c>&step=<n>`). The page ships:

- a **countdown ring** (`data-wait` seconds) + a progress bar
- the step number / total (e.g. *Step 2 / 4*)
- empty **ad-slot containers** for your ad network
- a **Continue** button — disabled until the timer ends

**Trigger logic (`assets/js/task.js`):**

- The timer **starts on first scroll** (`scrollY > 40`) — a reader moving down the page is a *real* reader.
- Each second: `remaining−−`, countdown + progress bar update.
- At `remaining <= 0`: button enables (`disabled=false`, `.active`), timer class `done`.
- On click, the button reads `data-next` (next step URL) or `data-dest` (final destination) and navigates.
- **`?skip=1`** in the URL shortens the wait (used in local QA / the smoke test).

**Button behavior by position:**

| Position | `data-*` | Behavior |
|---|---|---|
| Non-final step | `data-next="/task.php?chain=…&step=n+1"` | Continue → next task page |
| Final step | `data-dest="https://offer…"` | **Open Your Link** → destination (usually new tab) |

So a chain with 4 steps behaves exactly like the hero diagram: *short-link → task-1 → task-2 → task-3 → task-4 → destination*.

### Click logging

Every step short-link or task-page request writes a `clicks` row:

```php
nslink_log_click($linkId, $chainId, $step);   // inside go.php / chain.php
```

- **Never blocks the page** — logging is non-fatal (`try/catch`), so even a storage hiccup won't 500 a redirect.
- `ip`, `user_agent`, `referer` are stored as-is — treat them as private data (see **Security** below).

### Stats page

`admin/stats.php` groups `clicks` by chain and by step:

- Per-chain totals + per-step totals (the `step` column).
- No aggregate pipeline needed; it's a couple of `SELECT COUNT(*) … GROUP BY` queries, nicely table-ized in the same neo-brutalist style.

---

## 🛠️ Deployment — step by step (choose your host)

Four ways to run the same `shortener/` folder. Pick the one that matches your hosting.

| Route | Skills needed | Best for |
|---|---|---|
| **A. PHP built-in server** | Terminal only | Local testing, quick demo |
| **B. Shared hosting (Apache / cPanel)** | File upload via FTP/File Manager | InfinityFree, 000webhost, Hostinger, … |
| **C. Subdomain on cPanel** | cPanel → create subdomain | A live vanity domain (`go.yourdomain.com`) |
| **D. VPS + Nginx** | Basic Linux + Nginx | Full control, high traffic |
| **E. Local (XAMPP/WAMP/Laragon)** | Desktop installer | Offline editing on your own machine |

> **One rule for every route:** the `data/` folder must be **writable by PHP**, and the web server must
> route `/go/<code>` to `go.php`. Route A uses `router.php`; routes B–E use the included `.htaccess`
> (Apache) or a short rewrite config (Nginx).

### Option A — PHP built-in server (local, 60 seconds)

```bash
cd shortener
php -S 127.0.0.1:8099 router.php
```

- Open <http://127.0.0.1:8099/> → landing page
- Admin: <http://127.0.0.1:8099/admin/login.php>
- **You must pass `router.php`** as the router argument — without it, `/go/<code>` won't resolve
  (the built-in server would send `/go/*` to `index.php` instead of `go.php`).

To expose it beyond localhost on a LAN:

```bash
php -S 0.0.0.0:8099 router.php
# then visit http://YOUR_LAN_IP:8099/
```

### Option B — Shared hosting (Apache, e.g. InfinityFree / 000webhost / Hostinger)

1. **Download the zip** of the repo (`Code ▾ → Download ZIP`) and extract.
2. Put **only the `shortener/` folder** in your site root, e.g. `htdocs/` or `public_html/`:
   ```
   public_html/
   └── shortener/
       ├── index.php
       ├── go.php
       ├── router.php
       ├── .htaccess
       ├── config.php
       └── ...
   ```
   *(Or upload the app's contents to the root `public_html/` directly if you want the app at the domain root.)*
3. **Permissions:** make sure `shortener/data/` is writable by PHP (usually `755` for folders, `644`
   for files; on cPanel set `data/` to `755` or `775`). On cPanel/FTP, `data/` may not exist yet — it's
   created automatically on first run (PHP must be allowed to `mkdir`).
4. The `.htaccess` file already contains the `/go/<code>` rewrite — check the host doesn't block
   `.htaccess`. If you get a **500 on every page**, see **Troubleshooting** below.
5. Visit `https://yourhost/shortener/admin/login.php` (or `/admin/login.php` if at root) and log in
   with the defaults. **Change the password immediately.**

> 💡 **Domain-root install:** if you want `https://yourdomain.com/go/abc` instead of
> `https://yourdomain.com/shortener/go/abc`, upload the **contents** of `shortener/` into
> `public_html/` (don't keep the wrapping `shortener/` folder). Everything else is identical.

### Option C — cPanel subdomain (nice vanity link)

1. cPanel → **Subdomains** → create `go.yourdomain.com`, document root
   `public_html/shortener` (or wherever you uploaded the folder).
2. Upload `shortener/` contents to that document root via **File Manager** or FTP.
3. In cPanel → **PHP** or **MultiPHP**, select PHP **7.4+ / 8.x** and confirm the extensions:
   `pdo`, `pdo_sqlite`, `session`.
4. Set `data/` writable (Option B step 3).
5. Open `https://go.yourdomain.com/admin/login.php` → login → change password.
6. Optional: set `base_url` in `config.php` to `https://go.yourdomain.com` if you want fully-qualified
   admin links (the app auto-detects the host, so this is usually unnecessary).

### Option D — VPS with Nginx

Nginx doesn't understand `.htaccess`, so add a rewrite to your server block:

```nginx
server {
    listen 80;
    server_name go.example.com;
    root /var/www/shortener;
    index index.php;

    location / {
        try_files $uri $uri/ @shortener;
    }

    # /go/<code>  ->  go.php?code=<code>
    location ~ ^/go/([A-Za-z0-9]+)/?$ {
        rewrite ^/go/([A-Za-z0-9]+)/?$ /go.php?code=$1 last;
    }

    location @shortener {
        rewrite ^ /index.php last;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    }
}
```

Then reload Nginx and fix ownership of the data dir:

```bash
sudo systemctl reload nginx
sudo chown -R www-data:www-data /var/www/shortener/data
```

### Option E — Local desktop (XAMPP / WAMP / Laragon)

1. Install XAMPP → place the `shortener/` folder in `htdocs/`.
2. Start Apache. Visit `http://localhost/shortener/admin/login.php`.
3. Same checklist: change the password, create a test link.

---

## ✅ First-run setup (all routes)

When the first PHP request hits the app, `db.php`:

1. Creates `shortener/data/` if missing.
2. Opens `shortener/data/ns-link.sqlite` (SQLite, WAL mode).
3. Applies `schema.sql` (idempotent `CREATE TABLE IF NOT EXISTS`) — creates `links`, `chains`,
   `clicks`, `settings`, and seeds default settings.

So there is **no `php migrate` step** — the first page visit boots the DB for you.

**First-run checklist** (do every item once):

- [ ] Log into `/admin/login.php` with the defaults (`admin` / `ns-admin-2026`)
- [ ] **Change the admin password** (Settings → Change Password, at least 8 chars)
- [ ] Set `cookie_secure = true` in `config.php` if the site is HTTPS (recommended)
- [ ] Optional: set `allowed_hosts` to a whitelist like `['go.example.com']`
- [ ] Create a **test simple link** → open `/go/<code>` → confirm the 302
- [ ] Create a **test chain** → open `/go/<chain-code>` → walk all steps → confirm the final
      "Open Your Link" goes to the destination
- [ ] Watch the click counter appear in **Stats** after a few visits

---

## 🕹️ How to operate — 1-by-1

The admin panel is the whole control room. Every screen:

| Screen | URL | What it does |
|---|---|---|
| **Dashboard** | `admin/index.php` | Create simple link, create chain, recent links & chains, quick delete |
| **Chains** | `admin/chains.php` | List all chains with **short link** + **chain link** preview, delete (cascades step links) |
| **Links** | `admin/links.php` | All short links, copy codes, delete |
| **Stats** | `admin/stats.php` | Per-chain & per-step click counts + last-click time |
| **Settings** | `admin/settings.php` | Change the admin password (writes `config.php` via `var_export`) |
| **Logout** | `admin/logout.php` | End the session |

### Step-by-step: create a simple link

1. Log in → **Dashboard**.
2. *(Optional)* type a **Label** (e.g. `Offer page`).
3. Paste the destination URL into **Destination URL** (e.g. `https://example.com/offer`).
4. Click **Create short link**.
5. The success banner shows the new code, e.g. `/go/aB3xY9`.
6. Share `https://yourdomain/go/aB3xY9` — anyone who visits is logged + 302-redirected.

### Step-by-step: create a chain

1. Log in → **Dashboard** → **Create Chain**.
2. Give the chain a **name** (optional, e.g. `Autumn campaign`).
3. Set the **Final destination URL** — the page the last step sends people to.
4. Fill the **Step rows**: each row is one URL the visitor must sit through, with a **wait** time
   (seconds) before the Continue button unlocks. Four rows are shown by default.
   - `Step URL` — the task page you want them to read (article, landing, ad page…).
   - `Wait` — how many seconds the countdown goes before Continue enables (`1–120`).
5. Click **+ Add step** to add a fifth (or more) step, if needed.
6. Click **Create chain**.
7. Go to **Chains** to see the new entry with two ready-made links:
   - **Short link** — `/go/<first-step-code>` (drops you straight on step 1)
   - **Chain link** — `/go/<chain-code>` (the canonical code; 302s to step 1's short link)
8. Test it: open the **chain link** in an incognito window, scroll, wait for the timer, Continue
   through every step, land on the destination. Then check **Stats**.

### Step-by-step: read the stats

- **Total clicks** is shown at the top of the Stats page.
- The table groups by **chain**, then shows **step link**, **step #**, **clicks**, **last click**.
- A click is recorded when `/go/<code>` resolves *or* when a task page renders — a chain of 4 steps
  typically adds 4–5 clicks per full visitor (chain code 302 + each step render).
- The table shows the latest 100 rows by last-click time.

### Step-by-step: maintain (update / delete)

- **Delete a link:** Dashboard → Recent Links → Delete → confirm. (Also the Links page.)
- **Delete a chain:** Dashboard → Recent Chains → Delete, or **Chains** page → Delete. This
  **cascades** — it also deletes each step's short link, so stats for those step links are removed too.
- **Change password:** Settings → current + new + confirm → **Update password**. The new bcrypt hash
  is written straight back into `config.php` (`admin_pass_hash`), which now overrides `admin_pass`.

### Backup & restore the database

The whole state lives in one SQLite file (`shortener/data/ns-link.sqlite`). To back it up:

```bash
# Safe online backup (SQLite VACUUM INTO, WAL-aware)
cd shortener
php -r '$db = new PDO("sqlite:data/ns-link.sqlite"); $db->exec("VACUUM INTO \047backup-$(date +%F).sqlite\047");'
# or simply copy while the site is idle:
cp data/ns-link.sqlite ~/ns-link-backup-$(date +%F).sqlite
```

To restore: overwrite the file, then fix the permission:

```bash
cp ~/ns-link-backup-YYYY-MM-DD.sqlite shortener/data/ns-link.sqlite
chown www-data:www-data shortener/data/ns-link.sqlite   # match your web user
```

To **reset to a clean slate** (fresh demo, no clicks): stop the site, delete the DB:

```bash
rm -f shortener/data/ns-link.sqlite shortener/data/ns-link.sqlite-*
# next page load recreates schema + defaults
```

---

## 🧯 Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| **500 on every page** | `.htaccess` blocked / `RewriteEngine` not allowed (or wrong PHP version) | Confirm `.htaccess` present; on strict hosts upload to root and test `index.php`; ensure PHP 7.4+ |
| **`/go/abc` returns the landing page** | Routing missing (`router.php` not passed, or `.htaccess` ignored) | Built-in server: run `php -S ... router.php`. Apache: confirm `.htaccess` allowed |
| **`/go/abc` → 404** | Rewrite not active or code typed wrong | Check admin → Links/Chains for the code; test with a fresh link |
| **`SQLSTATE[HY000] … unable to open database file`** | `data/` not writable | `chmod` / `chown` the `data/` folder to the web user; create it manually with `755` if needed |
| **Login fails even with the right password** | stale `admin_pass_hash` in config | Edit `config.php` → remove the `admin_pass_hash` line, or update via Settings |
| **`php: command not found`** | PHP not installed | Install PHP 7.4+ (Debian/Ubuntu: `sudo apt install php php-sqlite3`) |
| **`pdo_sqlite` extension missing** | PHP without SQLite driver | `sudo apt install php-sqlite3` or enable `pdo_sqlite` in `php.ini`; verify `php -m \| grep pdo_sqlite` |
| **Session cookie not set over HTTPS** | `cookie_secure` is false | Set `cookie_secure => true` in `config.php` |
| **Admin "Could not write config file"** | `config.php` not writable | `chmod 664 config.php` (or give write permission) then retry Settings |
| **Clicks not increasing in Stats** | Browser cached the redirect / ad-block blocked the request | Try incognito; check `data/ns-link.sqlite` grows |

---

## 🔐 Security (production baseline)

| Area | What NS Link does | What you should do |
|---|---|---|
| **Password** | Default `admin` / `ns-admin-2026`; Settings writes a bcrypt hash to `config.php` | **Change it on first login.** Keep `config.php` out of web root where possible |
| **SQL** | All queries use **PDO prepared statements** (`EMULATE_PREPARES` off) | Keep PHP/SQLite versions patched |
| **Output** | Everything escaped with `htmlspecialchars()` | Keep the admin panel under a private path / basic-auth if you like |
| **Sessions** | HTTP-only, configurable name, `SameSite=Lax` | Deploy behind HTTPS; set `cookie_secure = true` |
| **Hosts** | `allowed_hosts` array in config | Whitelist your real domain to blunt host-header attacks |
| **Clicks data** | Logs `ip`, `user_agent`, `referer` | These are personal data — add retention cleanup if you care (e.g. delete rows older than N days); mention it in your privacy policy |
| **Tests** | `tests/` contains no secrets | Remove `tests/` from production or restrict access |

---

## 🧪 Tests

`tests/smoke.php` is a **true end-to-end smoke test** — it boots against the running server and asserts the real HTTP behavior:

```bash
cd shortener
php -S 127.0.0.1:8899 router.php &
php tests/smoke.php
```

Expected tail:

```
---- Summary ----
Passed: 22
Failed: 0
```

What it covers (currently **22 assertions**):

- `GET /` → 200
- login with default creds → 302 → dashboard 200
- create chain → 302 → chains list parse (chain code + first-step short code)
- `/go/<chain-code>` → task page
- `/go/<step-1-code>` → step 1 with correct step/total/wait + Continue button
- Continue points to **step 2** (`/task.php?chain=…&step=2`)
- `/task.php` final step → **Open Your Link** → points at the destination
- simple link create → 302 → `/go/<code>` → 302 to the target
- `/admin/stats.php` renders

> The test writes a throwaway SQLite DB + cookie jar, re-creating the schema each run. Safe to run repeatedly.

---

## 📁 File layout

```
shortener/
├── index.php          # Landing / marketing surface
├── go.php             # /go/<code> handler — redirects, chain-step render, logging
├── task.php           # /task.php?chain=<code>&step=<n> (deep-link to a step)
├── router.php         # PHP built-in server router (clean /go/<code>)
├── .htaccess          # Apache rewrite: /go/<code> → go.php
├── chain.php          # Chain CRUD helpers, task renderer, click logging
├── config.php         # Settings (DB path, admin user/pass, base_url)
├── db.php             # PDO SQLite bootstrap (WAL, schema auto-apply) + helpers
├── auth.php           # Session + login gate for admin
├── schema.sql         # SQLite schema (auto-run on first boot)
├── LICENSE            # GPL-2.0-or-later (canonical GNU text)
├── data/              # SQLite DB lives here (data/ns-link.sqlite) — runtime, gitignored
├── admin/
│   ├── login.php      # Login form
│   ├── logout.php     # End session
│   ├── index.php      # Dashboard: simple links + chain builder + recent rows
│   ├── links.php      # All short links (delete)
│   ├── chains.php     # Chain list + preview + delete (cascade step links)
│   ├── stats.php      # Click statistics
│   └── settings.php   # Change admin password (var_export-safe write)
├── assets/
│   ├── css/admin.css  # Literary neo-brutalism styles
│   └── js/task.js     # Timer + Continue control on task pages
└── tests/
    └── smoke.php      # End-to-end smoke test (22 assertions)
```

---

## 🆕 What's new & why it's better

- **No framework, no composer** — a real shared-host drop-in. Works on PHP 7.4+ and 8.x.
- **Single SQLite file** — backup = copy one file.
- **Auto-booting schema** — zero migration commands.
- **`var_export`-safe config writes** — password hashes round-trip exactly (`$2y$…` survives).
- **Cascading chain delete** — deleting a chain removes its step links too, so no dead rows.
- **`step` column used everywhere** — clean per-step click stats.

---

## 🧭 Next steps

- **Part 1** (WordPress control layer): [`../ns-link-wp/README.md`](../ns-link-wp/README.md)
- **Repo root**: [`../README.md`](../README.md)

---

## 📜 License

**NS Link — URL Shortener Web App** is licensed under the **GNU General Public License v2.0 or later
(GPL-2.0-or-later)** — the same license as the rest of the NSKWeb/ns repository.

| | |
|---|---|
| **License** | GNU GPL **v2.0 or later** (GPL-2.0-or-later) |
| **File** | [`shortener/LICENSE`](LICENSE) — canonical GNU text, identical to repo root [`LICENSE`](../LICENSE) |
| **Copyright** | © 2026 NSKWeb |
| **Source** | [github.com/NSKWeb/ns](https://github.com/NSKWeb/ns) |

### What you may do

- ✅ Use, run, copy, modify, and **redistribute** the software freely — **even commercially**.
- ✅ Sell it or offer it as a managed service.
- ✅ Study and change the source — the whole app is plain PHP, no obfuscation.

### What you must do

| If you… | You must… |
|---|---|
| **Redistribute** (publish a copy, modified or not) | Provide the **source code** under the same **GPL** license, include a **copy of the license** + **copyright notice** |
| **Modify and distribute** | Release your changes under **GPL-2.0-or-later**, keep attribution, document that you changed files |
| **Run it as a service** (hosted SaaS) | Running it for users is your right — GPL's source obligations trigger on **distribution**, not on running it yourself |
| **Combine with other software** | The combined work must be GPL-compatible (see compatibility note) |

### The short version (plain English)

> NS Link is **free software**: you get it for free and can do almost anything with it, but if you
> hand it to someone else — even in modified form — you have to hand them the **source** too, under
> the same license, with the license text and copyright notice intact. There is **no warranty**:
> it comes "AS IS".

### Attribution (recommended, not required)

A little line in your README or site footer keeps the project discoverable:

```
NS Link — URL shortener + chain generator (GPL-2.0-or-later) — https://github.com/NSKWeb/ns
```

### GPL compatibility

GPL-**v2-or-later** code can be combined with other GPL-v2-or-later and GPL-v3 projects, per the
"or later" clause. It is **not** compatible with proprietary/closed-source licenses — a modified /
derivative version must stay GPL.