# Download statistics

`wpfd-downloads-2020-2026.csv` is the complete daily log kept by the WP File Download
plugin on petercorke.com, from 4 February 2020 until the plugin was removed in March 2026.
Exported 29 September 2026 from its `wpfd_statistics` table.

| column | meaning |
|---|---|
| `date` | day |
| `kind` | `default` = a download, `preview` = viewed in the browser |
| `wpfd_id` | the plugin's file ID (see `wpfd-manifest.csv` alongside the site review) |
| `file` | the file's title in the plugin |
| `count` | number of events that day |

Counts are raw: the plugin did not filter bots or crawlers.

Since the move to GitHub, downloads of the large files are counted by GitHub itself:

```bash
gh api repos/petercorke/writings/releases/tags/assets --jq '.assets[] | "\(.name) \(.download_count)"'
```

Files served from GitHub Pages (the smaller PDFs) are not counted.

## Site traffic from AWStats, 2017–2026

`awstats/` holds monthly data parsed from SiteGround's AWStats summaries for
petercorke.com (January 2017 to September 2026), exported 29 September 2026. It fills the
gap after the plugin went and covers files the plugin never served. Per-visitor data
(IP addresses) was left out.

| file | contents |
|---|---|
| `awstats-downloads.csv` | month, section (`downloads` = AWStats download list, `urls` = other requested files: .pdf .zip .mltbx .mlx …), url, hits, bytes |
| `awstats-traffic_daily.csv` | date, visits, pages, hits, bytes |
| `awstats-robots.csv` | month, robot, hits, bytes |
| `awstats-notfound.csv` | month, url, hits — the 100 most-requested missing URLs each month |

Observations (September 2026): visits held at about 25,000 a month throughout, while hits
halved after March 2026 (about 300k to 75k a month) and bandwidth fell from about 48 to
11 GB a month. Bingbot alone made 260,000 requests in March 2026 and 4,600 in April.
From April to September 2026, old plugin download links were requested about 16,000
times and returned 404 (10,558 of them for the ICRA 2020 Python toolbox paper).
