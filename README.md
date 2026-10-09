# data branch

Machine-written data for petercorke/writings. Nothing here is edited by hand, and `main`
never sees these commits (so they don't trigger the site build or interrupt your work).

| file | written by | read by |
|---|---|---|
| `stats/rtb/latest.json`, `monthly.csv`, `daily.csv` | `.github/workflows/rtb-stats.yml` (weekly) | the weekly RVC Ecosystem Report routine |
| `resources/linkcheck.json` | `.github/workflows/link-check.yml` / `linkcheck.yml` (monthly) | the same routine, and `tools/linkcheck.py` for its previous state |
| `reports/discussions-awaiting.json` | `.github/workflows/discussions-awaiting.yml` (weekly) | the same routine |
| `blog/discussions.json` | `.github/workflows/blog-feed.yml` (daily) | the Blog page's `[blog_feed]` plugin on petercorke.com |

Read a file with `ref: data`, e.g. `raw.githubusercontent.com/petercorke/writings/data/stats/rtb/latest.json`.
