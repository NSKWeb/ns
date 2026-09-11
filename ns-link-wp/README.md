# NS Link — Control Layer (WordPress)

WP Safelink ka **FREE alternative**. Ek simple WordPress page/post par ek "control layer" inject karta hai: **timer ring**, "Scroll Down to Continue" hint, **Continue / Open-Your-Link button**, **ad-slot containers**, **edition indicator**. Ads aur article content aapke apne WordPress / cPanel se control hote hain. Plugin sirf control layer hai — **koi ad code nahi, koi article nahi, koi backend nahi.**

## Kya hai (features)

- **Timer ring** — SVG ring + mono numeral. Har page ka waqt alag set karo via URL: `?wait=14` (ya settings se default).
- **Scroll-down hint** — Timer khtm hote hi line dikhti hai: "⬇ Scroll Down to Continue"
- **Continue / Open-Your-Link button** — Pages 1–3 par `Continue →` (agla page), final page par `🎁 Open Your Link →` (destination).
- **Final page auto-scroll** — Timer khtm hote hi button tak khud scroll.

- **Ad-slot containers** — Empty divs (`.nslink-ad-top`, `.nslink-ad-mid`, `.nslink-ad-foot`) jo WP/cPanel se ad codes se bhare jaate hain..
- **Edition indicator** — "EDITION 02 / 04" masthead me, user ko pata rahe kitne page bache hain..
- **Sticky bar (mobile)** — Continue button mobile par hamesha neeche chipka rahta hai..
- **Progress rule** — Timer ke saath bharne wali patli rule (pages 1–3).
- **Design** — Literary Neo-Brutalism / editorial: Fraunces + Space Grotesk + IBM Plex Mono, cream paper, ink, vermilion, ochre, grain, reg-marks. Athwa **Auto theme mode** — aapke WP theme ke colors se match karo..



## Install

1. `/wp-content/plugins/ns-link-wp/` me is folder ko copy karo (ya plugin zip upload karo..
2. WordPress admin → **Plugins** → activate **"NS Link — Control Layer"**.
3. Settings → **NS Link** — defaults set karo (default timer, labels, theme mode, ad slots toggle..



## Kaise use karein

Control layer **sirf tab dikhti hai** jab page URL par `step`/`next`/`done` params hon. Direct visits par plugin chup rehta hai..

### Pages 1–3 (simple ad pages:

```
https://yoursite.com/task-1/?step=1&total=4&wait=8&next=https://yoursite.com/task-2/?step=2&total=4&wait=8&next=...
```

- `step` — kaunsa page hai (01/04..
- `total` — kitne pages hain (4..
- `wait` — timer seconds (8 / 14..
- `next` — agla page URL (Continue button wahan le jaata hai..



### Final page 4 (destination:

```
https://yoursite.com/task-4/?step=4&total=4&wait=10&done=1&dest=https://destination-link.com/offer
```

- `done=1` — final page mode (line "Continue to Link" + button "🎁 Open Your Link"..
- `dest` — destination URL jahan final button le jaata hai..



### Dev testing (skip timer:

```
...?step=1&total=4&wait=8&skip=1&next=...
```

Timer sirf 1 second — testing fast.



## Ad slots — kaise bharein

Plugin **sirf empty containers** deta hai:

```html
<div class="nslink-ad nslink-ad-top nslink-ad-1"></div>
<div class="nslink-ad nslink-ad-top nslink-ad-2"></div>
<div class="nslink-ad nslink-ad-mid nslink-ad-mid-up"></div>
<div class="nslink-ad nslink-ad-mid nslink-ad-mid-down"></div>
<div class="nslink-ad nslink-ad-foot"></div>
```

**Tarika A — WordPress me (Ad Inserter / HTML block:** us class par ad code daalo.

**Tarika B — cPanel me (server-side:** is HTML ko edit karke ad codes direct daal do — kyunki skeleton `wp_footer` se aata hai, aap theme file me filter/override se bhi ad daal sakte ho..



## URL params ka summary

| Param | Kya karta hai |
|---|---|
| `step` | Edition number (01/04) |
| `total` | Total pages (4) |
| `wait` | Timer seconds (3–120) |
| `next` | Continue button ka agla page |
| `done` | Final mode ON (=1) |
| `dest` | Final button ka destination URL |
| `skip` | Dev-only fast timer (=1) |

## License

**NS Link plugin** — [GPL-2.0-or-later](../LICENSE).

| | |
|---|---|
| **License** | GNU General Public License v2.0 or later (GPL-2.0-or-later) |
| **Copyright** | © 2026 NSKWeb |
| **Kya kar sakte ho** | Free me use, copy, modify, distribute karo (commercial bhi) |
| **Condition** | Source code + license notice apne users ko dena (jab distribute karo) |
| **Warranty** | Koi nahi — software "AS IS", as per GPL Sections 11–12 |

**Note:** Plugin ke saath distribute karte waqt [`LICENSE`](../LICENSE) file copy rakhna best practice hai — WordPress repo guidelines bhi yehi kehte hain..
