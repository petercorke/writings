# Buy links: petercorke.com/buy/

`index.php` sends a reader to the best place to buy a book, by the country of their IP
address: an Amazon store where Peter is an Associate (with that store's tag), otherwise
the publisher (Peter's books) or the reader's own Amazon store (other books). See the
comment at the top of `index.php`.

- `/buy/rvc3p`, `/buy/rvc3m`, `/buy/rvc2`, `/buy/rvc1`: Peter's books (the Books page)
- `/buy/isbn/<ISBN-10>`: any book by its print ISBN-10 ("Books I like")
- add `?store=au`, `?store=us` or `?store=<domain>` (e.g. `co.uk`) to force a store you
  have a tag for, for testing

Clicks are counted by country, book and destination as the event `buy` in
`~/rtb-telemetry/` (see `../rtb/telemetry.php`); no IP addresses are kept.

## Joining another Amazon store: checklist

1. **Enrol** (Peter: the form needs tax and bank details). List `https://petercorke.com`
   as your website; Amazon's rules need the clicks to come from a listed site. Note the
   tag it gives you (e.g. `petercorke-21`). The new account must make about three sales
   within 180 days or Amazon closes it, so join only where the click counts show demand.
2. **Add the tag** to `TAGS` in `index.php`, keyed by the store's domain:
   `'co.uk' => 'petercorke-21',`.
3. **Check the country map**: `STORES` maps each country to its Amazon store; make sure
   every country that store serves points at it (e.g. `GB` and `IE` → `co.uk`; `DE`, `AT`
   and `CH` → `de`).
4. **Check the books exist there**: `https://www.amazon.<domain>/dp/<ISBN-10>` for the four
   RVC ISBNs in `BOOKS` (and spot-check a few from "Books I like").
5. **Deploy and test**: copy `index.php` to `~/www/petercorke.com/www/buy/` on the
   server, run `php -l` on it, then open `https://petercorke.com/buy/rvc3p?store=<domain>`
   and check the Amazon page shows your tag in the address. Delete the test clicks from
   `~/rtb-telemetry/raw-<month>.tsv` (lines with `<TAB>buy<TAB>`).
6. **Update the disclosures**, which name the stores: the Affiliate disclosure page
   (`/affiliate-disclosure/`), the line at the bottom of the Books page (`/books/`) and
   the one at the bottom of "Books I like" (`/interesting-books/`).
7. **Commit** `index.php` here.

Readers in that store's countries then get your tagged link instead of Springer (Peter's
books) or an untagged link (other books); nothing else changes.
