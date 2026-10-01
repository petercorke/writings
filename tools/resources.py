"""Publish the curated resource lists in ``resources/`` as web pages and JSON.

For each topic in ``resources/topics.yml`` the build writes, under ``_site/resources/``:

- ``<topic>.html``: the list as a page on docs.petercorke.com;
- ``<topic>.json``: the same list as data, read by the ``[rvc_resources]`` shortcode on the
  petercorke.com chapter pages (see ``wordpress/``);

plus ``index.html``, the topics in RVC3 chapter order with their chapter number in each
edition, and ``topics.json``, the same as data.
"""

from __future__ import annotations

import html
import json
import re
from dataclasses import dataclass
from pathlib import Path

import yaml

NOTE_LINK = re.compile(r"\[([^\]]+)\]\((https?://[^)\s]+)\)")  # [text](url) in a note
EDITIONS = {"rvc3p": "RVC3 Python", "rvc3m": "RVC3 MATLAB", "rvc2": "RVC2", "rvc1": "RVC1"}
ITEM_KEYS = {"title", "url", "note", "archived"}


@dataclass
class Topic:
    """One topic's resource list, as described by ``resources/<id>.yml``."""

    id: str
    title: str
    chapters: dict[str, int]  # edition -> chapter number
    groups: list[dict]  # [{"heading": str, "items": [{"title", "url", "note"?, "archived"?}]}]

    @property
    def count(self) -> int:
        return sum(len(g["items"]) for g in self.groups)


def load_topics(folder: Path) -> list[Topic]:
    """Load and validate every topic, in the order of ``topics.yml``.

    :param folder: the ``resources/`` directory
    :raises ValueError: if a topic file is missing, or an item lacks a title or URL
    :return: the topics, in RVC3 chapter order
    """
    index = yaml.safe_load((folder / "topics.yml").read_text())["topics"]
    topics = []
    for tid, meta in index.items():
        path = folder / f"{tid}.yml"
        if not path.exists():
            raise ValueError(f"resources: {tid} is in topics.yml but {path.name} is missing")
        data = yaml.safe_load(path.read_text())
        for g in data["groups"]:
            for it in g["items"]:
                if not it.get("title") or not it.get("url"):
                    raise ValueError(f"resources/{path.name}: item without title or url under {g['heading']!r}")
                if re.search(r"https?://", NOTE_LINK.sub("", it.get("note", ""))):
                    raise ValueError(f"resources/{path.name}: bare URL in the note of {it['title']!r}; write [text](url)")
                if extra := set(it) - ITEM_KEYS:
                    raise ValueError(f"resources/{path.name}: unknown field(s) {sorted(extra)} in {it['title']!r}")
        chapters = {ed: meta[ed] for ed in EDITIONS if ed in meta}
        topics.append(Topic(tid, meta["title"], chapters, data["groups"]))
    unlisted = {p.stem for p in folder.glob("*.yml")} - {"topics"} - set(index)
    if unlisted:
        raise ValueError(f"resources: {sorted(unlisted)} not in topics.yml")
    return topics


def note_html(note: str) -> str:
    """A note as HTML: escaped text, with any ``[text](url)`` turned into a link."""
    out, pos = [], 0
    for m in NOTE_LINK.finditer(note):
        out.append(html.escape(note[pos:m.start()]))
        out.append(f'<a href="{html.escape(m[2])}">{html.escape(m[1])}</a>')
        pos = m.end()
    out.append(html.escape(note[pos:]))
    return "".join(out)


def chapter_line(topic: Topic) -> str:
    """Where the topic sits in each edition, e.g. "RVC3 ch. 13 · RVC2 ch. 11".

    Editions that share a number are combined.
    """
    by_number: dict[int, list[str]] = {}
    for ed, n in topic.chapters.items():
        by_number.setdefault(n, []).append(EDITIONS[ed])
    parts = []
    for n, eds in by_number.items():
        if {"RVC3 Python", "RVC3 MATLAB"} <= set(eds):
            eds = ["RVC3"] + [e for e in eds if not e.startswith("RVC3")]
        parts.append(f"{', '.join(eds)} ch. {n}")
    return " · ".join(parts)


def topic_body(topic: Topic) -> str:
    """HTML for one topic's list."""
    esc = html.escape
    out = [f'<p class="chapters">{esc(chapter_line(topic))}</p>']
    for g in topic.groups:
        out.append(f"<section><h2>{esc(g['heading'])}</h2><ul class=\"res\">")
        for it in g["items"]:
            archived = ' <span class="archived">(archived)</span>' if it.get("archived") else ""
            note = f' <span class="note">{note_html(it["note"])}</span>' if it.get("note") else ""
            out.append(f'<li><a href="{esc(it["url"])}">{esc(it["title"])}</a>{archived}{note}</li>')
        out.append("</ul></section>")
    return "\n".join(out)


def index_body(topics: list[Topic]) -> str:
    """HTML for the list of topics."""
    esc = html.escape
    rows = "".join(
        f'<li><a href="{t.id}.html">{esc(t.title)}</a> <span class="note">{esc(chapter_line(t))} · {t.count} links</span></li>'
        for t in topics
    )
    return f'<ul class="res topics">{rows}</ul>'


def write_site(topics: list[Topic], site: Path, template: str) -> list[str]:
    """Write the pages and JSON for all topics.

    :param topics: from :func:`load_topics`
    :param site: the output directory (``_site``)
    :param template: page template with ``{{title}}``, ``{{lede}}`` and ``<!-- BODY -->``
    :return: site-relative paths of the HTML pages written, for the sitemap
    """
    out = site / "resources"
    out.mkdir(parents=True, exist_ok=True)

    def page(title: str, lede: str, body: str) -> str:
        return (template.replace("{{title}}", html.escape(title)).replace("{{lede}}", html.escape(lede))
                .replace("<!-- BODY -->", body))

    written = ["resources/index.html"]
    (out / "index.html").write_text(page(
        "Resources for Robotics, Vision & Control",
        "Curated links for each topic of the book: papers, tutorials, software, datasets, videos and history.",
        index_body(topics)))
    for t in topics:
        (out / f"{t.id}.html").write_text(page(
            f"{t.title}: resources", "Links for this topic of Robotics, Vision & Control.", topic_body(t)))
        (out / f"{t.id}.json").write_text(json.dumps(
            {"topic": t.id, "title": t.title, "chapters": t.chapters, "groups": t.groups}, indent=1, ensure_ascii=False))
        written.append(f"resources/{t.id}.html")
    (out / "topics.json").write_text(json.dumps(
        [{"topic": t.id, "title": t.title, "chapters": t.chapters, "count": t.count} for t in topics], indent=1))
    return written
