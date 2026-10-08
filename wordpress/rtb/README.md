# The old Robotics Toolbox pages, /RTB/

petercorke.com/RTB/ is the Robotics Toolbox for MATLAB's download area from about 2009. It
still serves RTB 9.x and the early releases, behind a one-page guestbook (organisation
and country), and it answers the version check made by RTB 9.10, 10.0 and 10.1 every time
MATLAB runs `startup_rtb`. Only the files here have been changed; the rest of /RTB/
(guestbook, landing page, zips) is as it was.

- `dl-zip.php`: serves a zip once the guestbook has been filled in. Since 1 Oct 2026 it
  serves only the toolbox zips; before that it would read any file or URL it was given.
- `currentversion.php`: answers the version check with `9.10`. (Before 1 Oct 2026 an
  `.htaccess` rule redirected it to the static `currentversion.txt`.)
- `telemetry.php`: counts both of these, by country and MATLAB release or file, with no IP
  address kept; see the comment at its top. Data is in `~/rtb-telemetry/` on the server,
  outside the web root.
- `stats.php`: the totals as JSON (`/RTB/stats.php`), or as CSV (`?csv=monthly`,
  `?csv=daily`). Read by the weekly RVC ecosystem report, and copied into `stats/rtb/` on the `data` branch
  monthly by `.github/workflows/rtb-stats.yml`.

Country data is DB-IP's "IP to Country Lite" (CC BY 4.0, https://db-ip.com), downloaded
and converted by `rtb_geo_refresh()` when it is more than a month old.
