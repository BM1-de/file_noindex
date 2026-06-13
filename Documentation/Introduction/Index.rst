..  _introduction:

============
Introduction
============

What does it do?
================

A person on a team photo asks not to appear in Google Image Search. Instead of
touching templates or moving files, an editor opens the file's metadata, ticks
*"Do not index in search engines"*, saves — done. The next time crawlers fetch
:file:`robots.txt`, the file and all its rendered variants are disallowed.

:php:`file_noindex` works for **every file type** (images, PDFs, …),
independently of where and how the file is referenced, and it needs no
configuration.

How it works in a nutshell
==========================

A PSR-15 middleware answers ``GET /robots.txt``. It takes the base rules from
your site configuration (the :ref:`staticText route <administration>`, if
present) and appends ``Disallow`` entries for every file whose metadata has the
"do not index" flag set:

*   the original file path,
*   all currently existing processed variants (:file:`_processed_/…`),
*   wildcard patterns covering variants that will be generated in the future.

The response is generated live on every request, so toggling the checkbox takes
effect immediately — no cache to flush.

..  _introduction-scope:

What it is — and what it is not
===============================

..  card-group::

    ..  card:: It keeps files out of search results

        Crawlers that respect :file:`robots.txt` (Google, Bing, …) stop
        indexing the marked files. This is the method Google officially
        recommends for images.

    ..  card:: It is **not** access protection

        The file stays reachable via its direct link. If you need to actually
        protect files from being downloaded, use
        `EXT:fal_protect <https://extensions.typo3.org/extension/fal_protect>`__
        instead.

See :ref:`known-problems` for the full list of deliberate limitations.
