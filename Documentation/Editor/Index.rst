..  _editor:

==================
For editors: usage
==================

#.  Open the **File list** module and edit the metadata of a file (or open the
    file resource in the Media module).
#.  Switch to the :guilabel:`SEO` tab, enable :guilabel:`Do not index in search
    engines` and save.

..  figure:: /Images/checkbox-file-metadata.png
    :alt: The "Do not index in search engines" checkbox on the SEO tab

    The checkbox on the SEO tab of the file metadata form.

That's it. The file's :file:`robots.txt` entries appear immediately — no cache
flush needed:

..  figure:: /Images/robots-txt.png
    :alt: Generated robots.txt with disallow entries

    The generated :file:`robots.txt` with the disallow entries for the marked
    file and its processed variants.

Unticking the box removes the entries just as immediately.

..  important::

    Already indexed images disappear only **after the next crawl** (days to
    weeks). For immediate removal, additionally use
    :guilabel:`Google Search Console > Removals`.
