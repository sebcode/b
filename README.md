# b - Bookmark manager

b is a minimalistic bookmark manager for your own server. Written in PHP.
Bookmarks are stored in a sqlite database. Features:

 * filtering
 * tagging
 * automatic fetching of page title
 * infinite scrolling (optional)
 * bookmarklet
 * multiple users

### Requirements

 * make
 * docker + docker compose

Tested on Ubuntu 21.04 and macOS Big Sur.

### Setup

This web app uses HTTP basic auth password protection. Create a `htusers` file
and specify username/password like this:

    mkdir db
    ./htpasswd -c db/htusers peter

The bookmark manager can host multiple databases. To initialize a new database,
simply create a subdirectory:

    mkdir db/peter

This will make bookmarks accessible via `http://localhost:9090/peter`.

Use `make` to start the webserver container and `make down` to stop it.

To prevent forking the container into the background, use `make up` instead of
`make` (useful for debugging).

### How to use

 * To add a new bookmark, simply paste it into the input field and press
   return. the url may be followed by hash tags, e.g. `http://example.com
   #example #bla #wurst`
 * The website's title is automatically fetched and the bookmark is added to
   the database.
 * Edit title by double clicking it. This opens a prompt-dialog where you can
   edit the title. Enter '-' (minus sign) to remove an entry.
 * To edit the URL, double click beside the link.
 * The input field can also be used to filter bookmarks. Filtering is done with
   a full-text search on all titles. Search terms are separated by spaces
   and joined with AND.

### Infinite scrolling

If you have a massive amount of bookmarks and you don't want to load them all at
once, you can activate infinite scrolling. This will load a limited amount of
bookmarks initially and load more when you scroll to the bottom of the page.
Activate infinite scrolling by adding `INFINITE_SCROLLING=200` to `.env`.
Replace `200` with the number of bookmarks you want to load each time you hit
the bottom.

### Bookmarklet

Visit `/[user]/bookmarklet` to access the user's bookmarklet, e.g.
`http://bookmarks.example.com/peter/bookmarklet`. (Thanks to nibreh for the
suggestion!)

### Credits

Copyright (c) 2011-2021 Sebastian Volland http://github.com/sebcode

The source code is licensed under the terms of the MIT license (see LICENSE
file).
