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
