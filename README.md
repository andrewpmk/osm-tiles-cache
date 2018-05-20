osm-tiles-cache
===============

PHP-based cache for openstreetmap tiles
Copyright (c) 2018 Andrew MacKinnon
Copyright (c) 2014 Stefan Lemme

Released under the MIT license, see LICENSE for more information.

Requirements: A Linux server with Apache and PHP installed.

How to install:

1. Create a directory /var/www/html/api/tiles/
2. Copy the load.php file to /var/www/html/api/tiles/
3. Copy the provided .htaccess file to /var/www/html/api/tiles/ and enable mod_rewrite support. This will rewrite requests for tiles like /api/tiles/osm/10/283/370.png to load.php.
4. Create a folder /var/www/html/api/tiles/preload/. For each tile server you want to use, copy a .php.sample to name.php where name is the name of the tile server that you wish to use. Please see https://wiki.openstreetmap.org/wiki/Tile_servers for a list of tile servers. Make sure you have permission to use the tile server you wish to use. Please be advised that the Mapbox tile server service does not permit tile caching under its terms of service.
5. Optional: There is also a preload.php which pre-downloads tiles and places them into the cache directory. If you wish, you can also copy this file to /var/www/html/api/tiles/ to enable the preloader. WARNING: ALWAYS add an entry to .htaccess that either restricts access to this file to the localhost or requires a password to view preload.php. Otherwise, a malicious user can download a large area of tiles and fill up all your disk space! Also, keep in mind that some tile providers may dislike you using preload.php because it downloads a large number of tiles and uses up a large amount of server resources.
6. Strongly recommended:

Run sudo crontab -e and add the following line to crontab:

0 7 * * * find /var/www/html/api/tiles/cache/ -mindepth 1 -mtime +7 -delete

This deletes any tile in the tile cache directory every day at 7:00 UTC which is older than 1 week (+7). Change the directory, age and time to run the cron script as appropriate. This will prevent the tile cache directory from taking up too much disk space from old tiles which we never will use anyway.