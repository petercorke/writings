# Home page spacing (10 Oct 2026)

`home-spacing.patch` is applied to the theme's `style.css` **after** `../footer/style.css.patch` (it is a diff against the
file as it stands once the footer patch is in). Only `page-home.php` uses these classes.

| Rule | Was | Now |
|---|---|---|
| `.hm-feature` padding | `120px 0` | `56px 0 0` (no bottom padding) |
| `.hm-feature + .hm-feature` padding-top | n/a | `56px` (the gap between successive sections) |
| `h2.hm-feature-title` margin-bottom | `80px` | `36px` |
| `.hm-posts` padding | `80px 0` | `56px 0 48px` |
| phone (`max-width` query) | `.hm-feature{padding:70px 0}` | unchanged; a matching `.hm-feature + .hm-feature{padding-top:70px}` keeps it so, because the new sibling rule would otherwise out-rank it |

Result at 1040px wide: the home page goes from 3563px to 2779px; hero (860px, absolutely positioned logo and text) untouched.
The hero cannot simply be shortened: at 620px the logo's `.com` overlaps the tagline.

Backup on the server: `~/backups-20260928/style.css.before-home-spacing-20261010`.
