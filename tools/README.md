# Tools

- `build.py`: builds the site into `_site/`: reads `groups.yml` and every
  `docs/<slug>/doc.yml`, finds or builds each PDF, renders thumbnails and previews, and
  writes the page, `docs.json`, `sitemap.xml` and `robots.txt`. Run `build.py --check` to
  validate the metadata only, and `--latex` to rebuild PDFs from source. The GitHub
  Actions workflow runs it on every push to `main`.
