#!/usr/bin/env python3
"""List the GitHub Discussions that are waiting for Peter's reply, for the weekly ecosystem report.

    python tools/discussions_awaiting.py [output.json]    # default: reports/discussions-awaiting.json

The same rule the report uses for issues and PRs: an open discussion is "awaiting your reply" when
its most recent activity (the post itself, a comment, or a reply to a comment) is from a human
other than Peter. Bots are ignored. Looks at every public, non-fork, non-archived repo of OWNER
that has Discussions on (found the same way as tools/blogfeed.py, whose helpers this reuses).

Two tiers, split at RECENT_DAYS: "recent" discussions are listed one by one in the report, and
the older "backlog" is reported as a count plus the oldest few, so a pile of stale threads can't
bury the new ones. `answered` is GitHub's Q&A "answered" mark.

Looks at the latest 30 comments of a discussion and the latest 30 replies to each, so a reply
added under a very old comment of a very long thread could be missed. On any API error it exits
non-zero without touching the output file.
"""
import datetime
import json
import sys
from pathlib import Path

from blogfeed import ME, OWNER, graphql, repos_with_discussions

RECENT_DAYS = 365      # waiting no longer than this: listed individually
OLDEST_SHOWN = 3       # backlog items named in the summary

QUERY = """
query($owner: String!, $name: String!, $after: String) {
  repository(owner: $owner, name: $name) {
    discussions(first: 50, after: $after, orderBy: {field: CREATED_AT, direction: DESC}) {
      pageInfo { hasNextPage endCursor }
      nodes {
        number title url createdAt closed isAnswered category { name }
        author { login __typename }
        comments(last: 30) {
          totalCount
          nodes {
            createdAt author { login __typename }
            replies(last: 30) { nodes { createdAt author { login __typename } } }
          }
        }
      }
    }
  }
}"""


def is_bot(author: dict | None) -> bool:
    return bool(author) and (author.get("__typename") == "Bot" or author["login"].endswith("[bot]"))


def latest_activity(d: dict) -> tuple[str, dict | None]:
    """(timestamp, author) of the newest post, comment or reply."""
    events = [(d["createdAt"], d["author"])]
    for c in d["comments"]["nodes"]:
        events.append((c["createdAt"], c["author"]))
        events += [(r["createdAt"], r["author"]) for r in c["replies"]["nodes"]]
    return max(events, key=lambda e: e[0])


def collect(now: datetime.datetime) -> list[dict]:
    items = []
    for repo in repos_with_discussions():
        after = None
        while True:
            page = graphql(QUERY, owner=OWNER, name=repo, after=after)["repository"]["discussions"]
            for d in page["nodes"]:
                if d["closed"]:
                    continue
                when, who = latest_activity(d)
                if (who or {}).get("login") == ME or is_bot(who):
                    continue
                days = (now - datetime.datetime.fromisoformat(when.replace("Z", "+00:00"))).days
                items.append({
                    "repo": repo,
                    "number": d["number"],
                    "title": d["title"],
                    "url": d["url"],
                    "category": d["category"]["name"],
                    "opened_by": (d["author"] or {}).get("login", "ghost"),
                    "last_by": (who or {}).get("login", "ghost"),
                    "last_at": when,
                    "days": days,
                    "comments": d["comments"]["totalCount"],
                    "answered": bool(d["isAnswered"]),
                    "tier": "recent" if days <= RECENT_DAYS else "backlog",
                })
            if not page["pageInfo"]["hasNextPage"]:
                break
            after = page["pageInfo"]["endCursor"]
    return sorted(items, key=lambda i: i["last_at"])  # oldest-waiting first


def main() -> int:
    out = Path(sys.argv[1] if len(sys.argv) > 1 else "reports/discussions-awaiting.json")
    now = datetime.datetime.now(datetime.timezone.utc)
    items = collect(now)
    backlog = [i for i in items if i["tier"] == "backlog"]
    report = {
        "generated": now.strftime("%Y-%m-%dT%H:%M:%SZ"),
        "owner": OWNER,
        "recent_days": RECENT_DAYS,
        "counts": {
            "total": len(items),
            "recent": len(items) - len(backlog),
            "backlog": len(backlog),
            "backlog_never_replied": sum(1 for i in backlog if i["comments"] == 0),
        },
        "oldest_backlog": [{k: i[k] for k in ("repo", "number", "title", "url", "days")} for i in backlog[:OLDEST_SHOWN]],
        "items": items,
    }
    out.parent.mkdir(parents=True, exist_ok=True)
    out.write_text(json.dumps(report, indent=1, ensure_ascii=False) + "\n")
    print(f"{len(items)} awaiting a reply -> {out}: {report['counts']}", file=sys.stderr)
    return 0


if __name__ == "__main__":
    sys.exit(main())
