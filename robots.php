<?php
require_once __DIR__ . '/includes/database_connect.php';

header('Content-Type: text/plain; charset=utf-8');

$base = site_base_url();

echo "User-agent: *\n";
echo "Disallow: /api/\n";
echo "Disallow: /dashboard\n";
echo "Disallow: /logout\n";
echo "Disallow: /storage/\n";
echo "Disallow: /img/kyc/\n";
echo "Disallow: /img/receipts/\n";
echo "Disallow: /img/profiles/\n";
echo "Disallow: /vendor/\n";
echo "Disallow: /database/\n";
echo "Disallow: /tests/\n";
echo "Disallow: /includes/\n";
echo "\n";
echo "Allow: /\n";
echo "Allow: /home\n";
echo "Allow: /properties/\n";
echo "Allow: /pg/\n";
echo "Allow: /privacy\n";
echo "Allow: /terms\n";
echo "Allow: /disclaimer\n";
echo "Allow: /sitemap.xml\n";
echo "\n";
echo "Sitemap: {$base}/sitemap.xml\n";
