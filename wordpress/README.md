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
- `llms.txt`: petercorke.com's `/llms.txt`, a guide for AI assistants to the books,
  toolboxes and this site. A static file in the site root.
