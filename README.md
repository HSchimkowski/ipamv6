# Painting by Numbers: Visualisation of Structured IPv6-addressing
After getting IPv6-blocks (::/48 or ::/32) everyone has the problem to structure these addresses in an appropriate way, since otherwise the routing-tables will explode. Showing a two-dimensional approach to get a structured address-plan just by coloring squares (painting by numbers) instead of calculating hexadecimal numbers.

## Requirements
IPAMv6 is a web-server based PHP-Script, which interacts with the browser. For this reason the requirements are very small.
On the server-side you need a typical Webserver installation with:
- Apache webserver (or any other webserver with PHP support)
- PHP 8.2 or newer with the mysqli extension (tested with PHP 8.2, 8.3 and 8.4)
- MySQL 5.7+ or MariaDB 10.3+

On the client-side you only need a modern webbrowser with activated Javascript and HTML5 support.

## Installation
1. Copy the files to your webservers directory.
2. Create a database and import `ipamv6.sql`.
3. Configure the database credentials. Either create a file `config.local.php` (it is not under version control):
   ```php
   <?php
   define("DB_HOST", 'localhost');
   define("DB_NAME", 'ipamv6');
   define("DB_USER", 'ipamv6');
   define("DB_PASS", 'a-secure-password');
   ```
   or set the environment variables `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` and `DB_PASS`.
   The defaults in `config.inc.php` are only meant for development.
4. Make sure `display_errors` is disabled in the `php.ini` of your production server.

IPAMv6 has no user management. Run it only in a protected network or behind an authentication of your webserver (e.g. HTTP Basic Auth).
All forms that change data use POST with a CSRF token, so PHP sessions must work on your server.

## Tests
The smoke tests start PHP's built-in webserver and run against a MySQL/MariaDB database (the database is reset with `ipamv6.sql` before each test):
```sh
DB_HOST=127.0.0.1 tests/run.sh
```
On GitHub the tests run automatically for PHP 8.2, 8.3 and 8.4 on every push (see `.github/workflows/ci.yml`).
