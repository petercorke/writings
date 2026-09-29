// Open each hover preview towards whichever side of its thumbnail has more room, and
// shrink it to fit that space (up to 440 px), so it never runs off the screen.
const GAP = 12, MARGIN = 16, MAX = 440;
for (const thumb of document.querySelectorAll(".thumb")) {
  const preview = thumb.querySelector(".preview");
  const place = () => {
    const r = thumb.getBoundingClientRect();
    const right = window.innerWidth - r.right - GAP - MARGIN;
    const left = r.left - GAP - MARGIN;
    thumb.classList.toggle("flip", left > right);
    preview.style.width = `${Math.min(MAX, Math.max(left, right))}px`;
  };
  thumb.addEventListener("mouseenter", place);
  thumb.addEventListener("focus", place);
}
