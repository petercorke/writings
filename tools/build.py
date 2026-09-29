"""Build the writings site: a grouped page of document tiles with page previews.

Reads ``groups.yml`` and every ``docs/<slug>/doc.yml``, works out each document's PDF,
renders a thumbnail of it, and writes a static site to ``_site/``::

    python tools/build.py            # build into _site/
    python tools/build.py --latex    # also rebuild PDFs from LaTeX source where possible
    python tools/build.py --check    # validate metadata only, write nothing

Where a document's PDF comes from, in order of preference:

1. built from LaTeX (``latex: main.tex`` in ``doc.yml``, sources in ``src/``), with
   ``--latex``; if the build fails the next option is used and a warning printed;
2. ``docs/<slug>/<slug>.pdf`` committed in the repo;
3. ``asset: <file>`` — a large file kept as a GitHub release asset, not in git. For
   thumbnails it must be present in ``assets/`` (CI downloads the release assets there);
   the tile links to the release download URL.

Companion files (``files:`` in ``doc.yml``) are listed on the tile as extra downloads;
each is either a committed file in ``docs/<slug>/files/`` or an ``asset:``.
"""

from __future__ import annotations

import argparse
import hashlib
import html
import json
import os
import shutil
import subprocess
import sys
import tempfile
from dataclasses import dataclass, field
from pathlib import Path

import pypdfium2 as pdfium
import yaml

ROOT = Path(__file__).resolve().parent.parent
DOCS = ROOT / "docs"
ASSETS = ROOT / "assets"
SITE = ROOT / "_site"
RELEASE_URL = "https://github.com/petercorke/writings/releases/download/assets"
THUMB_WIDTH = 360  # pixels; tiles display at 90 CSS px
PREVIEW_WIDTH = 900  # pixels; the hover preview displays at up to 440 CSS px, so it is readable
# LaTeX sources \input{rvc-notation}; locally it's on TEXINPUTS, in CI it's checked out here
RVC_NOTATION = Path(os.environ.get("RVC_NOTATION", Path.home() / "code" / "rvc-notation"))


@dataclass
class Companion:
    """An extra download listed on a document's tile, e.g. example code."""

    label: str
    href: str
    local: Path | None = None  # file to copy into the site, if not a release asset
    size: int | None = None


@dataclass
class Doc:
    """One document, as described by its ``doc.yml``."""

    slug: str
    title: str
    group: str
    year: int
    summary: str
    authors: list[str] = field(default_factory=list)
    draft_summary: bool = False
    thumb_page: int = 1  # 1-based page used for the preview
    latex: str | None = None  # main .tex file in src/, if the PDF can be built
    source_format: str | None = None  # "latex", "pages", …: what src/ holds
    committed_pdf: Path | None = None
    asset: str | None = None  # release-asset file name for a large PDF
    companions: list[Companion] = field(default_factory=list)
    # provenance, mainly for scanned third-party reports
    institution: str | None = None  # e.g. "Stanford AI Lab"
    report: str | None = None  # report number, e.g. "AIM-177"
    source: str | None = None  # where the scan came from
    rights: str | None = None  # why it may be hosted, e.g. "Approved for public release"
    # filled in by the build
    pdf: Path | None = None  # the PDF actually used
    href: str = ""  # where the tile links
    pages: int | None = None
    size: int | None = None
    built_from_source: bool = False


def load_groups() -> list[dict]:
    """Load the ordered group list from ``groups.yml``.

    :return: groups in display order, each with ``id``, ``title`` and optional ``blurb``
    """
    return yaml.safe_load((ROOT / "groups.yml").read_text())["groups"]


def load_doc(folder: Path, group_ids: set[str]) -> Doc:
    """Load and validate one document folder.

    :param folder: ``docs/<slug>/`` directory containing ``doc.yml``
    :param group_ids: valid group identifiers from ``groups.yml``
    :raises ValueError: if required fields are missing or inconsistent
    :return: the document description
    """
    slug = folder.name
    meta = yaml.safe_load((folder / "doc.yml").read_text())
    missing = {"title", "group", "year", "summary"} - meta.keys()
    if missing:
        raise ValueError(f"{slug}: missing {sorted(missing)}")
    if meta["group"] not in group_ids:
        raise ValueError(f"{slug}: unknown group {meta['group']!r}")

    doc = Doc(
        slug=slug,
        title=meta["title"],
        group=meta["group"],
        year=int(meta["year"]),
        summary=meta["summary"].strip(),
        authors=meta.get("authors", []),
        draft_summary=bool(meta.get("draft_summary", False)),
        thumb_page=int(meta.get("thumb_page", 1)),
        latex=meta.get("latex"),
        source_format=meta.get("source_format"),
        asset=meta.get("asset"),
        institution=meta.get("institution"),
        report=meta.get("report"),
        source=meta.get("source"),
        rights=meta.get("rights"),
    )
    if doc.group == "historical" and not doc.rights:
        raise ValueError(f"{slug}: historical documents need a 'rights' entry")
    if doc.latex and not (folder / "src" / doc.latex).exists():
        raise ValueError(f"{slug}: latex source src/{doc.latex} not found")
    committed = folder / f"{slug}.pdf"
    if committed.exists():
        doc.committed_pdf = committed
    if not (doc.latex or doc.committed_pdf or doc.asset):
        raise ValueError(f"{slug}: needs latex:, {committed.name} or asset:")

    for entry in meta.get("files", []):
        if "asset" in entry:
            doc.companions.append(Companion(entry["label"], f"{RELEASE_URL}/{entry['asset']}"))
        else:
            path = folder / "files" / entry["file"]
            if not path.exists():
                raise ValueError(f"{slug}: companion file files/{entry['file']} not found")
            doc.companions.append(Companion(entry["label"], f"files/{slug}/{path.name}", path, path.stat().st_size))
    return doc


