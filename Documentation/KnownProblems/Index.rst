..  _known-problems:

==========================
Known problems and limits
==========================

These are conscious design decisions of version 1, not bugs.

No access protection
====================

The file stays reachable via its direct link. :file:`robots.txt` only affects
crawlers that respect it (Google, Bing, …). For hard protection use
`EXT:fal_protect <https://extensions.typo3.org/extension/fal_protect>`__.

Already indexed images
======================

They disappear only after the next crawl (days to weeks). For immediate removal
additionally use :guilabel:`Google Search Console > Removals`.

Deliberate over-blocking by wildcards
=====================================

``csm_photo_*`` also matches variants of a file named :file:`photo_2.jpg`. When
in doubt the extension blocks too much rather than too little.

Specific user-agent groups win
==============================

If your :file:`robots.txt` contains a more specific group such as
``User-agent: Googlebot-Image``, that crawler ignores the ``User-agent: *``
group entirely — including the entries added by this extension. In that case
replicate the disallows in the specific group or remove it.

robots.txt size limit
=====================

Google reads :file:`robots.txt` only up to 500 KiB. With roughly three lines
per file this allows thousands of marked files; if you get anywhere near that,
reconsider your setup.

Multi-site with a shared fileadmin
==================================

All marked files are listed in the :file:`robots.txt` of **every** site that
shares the storage. Over-blocking across hosts is accepted in favour of a
simple and robust version 1.

Language independent
====================

The checkbox lives on the default-language metadata record and applies to the
file as such (``l10n_mode = exclude``).
