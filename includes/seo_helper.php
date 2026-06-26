<?php
/**
 * PGLife SEO Helper
 * Centralizes all meta tag, Open Graph, Twitter Card, canonical,
 * and JSON-LD structured data generation.
 *
 * Creator: Keshab Kumar — https://github.com/Keshabkjha
 */

define('SITE_URL',        'https://www.pglife.in');
define('SITE_NAME',       'PG Life');
define('SITE_TAGLINE',    'Find Your Perfect PG Accommodation in India');
define('SITE_LOGO',       SITE_URL . '/img/logo.png');
define('CREATOR_NAME',    'Keshab Kumar');
define('CREATOR_GITHUB',  'https://github.com/Keshabkjha');
define('CREATOR_LINKEDIN','https://www.linkedin.com/in/keshabkjha');
define('CREATOR_EMAIL',   'keshabkumarjha876@gmail.com');
define('DEFAULT_OG_IMG',  SITE_URL . '/img/bg.jpg');
define('CREATOR_SOCIAL',  [
    CREATOR_GITHUB,
    CREATOR_LINKEDIN,
    'https://www.kaggle.com/keshabkkumar',
    'https://peerlist.io/keshabkjha',
    'https://instagram.com/keshabkjha',
    'https://www.facebook.com/keshabkjha',
    'https://wakatime.com/@Keshabkjha',
    'https://codolio.com/profile/Keshabkjha',
    'https://leetcode.com/Keshabkjha/',
    'https://linktr.ee/Keshabkjha',
    'https://codeforces.com/profile/keshabkjha',
    'https://medium.com/@keshabkjha',
]);

/**
 * Render the full <head> SEO block for a page.
 *
 * @param array $opts {
 *   title       string  Page <title>
 *   description string  Meta description (max ~155 chars)
 *   canonical   string  Full canonical URL
 *   og_title    string  OG/Twitter title (optional, falls back to title)
 *   og_desc     string  OG/Twitter description (optional)
 *   og_image    string  Absolute OG image URL
 *   og_type     string  "website"|"article"|"product" (default: website)
 *   noindex     bool    true = noindex,nofollow
 *   schema      array   Array of JSON-LD objects to embed
 *   breadcrumbs array   [{name, url}, ...] for BreadcrumbList schema
 *   keywords    string  Optional meta keywords
 * }
 */
