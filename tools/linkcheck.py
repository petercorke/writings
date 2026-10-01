"""Check every link in resources/*.yml and report the ones that need attention.

    python tools/linkcheck.py            # write resources/linkcheck.json, print a summary
    python tools/linkcheck.py --markdown # also print the report as Markdown (for the issue)

Three kinds of problem are reported:

- ``broken``: an HTTP error, or no answer at all (checked twice, a minute apart). A link
  that doesn't answer is only reported as broken once it also failed the previous check;
  the first time it is listed as ``noanswer``, since slow servers often don't answer the
  data-centre addresses GitHub runs from;
- ``moved``: the link now redirects to a different site;
- ``homepage``: the link now redirects to the home page of its site, which usually means
  the page itself is gone.

Redirects that don't change what a reader sees (http to https, adding or dropping "www.",
a trailing slash, DOI and handle resolvers, arXiv) are not reported. Links marked
``archived: true`` are Internet Archive copies and are not checked. Some sites refuse
automated requests (403) although they work in a browser; ``BOT_BLOCKED`` lists them, and
a 403 from one of them, or from a publisher reached through a DOI, is not reported; nor is
a 429 (rate limited).
"""

from __future__ import annotations

import argparse
import concurrent.futures
import datetime
import json
import re
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

import yaml

ROOT = Path(__file__).resolve().parent.parent
RESOURCES = ROOT / "resources"
UA = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Safari/605.1.15"
# sites known to refuse automated requests although they work in a browser
BOT_BLOCKED = {"mathworks.com", "researchgate.net", "journals.sagepub.com", "nytimes.com", "cloudcompare.org",
               "ieeexplore.ieee.org", "tandfonline.com", "sciencedirect.com", "cse.sc.edu", "umich.edu",
               "asmedigitalcollection.asme.org", "sourceforge.net", "rosettacode.org"}
# resolvers that always redirect somewhere else, by design
RESOLVERS = {"doi.org", "dx.doi.org", "hdl.handle.net", "arxiv.org"}


def host(url: str) -> str:
    h = urllib.parse.urlsplit(url).hostname or ""
    return h[4:] if h.startswith("www.") else h


def matches(h: str, domains: set[str]) -> bool:
    return any(h == d or h.endswith("." + d) for d in domains)


def fetch(url: str) -> tuple[int, str]:
    """Status and final URL after redirects; status 0 if there was no answer."""
    req = urllib.request.Request(url, headers={"User-Agent": UA, "Accept": "text/html,application/pdf,*/*"})
    try:
        with urllib.request.urlopen(req, timeout=30) as r:
            return r.status, r.geturl()
    except urllib.error.HTTPError as e:
        return e.code, e.geturl() or url
    except Exception:
        return 0, url


def classify(url: str, status: int, final: str) -> str | None:
    """The kind of problem, or None if the link is fine."""
    h, fh = host(url), host(final)
    if status == 403 and (matches(h, BOT_BLOCKED) or matches(fh, BOT_BLOCKED) or matches(h, RESOLVERS)):
        return None  # a site that refuses checkers, or a publisher reached through a DOI
    if status == 429:
        return None  # rate limited: says nothing about the link
    if status == 0 or status >= 400:
        return "broken"
    if matches(h, RESOLVERS):
        return None
    if fh and fh != h and not matches(fh, {h}) and not matches(h, {fh}):
        return "moved"
    old_path = urllib.parse.urlsplit(url).path.strip("/")
    new = urllib.parse.urlsplit(final)
    if old_path and not new.path.strip("/") and not new.query:
        return "homepage"
    return None


def check(item: dict) -> dict | None:
    status, final = fetch(item["url"])
    kind = classify(item["url"], status, final)
    if kind == "broken":  # try once more, a minute later, before reporting
        time.sleep(60)
        status, final = fetch(item["url"])
        kind = classify(item["url"], status, final)
    if kind is None:
        return None
    return {**item, "kind": kind, "status": status, "final": final if final != item["url"] else None}


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    parser.add_argument("--markdown", action="store_true", help="also print the report as Markdown")
    args = parser.parse_args()

    topics = yaml.safe_load((RESOURCES / "topics.yml").read_text())["topics"]
    items = []
    for tid, meta in topics.items():
        for g in yaml.safe_load((RESOURCES / f"{tid}.yml").read_text())["groups"]:
            for it in g["items"]:
                if not it.get("archived"):
                    items.append({"topic": tid, "topic_title": meta["title"], "heading": g["heading"],
                                  "title": it["title"], "url": it["url"]})
                for text, url in re.findall(r"\[([^\]]+)\]\((https?://[^)\s]+)\)", it.get("note", "")):
                    items.append({"topic": tid, "topic_title": meta["title"], "heading": g["heading"],
                                  "title": f"{it['title']}: {text}", "url": url})
    with concurrent.futures.ThreadPoolExecutor(max_workers=8) as pool:
        problems = [p for p in pool.map(check, items) if p]
    # carry forward when each problem was first seen; hold back first-time non-answers
    today = datetime.date.today().isoformat()
    previous, prev_date = {}, None
    if (RESOURCES / "linkcheck.json").exists():
        prev = json.loads((RESOURCES / "linkcheck.json").read_text())
        previous = {q["url"]: q for q in prev.get("problems", [])}
        prev_date = datetime.date.fromisoformat(prev["checked"][:10])
    # a non-answer is confirmed only by a check at least a week earlier (not a rerun today)
    confirming = prev_date is not None and (datetime.date.today() - prev_date).days >= 7
    for p in problems:
        before = previous.get(p["url"], {})
        p["since"] = before.get("since", today)
        if p["kind"] == "broken" and p["status"] == 0 and not (confirming and before.get("status") == 0):
            p["kind"] = "noanswer"
    problems.sort(key=lambda p: (list(topics).index(p["topic"]), p["heading"], p["title"]))

    report = {
        "checked": datetime.datetime.now(datetime.timezone.utc).strftime("%Y-%m-%dT%H:%MZ"),
        "links": len(items),
        "problems": problems,
        "counts": {k: sum(p["kind"] == k for p in problems) for k in ("broken", "moved", "homepage", "noanswer")},
    }
    (RESOURCES / "linkcheck.json").write_text(json.dumps(report, indent=1, ensure_ascii=False) + "\n")
    print(f"checked {len(items)} links: {len(problems)} need attention {report['counts']}", file=sys.stderr)

    if args.markdown:
        need = len(problems) - report["counts"]["noanswer"]
        lines = [f"Link check of `resources/` on {report['checked']}: {len(items)} links checked, "
                 f"**{need} need attention** (archived copies and sites that block checkers are skipped).", ""]
        what = {"broken": "Broken (error, or no answer two checks running)", "moved": "Now redirects to a different site",
                "homepage": "Now redirects to the site's home page", "noanswer": "No answer this time (will recheck next month)"}
        for kind, heading in what.items():
            rows = [p for p in problems if p["kind"] == kind]
            if rows:
                lines += [f"### {heading}", ""]
                for p in rows:
                    extra = f" → {p['final']}" if p.get("final") else ""
                    code = f" ({p['status']})" if p["status"] else " (no answer)"
                    since = f", since {p['since']}" if p["since"] != today else ""
                    lines.append(f"- **{p['topic_title']}** / {p['heading']}: [{p['title']}]({p['url']}){code}{extra}{since}")
                lines.append("")
        lines.append("Fix them in `resources/<topic>.yml`; this issue is updated by the monthly link check and closed when nothing needs attention.")
        print("\n".join(lines))


if __name__ == "__main__":
    main()
