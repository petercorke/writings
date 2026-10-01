# WordPress add-ons for petercorke.com

Files installed on petercorke.com that belong with this site. Edit them here, then copy
them to the server; the copies here are the masters.

- `docs-search.php`: a must-use plugin (`wp-content/mu-plugins/`) that lists matching
  documents from `docs.json` in the site search results.
- `search.php`: the theme's search template (`wp-content/themes/zephyr_petercorke/`),
  changed to show those documents above the page and post results.
- `rvc-resources.php`: a must-use plugin providing `[rvc_resources topic="…"]`, which shows
  a topic's resource list (from `resources/`, via docs.petercorke.com) on a chapter page.
- `mathjax-uncombined.php`: a must-use plugin that keeps MathJax out of SiteGround Speed
  Optimizer's combined JavaScript, where it can't load its configuration and equations
  show as raw LaTeX.
- `anniversary-tag.php`: a must-use plugin adding `[anniversary]` to Simple Calendar's event
  template, so "This day in robotics" shows "1930 (96 years ago): Jacques Denavit born". The
  calendar's template (calendar post 832) uses `[anniversary]` in place of `[title]`.
- `rtb/`: the old Robotics Toolbox download area, petercorke.com/RTB/: the download
  script, the version check answered by RTB 9.10–10.1, and usage counting by country
  (see `rtb/README.md`).
- `llms.txt`: petercorke.com's `/llms.txt`, a guide for AI assistants to the books,
  toolboxes and this site. A static file in the site root.
