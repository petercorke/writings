# Tools

- `build.py`: builds the site into `_site/`: reads `groups.yml` and every
  `docs/<slug>/doc.yml`, finds or builds each PDF, renders thumbnails and previews, and
  writes the page, `docs.json`, `sitemap.xml` and `robots.txt`. Run `build.py --check` to
  validate the metadata only, and `--latex` to rebuild PDFs from source. The GitHub
  Actions workflow runs it on every push to `main`.
- `resources.py`: called by `build.py`; validates `resources/*.yml` and writes a page and a
  JSON file per topic under `_site/resources/`, plus an index.
- `linkcheck.py`: checks every link in `resources/*.yml` and writes `resources/linkcheck.json`
  (kept on the `data` branch); run monthly by `.github/workflows/linkcheck.yml`.
- `blogfeed.py`: collects the GitHub Discussions Peter started (plus announcements) from every
  repo of his that has them, for the Blog page; run daily by `.github/workflows/blog-feed.yml`.
- `discussions_awaiting.py`: lists open Discussions waiting for his reply, in two tiers (recent,
  backlog), for the weekly ecosystem report; run weekly by `.github/workflows/discussions-awaiting.yml`.
