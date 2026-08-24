..  _installation:

============
Installation
============

The extension supports TYPO3 ``^12.4``, ``^13.4`` and ``^14.0`` on PHP 8.1 – 8.4
and has no dependencies besides the TYPO3 core.

Composer (recommended)
======================

..  code-block:: bash

    composer require bm1/file-noindex

Classic / Extension Manager
===========================

Install :php:`file_noindex` from the
`TYPO3 Extension Repository <https://extensions.typo3.org/extension/file_noindex>`__
via the Extension Manager.

Database schema
===============

After installation, run a database schema update so the new metadata field is
created:

*   **TYPO3 backend:** :guilabel:`Admin Tools > Maintenance > Analyze Database
    Structure`, then apply the suggested *ADD* change.
*   **CLI:** :bash:`vendor/bin/typo3 extension:setup` (TYPO3 v12:
    :bash:`vendor/bin/typo3 database:updateschema`, e.g. via EXT:typo3_console)

No further configuration is required.

..  note::

    On TYPO3 v13 and v14 the field is created from the TCA definition. For
    TYPO3 v12, which has no schema generation from the TCA, the extension
    ships an :file:`ext_tables.sql` with the identical column definition — so
    v13/v14 installations do not see a schema change either way.
