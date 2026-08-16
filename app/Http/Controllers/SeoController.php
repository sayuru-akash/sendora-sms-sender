<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SeoController extends Controller
{
    /**
     * @return array<int, array{path: string, lastmod: string, changefreq: string, priority: string}>
     */
    public static function sitemapPages(): array
    {
        return [
            ['path' => '/', 'lastmod' => '2026-08-17', 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['path' => '/privacy', 'lastmod' => '2026-08-17', 'changefreq' => 'yearly', 'priority' => '0.4'],
            ['path' => '/terms', 'lastmod' => '2026-08-17', 'changefreq' => 'yearly', 'priority' => '0.4'],
        ];
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /dashboard',
            'Disallow: /contacts',
            'Disallow: /campaigns',
            'Disallow: /imports',
            'Disallow: /lists',
            'Disallow: /reports',
            'Disallow: /segments',
            'Disallow: /settings',
            'Disallow: /tags',
            'Disallow: /templates',
            'Disallow: /users',
            'Disallow: /activity-logs',
            'Disallow: /global-search',
            'Disallow: /profile',
            'Disallow: /login',
            'Disallow: /forgot-password',
            'Disallow: /reset-password',
            'Disallow: /verify-email',
            'Disallow: /confirm-password',
            'Sitemap: '.self::absoluteUrlFor('/sitemap.xml'),
        ];

        return response(implode("\n", $lines)."\n", 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=86400');
    }

    public function sitemap(): Response
    {
        $urls = collect(self::sitemapPages())
            ->map(fn (array $page): string => $this->sitemapUrl($page))
            ->implode("\n");

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{$urls}
</urlset>
XML;

        return response($xml."\n", 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=86400');
    }

    /**
     * @return array{title: string, fullTitle: string, description: string, canonical: string, image: string, imageAlt: string, imageWidth: int, imageHeight: int, siteName: string, robots: string, locale: string}
     */
    public static function publicPageSeo(string $path, string $title, string $description): array
    {
        return [
            'title' => $title,
            'fullTitle' => "{$title} - Sendora",
            'description' => $description,
            'canonical' => self::absoluteUrlFor($path),
            'image' => self::absoluteUrlFor('/images/og/sendora.png'),
            'imageAlt' => 'Sendora SMS campaign operations workspace.',
            'imageWidth' => 1200,
            'imageHeight' => 630,
            'siteName' => 'Sendora',
            'robots' => 'index,follow',
            'locale' => 'en_US',
        ];
    }

    public static function absoluteUrlFor(string $path): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        return $path === '/'
            ? $baseUrl.'/'
            : $baseUrl.'/'.ltrim($path, '/');
    }

    /**
     * @param  array{path: string, lastmod: string, changefreq: string, priority: string}  $page
     */
    private function sitemapUrl(array $page): string
    {
        $location = htmlspecialchars(self::absoluteUrlFor($page['path']), ENT_XML1, 'UTF-8');

        return <<<XML
    <url>
        <loc>{$location}</loc>
        <lastmod>{$page['lastmod']}</lastmod>
        <changefreq>{$page['changefreq']}</changefreq>
        <priority>{$page['priority']}</priority>
    </url>
XML;
    }
}
