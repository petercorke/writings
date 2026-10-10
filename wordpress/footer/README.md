# Footer tidy (10 Oct 2026)

Masters of what was installed on the petercorke.com theme (`zephyr_petercorke`) and in its ACF options.
The server holds copies; keep these in step.

| File | Installed at | What it does |
|---|---|---|
| `footer.php` | `wp-content/themes/zephyr_petercorke/footer.php` | Each icon link gets a hovertip (`title`) and an accessible name (`aria-label`), derived from the link's host because the ACF rows have no label field; `rel="noopener"`; images get `alt=""` |
| `style.css.patch` | applied to the theme's `style.css` | footer `height` 190px -> 100px; wordmark and icons raised 8px (`top: calc(50% - 8px)`) to clear the blue tab; copyright bar padding 25px -> 14px 25px; phone footer padding 50px -> 24px and logo gap 30px -> 16px |
| `orcid.svg`, `dblp.svg` | WordPress media library (attachments 2556, 2557) | White glyphs from Simple Icons (CC0) for the dark footer |

## The icons are data, not template

The icon row is the ACF options repeater `social_media` (sub-fields `logo`, an image, and `link`, a URL), edited in the
database, not in `footer.php`. Current order: GitHub, LinkedIn, Google Scholar, ORCID, DBLP, ResearchGate. Mendeley (its
public profiles were closed in Nov 2020) and the "Books I like" icon (already in the Resources menu) were removed.

Raw repeater rows are keyed by ACF *field keys* (`field_5e24e80e2b3f2` logo, `field_5e24e8192b3f3` link), not by name, so
read them with `get_field("social_media", "option", false)` and write with `update_field(...)` using the names.

## Caching gotcha

Logged-in views load `zephyr_petercorke-style.min.css` straight from the theme directory with
`cache-control: max-age=31536000` and no version in the URL, so your own browser can show the old CSS for a year.
Visitors get SiteGround Optimizer's combined, hashed file, which updates on `wp sg purge`. To refresh your own view:
`fetch(<url>, {cache: "reload"})` then reload, or a hard reload.

## Restoring

Backups are in `~/backups-20260928/` on the server (`*.before-footer-tidy-20261010`): `footer.php`, `style.css`, the
minified CSS and `acf-social_media-before-footer-tidy-20261010.json` (the old rows by logo URL; attachment IDs 48-53).
