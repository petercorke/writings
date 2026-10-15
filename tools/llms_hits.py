"""Report who fetches ``/llms.txt`` and ``/robots.txt`` on petercorke.com.

The server keeps about 30 daily access logs.  This reads them over ``ssh sg``, keeps only
the requests for the files named below, and prints counts by kind of visitor and by week.
No addresses are printed or stored.

Run it from anywhere that can ``ssh sg``::

    python tools/llms_hits.py
    python tools/llms_hits.py --file hits.log     # a pre-fetched log, for testing
"""

import argparse
import collections
import re
import subprocess
import sys
from datetime import datetime

PATHS = ("/llms.txt", "/llms-full.txt", "/robots.txt")

# the first match wins, so the specific names come before the generic "Mozilla"
KINDS: dict[str, tuple[str, ...]] = {
    "ai-crawler": (
        "GPTBot", "OAI-SearchBot", "ChatGPT-User", "ClaudeBot", "Claude-User",
        "Claude-SearchBot", "anthropic", "PerplexityBot", "Perplexity-User",
        "DuckAssistBot", "Google-Extended", "Meta-ExternalAgent", "Amazonbot",
        "Applebot", "Bytespider", "CCBot", "LinkupBot",
    ),
    "search": ("Googlebot", "bingbot", "YandexBot", "DuckDuckBot", "Baiduspider"),
    "seo-tool": (
        "SEOJuice", "SiteAuditBot", "Semrush", "Ahrefs", "BuiltWith", "TheWebReport",
        "PipericBot", "Dataprovider", "Audisto", "SeltzSitemapFetcher",
    ),
    "script": ("curl", "python-requests", "Go-http", "axios", "wget", "HeadlessChrome"),
    "browser-like": ("Mozilla",),
}

LINE = re.compile(
    r'\[(?P<day>\d+/\w+/\d+):[^\]]+\] "GET (?P<path>\S+) [^"]*" (?P<status>\d+) \d+ "[^"]*" "(?P<ua>[^"]*)"'
)


def classify(ua: str) -> str:
    """Name the kind of visitor for a User-Agent string.

    :param ua: the User-Agent header
    :returns: one of the keys of ``KINDS``, or ``"other"``
    """
    low = ua.lower()
    for kind, names in KINDS.items():
        if any(n.lower() in low for n in names):
            return kind
    return "other"


def bot_name(ua: str) -> str:
    """Return the first token of a User-Agent that looks like a named bot, else ``""``."""
    m = re.search(r"([A-Za-z][\w.-]*(?:bot|Bot|Crawler|Agent|User|Fetcher|Report))\b", ua)
    return m.group(1) if m else ""


def read_lines(path: str | None) -> list[str]:
    """Get the matching log lines, from a file or from the server over ssh."""
    if path:
        with open(path) as f:
            return f.readlines()
    cmd = "cd ~/www/petercorke.com/logs && for f in *.gz; do zcat $f; done | grep -E '\"GET /(llms|robots)'"
    out = subprocess.run(["ssh", "sg", cmd], capture_output=True, text=True, check=False)
    if out.returncode not in (0, 1):
        sys.exit(out.stderr or "ssh failed")
    return out.stdout.splitlines()


def main() -> None:
    ap = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    ap.add_argument("--file", help="read a log extract instead of using ssh")
    args = ap.parse_args()

    by_kind: dict[str, collections.Counter] = collections.defaultdict(collections.Counter)
    by_week: dict[str, collections.Counter] = collections.defaultdict(collections.Counter)
    named: dict[str, collections.Counter] = collections.defaultdict(collections.Counter)
    for line in read_lines(args.file):
        m = LINE.search(line)
        if not m or m["path"].split("?")[0] not in PATHS:
            continue
        path = m["path"].split("?")[0]
        ua = m["ua"]
        by_kind[classify(ua)][path] += 1
        day = datetime.strptime(m["day"], "%d/%b/%Y")
        by_week[f"{day.isocalendar().year}-W{day.isocalendar().week:02d}"][path] += 1
        if (n := bot_name(ua)):
            named[path][n] += 1

    print("Requests by kind of visitor")
    print(f"{'kind':14}" + "".join(f"{p:>16}" for p in PATHS))
    for kind in [*KINDS, "other"]:
        if kind in by_kind:
            print(f"{kind:14}" + "".join(f"{by_kind[kind][p]:>16}" for p in PATHS))
    print("\nRequests by ISO week")
    print(f"{'week':14}" + "".join(f"{p:>16}" for p in PATHS))
    for wk in sorted(by_week):
        print(f"{wk:14}" + "".join(f"{by_week[wk][p]:>16}" for p in PATHS))
    print("\nNamed bots fetching /llms.txt or /llms-full.txt")
    for p in ("/llms.txt", "/llms-full.txt"):
        for n, c in named[p].most_common(10):
            print(f"  {p:16}{n:36}{c:>5}")


if __name__ == "__main__":
    main()