def build_latex(doc: Doc, workdir: Path) -> Path | None:
    """Compile a document's LaTeX source in a scratch directory.

    :param doc: the document, with ``latex`` set
    :param workdir: scratch directory to build in
    :return: the built PDF, or ``None`` if the build failed
    """
    src = DOCS / doc.slug / "src"
    build_dir = workdir / doc.slug
    shutil.copytree(src, build_dir)
    # shared/ holds frozen copies of common files the old notes use: macros from
    # ~/Dropbox/lib/tex/inputs and bibliographies from ~/Dropbox/lib/bib
    shared = ROOT / "shared"
    env = {
        **os.environ,
        "TEXINPUTS": f".:{shared / 'tex'}//:{RVC_NOTATION}//:",
        "BIBINPUTS": f".:{shared / 'bib'}//:",
    }
    result = subprocess.run(
        ["latexmk", "-pdf", "-interaction=nonstopmode", "-halt-on-error", doc.latex],
        cwd=build_dir, env=env, capture_output=True, text=True,
    )
    pdf = build_dir / Path(doc.latex).with_suffix(".pdf").name
    log = pdf.with_suffix(".log")
    lines = log.read_text(errors="ignore").splitlines() if log.exists() else []
    if result.returncode == 0 and pdf.exists():
        # a PDF with [?] citations or ?? references is not a usable build
        if not any("undefined" in line and ("Citation" in line or "Reference" in line) for line in lines):
            return pdf
        problem = "undefined citations or references (missing .bib?)"
    else:
        problem = next((line for line in lines if line.startswith("!")), "no log")
    print(f"  warning: {doc.slug}: LaTeX build failed: {problem}; using the published PDF")
    return None


def render_thumbnail(pdf: Path, slug: str, page_number: int = 1) -> int:
    """Render one page of a PDF as a small tile thumbnail and a larger hover preview.

    Writes ``_site/thumbs/<slug>.webp`` and ``_site/previews/<slug>.webp``.

    :param pdf: source PDF
    :param slug: document identifier, used for the image file names
    :param page_number: 1-based page to render; the first page by default
    :return: number of pages in the PDF
    """
    document = pdfium.PdfDocument(pdf)
    page = document[min(page_number, len(document)) - 1]
    bitmap = page.render(scale=PREVIEW_WIDTH / page.get_width()).to_pil()
    bitmap.save(SITE / "previews" / f"{slug}.webp", "WEBP", quality=75)
    small = bitmap.resize((THUMB_WIDTH, round(bitmap.height * THUMB_WIDTH / bitmap.width)))
    small.save(SITE / "thumbs" / f"{slug}.webp", "WEBP", quality=80)
    return len(document)


def human_size(n: int) -> str:
    """Format a byte count for display.

    :param n: size in bytes
    :return: size such as ``"385 kB"`` or ``"8.7 MB"``
    """
    if n < 1_000_000:
        return f"{n / 1000:.0f} kB"
    return f"{n / 1_000_000:.1f} MB"


def tile(doc: Doc) -> str:
    """Render one document tile as HTML.

    :param doc: the document, after the build has resolved its PDF
    :return: an ``<article>`` element
    """
    esc = html.escape
    facts = [x for x in (doc.institution, doc.report, str(doc.year)) if x]
    if doc.pages:
        facts.append(f"{doc.pages} pages")
    if doc.size:
        facts.append(human_size(doc.size))
    byline = f'<p class="byline">{esc(", ".join(doc.authors))}</p>' if doc.authors else ""
    rights = f'<p class="rights">{esc(doc.rights)}</p>' if doc.rights else ""
    extras = ""
    if doc.companions:
        links = " · ".join(
            f'<a href="{esc(c.href)}">{esc(c.label)}</a>' + (f" ({human_size(c.size)})" if c.size else "")
            for c in doc.companions
        )
        extras = f'<p class="extras">Also: {links}</p>'
    return f"""
    <article class="tile">
      <a class="thumb" href="{esc(doc.href)}">
        <img src="thumbs/{doc.slug}.webp" alt="" loading="lazy" width="90">
        <span class="preview"><img src="previews/{doc.slug}.webp" alt="Preview of {esc(doc.title)}" loading="lazy" width="440"></span>
      </a>
      <div class="text">
        <h3><a href="{esc(doc.href)}">{esc(doc.title)}</a></h3>
        {byline}<p class="facts">{esc(" · ".join(facts))}</p>
        <p class="summary">{esc(doc.summary)}</p>
        {extras}{rights}
      </div>
    </article>"""


