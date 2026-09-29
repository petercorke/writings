# Writings

Technical notes, papers, books and historical documents by (and collected by) Peter Corke,
published as a grouped page of tiles with first-page previews.

**Status: proof of concept** (September 2026). All 15 non-toolbox documents from the old
petercorke.com download plugin; summaries are drafts.

## Layout

```
groups.yml                 group order, headings and blurbs
docs/<slug>/doc.yml        one folder per document: its description
docs/<slug>/<slug>.pdf     the PDF (for now; see "Large files" below)
docs/<slug>/src/           source, where it exists (LaTeX, figures, …) — planned
tools/build.py             builds _site/ (tiles page, thumbnails, docs.json)
site/                      page template and stylesheet
.github/workflows/         builds and publishes to GitHub Pages on push to main
```

## doc.yml

```yaml
title: Precision-recall curves
group: articles            # an id from groups.yml
year: 2016
authors: [Peter Corke]     # optional
summary: A tutorial introduction to …
thumb_page: 1              # optional: page to use for the preview
# provenance, required for the "historical" group (scans of third-party reports)
institution: Stanford AI Lab
report: AIM-177
source: NTIS reprint AD-785 071, scanned
rights: Approved for public release; distribution unlimited
```

Set `draft_summary: true` while a summary still needs writing; `build.py --check`
reports how many remain.

### Documents hosted elsewhere

`url:` makes a tile link to another site (arXiv, a journal). The preview comes from the PDF,
fetched once and cached in `external/` (arXiv) — or, for hosts that block scripts or PDFs
that mustn't be redistributed, from a committed `preview_image:` (with `pages:`).
`doi:` + `venue:` add a link to a paywalled published version.

**When making a `preview_image` from a publisher's download, check it for a download
stamp** — Annual Reviews, for one, prints "Downloaded from … IP: <your address> On: <date>"
in the margin of every page. Paint it out before committing.

## Build locally

```bash
python -m venv .venv && .venv/bin/pip install -r requirements.txt
.venv/bin/python tools/build.py
```

## Source status (2026-09-28)

All 15 documents from the old petercorke.com download plugin are here. Nine have LaTeX
source in `src/`; `build.py --latex` rebuilds 4 of them locally (precision-recall-curves,
rtb-real-robot, solving-trig-equations, xml-matlab), each matching its published page
count; CI also builds insertion-jacobian.

**Decision (2026-09-28): the others keep their published PDF as the master; their source
is archived as is, not repaired.** dh-common-robots, ets-jacobian and
four-is-harder-than-six were written in 2014–18 against the global `rvc-notation.tex` of
the day, which has since changed: they use macros later removed or never committed
(`\var`, `\Mlab`, `\so`, a `Code` environment). That version predates the
`petercorke/rvc-notation` git history (August 2019) and no copy survives — the
`Writing/` folders hold only `rvc-notation.aux` leftovers, no local variants. Neither the
2016 `notation.tex` nor today's `rvc-notation` reproduces it. insertion-jacobian (builds on
CI only) and urdf-matlab are in the same position. If one of these notes is ever revised,
modernise its source against the current `rvc-notation` then.

- **Bibliographies**: in `shared/bib/` (see below). Only `extra.bib`, cited by
  xml-matlab, is missing, and that note builds without it.
- Locally, an uncommitted work-in-progress `rvc-notation.tex` (in `~/code/rvc-notation`)
  also causes a `\dddot already defined` clash; CI uses the committed version, which is
  fine.

bones-of-descartes has Pages source (`source_format: pages`): re-export the PDF by hand.

## Planned

- **Python alternatives for the MATLAB-based notes**: four-is-harder-than-six,
  rtb-real-robot, solving-trig-equations (`sincos.mlx`), urdf-matlab, xml-matlab — provide
  modern Python versions (Robotics Toolbox for Python, Python XML/URDF parsing). Each has a
  `TODO` comment in its `doc.yml`.

- **OCR**: run OCRmyPDF on scans (AIM-177, the Puma report) in CI so they're searchable
  and indexable.

Large files are already handled: they're on the `assets` release (`asset:` in doc.yml),
and CI downloads them to render thumbnails.

## Shared files

`shared/tex/` and `shared/bib/` hold frozen copies of the macro files (`pic-common.tex`,
`matlab.tex`) and bibliographies the older notes use, taken from `~/Dropbox/lib/tex/inputs`
and `~/Dropbox/lib/bib` in September 2026. Private fields (`annote`, `File`,
`Bdsk-File-*`) were stripped from the `.bib` copies. They aren't kept in sync: the notes are old and
so are their references. `rvc-notation` is not copied; it comes from its own repo.
