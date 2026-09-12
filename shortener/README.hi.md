# 🧷 NS Link — URL Shortener Web App (Part 2) — Hinglish

> **Ek short link, editorial chain of task pages, ek final destination.**
> Self-hosted URL shortener + chain generator — **vanilla PHP + SQLite**, koi framework nahi, koi composer nahi. Folder kisi bhi PHP host par drop karo, chal jayega.

<p align="center">
  <img src="../assets/brand/ns-link-shortener-flow.svg" alt="NS Link shortener — chain engine flow" width="900">
</p>

**Yeh [NSKWeb/ns](../README.md) repo ka hissa hai** (main README → Part 2). Visual voice wahi hai jo WordPress plugin ka hai: *literary neo-brutalism* — paper background, ink lines, vermilion + ochre accents, serif headlines + monospace labels.

> 🌐 **Language:** Hinglish — yeh page · [**English**](README.md)

---

## 🧪 Live demo

> ⚠️ **Demo ek temporary all-hands container URL par chalta hai** — jab aap kholo tab tak ho nahi sakta. Jab chalta hai tab real app + real SQLite DB dikhata hai, aur `shortener/data/` delete karke kabhi bhi reset kar sakte ho.

**👉 Abhi try karo:** [**work-2 … all-hands.dev**](https://work-2-axtomacioldgqets.prod-runtime.all-hands.dev/)
*(backup mirror: [**work-1 … all-hands.dev**](https://work-1-axtomacioldgqets.prod-runtime.all-hands.dev/))*

- **Chain code** — [`/go/qTqvVFAZ`](https://work-2-axtomacioldgqets.prod-runtime.all-hands.dev/go/qTqvVFAZ) → step-1 task page (timer) → Continue → … → final "Open Your Link"
- **Direct link** — [`/go/fPfMju`](https://work-2-axtomacioldgqets.prod-runtime.all-hands.dev/go/fPfMju) → 302 to `https://example.com/direct`
- **Admin** — [`/admin/login.php`](https://work-2-axtomacioldgqets.prod-runtime.all-hands.dev/admin/login.php) → `admin` / `ns-admin-2026` *(login karte hi change karo!)*

### Screenshots

<details open>
<summary>📸 Live-demo screen recording stills (default open)</summary>

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

## 🗺️ Yeh app kya karta hai

| Aapko milta hai | Kaise kaam karta hai |
|---|---|
| **Short links** | `/go/<code>` → turant **302 redirect** kisi bhi URL par, click logged |
| **Chain generator** | Admin form se **N step URLs + ek final destination**; har step ek task page ban jata hai |
| **Timed task pages** | Timer scroll par start → **Continue** button unlock → next step (ya last par destination) |
| **Click stats** | Har step view `clicks` table me logged; per-chain / per-step totals admin **Stats** me |
| **SQLite, single file** | DB auto-create (`data/ns-link.sqlite`, WAL mode), schema first run par auto-apply |
| **Single admin** | Password-protected panel (`admin/`) — links, chains, stats, password change |

**Ek line me:** `site.com/go/abc123` → visitor tumhare task pages *padhta/browsing karta hai* (ad slots yahan) → timer khatam hote hi **Continue** dabate hain → akhir me destination par pahunchte hain. Har step measured hai.

---

## ✨ Kyu aisa dikhta hai

App repo ke **literary neo-brutalism** house style ko inherit karti hai:

- 🧷 paper `#F7F1E5` canvas, ink `#1C1A17` rules, vermilion `#E4572E` accents, ochre `#C98A00` labels
- 📰 serif headlines (Georgia) + monospace labels (Courier), hard 3px borders, grain & registration-mark flavor
- 🧩 Ad slots **empty containers** hain (`.nslink-ad-top`, `.nslink-ad-mid`, `.nslink-ad-foot`) — plugin ya cPanel se ad codes daalo, bilkul Part 1 jaisa

---

## 🚀 Quick start

### Requirements

- PHP **7.4+** (tested **8.x** par) with `pdo`, `pdo_sqlite`, `session`
- `shortener/data/` par write access
- Apache (`mod_rewrite`, `.htaccess` included) **ya** Nginx — ya PHP built-in server local work ke liye

### 60-second run (local)

```bash
cd shortener
php -S 127.0.0.1:8099 router.php
# open http://127.0.0.1:8099/   →  admin:  http://127.0.0.1:8099/admin/login.php
```

> **Important:** **`router.php`** ko router script ke roop me pass karo. Usi se `/go/<code>` clean URLs CLI server par chalte hain. (Router ke bina `-t` `/go/*` ke liye `index.php` par gir jata hai aur shortener tut jata hai.)

### First-login checklist

| Cheez | Default / note |
|---|---|
| Admin user | `admin` (`config.php` → `admin_user`) |
| Admin password | `ns-admin-2026` — **turant change karo!** (`config.php` ya Settings) |
| Step wait | `8` seconds (`config.php` → `default_wait`) |
| Do security toggles | `cookie_secure = true` over HTTPS; `allowed_hosts` optional host-whitelist |

1. Login karo → **Settings → change password**
2. `base_url` `config.php` me set karo agar absolute URLs chahiye (warna auto-detect)
3. Simple link ya chain banao → `/go/<code>` test karo

---

## 🔗 Short links kaise kaam karte hain *(logic, end to end)*

### Teen database tables

`schema.sql` chaar tables banata hai; teen heart hain:

```
links:   id · code · url · title · created_at
chains:  id · code · name · steps_json · created_at · updated_at
clicks:  id · link_id · chain_id · step · ip · user_agent · referer · created_at
```

- **`links`** — har redirect target. `code` random 7-char base-string hai (`[A-Za-z0-9]`), unique.
- **`chains`** — chain bas ek **name + JSON of steps** hai. Har step `{ url, wait, link_id }` rakhta hai; admin form N steps banata hai aur ek JSON document me store karta hai.
- **`clicks`** — har short link ya step page view par ek row. `step` column chain ke andar step number hai.

### `/go/abc123` ka request lifecycle

```
GET /go/abc123
        │
        ▼
   router.php / .htaccess  →  go.php?code=abc123
        │
        ▼
   go.php  →  sanitize code ([^A-Za-z0-9] stripped)
        │
        ├─ 1. Plain link?   ─▶ click log → 302 → target URL
        │
        ├─ 2. Chain step link?  ─▶ click log → task page render (2xx)
        │                       (url me chain=<c>&step=<n>)
        │
        ├─ 3. Chain code?   ─▶ click log → 302 → pehle step ka short link
        │
        └─ else  ─▶ 404 Not found
```

**Order zaroori hai.** `go.php` isi sequence me check karta hai: pehle plain link, phir chain-step link (URL jis me `chain=…&step=…` hai), phir chain code (jo step 1 par redirect karta hai). Isliye chain code kabhi step link ko shadow nahi karta, aur `/go/<step-code>` task page render kar sakta hai *bina* browser-navigation change ke.

### Chain JSON

Jab tum **Create chain** dabate ho, admin ye banata hai:

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

- Har step URL ko **apna short link** milta hai (`link_id`) — toh agar visitor sirf us step ka short URL directly kholta hai, toh bhi step track kar sakte ho.
- Final destination **short-hand nahi hoti**; woh chain ki `dest` hai aur sirf last Continue dabane par reveal hoti hai.

### Task page mechanics

Task page **`chain.php → nslink_chain_render()`** (ya `task.php?chain=<c>&step=<n>`) render karta hai. Page me:

- **countdown ring** (`data-wait` seconds) + progress bar
- step number / total (e.g. *Step 2 / 4*)
- ad network ke liye empty **ad-slot containers**
- **Continue** button — timer khatam hone tak disabled

**Trigger logic (`assets/js/task.js`):**

- Timer **first scroll par start** hota hai (`scrollY > 40`) — jo reader page niche scroll karta hai woh *real* reader hai.
- Har second: `remaining−−`, countdown + progress bar update.
- `remaining <= 0` par: button enable (`disabled=false`, `.active`), timer class `done`.
- Click par button `data-next` (next step URL) ya `data-dest` (final destination) padhta hai aur navigate karta hai.
- **URL me `?skip=1`** wait chhota karta hai (local QA / smoke test me use hota hai).

**Button behavior position se:**

| Position | `data-*` | Behavior |
|---|---|---|
| Non-final step | `data-next="/task.php?chain=…&step=n+1"` | Continue → next task page |
| Final step | `data-dest="https://offer…"` | **Open Your Link** → destination (usually new tab) |

Toh 4 steps wali chain bilkul hero diagram jaisa behave karti hai: *short-link → task-1 → task-2 → task-3 → task-4 → destination*.

### Click logging

Har step short-link ya task-page request `clicks` row likhti hai:

```php
nslink_log_click($linkId, $chainId, $step);   // inside go.php / chain.php
```

- **Kabhi page block nahi karta** — logging non-fatal hai (`try/catch`), toh storage hiccup se bhi redirect 500 nahi hoga.
- `ip`, `user_agent`, `referer` as-is store hote hain — inhe private data samjho (neeche **Security** dekho).

### Stats page

`admin/stats.php` `clicks` ko chain aur step ke hisaab se group karta hai:

- Per-chain totals + per-step totals (`step` column se).
- Koi aggregate pipeline nahi; bas kuch `SELECT COUNT(*) … GROUP BY` queries, usi neo-brutalist style me table-ized.

---

## 🛠️ Deployment — step by step (apna host choose karo)

Usi `shortener/` folder ko chalane ke raste. Apni hosting ke hisaab se chuno.

| Route | Skills needed | Best for |
|---|---|---|
| **A. PHP built-in server** | Sirf terminal | Local testing, quick demo |
| **B. Shared hosting (Apache / cPanel)** | FTP/File Manager se file upload | InfinityFree, 000webhost, Hostinger, … |
| **C. cPanel par subdomain** | cPanel → subdomain banao | Live vanity domain (`go.yourdomain.com`) |
| **D. VPS + Nginx** | Basic Linux + Nginx | Full control, high traffic |
| **E. Local (XAMPP/WAMP/Laragon)** | Desktop installer | Apni machine par offline editing |

> **Har route ke liye ek rule:** `data/` folder **PHP ke liye writable** hona chahiye, aur web server `/go/<code>` ko `go.php` par route kare. Route A `router.php` use karta hai; routes B–E included `.htaccess` (Apache) ya chhoti rewrite config (Nginx) use karte hain.

### Option A — PHP built-in server (local, 60 seconds)

```bash
cd shortener
php -S 127.0.0.1:8099 router.php
```

- Open <http://127.0.0.1:8099/> → landing page
- Admin: <http://127.0.0.1:8099/admin/login.php>
- **`router.php` pass karna zaroori hai** — bina iske `/go/<code>` resolve nahi hoga
  (built-in server `/go/*` ko `index.php` bhej dega instead of `go.php`).

LAN par localhost se aage expose karne ke liye:

```bash
php -S 0.0.0.0:8099 router.php
# phir visit karo http://YOUR_LAN_IP:8099/
```

### Option B — Shared hosting (Apache, e.g. InfinityFree / 000webhost / Hostinger)

1. Repo ka **zip download** karo (`Code ▾ → Download ZIP`) aur extract karo.
2. **Sirf `shortener/` folder** apni site root me rakho, e.g. `htdocs/` ya `public_html/`:
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
   *(Ya app ka content seedha `public_html/` root par upload karo agar app domain root par chahiye.)*
3. **Permissions:** paka karo ki `shortener/data/` PHP ke liye writable hai (folders ke liye usually `755`, files `644`; cPanel par `data/` ko `755` ya `775` set karo). cPanel/FTP par `data/` abhi exist na kare — first run par automatically ban jata hai (PHP ko `mkdir` ki permission honi chahiye).
4. `.htaccess` file me `/go/<code>` rewrite pehle se hai — check karo host `.htaccess` block to nahi karta. Agar **har page par 500** aaye, neeche **Troubleshooting** dekho.
5. `https://yourhost/shortener/admin/login.php` (ya root par `/admin/login.php`) kholo aur defaults se login karo. **Password turant change karo.**

> 💡 **Domain-root install:** agar `https://yourdomain.com/go/abc` chahiye (instead of `https://yourdomain.com/shortener/go/abc`), toh `shortener/` ke **contents** ko `public_html/` me upload karo (wrapping `shortener/` folder mat rakho). Baaki sab same.

### Option C — cPanel subdomain (nice vanity link)

1. cPanel → **Subdomains** → `go.yourdomain.com` banao, document root
   `public_html/shortener` (ya jahan folder upload kiya).
2. `shortener/` ke contents wo document root me **File Manager** ya FTP se upload karo.
3. cPanel → **PHP** ya **MultiPHP** me PHP **7.4+ / 8.x** select karo aur extensions confirm karo:
   `pdo`, `pdo_sqlite`, `session`.
4. `data/` writable set karo (Option B step 3).
5. `https://go.yourdomain.com/admin/login.php` kholo → login → password change.
6. Optional: `config.php` me `base_url` ko `https://go.yourdomain.com` set karo agar fully-qualified admin links chahiye (app host auto-detect karti hai, toh usually zaroori nahi).

### Option D — VPS with Nginx

Nginx `.htaccess` samajhta nahi, toh apne server block me rewrite add karo:

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

Phir Nginx reload karo aur data dir ki ownership theek karo:

```bash
sudo systemctl reload nginx
sudo chown -R www-data:www-data /var/www/shortener/data
```

### Option E — Local desktop (XAMPP / WAMP / Laragon)

1. XAMPP install karo → `shortener/` folder `htdocs/` me rakho.
2. Apache start karo. `http://localhost/shortener/admin/login.php` kholo.
3. Same checklist: password change, test link banao.

---

## ✅ First-run setup (sab routes)

Jab first PHP request app par aati hai, `db.php`:

1. `shortener/data/` missing par create karta hai.
2. `shortener/data/ns-link.sqlite` kholta hai (SQLite, WAL mode).
3. `schema.sql` apply karta hai (idempotent `CREATE TABLE IF NOT EXISTS`) — `links`, `chains`,
   `clicks`, `settings` banati hai, aur default settings seed karta hai.

Toh **koi `php migrate` step nahi** — first page visit DB boot kar deta hai.

**First-run checklist** (har item ek baar karo):

- [ ] `/admin/login.php` par defaults se login karo (`admin` / `ns-admin-2026`)
- [ ] **Admin password change karo** (Settings → Change Password, kam se kam 8 chars)
- [ ] Site HTTPS hai toh `cookie_secure = true` `config.php` me set karo (recommended)
- [ ] Optional: `allowed_hosts` ko whitelist set karo jaise `['go.example.com']`
- [ ] **Test simple link** banao → `/go/<code>` kholo → 302 confirm karo
- [ ] **Test chain** banao → `/go/<chain-code>` kholo → saare steps walk karo → final
      "Open Your Link" destination par jata hai confirm karo
- [ ] Kuch visits ke baad **Stats** me click counter aata hai dekho

---

## 🕹️ How to operate — 1-by-1

Admin panel hi poori control room hai. Har screen:

| Screen | URL | Kya karti hai |
|---|---|---|
| **Dashboard** | `admin/index.php` | Simple link banao, chain banao, recent links & chains, quick delete |
| **Chains** | `admin/chains.php` | Saari chains list with **short link** + **chain link** preview, delete (cascades step links) |
| **Links** | `admin/links.php` | Saare short links, copy codes, delete |
| **Stats** | `admin/stats.php` | Per-chain & per-step click counts + last-click time |
| **Settings** | `admin/settings.php` | Admin password change (writes `config.php` via `var_export`) |
| **Logout** | `admin/logout.php` | Session end |

### Step-by-step: simple link banana

1. Login karo → **Dashboard**.
2. *(Optional)* **Label** daalo (e.g. `Offer page`).
3. Destination URL ko **Destination URL** me paste karo (e.g. `https://example.com/offer`).
4. **Create short link** dabao.
5. Success banner me naya code dikhta hai, e.g. `/go/aB3xY9`.
6. `https://yourdomain/go/aB3xY9` share karo — jo kholta hai logged + 302-redirect hota hai.

### Step-by-step: chain banana

1. Login karo → **Dashboard** → **Create Chain**.
2. Chain ko **name** do (optional, e.g. `Autumn campaign`).
3. **Final destination URL** set karo — page jahan last step logon ko bheje.
4. **Step rows** bharo: har row ek URL hai jahan visitor rukna chahiye, **wait** time ke saath
   (seconds) jo Continue button unlock hone se pehle chale. Default me chaar rows dikhti hain.
   - `Step URL` — task page jo woh padhein (article, landing, ad page…).
   - `Wait` — kitne seconds countdown Continue enable hone tak (`1–120`).
5. Need ho toh **+ Add step** dabao fifth (ya zyada) step add karne ke liye.
6. **Create chain** dabao.
7. **Chains** par jao aur nayi entry me do ready-made links dekho:
   - **Short link** — `/go/<first-step-code>` (seedha step 1 par daal deta hai)
   - **Chain link** — `/go/<chain-code>` (canonical code; step 1 ke short link par 302 karta hai)
8. Test karo: **chain link** incognito window me kholo, scroll, timer ka wait karo, Continue
   se saare steps, destination par pahuncho. Phir **Stats** check karo.

### Step-by-step: stats padhna

- **Total clicks** Stats page ke top par dikhta hai.
- Table **chain** se group karti hai, phir **step link**, **step #**, **clicks**, **last click** dikhati hai.
- Click tab recorded hota hai jab `/go/<code>` resolve *ya* task page render hoti hai — 4 steps ki
  chain typically 4–5 clicks per full visitor add karti hai (chain code 302 + har step render).
- Filtering built-in nahi — table last-click time se latest 100 rows dikhati hai.

### Step-by-step: maintain (update / delete)

- **Link delete:** Dashboard → Recent Links → Delete → confirm. (Links page se bhi.)
- **Chain delete:** Dashboard → Recent Chains → Delete, ya **Chains** page → Delete. Yeh
  **cascade** karta hai — har step ka short link bhi delete, toh un step links ki stats bhi remove.
- **Password change:** Settings → current + new + confirm → **Update password**. Naya bcrypt hash
  seedha `config.php` me likha jata hai (`admin_pass_hash`), jo ab `admin_pass` override karta hai.

### Database ka backup & restore

Poori state ek SQLite file me hai (`shortener/data/ns-link.sqlite`). Backup ke liye:

```bash
# Safe online backup (SQLite VACUUM INTO, WAL-aware)
cd shortener
php -r '$db = new PDO("sqlite:data/ns-link.sqlite"); $db->exec("VACUUM INTO \047backup-$(date +%F).sqlite\047");'
# ya site idle hone par bas copy:
cp data/ns-link.sqlite ~/ns-link-backup-$(date +%F).sqlite
```

Restore: file overwrite karo, phir permission theek karo:

```bash
cp ~/ns-link-backup-YYYY-MM-DD.sqlite shortener/data/ns-link.sqlite
chown www-data:www-data shortener/data/ns-link.sqlite   # apne web user se match karo
```

**Reset to clean slate** (fresh demo, koi clicks nahi): site stop, DB delete:

```bash
rm -f shortener/data/ns-link.sqlite shortener/data/ns-link.sqlite-*
# next page load schema + defaults recreate karta hai
```

---

## 🧯 Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| **Har page par 500** | `.htaccess` blocked / `RewriteEngine` allowed nahi (ya galat PHP version) | `.htaccess` present confirm; strict hosts par root par upload karke `index.php` test; PHP 7.4+ ensure |
| **`/go/abc` landing page dikhata hai** | Routing missing (`router.php` pass nahi kiya, ya `.htaccess` ignored) | Built-in server: `php -S ... router.php` chalao. Apache: `.htaccess` allowed confirm |
| **`/go/abc` → 404** | Rewrite active nahi ya code galat typo | Admin → Links/Chains me code check; fresh link se test |
| **`SQLSTATE[HY000] … unable to open database file`** | `data/` writable nahi | `data/` folder ko web user ko `chmod` / `chown`; zaroori ho toh manually `755` se create |
| **Sahi password se bhi login fail** | config me stale `admin_pass_hash` | `config.php` edit → `admin_pass_hash` line hatado, ya Settings se update karo |
| **`php: command not found`** | PHP installed nahi | PHP 7.4+ install (Debian/Ubuntu: `sudo apt install php php-sqlite3`) |
| **`pdo_sqlite` extension missing** | PHP SQLite driver ke bina | `sudo apt install php-sqlite3` ya `php.ini` me `pdo_sqlite` enable; verify `php -m \| grep pdo_sqlite` |
| **HTTPS par session cookie set nahi** | `cookie_secure` false hai | `config.php` me `cookie_secure => true` set karo |
| **Admin "Could not write config file"** | `config.php` writable nahi | `chmod 664 config.php` (ya write permission) phir Settings retry |
| **Stats me clicks badh nahi rahe** | Browser ne redirect cache kiya / ad-block ne request block ki | Incognito try; `data/ns-link.sqlite` badta hai check |

---

## 🔐 Security (production baseline)

| Area | NS Link kya karta hai | Aapko kya karna chahiye |
|---|---|---|
| **Password** | Default `admin` / `ns-admin-2026`; Settings `config.php` me bcrypt hash likhta hai | **First login par change karo.** `config.php` ko web root se bahar rakho jahan ho sake |
| **SQL** | Saare queries **PDO prepared statements** hain (`EMULATE_PREPARES` off) | PHP/SQLite versions patched rakho |
| **Output** | Sab `htmlspecialchars()` se escaped | Admin panel private path / basic-auth ke neeche rakho agar chaaho |
| **Sessions** | HTTP-only, configurable name, `SameSite=Lax` | HTTPS ke peeche deploy; `cookie_secure = true` set |
| **Hosts** | Config me `allowed_hosts` array | Apna real domain whitelist karo host-header attacks se bachane ke liye |
| **Clicks data** | `ip`, `user_agent`, `referer` log karta hai | Yeh personal data hai — retention cleanup add karo agar care (e.g. N din se purani rows delete); privacy policy me mention |
| **Tests** | `tests/` me koi secrets nahi | Production se `tests/` hatado ya access restrict |

---

## 🧪 Tests

`tests/smoke.php` ek **true end-to-end smoke test** hai — running server ke against boot karta hai aur real HTTP behavior assert karta hai:

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

Kya cover karta hai (currently **22 assertions**):

- `GET /` → 200
- defaults se login → 302 → dashboard 200
- chain create → 302 → chains list parse (chain code + first-step short code)
- `/go/<chain-code>` → task page
- `/go/<step-1-code>` → step 1 with correct step/total/wait + Continue button
- Continue **step 2** ki taraf point karta hai (`/task.php?chain=…&step=2`)
- `/task.php` final step → **Open Your Link** → destination ki taraf point
- simple link create → 302 → `/go/<code>` → 302 to target
- `/admin/stats.php` renders

> Test throwaway SQLite DB + cookie jar likhta hai, har run par schema recreate karta hai. Baar-baar chalana safe hai.

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
├── data/              # SQLite DB yahan rehta hai (data/ns-link.sqlite) — runtime, gitignored
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

## 🆕 Naya kya hai & kyu better hai

- **No framework, no composer** — real shared-host drop-in. PHP 7.4+ aur 8.x par chalta hai.
- **Single SQLite file** — backup = ek file copy karo.
- **Auto-booting schema** — zero migration commands.
- **`var_export`-safe config writes** — password hashes exact round-trip (`$2y$…` survive).
- **Cascading chain delete** — chain delete karne par uske step links bhi remove, no dead rows.
- **`step` column har jagah use** — clean per-step click stats.

---

## 🧭 Next steps

- **Part 1** (WordPress control layer): [`../ns-link-wp/README.md`](../ns-link-wp/README.md)
- **Repo root**: [`../README.md`](../README.md)

---

## 📜 License

**NS Link — URL Shortener Web App** **GNU General Public License v2.0 or later
(GPL-2.0-or-later)** ke under licensed hai — bilkul NSKWeb/ns repo ke baaki hisson jaisa.

| | |
|---|---|
| **License** | GNU GPL **v2.0 or later** (GPL-2.0-or-later) |
| **File** | [`shortener/LICENSE`](LICENSE) — canonical GNU text, repo root [`LICENSE`](../LICENSE) jaisa hi |
| **Copyright** | © 2026 NSKWeb |
| **Source** | [github.com/NSKWeb/ns](https://github.com/NSKWeb/ns) |

### Aap kya kar sakte ho

- ✅ Software ko freely use, run, copy, modify, aur **redistribute** karo — **commercially bhi**.
- ✅ Becho ya managed service ke roop me offer karo.
- ✅ Source padho aur change karo — poori app plain PHP hai, koi obfuscation nahi.

### Aapko kya karna hoga

| Agar aap… | Aapko… |
|---|---|
| **Redistribute** (copy publish, modified ho ya nahi) | **Source code** same **GPL** license ke under provide karo, **license ki copy** + **copyright notice** include karo |
| **Modify aur distribute** karo | Apne changes **GPL-2.0-or-later** ke under release karo, attribution rakho, batado ki files change ki |
| **Service ke roop me chalao** (hosted SaaS) | Users ke liye chalana tumhara haq hai — GPL ki source obligations **distribution** par trigger hoti hain, khud chalane par nahi |
| **Dusre software ke saath combine** karo | Combined work GPL-compatible hona chahiye (compatibility note dekho) |

### Short version (simple Hindi)

> NS Link **free software** hai: aap ise free me paate ho aur iske saath almost kuch bhi kar sakte ho, lekin agar ise kisi aur ko dete ho — modified form me bhi — toh unhe **source** bhi dena hoga, same license ke under, license text + copyright notice ke saath. **Koi warranty nahi:** yeh "AS IS" aata hai.

### Attribution (recommended, required nahi)

Apne README ya site footer me ek line the project ko discoverable rakhti hai:

```
NS Link — URL shortener + chain generator (GPL-2.0-or-later) — https://github.com/NSKWeb/ns
```

### GPL compatibility

GPL-**v2-or-later** code dusre GPL-v2-or-later aur GPL-v3 projects ke saath combine ho sakta hai, "or later" clause ke through. Yeh **proprietary/closed-source licenses ke saath compatible nahi** — modified/derivative version GPL hi rehna chahiye.
