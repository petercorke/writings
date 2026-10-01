# Robotics Toolbox for MATLAB: usage

Totals from petercorke.com/RTB/, copied here weekly by `.github/workflows/rtb-stats.yml`, the toolbox's old download area. See
`wordpress/rtb/README.md` for how they are made. No IP addresses or organisation names
are kept here.

| file | contents |
|---|---|
| `monthly.csv` | month, event, country, detail, count, users |
| `daily.csv` | date, event, country, detail, count, users (from September 2026; finished months only) |
| `latest.json` | the current summary from `/RTB/stats.php`: this month and last by country and by MATLAB release or file, the last 60 days day by day, and guestbook entries by country for the last 12 months. Read by the weekly ecosystem report |
| `awstats-rtb-2017-2026.csv` | month, version_checks, landing_page, guestbook_submit, zip_download: requests per month from SiteGround's AWStats summaries |

In `monthly.csv` and `daily.csv`:

- `event` is `check` (RTB 9.10, 10.0 or 10.1 started in MATLAB; `detail` is the MATLAB
  release from the request's User-Agent) or `download` (a zip from the old page; `detail`
  is the file, `other` for malformed requests);
- `country` is an ISO 3166 code from DB-IP's IP to Country Lite (CC BY 4.0), `ZZ` if unknown;
- `users` is the number of distinct IP addresses in the period, so one lab behind one
  address counts once, and one laptop on several networks counts several times;
- rows with `*` for country or detail total over it; use them for totals, since users
  can't be added across rows.

Coverage: downloads from November 2012; version checks from September 2026 (from the
raw access logs, which SiteGround keeps for 30 days), counted live from 1 October 2026.
Download counts for 2014–2016 are inflated by crawlers.

In `awstats-rtb-2017-2026.csv`, version checks are requests for `currentversion.php`.
Months between August 2017 and February 2021 are missing from AWStats. From April 2026
until 1 October 2026 the check was redirected to `currentversion.txt`, which AWStats
counted separately, so the column reads 0 for those months.

Observations (October 2026): roughly 500 distinct addresses a month still run RTB 9.10,
10.0 or 10.1, with a clear teaching-term pattern; in September 2026, 222 of 491 were in
China, then Colombia and Taiwan (46 each), the USA (42) and Mexico (39). Most run a
recent MATLAB (R2022b–R2026a), but releases back to R2012b still check in.
