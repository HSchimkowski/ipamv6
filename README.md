# Painting by Numbers: Visualisation of Structured IPv6-addressing
After getting IPv6-blocks (::/48 or ::/32) everyone has the problem to structure these addresses in an appropriate way, since otherwise the routing-tables will explode. Showing a two-dimensional approach to get a structured address-plan just by coloring squares (painting by numbers) instead of calculating hexadecimal numbers.

## Requirements
IPAMv6 is a web-server based PHP-Script, which interacts with the browser. For this reason the requirements are very small.
On the server-side you need a typical Webserver installation with:
- Apache webserver
- PHP 5.4
- MySQL 5.5
Other software versions should also work well – but i have never tested it.

On the client-side you only need a modern webbrowser with activated Javascript and HTML5 support.

The Installation is also very simple. In a few steps you can just unzip the Script to your webservers directory, create a MySQL database and enter your database credentials to the file: "config.php" - like millions of other web-sever based scripts.
