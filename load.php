<?php
// OpenStreetMap tile cache to save bandwidth of tile servers in order to save money on tile hosting costs
// by Andrew MacKinnon
// Adapted from https://github.com/stlemme/osm-tiles-cache by Stefan Lemme
// Last updated May 21, 2018
//
// The MIT License (MIT)
//
// Copyright (c) 2018 Andrew MacKinnon
// Copyright (c) 2014 Stefan Lemme
//
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in all
// copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
// SOFTWARE.

$max_cache_time = 60*60*24*7; // maximum time to cache a tile before redownloading it in seconds (default 1 week)
$min_disk_space = 1073741824; // stop caching tiles when there is less than this amount of disk space (default 1 GiB)

// The following parameters must be set order for the tile cache to work: x, y, z, provider
// See https://wiki.openstreetmap.org/wiki/Slippy_map_tilenames for how the naming convention of OSM tiles works
// provider = provider of map tiles. For each provider there is a .php file in the 
// Tiles are fetched from the /api/tiles/provider/z/x/y.png directory
// Apache mod_rewrite is used to map this to parameters in load.php file

if (!isset($_GET["x"]) || !isset($_GET["y"]) || !isset($_GET["z"]) || !isset($_GET["provider"])) {
	header("HTTP/1.1 404 Not Found");
	die("Missing parameters!");
}

$x = intval($_GET["x"]);
$y = intval($_GET["y"]);
$z = intval($_GET["z"]);

$provider = $_GET["provider"];

// Provider configuration file is in provider/$provider.php
$provider_config = __DIR__ . "/provider/" . $provider . ".php";

if (!file_exists($provider_config)) {
	header("HTTP/1.1 404 Not Found");
	die("Provider not found: " . $provider);
}

require($provider_config);

// In each $provider.php there is an array called $urls with urls of 1 or more tile servers to download from
// We randomly choose one of the tile servers if more than one tile server is specified
$url = $urls[array_rand($urls)];

// PNGs are placed in the /api/tiles/cache/provider/ directory
$cache_dir = realpath(__DIR__ . "/cache");

if (!is_dir($cache_dir))
	mkdir($cache_dir, 0775);

$path = $cache_dir . "/" . $provider . "/" . $z . "/" . $x. "/";

// The file to load cached tile from and save the cached tile to if not already there
$file = $path . $y . ".png";

// If file already exists locally and not older than $max_cache_time
// Then output the file from disk
// HTTP cache control is set to 1 day so it will redownload from this server after 1 day
// Otherwise download it
if (file_exists($file) && time()-filemtime($file) < $max_cache_time) {
	header("HTTP/1.1 200 Found");
	header("Content-type: image/png");
	header("Cache-Control: public, max-age=86400");
	echo file_get_contents($file);
} else {
	$png = file_get_contents($url);
	if ($png === FALSE) {
		header("HTTP/1.1 404 Not Found");
		die("Could not download remote file: " . $url);
	} else {
		header("HTTP/1.1 200 Found");
		header("Content-type: image/png");
		header("Cache-Control: public, max-age=86400");
		echo $png;
		// If there is less than 1 GiB of free disk space do not write any files to disk
		// To prevent the entire hard drive from being filled up with cached tiles
		if (disk_free_space($cache_dir) >= $min_disk_space + strlen($png)) {
			// Create directory for cache if it does not exist already
			if (!is_dir($path)) {
				if (!mkdir($path, 0755, true)) {
					header("HTTP/1.1 500 Internal Server Error");
					die("Could not create path: " . $path);
				}
			}
			file_put_contents($file,$png);
		}
	}
}
?>