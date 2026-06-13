..  _administration:

==============
Administration
==============

The robots.txt middleware
==========================

A PSR-15 middleware answers ``GET`` and ``HEAD`` requests to exactly
:file:`/robots.txt` in the frontend stack — registered *after* the site
resolver (it needs the resolved site) and *before* TYPO3's static route
resolver (it must win over a configured :file:`robots.txt` route). Any other
request is passed through unchanged.

The response is ``200 text/plain; charset=utf-8`` with
``Cache-Control: public, max-age=3600``.

..  _administration-base-rules:

Base rules: the staticText route stays the source of truth
==========================================================

The middleware does not replace your base rules. It takes them from the site
configuration's :file:`robots.txt` route (type ``staticText``) and only appends
the file disallows. Your site configuration therefore stays the single place to
maintain the base rules:

..  code-block:: yaml
    :caption: config/sites/<identifier>/config.yaml

    routes:
      -
        route: robots.txt
        type: staticText
        content: "User-agent: *\r\nDisallow: /typo3/\r\n"

If no such route exists, a minimal ``User-agent: *`` group is used as the base.

The disallow entries are inserted into the **last existing**
``User-agent: *`` group — not appended as a second ``*`` group, because not all
crawlers merge groups of the same name.

What gets listed per file
=========================

Only files from **local, public** storages are considered. For each marked
file the builder emits:

#.  the original file path (properly URL-encoded),
#.  every currently existing processed variant from
    :sql:`sys_file_processedfile`,
#.  wildcard patterns (``csm_<name>_*`` and ``preview_<name>_*`` inside the
    storage's processing folder) covering variants generated in the future.

Renamed or moved files are reflected automatically on the next
:file:`robots.txt` request, because the lookup happens live.

..  _administration-caching:

Caching
=======

Version 1 deliberately ships **without** its own cache. :file:`robots.txt` is
requested rarely (by crawlers); one indexed query per request is uncritical,
and live generation makes every checkbox change effective immediately without
any cache-invalidation logic.

Fail-safe
=========

A :file:`robots.txt` answered with a ``5xx`` status makes crawlers treat the
**whole site** as disallowed. Should the disallow list fail to build (for
example, when the extension is installed but the database schema update has not
run yet), the middleware logs the error and serves the base rules **without**
file entries instead of letting the request fail.
