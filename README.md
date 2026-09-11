<p align="center">
  <img src="assets/brand/ns-link-masthead.svg" alt="NS Link masthead" width="880">
</p>

<p align="center"><b>WP Safelink ka <span style="color:#E4572E">FREE</span> alternative</b> - literary neo-brutalism / editorial design ke saath. <em>One link, four readings, ad-funded press run.</em></p>

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
| `assets/brand/` | SVG brand assets - masthead, flow diagram, palette, badges |
| `preview-ns-link.html` | Full editorial article page (design reference) |
| `preview-ns-link-simple.html` | Sirf control layer preview (no article) |
| `preview-ns-link-flow.html` | Side-by-side flow template (P1-3 vs P4) |
| `test-ns-link-layer.html` | **Live test harness** - timer chalta hai |
| `README.md` | Yeh page - quick start + deployment guide |

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



**Option B - Git clone (recommended:**
```bash
git clone https://github.com/NSKWeb/ns.git
cd ns
```

### Step Step  1 - WordPress par plugin install karo

1. Andar ka folder `ns-link-wp` ko zip karo:
   ```bash
   zip -r ns-link-wp.zip ns-link-wp/
   ```
2. WordPress admin -> **Plugins -> Add New -> Upload Plugin**.
3. `ns-link-wp.zip` choose karo -> **Install Now** -> **Activate**.
4. YA cPanel rasta: `public_html/wp-content/plugins/` me `ns-link-wp` folder upload karo -> WP admin -> Plugins -> **Activate**.



> Folder ka naam `ns-link-wp` hi rahe (zip me root folder ho, WP usi par depend karta hai).



### Step Step  2 - Plugin settings set karo

1. WP admin -> **Settings -> NS Link**.
2. Defaults set karo:
   - **Default timer** - 8 seconds (ya apni pasand.
   - **Line - pages  1-3** - `Scroll Down to Continue`
   - **Line - final page** - `Continue to Link`
   - **Button - continue** - `Continue ->`
   - **Button - final** - `Open Your Link ->`
   - **Theme mode** - `Custom (Literary Neo-Brutalism)` (ya `Auto` agar aapke WP theme ke colors se match karna ho)

   - Baaki toggles ON rakho (ad slots, sticky bar, auto-scroll, progress rule.
3. **Save Changes**.



### Step Step  3 - Apne 4 ad pages banao (WordPress:




1. WP admin -> **Pages -> Add New** -> `task-1` banao (article content + images, jaise pehle decide kiya the.
2. Article page par **ad codes** daalo:
   - **Ad Inserter** plugin(free se har ad position control karo, ya
   - Gutenberg me **Custom HTML** block,, ya
   - **cPanel** se server-side header/footer inject.

3. Isi tarah `task-2`, `task-3`, `task-4` banao.



> Plugin ke **empty ad-slot containers** unme bhi ad codes daal sakte ho - class names: `.nslink-ad-top`, `.nslink-ad-mid`, `.nslink-ad-foot`. Container ke andar ad code rakho aur container ko visible karo(.





### Step Step  4 - Chain URL banao aur test karo

Chain URL ka pattern ((README ke upar wala example dekho):
```bash
# Page  1
https://site.com/task-1/?step=1&total=4&wait=8&next=https%3A%2F%2Fsite.com%2Ftask-2%2F%3Fstep%3D2%26total%3D4%26wait%3D8%26next%3D...
# Page  2,  3 - wahi pattern,, step+=1
# Page  4 (final)
https://site.com/task-4/?step=4&total=4&wait=10&done=1&dest=https%3A%2F%2Fdestination-link.com%2Foffer
```



> `next` aur `dest` URLs ko **URL-encode** karna zaroori hai (agar unme bhi `?` params hain - `%3F` = `?`, `%26` = `&`, `%2F` = `/`). Koi bhi URL-encoder tool use karo(ya Python: `python3 -c "import urllib.parse; print(urllib.parse.quote('https://...', safe=''))"`)。



1. Pehle **sirf task-1 kholo** - control layer dikhegi( timer chalta hua,, ad slots,, "Scroll Down to Continue"-> jab timer khtm hoga,, sticky Continue button active ->
2. Chain URLs ko test karo( ya `?skip=1` daal do - timer 1 second fast testing ke liye.3
3. Final page par `Open Your Link ->` dabao -> destination khulna chahiye.



### Step Step  5 - Live dekho( preview files:

Preview/design files isi repo me hain - direct browser me kholo ya kisi free static hosting par daal do:
```bash
# Local test
cd ns
python3 -m http.server 8080
# -> http://localhost:8080/test-ns-link-layer.html
```
Free hosting( Netlify Drop / Vercel / GitHub Pages): repo ka root folder drag-drop karo -> live link mil jayega -> `preview-ns-link-flow.html` etc.. dekh sako.



### Step Step  6 - Part  2 (shortener app) jab ayega,, deploy

Alag-domain shortener app( PHP) jab isi repo me aayega - deploy rasta( InfinityFree/000webhost:
```bash
1.. shortener app folder ko hosting ke `htdocs/` me upload karo
2.. Domain point karo( short.site)) -> `site.com/go/<short-code>` se flow chain chalega.

3.. Admin panel `your-host/admin.php` se 4 URLs + timer + destination set karo
```
Detail tab ayega( Part 2 commit ke saath.



### Step Step  7 - GitHub par changes push karna( baad me:




```bash
cd ns
git add -A
git commit -m "update"
git push origin main
```
(Write-access token/SSH hona chahiye.



---

## Next steps

- **Part  2** - alag-domain URL shortener app( chain generator + admin panel) - **isi repo me** folder `shortener/` me aayega..
- **Part  3** - GitHub Actions se free hosting deploy optional..

---

## License

GPL-2.0-or-later - free,, hamesha..