function seo_head(array $opts): void {
    $title      = $opts['title']      ?? SITE_NAME;
    $desc       = $opts['description']?? SITE_TAGLINE;
    $canonical  = $opts['canonical']  ?? SITE_URL . '/home';
    $og_title   = $opts['og_title']   ?? $title;
    $og_desc    = $opts['og_desc']    ?? $desc;
    $og_image   = $opts['og_image']   ?? DEFAULT_OG_IMG;
    $og_type    = $opts['og_type']    ?? 'website';
    $noindex    = $opts['noindex']    ?? false;
    $schemas    = $opts['schema']     ?? [];
    $breadcrumbs = $opts['breadcrumbs'] ?? [];
    $keywords   = $opts['keywords']   ?? '';

    // --- Title ---
    echo '<title>' . htmlspecialchars($title) . '</title>' . "\n";

    // --- Robots ---
    $robots = $noindex ? 'noindex,nofollow' : 'index,follow,max-snippet:-1,max-image-preview:large,max-video-preview:-1';
    echo '<meta name="robots" content="' . $robots . '">' . "\n";

    // --- Canonical ---
    echo '<link rel="canonical" href="' . htmlspecialchars($canonical) . '">' . "\n";

    // --- Description ---
    echo '<meta name="description" content="' . htmlspecialchars($desc) . '">' . "\n";

    if ($keywords) {
        echo '<meta name="keywords" content="' . htmlspecialchars($keywords) . '">' . "\n";
    }

    // --- Author / Creator ---
    echo '<meta name="author" content="' . CREATOR_NAME . '">' . "\n";
    echo '<meta name="creator" content="' . CREATOR_NAME . '">' . "\n";

    // --- Open Graph ---
    echo '<meta property="og:type"        content="' . htmlspecialchars($og_type)  . '">' . "\n";
    echo '<meta property="og:site_name"   content="' . SITE_NAME . '">' . "\n";
    echo '<meta property="og:title"       content="' . htmlspecialchars($og_title) . '">' . "\n";
    echo '<meta property="og:description" content="' . htmlspecialchars($og_desc)  . '">' . "\n";
    echo '<meta property="og:url"         content="' . htmlspecialchars($canonical) . '">' . "\n";
    echo '<meta property="og:image"       content="' . htmlspecialchars($og_image)  . '">' . "\n";
    echo '<meta property="og:image:width"  content="1200">' . "\n";
    echo '<meta property="og:image:height" content="630">' . "\n";
    echo '<meta property="og:image:alt"    content="' . htmlspecialchars($og_title) . '">' . "\n";
    echo '<meta property="og:locale"      content="en_IN">' . "\n";
    echo '<meta property="og:locale:alternate" content="en_US">' . "\n";

    // --- Twitter / X Card ---
    echo '<meta name="twitter:card"        content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title"       content="' . htmlspecialchars($og_title) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . htmlspecialchars($og_desc)  . '">' . "\n";
    echo '<meta name="twitter:image"       content="' . htmlspecialchars($og_image)  . '">' . "\n";
    echo '<meta name="twitter:image:alt"   content="' . htmlspecialchars($og_title) . '">' . "\n";
    echo '<meta name="twitter:creator"     content="@keshabkjha">' . "\n";
    echo '<meta name="twitter:site"        content="@keshabkjha">' . "\n";

    // --- Structured Data: WebSite (sitelinks searchbox eligible) ---
    $website_schema = [
        '@context'      => 'https://schema.org',
        '@type'         => 'WebSite',
        '@id'           => SITE_URL . '/#website',
        'url'           => SITE_URL,
        'name'          => SITE_NAME,
        'description'   => SITE_TAGLINE,
        'inLanguage'    => 'en-IN',
        'copyrightYear' => date('Y'),
        'copyrightHolder' => [
            '@type' => 'Organization',
            '@id'   => SITE_URL . '/#organization',
        ],
        'author'        => ['@id' => SITE_URL . '/#person'],
        'publisher'     => ['@id' => SITE_URL . '/#organization'],
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => SITE_URL . '/properties/{search_term_string}'],
            'query-input' => 'required name=search_term_string',
        ],
    ];
    _emit_schema($website_schema);

    // --- Structured Data: Person (Keshab Kumar) ---
    $person_schema = [
        '@context'   => 'https://schema.org',
        '@type'      => 'Person',
        '@id'        => SITE_URL . '/#person',
        'name'       => CREATOR_NAME,
        'url'        => CREATOR_GITHUB,
        'email'      => CREATOR_EMAIL,
        'jobTitle'   => 'Software Developer',
        'description' => 'Full-stack software developer and creator of PG Life. Open source contributor with expertise in PHP, JavaScript, Bootstrap, and MySQL.',
        'sameAs'     => CREATOR_SOCIAL,
        'knowsAbout' => ['Full-Stack Development', 'PHP', 'JavaScript', 'Bootstrap', 'MySQL', 'SEO', 'Web Performance', 'Open Source'],
    ];
    _emit_schema($person_schema);

    // --- Structured Data: Organization ---
    $org_schema = [
        '@context'        => 'https://schema.org',
        '@type'           => 'Organization',
        '@id'             => SITE_URL . '/#organization',
        'name'            => SITE_NAME,
        'url'             => SITE_URL,
        'logo'            => ['@type' => 'ImageObject', 'url' => SITE_LOGO],
        'image'           => SITE_LOGO,
        'founder'         => ['@id' => SITE_URL . '/#person'],
        'foundingDate'    => '2024',
        'description'     => SITE_TAGLINE . ' Created by Keshab Kumar to simplify PG accommodation search across India.',
        'areaServed'      => 'IN',
        'knowsAbout'      => ['PG Accommodation', 'Paying Guest', 'Co-living', 'India Rental Housing', 'Property Management'],
        'sameAs'          => [
            SITE_URL,
            CREATOR_GITHUB,
            CREATOR_LINKEDIN,
            'https://www.kaggle.com/keshabkkumar',
            'https://peerlist.io/keshabkjha',
            'https://instagram.com/keshabkjha',
            'https://www.facebook.com/keshabkjha',
            'https://wakatime.com/@Keshabkjha',
            'https://codolio.com/profile/Keshabkjha',
            'https://leetcode.com/Keshabkjha/',
            'https://linktr.ee/Keshabkjha',
            'https://codeforces.com/profile/keshabkjha',
            'https://medium.com/@keshabkjha',
        ],
    ];
    _emit_schema($org_schema);

    // --- BreadcrumbList ---
    if (!empty($breadcrumbs)) {
        $items = [];
        foreach ($breadcrumbs as $pos => $crumb) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $pos + 1,
                'name'     => $crumb['name'],
                'item'     => $crumb['url'],
            ];
        }
        _emit_schema(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items]);
    }

    // --- Custom page-level schemas ---
    foreach ($schemas as $s) {
        _emit_schema($s);
    }
}

