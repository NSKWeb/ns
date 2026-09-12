<p align="center">
  <img src="assets/brand/ns-link-masthead.svg" alt="NS Link masthead" width="880">
</p>

<p align="center"><b>WP Safelink ka <span style="color:#E4572E">FREE</span> alternative</b> - literary neo-brutalism / editorial design ke saath. <em>One link, four readings, ad-funded press run.</em></p>

<p align="center">
  <a href="https://github.com/NSKWeb/ns"><img src="https://img.shields.io/badge/GitHub-NSKWeb%2Fns-1C1A17?logo=github&style=for-the-badge&labelColor=F7F1E5" alt="GitHub - NSKWeb/ns"></a>
  &nbsp;
  <a href="shortener/README.md"><img src="https://img.shields.io/badge/Part2-URL%20shortener-E4572E?style=for-the-badge&labelColor=F7F1E5" alt="Part 2 - URL shortener"></a>
  &nbsp;
  <a href="https://work-2-axtomacioldgqets.prod-runtime.all-hands.dev/"><img src="https://img.shields.io/badge/Live%20demo-NS%20Link-C98A00?style=for-the-badge&labelColor=F7F1E5" alt="Live demo"></a>
</p>
> 🌐 **Language:** Hinglish — yeh page · [**English**](README.en.md)

<div align="center">

| | |
|---|---|
| 🕰️ | **Control layer** - timer ring, "Scroll Down to Continue", Continue / Open-Your-Link button |
| 🧩 | **Ad-slot containers** - empty divs, WP/cPanel se bharte hain |
| 📰 | **Editorial design** - masthead branding, grain, reg-marks - literary neo-brutalism |
| 💰 | **100% FREE, GPL, koi backend nahi** |

</div>

<p align="center">
  <img src="assets/brand/ns-link-flow.svg" alt="NS Link flow" width="880">
</p>

---

## Repo structure - press room

| Path | Kya hai |
|---|---|
| `ns-link-wp/` | **WordPress plugin (Part 1)** - control layer + settings |
| `shortener/` | **🧷 URL shortener web app (Part 2)** — PHP + SQLite, chain generator + single admin panel (links / chains / stats / settings). Flow diagram: [`ns-link-shortener-flow.svg`](assets/brand/ns-link-shortener-flow.svg). Docs: [`shortener/README.md`](shortener/README.md) |
| `assets/brand/` | SVG brand assets — masthead, Part-1 flow, **Part-2 shortener flow**, palette, badges |
| `assets/screenshots/` | **Live-demo screenshots** (landing, task page, final step, admin dashboard, stats) |
| `preview-ns-link.html` | Full editorial article page (design reference) |
| `preview-ns-link-simple.html` | Sirf control layer preview (no article) |
| `preview-ns-link-flow.html` | Side-by-side flow template (P1-3 vs P4) |
| `test-ns-link-layer.html` | **Live test harness** - timer chalta hai |
| `README.md` | Yeh page - Hinglish quick start + deployment guide |
| `README.en.md` | Full English version of the README |

---

## House style - palette

<p align="center">
  <img src="assets/brand/ns-link-palette.svg" alt="NS Link palette" width="880">
</p>

---

## Plugin - quick start

```
1. Folder /wp-content/plugins/ me copy karo (ya plugin zip upload karo)
2. Activate "NS Link - Control Layer"
3. Settings > NS Link > defaults set karo
4. Pages banao (task-1 ... task-4) aur chain URL se kholo:
```

```text
https://site.com/task-1/?step=1&total=4&wait=8&next=<task-2 URL>
https://site.com/task-2/?step=2&total=4&wait=8&next=<task-3 URL>
https://site.com/task-3/?step=3&total=4&wait=14&next=<task-4 URL>
https://site.com/task-4/?step=4&total=4&wait=10&done=1&dest=<destination URL>
```

> Ad slots plugin ke **empty divs** hain (`.nslink-ad-top`, `.nslink-ad-mid`, `.nslink-ad-foot`) - unme ad codes WordPress (Ad Inserter/Gutenberg) ya cPanel se daalein. Detail: [`ns-link-wp/README.md`](ns-link-wp/README.md).

---

## Why NS Link

<p align="center">
  <img src="assets/brand/ns-link-badges.svg" alt="NS Link why" width="880">
</p>

---

## Deployment Guide - step by step

### Step  0 - Repo download / clone

**Option A - ZIP download (no git needed):**
1. GitHub repo khulo: https://github.com/NSKWeb/ns
2. Green **"Code"** button -> **"Download ZIP"** -> zip extract karo.

**Option B - Git clone (recommended):**
```bash
git clone https://github.com/NSKWeb/ns.git
cd ns
```

### Step 1 - WordPress par plugin install karo

1. Andar ka folder `ns-link-wp` ko zip karo:
   ```bash
   zip -r ns-link-wp.zip ns-link-wp/
   ```