def resolve_pdf(doc: Doc, workdir: Path, use_latex: bool) -> None:
    """Choose the PDF for a document and where its tile links.

    :param doc: the document; ``pdf``, ``href`` and ``built_from_source`` are set
    :param workdir: scratch directory for LaTeX builds
    :param use_latex: try building from LaTeX source first
    :raises ValueError: if no PDF is available
    """
    if use_latex and doc.latex and (built := build_latex(doc, workdir)):
        doc.pdf, doc.built_from_source = built, True
    elif doc.committed_pdf:
        doc.pdf = doc.committed_pdf
    elif doc.asset:
        doc.pdf = ASSETS / doc.asset
        if not doc.pdf.exists():
            raise ValueError(f"{doc.slug}: asset {doc.asset} not in {ASSETS} (download the release assets)")
    else:
        raise ValueError(f"{doc.slug}: LaTeX build needed (--latex) and no published PDF")
    doc.href = f"{RELEASE_URL}/{doc.asset}" if doc.asset and doc.pdf.parent == ASSETS else f"pdf/{doc.slug}.pdf"


def build(check_only: bool = False, use_latex: bool = False) -> None:
    """Validate all documents and, unless checking, write the site.

    :param check_only: validate metadata without writing anything
    :param use_latex: rebuild PDFs from LaTeX source where possible
    """
    groups = load_groups()
    docs = [load_doc(f, {g["id"] for g in groups}) for f in sorted(DOCS.iterdir()) if (f / "doc.yml").exists()]
    drafts = sum(d.draft_summary for d in docs)
    print(f"{len(docs)} documents in {len(groups)} groups; {drafts} draft summaries")
    if check_only:
        return

    if SITE.exists():
        shutil.rmtree(SITE)
    for sub in ("thumbs", "previews", "pdf", "files"):
        (SITE / sub).mkdir(parents=True)
    for asset in ("style.css", "preview.js"):
        shutil.copy(ROOT / "site" / asset, SITE / asset)

    with tempfile.TemporaryDirectory() as workdir:
        for doc in docs:
            resolve_pdf(doc, Path(workdir), use_latex)
            doc.size = doc.pdf.stat().st_size
            doc.pages = render_thumbnail(doc.pdf, doc.slug, doc.thumb_page)
            if doc.href.startswith("pdf/"):
                shutil.copy(doc.pdf, SITE / doc.href)
            for c in doc.companions:
                if c.local:
                    (SITE / "files" / doc.slug).mkdir(exist_ok=True)
                    shutil.copy(c.local, SITE / c.href)
    built = [d.slug for d in docs if d.built_from_source]
    if use_latex:
        print(f"built from LaTeX: {len(built)} of {sum(bool(d.latex) for d in docs)}")

    sections = []
    for g in groups:
        members = sorted((d for d in docs if d.group == g["id"]), key=lambda d: -d.year)
        if not members:
            continue
        blurb = f'<p class="blurb">{html.escape(g["blurb"])}</p>' if g.get("blurb") else ""
        tiles = "".join(tile(d) for d in members)
        sections.append(f'<section id="{g["id"]}"><h2>{html.escape(g["title"])}</h2>{blurb}<div class="grid">{tiles}</div></section>')

    page = (ROOT / "site" / "index.template.html").read_text().replace("<!-- SECTIONS -->", "\n".join(sections))
    # fingerprint the stylesheet and script so browsers fetch fresh copies after a change
    for asset in ("style.css", "preview.js"):
        digest = hashlib.sha256((ROOT / "site" / asset).read_bytes()).hexdigest()[:10]
        page = page.replace("{{" + asset + "}}", digest)
    (SITE / "index.html").write_text(page)
    # machine-readable index, e.g. for a WordPress page to list the groups
    (SITE / "docs.json").write_text(json.dumps(
        [{"slug": d.slug, "title": d.title, "group": d.group, "year": d.year, "pages": d.pages, "href": d.href}
         for d in docs], indent=1))
    print(f"wrote {SITE}")


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    parser.add_argument("--check", action="store_true", help="validate metadata only")
    parser.add_argument("--latex", action="store_true", help="rebuild PDFs from LaTeX source where possible")
    args = parser.parse_args()
    try:
        build(args.check, args.latex)
    except ValueError as e:
        sys.exit(f"error: {e}")