function _emit_schema(array $schema): void {
    echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}

/**
 * Build a SoftwareApplication schema for the app itself.
 */
function schema_software_app(): array {
    return [
        '@context'           => 'https://schema.org',
        '@type'              => 'SoftwareApplication',
        'name'               => SITE_NAME,
        'url'                => SITE_URL,
        'applicationCategory'=> 'LifestyleApplication',
        'operatingSystem'    => 'Web',
        'offers'             => [
            '@type'        => 'Offer',
            'price'        => '0',
            'priceCurrency'=> 'INR',
            'description'  => 'Completely free to use for both seekers and property owners',
        ],
        'author'             => ['@id' => SITE_URL . '/#person'],
        'description'        => 'PG Life is a full-featured Paying Guest accommodation finder for India. Find, filter, book and review PGs in Delhi, Mumbai, Bengaluru, Hyderabad and 7 more cities.',
        'screenshot'         => SITE_URL . '/product/home_1.png',
        'featureList'        => 'PG search, gender filter, amenity filter, rent sort, map location, booking, reviews, owner dashboard',
        'inLanguage'         => 'en-IN',
        'softwareVersion'    => '2.0',
        'copyrightYear'      => date('Y'),
        'copyrightHolder'    => ['@id' => SITE_URL . '/#organization'],
    ];
}

/**
 * Build a RealEstateListing / LodgingBusiness schema for a single property.
 */
function schema_property(array $property, string $city_name, array $amenities = [], array $reviews = []): array {
    $url = SITE_URL . '/pg/' . (int)$property['id'];
    $avg = round(($property['rating_clean'] + $property['rating_food'] + $property['rating_safety']) / 3, 1);

    $schema = [
        '@context'       => 'https://schema.org',
        '@type'          => 'LodgingBusiness',
        '@id'            => $url,
        'name'           => $property['name'],
        'description'    => $property['description'] ?? '',
        'url'            => $url,
        'address'        => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $property['address'],
            'addressLocality' => $city_name,
            'addressCountry'  => 'IN',
        ],
        'priceRange'     => '₹' . number_format($property['rent']) . '/month',
        'telephone'      => $property['owner_phone'] ?? '',
        'amenityFeature' => [],
    ];

    if (!empty($property['latitude']) && !empty($property['longitude'])) {
        $schema['geo'] = [
            '@type'     => 'GeoCoordinates',
            'latitude'  => (float)$property['latitude'],
            'longitude' => (float)$property['longitude'],
        ];
    }

    if ($avg > 0) {
        $schema['aggregateRating'] = [
            '@type'       => 'AggregateRating',
            'ratingValue' => $avg,
            'bestRating'  => 5,
            'worstRating' => 1,
            'ratingCount' => max(1, count($reviews)),
        ];
    }

    if (!empty($amenities)) {
        $schema['amenityFeature'] = array_map(fn($a) => [
            '@type' => 'LocationFeatureSpecification',
            'name'  => $a['name'],
            'value' => true,
        ], $amenities);
    }

    if (!empty($reviews)) {
        $schema['review'] = array_slice(array_map(function($r) use ($url) {
            return [
                '@type' => 'Review',
                'author' => [
                    '@type' => 'Person',
                    'name'  => $r['user_name'],
                ],
                'datePublished' => date('Y-m-d', strtotime($r['created_at'])),
                'reviewRating' => [
                    '@type'       => 'Rating',
                    'ratingValue' => (int)$r['rating'],
                    'bestRating'  => 5,
                    'worstRating' => 1,
                ],
                'reviewBody' => $r['content'],
            ];
        }, $reviews), 0, 5);
    }

    $imgs = glob("img/properties/" . (int)$property['id'] . "/*");
    if (!empty($imgs)) {
        $schema['image'] = array_map(fn($img) => SITE_URL . '/' . htmlspecialchars($img), array_slice($imgs, 0, 10));
    }

    return $schema;
}

/**
 * Build an ItemList schema for a city PG listing page.
 */
