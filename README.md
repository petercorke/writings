# Writings

Technical notes, papers, books and historical documents by (and collected by) Peter Corke,
published at **[docs.petercorke.com](https://docs.petercorke.com)** as a grouped page of
tiles, each with a page preview that enlarges on hover.

The site is generated: every push to `main` runs `tools/build.py` in GitHub Actions, which
reads a small description file per document, renders the previews and publishes the page to
GitHub Pages. Nothing runs on petercorke.com.

## Adding a document

Each document is a folder `docs/<slug>/` holding a `doc.yml` and, depending on the case, the
PDF or its source. The slug is short, lowercase and hyphenated; it becomes the file names on
the site (e.g. `pdf/<slug>.pdf`). Then:

```bash
.venv/bin/python tools/build.py --check   # validate every doc.yml
.venv/bin/python tools/build.py           # build _site/ and look at _site/index.html
git add docs/<slug> && git commit -m "Add <title>" && git push   # publishes in ~2 min
```

Pick the recipe that matches where the PDF lives.

### 1. My own PDF, hosted here

Put the PDF at `docs/<slug>/<slug>.pdf` (up to a few MB) and write `doc.yml`:

```yaml
title: Precision-recall curves
group: articles
year: 2016
authors: [Peter Corke]
summary: A tutorial introduction to binary classifiers and precision-recall curves.
```

### 2. With LaTeX source

As 1, plus the source in `docs/<slug>/src/` — the main `.tex` and only the files it uses
(figures, `.bib`, local `.sty`) — and:

```yaml
latex: prcurves.tex      # main file in src/
source_format: latex
```

CI rebuilds the PDF from source; if that fails the committed PDF is used (with a warning in
the log). Shared macros and bibliographies are found in `shared/` and in the
`petercorke/rvc-notation` repo (see "Shared files").

### 3. A large file (over ~5 MB)

Don't commit it. Upload it to the `assets` release and name it in `doc.yml`:

```bash
gh release upload assets my-big-report.pdf --repo petercorke/writings
```

```yaml
asset: my-big-report.pdf
```

The tile links to the release download, which GitHub counts.

### 4. A paper on arXiv

No PDF needed:

```yaml
url: https://arxiv.org/abs/2207.01796
doi: 10.1109/MRA.2023.3270228            # optional: the published version
venue: IEEE Robotics and Automation Magazine
```

The tile links to the arXiv page and shows "arXiv". The build fetches the PDF once (from
export.arxiv.org) purely to render the preview; it is cached in `external/` and never
republished. The arXiv API (`https://export.arxiv.org/api/query?id_list=<id>`) gives the
title, authors, abstract and usually the DOI.

### 5. A journal page, where the PDF can't be fetched or mustn't be hosted

```yaml
url: https://www.annualreviews.org/content/journals/10.1146/annurev-control-061323-095841
preview_image: preview.webp   # page 1 as an image, in the document folder
pages: 31
```

Make the preview image from a PDF you've downloaded (it is not committed):

```bash
.venv/bin/python -c "
import pypdfium2 as p; d = p.PdfDocument('paper.pdf'); pg = d[0]
pg.render(scale=900 / pg.get_width()).to_pil().save('docs/<slug>/preview.webp', 'WEBP', quality=75)"
```

**Check the image for a download stamp before committing.** Publishers often print
"Downloaded from … IP: <your address> On: <date>" in the margin of every page; paint it
out. Crossref (`https://api.crossref.org/works/<doi>`) gives the metadata and licence.

### 6. An IEEE (or similar) paper: host the submitted manuscript

The publisher's final version may not be posted, but the submitted manuscript can be. Use
recipe 1 with the manuscript PDF, link the final version, and say which version it is:

```yaml
doi: 10.1109/MCS.2026.3667020
venue: IEEE Control Systems Magazine
rights: >-
  Submitted manuscript. The published version, © 2026 IEEE, is in IEEE Control Systems
  Magazine 46(3):98–111.
```

### 7. A scan of someone else's report

Use recipe 1 or 3, put it in the `historical` group, and record where it came from and why
it may be hosted — the build refuses a historical document without `rights:`:

```yaml
institution: Stanford AI Lab
report: AIM-177 (STAN-CS-72-311)
source: NTIS reprint AD-785 071, scanned
rights: Approved for public release; distribution unlimited (NTIS)
```

### Extra links on a tile

```yaml
files:
  - label: MATLAB live script     # a file in docs/<slug>/files/
    file: sincos.mlx
  - label: figures (EPS)          # a file on the assets release
    asset: RVC2-eps.zip
  - label: Jupyter notebooks      # any web page
    url: https://github.com/jhavl/dkt
```

### House rules

- **Never commit or host a publisher's final PDF** (IEEE, Annual Reviews, …); link to it
  with `url:` or `doi:`. Hosting a submitted manuscript is fine, with a `rights:` note
  saying so.
- **Strip download stamps** from any preview made from a publisher's download.
- **Tile titles**: drop a long subtitle after a colon.
- **Summaries**: one or two plain sentences. Mark a placeholder with `draft_summary: true`
  (`build.py --check` counts them) and remove it once rewritten.

## doc.yml reference

| field | | meaning |
|---|---|---|
| `title` | required | tile heading |
| `group` | required | an id from `groups.yml` (`articles`, `books`, `papers`, `historical`) |
| `year` | required | shown on the tile; tiles are newest first within a group |
| `summary` | required | one or two sentences |
| `authors` | | list of names for the byline |
| `draft_summary` | | `true` while the summary is a placeholder |
| `thumb_page` | | page used for the preview (default 1) |
| `latex`, `source_format` | | main `.tex` in `src/`; what `src/` holds (`latex`, `pages`, …) |
| `asset` | | PDF kept on the `assets` release instead of in git |
| `url` | | the document lives elsewhere; the tile links here |
| `pdf_url` | | its PDF, if not derivable from `url` (arXiv is automatic) |
| `preview_image`, `pages` | | committed page image and page count, instead of a PDF |
| `doi`, `venue` | | "Published version" link and its text |
| `files` | | extra links (see above) |
| `institution`, `report`, `source`, `rights` | | provenance; `rights` required for `historical` |

The PDF comes from, in order: a LaTeX build (with `--latex`), `<slug>.pdf`, `asset:`, or
`url:`. Every document needs at least one of those.

## Layout

```
groups.yml                   group order, headings and blurbs
docs/<slug>/doc.yml          one folder per document
docs/<slug>/<slug>.pdf       its PDF, when hosted here
docs/<slug>/src/             its source (LaTeX, figures; Pages for one note)
docs/<slug>/files/           companion files (code, examples)
tools/build.py               builds _site/: tiles page, thumbnails, previews, docs.json
site/                        page template, stylesheet, hover-preview and find scripts
wordpress/                   petercorke.com's site-search add-on (see "Search")
shared/tex, shared/bib       frozen macro files and bibliographies used by old notes
stats/                       download and traffic history from the old website
.github/workflows/pages.yml  build and publish on every push to main
assets/, external/, _site/   (not in git) downloaded release files, fetched external PDFs, output
```

## Search

- **Search engines:** the build writes `robots.txt` (all crawlers welcome) and `sitemap.xml`
  (the page and every PDF hosted here). Register the site (a Domain or URL-prefix property for
  `https://docs.petercorke.com/`) in Google Search Console and Bing Webmaster Tools and
  submit the sitemap there; that is also where to check what has been indexed.
- **Find box:** the page filters its tiles as you type (`site/filter.js`), in the browser.
  `?q=words` in the address pre-fills it, so a search can be linked to.
- **petercorke.com's site search** lists matching documents above its own results. It reads
  `docs.json` (at most every 12 hours) using `wordpress/docs-search.php`, installed as
  `wp-content/mu-plugins/docs-search.php`, and the theme's `search.php` prints them; the
  copy of `search.php` here is the theme file with that change. Edit these here, then copy
  them to the server.

## Build locally

```bash
python -m venv .venv && .venv/bin/pip install -r requirements.txt
.venv/bin/python tools/build.py            # add --latex to rebuild PDFs from source
gh release download assets --dir assets    # once, so large-file previews can render
```

## Source status of the older notes

Nine of the notes from the old website have LaTeX source in `src/`; 4 rebuild locally
(precision-recall-curves, rtb-real-robot, solving-trig-equations, xml-matlab), and CI also
builds insertion-jacobian.

**Decision (2026-09-28): the others keep their published PDF as the master; their source
is archived as is, not repaired.** dh-common-robots, ets-jacobian and
four-is-harder-than-six were written in 2014–18 against the global `rvc-notation.tex` of
the day, which has since changed: they use macros later removed or never committed
(`\var`, `\Mlab`, `\so`, a `Code` environment). That version predates the
`petercorke/rvc-notation` git history (August 2019) and no copy survives. insertion-jacobian
(builds on CI only) and urdf-matlab are in the same position. If one of these notes is ever
revised, modernise its source against the current `rvc-notation` then.

bones-of-descartes has Pages source (`source_format: pages`): re-export the PDF by hand.

## Shared files

`shared/tex/` and `shared/bib/` hold frozen copies of the macro files (`pic-common.tex`,
`matlab.tex`) and bibliographies the older notes use, taken from `~/Dropbox/lib/tex/inputs`
and `~/Dropbox/lib/bib` in September 2026, with private fields (`annote`, `File`,
`Bdsk-File-*`) stripped from the `.bib` copies. They aren't kept in sync: the notes are old,
and so are their references. `rvc-notation` isn't copied; CI checks out its repo.

## Planned

- **Python alternatives for the MATLAB-based notes** (four-is-harder-than-six,
  rtb-real-robot, solving-trig-equations, urdf-matlab, xml-matlab); each has a `TODO` in its
  `doc.yml`.
- **OCR** for the scans (AIM-177, the Puma report), so they're searchable.
- **Summaries**: all are drafts to be rewritten.
