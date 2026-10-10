#!/usr/bin/env python3
"""Checks the selection rule of blogfeed.wanted(). Run: python3 tools/test_blogfeed.py"""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from blogfeed import wanted


def d(title: str, category: str | None, author: str = "petercorke") -> dict:
    return {"title": title, "category": {"name": category} if category else None, "author": {"login": author}}


CASES = [
    ("a release note in Announcements", d("V1.4.0 released", "Announcements"), True),
    ("an Announcements post by someone else (cannot happen with the Announcement format)", d("x", "Announcements", "someone"), True),
    ("Peter's own General post is not promoted", d("Start here: where to post", "General"), False),
    ("Peter's own Ideas post is not promoted", d("Simple way to control IK solution configurations", "Ideas"), False),
    ("Peter's own Q&A post is not promoted", d("What do you use bdsim for?", "Q&A"), False),
    ("another person's question is not promoted", d("How do I...?", "Q&A", "someone"), False),
    ("GitHub's auto welcome post is skipped even in Announcements", d("Welcome to bdsim Discussions!", "Announcements"), False),
    ("a missing category is not promoted", d("x", None), False),
]

failed = 0
for label, disc, expected in CASES:
    got = wanted(disc)
    status = "ok  " if got == expected else "FAIL"
    failed += got != expected
    print(f"{status} {label}: wanted={got}")
sys.exit(1 if failed else 0)
