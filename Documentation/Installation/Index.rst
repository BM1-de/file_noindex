..  _installation:

============
Installation
============

The extension supports TYPO3 ``^13.4`` and ``^14.0`` on PHP 8.2 – 8.4 and has
no dependencies besides the TYPO3 core.

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
*   **CLI:** :bash:`vendor/bin/typo3 extension:setup`

No further configuration is required.

..  note::

    The field is created automatically from the TCA definition — the extension
    ships **no** :file:`ext_tables.sql`.
