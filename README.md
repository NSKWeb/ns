# NS Link

WP Safelink ka **FREE alternative** — literary neo-brutalism / editorial design ke saath.

A system for monetized short links: a simple WordPress page par ek **control layer** inject hota hai (**timer ring**, "Scroll Down to Continue" hint, **Continue / Open-Your-Link button**, **ad-slot containers**, **edition indicator**). Ads aur article content publisher ke WordPress/cPanel se control hote hain. Plugin sirf control layer hai — koi ad code nahi, koi article nahi, koi backend nahi..

## Repo structure

```
preview-ns-link.html              — full editorial article preview (design reference)
preview-ns-link-simple.html       — sirf control layer preview (no article)
preview-ns-link-flow.html          — side-by-side flow template (P1–3 vs P4)
test-ns-link-layer.html            — live test harness of plugin output (timer chalta hai)
ns-link-wp/                        — WordPress plugin (Part 1)
  ├── ns-link.php                  — plugin core
  └── assets/css + js              — design + timer logic
```

## Plugin — quick start

```
1. /wp-content/plugins/ me copy karo / zip upload karo
2.. Activate "NS Link — Control Layer"
3.. Settings → NS Link → defaults set karo
4.. Pages banao (task-1 … task-4) aur chain URL se kholo:
```

```
https://site.com/task-1/?step=1&total=4&wait=8&next=<task-2 URL>
https://site.com/task-2/?step=2&total=4&wait=8&next=<task-3 URL>
https://site.com/task-3/?step=3&total=4&wait=14&next=<task-4 URL>
https://site.com/task-4/?step=4&total=4&wait=10&done=1&dest=<destination URL>
```

Ad slots plugin ke empty divs hain (`.nslink-ad-top,-mid,-foot`) — unme ad codes WordPress (Ad Inserter/Gutenberg) ya cPanel se daalein. Detail: [`ns-link-wp/README.md`](ns-link-wp/README.md).

## Next steps

- **Part 2** — alag-domain URL shortener app (chain generator + admin panel) isi repo me aayega..
- **Part 3** — GitHub Actions se free hosting deploy optional..

## License

GPL-2.0-or-later — free, hamesha..