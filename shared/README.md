# Shared LaTeX files

Files used by more than one document's LaTeX source, found by the build through
`TEXINPUTS` and `BIBINPUTS`:

- `tex/`: macro files (`pic-common.tex`, `matlab.tex`).
- `bib/`: bibliographies, with private fields stripped.

They are frozen copies, taken in September 2026, and not kept in sync with the originals;
see "Shared files" in the top-level README. `rvc-notation` is not copied here: the build
checks out its repository.
