# Resources

Curated lists of links for each topic of *Robotics, Vision & Control*: papers, tutorials,
software, datasets, videos and history. One file per topic, `<topic>.yml`; `topics.yml`
gives the topics in RVC3 chapter order and the chapter number of each topic in each
edition (the editions number some chapters differently).

The lists appear on the chapter pages of every edition on petercorke.com, and on
docs.petercorke.com, so a link is fixed once and changes everywhere.

## Format

```yaml
topic: localization
groups:
  - heading: Kalman filter
    items:
      - title: A new approach to linear filtering and prediction problems
        url: https://doi.org/10.1115/1.3662552
        note: Kalman (the paper!)
      - title: Kalman filter home page
        url: https://web.archive.org/web/20260514081034/http://www.cs.unc.edu/~welch/kalman/
        note: Welch and Bishop
        archived: true
```

- `title` and `url` are required; `note` is a short phrase shown after the link. A note can
  contain links written as `[text](url)`, e.g. `also as [PDF](https://example.org/x.pdf)`;
  the link text is shown, never a bare address (the build rejects a bare URL in a note).
- `archived: true` marks an Internet Archive copy of a page that no longer exists; it is
  shown with "(archived)".
- Prefer a DOI (`https://doi.org/...`) for published papers, and https addresses.
- Groups and items appear in the order written.

## History

Built in September 2026 from the lists on the RVC2, RVC3-Python and RVC3-MATLAB chapter
pages (merged, since each edition had its own copy), with dead links repaired, moved or
replaced by an archived copy, and links added from the Further Reading sections of both
RVC3 books.
