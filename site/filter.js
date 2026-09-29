// Filter the tiles as you type: a tile stays if its text (title, authors, facts, summary,
// links) contains every word typed. Groups left empty are hidden. The query is kept in the
// address as ?q=…, so a search can be linked to (petercorke.com's site search does this).
const form = document.querySelector(".filter");
const box = form.querySelector("input");
const count = form.querySelector("output");
const tiles = [...document.querySelectorAll(".tile")];
const fold = (s) => s.normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase();
const text = new Map(tiles.map((t) => [t, fold(t.querySelector(".text").textContent)]));

function apply() {
  const words = fold(box.value).split(/\s+/).filter(Boolean);
  let shown = 0;
  for (const t of tiles) {
    const keep = words.every((w) => text.get(t).includes(w));
    t.hidden = !keep;
    shown += keep;
  }
  for (const section of document.querySelectorAll("main section")) {
    section.hidden = !section.querySelector(".tile:not([hidden])");
  }
  count.textContent = words.length ? `${shown} of ${tiles.length} documents` : "";
  const url = new URL(location);
  words.length ? url.searchParams.set("q", box.value.trim()) : url.searchParams.delete("q");
  history.replaceState(null, "", url);
}

form.hidden = false;
form.addEventListener("submit", (e) => e.preventDefault());
box.addEventListener("input", apply);
box.value = new URLSearchParams(location.search).get("q") ?? "";
if (box.value) apply();
