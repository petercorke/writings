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

## Build locally

```bash
python -m venv .venv && .venv/bin/pip install -r requirements.txt
.venv/bin/python tools/build.py
```

## Source status (2026-09-28)

All 15 documents from the old petercorke.com download plugin are here. Nine have LaTeX
source in `src/`; `build.py --latex` rebuilds 2 of them (solving-trig-equations,
xml-matlab). The rest fall back to their published PDF until these are fixed:

- **Missing shared bibliography**: `strings`, `kinematics`, `robot`, `dynamics`,
  `software`, `extra`, `book` (`.bib`). Only `publist.bib` was found
  (`Dropbox/CloudDocs/doc/`). Probably the RVC book's bibliography library.
- **Missing `pic-common.tex`** (precision-recall-curves, rtb-real-robot) — on its way.
- **`\dddot already defined`** (dh-common-robots, ets-jacobian, four-is-harder-than-six):
  current `rvc-notation` clashes with a newer LaTeX package; probably a one-line fix in
  `petercorke/rvc-notation`.
- **Other errors**: insertion-jacobian ("Missing }"), urdf-matlab (undefined control
  sequence) — need a look.

bones-of-descartes has Pages source (`source_format: pages`): re-export the PDF by hand.

## Planned

- **Python alternatives for the MATLAB-based notes**: four-is-harder-than-six,
  rtb-real-robot, solving-trig-equations (`sincos.mlx`), urdf-matlab, xml-matlab — provide
  modern Python versions (Robotics Toolbox for Python, Python XML/URDF parsing). Each has a
  `TODO` comment in its `doc.yml`.

- **Large files**: scans and big archives become GitHub release assets rather than
  committed files (`url:` in doc.yml instead of a local PDF); the build fetches them to
  render thumbnails.
- **OCR**: run OCRmyPDF on scans in CI so they're searchable and indexable.
- **Sources**: add `src/` folders and build those PDFs in CI.
