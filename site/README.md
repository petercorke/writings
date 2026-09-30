# Site template

The page template, stylesheet and scripts for docs.petercorke.com. `tools/build.py`
fills `index.template.html` with the document tiles and copies the rest into `_site/`.

- `index.template.html`: the page, with a placeholder for the tile sections.
- `style.css`: the stylesheet.
- `preview.js`: opens each tile's page preview beside it on hover.
- `filter.js`: the Find box, which filters tiles in the browser (`?q=` pre-fills it).
- `resources.template.html`: the page for each resource topic and their index (see
  `tools/resources.py`).