2. WordPress admin -> **Plugins -> Add New -> Upload Plugin**.
3. `ns-link-wp.zip` choose karo -> **Install Now** -> **Activate**.
4. YA cPanel rasta: `public_html/wp-content/plugins/` me `ns-link-wp` folder upload karo -> WP admin -> Plugins -> **Activate**.

> Folder ka naam `ns-link-wp` hi rahe (zip me root folder ho, WP usi par depend karta hai).

### Step 2 - Plugin settings set karo

1. WP admin -> **Settings -> NS Link**.
2. Defaults set karo:
   - **Default timer** - 8 seconds (ya apni pasand)
   - **Line - pages 1-3** - `Scroll Down to Continue`
   - **Line - final page** - `Continue to Link`
   - **Button - continue** - `Continue ->`
   - **Button - final** - `Open Your Link ->`
   - **Theme mode** - `Custom (Literary Neo-Brutalism)` (ya `Auto` agar aapke WP theme ke colors se match karna ho)

   - Baaki toggles ON rakho (ad slots, sticky bar, auto-scroll, progress rule)
3. **Save Changes**.

### Step 3 - Apne 4 ad pages banao (WordPress):

1. WP admin -> **Pages -> Add New** -> `task-1` banao (article content + images, jaise pehle decide kiya tha).
2. Article page par **ad codes** daalo:
   - **Ad Inserter** plugin (free) se har ad position control karo, ya
   - Gutenberg me **Custom HTML** block, ya
   - **cPanel** se server-side header/footer inject

3. Isi tarah `task-2`, `task-3`, `task-4` banao.

> Plugin ke **empty ad-slot containers** unme bhi ad codes daal sakte ho - class names: `.nslink-ad-top`, `.nslink-ad-mid`, `.nslink-ad-foot`. Container ke andar ad code rakho aur container ko visible karo.

### Step 4 - Chain URL banao aur test karo

Chain URL ka pattern (README ke upar wala example dekho):
```bash
# Page  1
https://site.com/task-1/?step=1&total=4&wait=8&next=https%3A%2F%2Fsite.com%2Ftask-2%2F%3Fstep%3D2%26total%3D4%26wait%3D8%26next%3D...
# Page 2, 3 - wahi pattern, step+=1
# Page  4 (final)
https://site.com/task-4/?step=4&total=4&wait=10&done=1&dest=https%3A%2F%2Fdestination-link.com%2Foffer
```

> `next` aur `dest` URLs ko **URL-encode** karna zaroori hai (agar unme bhi `?` params hain - `%3F` = `?`, `%26` = `&`, `%2F` = `/`). Koi bhi URL-encoder tool use karo (ya Python: `python3 -c "import urllib.parse; print(urllib.parse.quote('https://...', safe=''))"`).

1. Pehle **sirf task-1 kholo** - control layer dikhegi (timer chalta hua, ad slots, "Scroll Down to Continue"). Jab timer khatam hoga, sticky Continue button active ho jayega.
2. Chain URLs ko test karo (ya `?skip=1` daal do - timer 1 second ho jata hai fast testing ke liye).
3. Final page par `Open Your Link ->` dabao -> destination khulna chahiye.

### Step 5 - Live dekho (preview files):

Preview/design files isi repo me hain - direct browser me kholo ya kisi free static hosting par daal do:
```bash
# Local test
cd ns
python3 -m http.server 8080
# -> http://localhost:8080/test-ns-link-layer.html
```
Free hosting (Netlify Drop / Vercel / GitHub Pages): repo ka root folder drag-drop karo -> live link mil jayega -> `preview-ns-link-flow.html` etc. dekh sako.

### Step 6 - Part 2 (URL shortener web app) deploy

**Part 2 ab isi repo me `shortener/` folder me hai** — ek self-contained **PHP + SQLite URL shortener + chain generator**. Full docs + **step-by-step deploy/host/operate guide**: [`shortener/README.md`](shortener/README.md) (English) / [`shortener/README.hi.md`](shortener/README.hi.md) (Hinglish). License: **GPL-2.0-or-later** (`shortener/LICENSE`).

#### 🧪 Live demo (container preview)

> The demo runs on a temporary all-hands container URL — it may not always be up. Admin login: `admin` / `ns-admin-2026` *(change it soon)*. Fresh DB, reset-friendly.