function schema_property_list(array $properties, string $city_name): array {
    $items = [];
    foreach ($properties as $i => $p) {
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'url'      => SITE_URL . '/pg/' . (int)$p['id'],
            'name'     => $p['name'],
        ];
    }
    return [
        '@context'        => 'https://schema.org',
        '@type'           => 'ItemList',
        'name'            => 'PG Accommodations in ' . $city_name,
        'description'     => 'List of verified Paying Guest accommodations in ' . $city_name . ', India',
        'numberOfItems'   => count($items),
        'itemListElement' => $items,
    ];
}

/**
 * Build FAQ schema for the home page.
 */
function schema_home_faq(): array {
    return [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => [
            [
                '@type' => 'Question',
                'name'  => 'What is PG Life?',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'PG Life is an online platform that helps students and working professionals find verified Paying Guest (PG) accommodations across 11 major Indian cities including Delhi, Mumbai, Bengaluru, and Hyderabad.'],
            ],
            [
                '@type' => 'Question',
                'name'  => 'How do I find PG accommodation near me?',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Simply enter your city name in the search bar on the PG Life homepage and browse listings. You can filter by gender preference, maximum rent, and 13 amenities like WiFi, AC, meals, and parking.'],
            ],
            [
                '@type' => 'Question',
                'name'  => 'Is PG Life free to use?',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Yes, PG Life is completely free for both seekers and property owners. There are no platform fees or commissions charged.'],
            ],
            [
                '@type' => 'Question',
                'name'  => 'Which cities are covered by PG Life?',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'PG Life covers Delhi, Mumbai, Bengaluru, Hyderabad, Kolkata, Chennai, Pune, Ahmedabad, Jaipur, Noida, and Gurgaon — 11 major Indian cities.'],
            ],
            [
                '@type' => 'Question',
                'name'  => 'How do I list my PG property on PG Life?',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Register as a PG Owner, complete email verification, then go to your dashboard and click "List a New PG Property". You can add photos, set rent, select amenities, and manage room availability.'],
            ],
            [
                '@type' => 'Question',
                'name'  => 'How do I book a PG on PG Life?',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Browse listings in your city, click on a property to view details, then click "Book Now". You can also chat with the owner to negotiate rent, submit payment proof, and manage your booking from the dashboard.'],
            ],
            [
                '@type' => 'Question',
                'name'  => 'Are PG owners verified on PG Life?',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'PG Life offers optional KYC verification for property owners. Owners can upload identity documents which are reviewed by the platform. A verified badge indicates the owner\'s identity was checked.'],
            ],
        ],
    ];
}

/**
 * Build HowTo schema for the "find a PG" process.
 */
function schema_howto_find_pg(): array {
    return [
        '@context'   => 'https://schema.org',
        '@type'      => 'HowTo',
        'name'       => 'How to Find PG Accommodation on PG Life',
        'description'=> 'Step-by-step guide to finding and booking a Paying Guest accommodation using PG Life\'s free platform.',
        'image'      => SITE_URL . '/img/bg.jpg',
        'totalTime'  => 'PT5M',
        'estimatedCost' => [
            '@type' => 'MonetaryAmount',
            'currency' => 'INR',
            'value'    => '0',
        ],
        'step' => [
            [
                '@type' => 'HowToStep',
                'name'   => 'Search by City',
                'text'   => 'Enter your preferred city name in the search bar on the PG Life homepage. Choose from 11 major Indian cities.',
                'url'    => SITE_URL . '/home',
                'position' => 1,
            ],
            [
                '@type' => 'HowToStep',
                'name'   => 'Filter and Browse',
                'text'   => 'Use filters to narrow down results by gender preference, maximum rent, and amenities like WiFi, AC, meals, and parking.',
                'url'    => SITE_URL . '/home',
                'position' => 2,
            ],
            [
                '@type' => 'HowToStep',
                'name'   => 'View Property Details',
                'text'   => 'Click on any listing to see full details including photos, amenities, rent, ratings, owner information, and exact location on the map.',
                'url'    => SITE_URL . '/pg/1',
                'position' => 3,
            ],
            [
                '@type' => 'HowToStep',
                'name'   => 'Contact Owner or Book',
                'text'   => 'Use the built-in chat to negotiate rent with the owner, or click "Book Now" to reserve your room. Verify payment directly with the owner.',
                'url'    => SITE_URL . '/dashboard',
                'position' => 4,
            ],
        ],
        'author' => ['@id' => SITE_URL . '/#person'],
    ];
}
