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

 * PHP 5.6+
 * PHP module sqlite
 * PHP module curl

### Configuration instructions

 * Copy all repository files to a directory accessible by the webserver-user,
   like `/var/www/b`
 * Move `config.template.php` to `config.php` and edit it
   * `baseDir` is the directory where the sqlite dbs are stored, e.g.
     `/var/bookmarks/`. The directory must be readable and writeable by the
     webserver-user
   * `baseUri` is the base uri's relative path param, e.g. `/b/` if the website
     is accessible via `http://example.com/b/`. Or use `/` for
     `http://bookmarks.example.com/` for example, if you want to have a
     dedicated subdomain for the service.
 * Create a new user-account simply by creating a new directory in `baseDir`:
   `mkdir /var/bookmarks/peter/`

#### Webserver configuration example for apache

 * If you want your bookmarks to be accessible under their own subdomain like
   `http://bookmarks.example.com/`, add a virtual host to your `httpd.conf`:

        <VirtualHost *:80>
          ServerName bookmarks.example.com
          DocumentRoot "/var/www/b"

          <Directory />
            RewriteEngine On
            RewriteBase /
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule . index.php [L]

            # Password protection            
            AuthType Basic
            AuthName b
            AuthUserFile /opt/bookmarks/htusers
            Require user peter
          </Directory>
        </VirtualHost>

 * Create a password file and restart apache. If you don't want to use a
   password, remove the "Password protection" part from the virtual host
   configuration.

        htpasswd -c /opt/bookmarks/htusers peter
        apachectl restart

 * If you want to use a localhost fake domain, add the host to your
   `/etc/hosts` file:

        127.0.0.1 bookmarks.example.com

 * Peter's bookmarks should now be accessible via
   `http://bookmarks.example.com/peter`
 * Run a configuration-check via
   `http://bookmarks.example.com/index.php?configtest`

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
   a full-text search on all titles.

### Infinite scrolling

If you have a massive amount of bookmarks and you don't want to load them all at
once, you can activate infinite scrolling. This will load a limited amount on
bookmarks initially and load more when you scroll to the bottom of the page.
Activate infinite scrolling by adding `'infiniteScrolling' => 200` to your
`config.php`. Replace `200` with the number of bookmarks you want to load each
time you hit the bottom.

### Bookmarklet

Visit `/[user]/bookmarklet` to access the user's bookmarklet, e.g.
`http://bookmarks.example.com/peter/bookmarklet`. (Thanks to nibreh for the
suggestion!)

### Credits

Copyright (c) 2011-2017 Sebastian Volland http://github.com/sebcode

The source code is licensed under the terms of the MIT license (see LICENSE
file).
