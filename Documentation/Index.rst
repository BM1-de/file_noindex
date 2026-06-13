:navigation-title: File No-Index

..  _start:

=============
File No-Index
=============

..  rst-class:: horizbuttons-tip-m

*   Exclude any single file from search engines, toggled per file in the
    file list — no developer needed.

:Extension key:
    file_noindex

:Package name:
    bm1/file-noindex

:Version:
    |release|

:Language:
    en

:Author:
    Phillip Baumgärtner & contributors

:License:
    This document is published under the
    `Open Publication License <https://www.opencontent.org/openpub/>`__.

:Rendered:
    |today|

----

Editors can exclude **any file** (images of all kinds, PDFs, …) from search
engine indexing directly in the **File list** module — one checkbox in the
file metadata, no matter where the file is used and without a developer.

The extension serves a dynamically generated :file:`robots.txt` that contains
``Disallow`` entries for every marked file — the original file **plus** its
processed variants. Blocking via :file:`robots.txt` is the
`way officially recommended by Google
<https://developers.google.com/search/docs/crawling-indexing/prevent-images-on-your-page>`__
to keep images out of Google Image Search.

----

**Table of contents:**

..  toctree::
    :maxdepth: 2
    :titlesonly:

    Introduction/Index
    Installation/Index
    Editor/Index
    Administration/Index
    KnownProblems/Index
