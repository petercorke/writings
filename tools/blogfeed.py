#!/usr/bin/env python3
"""Collect GitHub Discussion announcements for the "Blog" list on petercorke.com.

    python tools/blogfeed.py [output.json]      # default: blog/discussions.json

Reads every public, non-fork, non-archived repo of OWNER that has Discussions enabled, and
keeps only the discussions in a category named "Announcements", minus GitHub's auto-created
"Welcome to ... Discussions!" posts. Give that category the Announcement *format* in the repo's
Discussions settings: that is what lets only maintainers post there, and the API does not
expose the format, so the name is all this script can test. Publishing something on the Blog
is therefore an explicit act: post it in Announcements. Everything else, including discussions
Peter started in other categories, is left out on purpose. Writes newest first as JSON; the
WordPress plugin wordpress/blog-feed.php merges it with the site's own posts.

Needs the GitHub CLI (`gh`) logged in or with GH_TOKEN set (a workflow's github.token works:
it only reads public data). On any API error it exits non-zero without touching the output file,
so the site keeps showing the last good list.
"""
import datetime
import json
import re
import subprocess
import sys
from pathlib import Path

OWNER = "petercorke"
ME = "petercorke"
EXCERPT = 220  # characters

REPOS_QUERY = """
query($owner: String!, $after: String) {
  repositoryOwner(login: $owner) {
    repositories(first: 100, after: $after, privacy: PUBLIC, isFork: false, ownerAffiliations: OWNER) {
      pageInfo { hasNextPage endCursor }
      nodes { name isArchived hasDiscussionsEnabled }
    }
  }
}"""

DISCUSSIONS_QUERY = """
query($owner: String!, $name: String!, $after: String) {
  repository(owner: $owner, name: $name) {
    discussions(first: 100, after: $after, orderBy: {field: CREATED_AT, direction: DESC}) {
      pageInfo { hasNextPage endCursor }
      nodes { title url createdAt bodyText category { name } author { login } }
    }
  }
}"""


def graphql(query: str, **variables) -> dict:
    """Run a GraphQL query with gh; raise on any error."""
    cmd = ["gh", "api", "graphql", "-f", f"query={query}"]
    for key, value in variables.items():
        if value is not None:
            cmd += ["-f", f"{key}={value}"]
    out = subprocess.run(cmd, capture_output=True, text=True)
    if out.returncode != 0:
        raise RuntimeError(f"gh api graphql failed: {out.stderr.strip() or out.stdout.strip()}")
    data = json.loads(out.stdout)
    if data.get("errors"):
        raise RuntimeError(f"GraphQL errors: {data['errors']}")
    return data["data"]


def repos_with_discussions() -> list[str]:
    names, after = [], None
    while True:
        page = graphql(REPOS_QUERY, owner=OWNER, after=after)["repositoryOwner"]["repositories"]
        names += [r["name"] for r in page["nodes"] if r["hasDiscussionsEnabled"] and not r["isArchived"]]
        if not page["pageInfo"]["hasNextPage"]:
            return sorted(names)
        after = page["pageInfo"]["endCursor"]


def excerpt(text: str) -> str:
    """The first sentence or so of a discussion body, plain text, cut at a word."""
    text = re.sub(r"\s+", " ", text or "").strip()
    if len(text) <= EXCERPT:
        return text
    cut = text[:EXCERPT].rsplit(" ", 1)[0].rstrip(",;:-")
    return cut + "…"


ANNOUNCEMENTS = "Announcements"      # category name; set its format to Announcement in the repo settings


def wanted(d: dict) -> bool:
    """True for a discussion that belongs on the Blog: it is in an Announcements category."""
    if d["title"].startswith("Welcome to "):
        return False
    return (d["category"] or {}).get("name") == ANNOUNCEMENTS


def collect() -> list[dict]:
    items = []
    for repo in repos_with_discussions():
        after = None
        while True:
            page = graphql(DISCUSSIONS_QUERY, owner=OWNER, name=repo, after=after)["repository"]["discussions"]
            for d in page["nodes"]:
                if wanted(d):
                    items.append({
                        "date": d["createdAt"],
                        "title": d["title"],
                        "url": d["url"],
                        "repo": repo,
                        "category": d["category"]["name"],
                        "excerpt": excerpt(d["bodyText"]),
                    })
            if not page["pageInfo"]["hasNextPage"]:
                break
            after = page["pageInfo"]["endCursor"]
    return sorted(items, key=lambda i: i["date"], reverse=True)


def main() -> int:
    out = Path(sys.argv[1] if len(sys.argv) > 1 else "blog/discussions.json")
    items = collect()
    report = {
        "generated": datetime.datetime.now(datetime.timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ"),
        "owner": OWNER,
        "items": items,
    }
    if out.exists() and json.loads(out.read_text()).get("items") == items:
        print(f"{len(items)} discussions, unchanged: {out} left as it is", file=sys.stderr)
        return 0  # keeps `generated` stable, so the daily workflow only commits real changes
    out.parent.mkdir(parents=True, exist_ok=True)
    out.write_text(json.dumps(report, indent=1, ensure_ascii=False) + "\n")
    by_repo = {}
    for i in items:
        by_repo[i["repo"]] = by_repo.get(i["repo"], 0) + 1
    print(f"{len(items)} discussions -> {out}: {by_repo}", file=sys.stderr)
    return 0


if __name__ == "__main__":
    sys.exit(main())