| What | Link |
|---|---|
| **App root (primary)** | [work-2 … all-hands.dev](https://work-2-axtomacioldgqets.prod-runtime.all-hands.dev/) |
| **App root (backup)** | [work-1 … all-hands.dev](https://work-1-axtomacioldgqets.prod-runtime.all-hands.dev/) |
| **Chain flow** | [chain `qTqvVFAZ`](https://work-2-axtomacioldgqets.prod-runtime.all-hands.dev/go/qTqvVFAZ) — 4 timed task steps → "Open Your Link" |
| **Direct link** | [direct `fPfMju`](https://work-2-axtomacioldgqets.prod-runtime.all-hands.dev/go/fPfMju) — 302 to example.com |
| **Admin panel** | [login](https://work-2-axtomacioldgqets.prod-runtime.all-hands.dev/admin/login.php) |

<details open>
<summary>📸 Screenshots (live app) — open by default</summary>

| Landing | Task page | Final step | Dashboard | Stats |
|---|---|---|---|---|
| <img src="assets/screenshots/demo-landing.png" alt="Landing" width="400"> | <img src="assets/screenshots/demo-task-page.png" alt="Task page" width="400"> | <img src="assets/screenshots/demo-final-step.png" alt="Final step" width="400"> | <img src="assets/screenshots/demo-dashboard.png" alt="Dashboard" width="400"> | <img src="assets/screenshots/demo-stats.png" alt="Stats" width="400"> |

</details>

<p align="center">
  <img src="assets/brand/ns-link-shortener-flow.svg" alt="NS Link shortener - chain engine flow" width="880">
</p>

**Kya karta hai (ek line me):** `site.com/go/abc123` → visitor task pages par rukta hai (ad slots) → timer khatam → **Continue** → final step par **Open Your Link** → destination. Har step ka click logged hota hai.

**Logic — `/go/<code>` ka flow:**

| Step | Kya hota hai |
|---|---|
| 1. Plain link? | `links` table me direct URL mila → click logged → **302 redirect** to target |
| 2. Chain-step link? | URL me `chain=<c>&step=<n>` hai → click logged → **task page render** (timed) |
| 3. Chain code? | `chains` table me chain mila → click logged → **302 redirect** to pehle step ka short link |
| 4. Kuch nahi | **404 Not Found** |

**Deploy rasta** (InfinityFree / 000webhost / koi bhi PHP host):
```bash
1. `shortener/` folder ko hosting ke `htdocs/` me upload karo
2. Domain point karo -> `site.com/go/<short-code>` se chain flow chalega
3. Admin panel `site.com/admin/` se links + chains + timer + destination set karo
4. Login hone ke baad turant default password badal do (Settings me)
```

**Local test ke liye** PHP built-in server (bina install):
```bash
cd shortener
php -S 127.0.0.1:8099 router.php
# -> http://127.0.0.1:8099/   (admin: /admin/login.php)
```

> **Smart table** — `links` (redirects), `chains` (steps JSON), `clicks` (per-step hits). SQLite single file, auto-schema, prepared statements. 22/22 smoke tests pass.

### Step 7 - GitHub par changes push karna (baad me):

```bash
cd ns
git add -A
git commit -m "update"
git push origin main
```
(Write-access token/SSH hona chahiye).

---

## Next steps

- **Part 2** ✅ - **URL shortener web app / website** (chain generator + admin panel) — ab isi repo me `shortener/` folder me ready hai. Docs: [`shortener/README.md`](shortener/README.md)
- **Part 3** ⏳ - GitHub Actions se free hosting deploy (optional)

---

## License

**NS Link** — [GPL-2.0-or-later](LICENSE) hai. 📜

| | |
|---|---|
| **License** | GNU General Public License **v2.0 or later** (GPL-2.0-or-later) |
| **File** | [`LICENSE`](LICENSE) — official GNU text (gnu.org se canonical copy) |
| **Kya kar sakte ho** | ✓ Free me use, copy, modify, distribute, sell karo ✓ |
| **Kya nahi kar sakte** | ✗ Adhikar mat chhino (unka GPL hona zaroori) ✗ |
| **Agar distribute karo** | Source code available rakhna hoga + GPL license + copyright notice retain karna hoga |
| **Works — repo ke sab parts par** | Plugin (`ns-link-wp/`), web app (`shortener/`), design assets (`assets/`), previews, docs — sab isi license ke under hain |
| **No warranty** | Ye software "AS IS" hai — koi warranty nahi, koi liability nahi (Section 11, 12) |
| **Compatibility** | GPL-v2-or-later free software ke saath compatible. GPL-v3 projects ke saath bhi (compatibility clause se) |

**Sahi tarika — attribution + license notice:**

Agar tum NS Link ko use karte ho ya distribute karte ho, to:

1. **Copyright + license notice** rakho — source files me (headers `ns-link.php` me already hai: `License: GPL-2.0-or-later`).
2. **LICENSE file** unke saath rakho — ya link karo is repo ke LICENSE par.
3. **Source code offer karo** — agar tum modified/binary version distribute karte ho, to usi license (GPL) ke under **source code available** rakhna mandatory hai.
4. **"AS IS"** — koi bhi claim mat karo ki guarantee hai; software free me milta hai, isliye iska koi warranty nahi.

| Author | NSKWeb |
|---|---|
| Copyright | © 2026 NSKWeb |
| License | [GPL-2.0-or-later](LICENSE) |
