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

- **Chain code** — [`/go/qTqvVFAZ`](https://work-2-axtomacioldgqets.prod-runtime.all-hands.dev/go/qTqvVFAZ) → step-1 task page (timer) → Continue → … → final "Open Your Link"
- **Direct link** — [`/go/fPfMju`](https://work-2-axtomacioldgqets.prod-runtime.all-hands.dev/go/fPfMju) → 302 to `https://example.com/direct`
- **Admin** — [`/admin/login.php`](https://work-2-axtomacioldgqets.prod-runtime.all-hands.dev/admin/login.php) → `admin` / `ns-admin-2026` *(change it once you log in!)*

### Screenshots

<details>
<summary>📸 Click to show live-demo screen recording stills</summary>

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

- PHP **7.4+** (tested on **8.x**) with `pdo`, `pdo_sqlite`, `session`, `fileinfo` *(optional)*
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

- Per-chain totals + per-step totals (the `step` column, *not* a `step_num` — the original typo is fixed).
- No aggregate pipeline needed; it's a couple of `SELECT COUNT(*) … GROUP BY` queries, nicely table-ized in the same neo-brutalist style.

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
├── data/              # SQLite DB lives here (data/ns-link.sqlite)
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

## 🔐 Security

- **Change the default password** before going live — Settings or `config.php`.
- **SQL**: all queries use **prepared statements** (PDO, emulated prepares off).
- **Output**: everything is escaped with `htmlspecialchars()`.
- **Password hash**: written back via `var_export` (never `addslashes`) so the `$2y$` bcrypt hash round-trips byte-for-byte.
- **Sessions**: HTTP-only cookies (`session_name` configurable), `cookie_secure` for HTTPS, optional `allowed_hosts` to harden against host-header abuse.
- **`clicks` stores raw IP/UA** — private data by nature. Delete old rows periodically if you care about retention.

---

## 🛠️ Deployment

- **Apache** (InfinityFree / 000webhost / cPanel): upload `shortener/` to `htdocs/`, keep `.htaccess`.
- **Nginx**: rewrite `/go/(?<code>[A-Za-z0-9]+)` → `go.php?code=$code`; map other PHP paths normally.
- **Permissions**: `data/` (and the `data/ns-link.sqlite*` siblings) must be writable by PHP.
- **Production hygiene**: remove or restrict `tests/`, keep `config.php` out of web-accessible paths if possible, always HTTPS.

---

## 📜 License

**GPL-2.0-or-later** — same as the rest of the repo. See the main [LICENSE](../LICENSE).
