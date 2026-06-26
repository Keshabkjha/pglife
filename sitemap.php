<?php
/**
 * Dynamic XML Sitemap for PG Life
 * Covers: home, city listing pages, property detail pages, legal pages
 * Author: Keshab Kumar — https://github.com/Keshabkjha
 */

require_once __DIR__ . '/includes/database_connect.php';

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');
header('Cache-Control: public, max-age=3600');

$base = 'https://www.pglife.in';
$now  = gmdate('Y-m-d');

$static_pages = [
    ['loc' => $base . '/home',       'changefreq' => 'daily',   'priority' => '1.0', 'lastmod' => $now],
    ['loc' => $base . '/privacy',    'changefreq' => 'monthly', 'priority' => '0.3', 'lastmod' => $now],
    ['loc' => $base . '/terms',      'changefreq' => 'monthly', 'priority' => '0.3', 'lastmod' => $now],
    ['loc' => $base . '/disclaimer', 'changefreq' => 'monthly', 'priority' => '0.3', 'lastmod' => $now],
];

$cities = [];
$res = mysqli_query($conn, "SELECT name, updated_at FROM cities ORDER BY name");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $cities[] = $row;
    }
}

$properties = [];
$res2 = mysqli_query($conn, "SELECT id, updated_at FROM properties ORDER BY id ASC");
if ($res2) {
    while ($row = mysqli_fetch_assoc($res2)) {
        $properties[] = $row;
    }
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

foreach ($static_pages as $page) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($page['loc']) . "</loc>\n";
    echo "    <lastmod>" . htmlspecialchars($page['lastmod']) . "</lastmod>\n";
    echo "    <changefreq>" . htmlspecialchars($page['changefreq']) . "</changefreq>\n";
    echo "    <priority>" . htmlspecialchars($page['priority']) . "</priority>\n";
    echo "  </url>\n";
}

foreach ($cities as $city) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($base . '/properties/' . rawurlencode($city['name'])) . "</loc>\n";
    $city_lastmod = !empty($city['updated_at']) ? date('Y-m-d', strtotime($city['updated_at'])) : $now;
    echo "    <lastmod>" . htmlspecialchars($city_lastmod) . "</lastmod>\n";
    echo "    <changefreq>daily</changefreq>\n";
    echo "    <priority>0.9</priority>\n";
    echo "  </url>\n";
}

foreach ($properties as $prop) {
    $lastmod = !empty($prop['updated_at']) ? date('Y-m-d', strtotime($prop['updated_at'])) : $now;
    $img_glob = glob("img/properties/" . (int)$prop['id'] . "/*");
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($base . '/pg/' . (int)$prop['id']) . "</loc>\n";
    echo "    <lastmod>" . $lastmod . "</lastmod>\n";
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>0.8</priority>\n";
    if (!empty($img_glob)) {
        foreach (array_slice($img_glob, 0, 5) as $img_path) {
            echo "    <image:image>\n";
            echo "      <image:loc>" . htmlspecialchars($base . '/' . htmlspecialchars($img_path)) . "</image:loc>\n";
            echo "    </image:image>\n";
        }
    }
    echo "  </url>\n";
}

echo '</urlset>';
