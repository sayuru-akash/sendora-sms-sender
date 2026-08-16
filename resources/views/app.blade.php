<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $component = $page['component'] ?? '';
            $seo = $page['props']['seo'] ?? null;
            $errorTitles = [
                'Errors/Forbidden' => 'Access Denied - Sendora',
                'Errors/NotFound' => 'Page Not Found - Sendora',
            ];
        @endphp

        <title inertia>{{ $seo['fullTitle'] ?? $errorTitles[$component] ?? config('app.name', 'Sendora') }}</title>

        <meta name="application-name" content="Sendora">
        <meta name="apple-mobile-web-app-title" content="Sendora">
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="manifest" href="/site.webmanifest">
        <meta name="theme-color" content="#0f172a">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
        @if ($seo)
            <meta name="description" content="{{ $seo['description'] }}">
            <meta name="robots" content="{{ $seo['robots'] }}">
            <link rel="canonical" href="{{ $seo['canonical'] }}">
            <meta property="og:type" content="{{ $component === 'Welcome' ? 'website' : 'article' }}">
            <meta property="og:site_name" content="{{ $seo['siteName'] }}">
            <meta property="og:locale" content="{{ $seo['locale'] }}">
            <meta property="og:title" content="{{ $seo['fullTitle'] }}">
            <meta property="og:description" content="{{ $seo['description'] }}">
            <meta property="og:url" content="{{ $seo['canonical'] }}">
            <meta property="og:image" content="{{ $seo['image'] }}">
            <meta property="og:image:alt" content="{{ $seo['imageAlt'] }}">
            <meta property="og:image:width" content="{{ $seo['imageWidth'] }}">
            <meta property="og:image:height" content="{{ $seo['imageHeight'] }}">
            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:title" content="{{ $seo['fullTitle'] }}">
            <meta name="twitter:description" content="{{ $seo['description'] }}">
            <meta name="twitter:image" content="{{ $seo['image'] }}">
            <meta name="twitter:image:alt" content="{{ $seo['imageAlt'] }}">
        @else
            <meta name="robots" content="noindex,nofollow">
        @endif
        @if ($component === 'Welcome' && $seo)
            @php
                $structuredData = [
                    '@context' => 'https://schema.org',
                    '@graph' => [
                        [
                            '@type' => 'Organization',
                            '@id' => $seo['canonical'].'#organization',
                            'name' => 'Codezela Technologies',
                            'url' => $seo['canonical'],
                            'brand' => [
                                '@type' => 'Brand',
                                'name' => 'Sendora',
                            ],
                            'logo' => $seo['image'],
                        ],
                        [
                            '@type' => 'WebApplication',
                            '@id' => $seo['canonical'].'#application',
                            'name' => 'Sendora',
                            'applicationCategory' => 'BusinessApplication',
                            'operatingSystem' => 'Web',
                            'url' => $seo['canonical'],
                            'image' => $seo['image'],
                            'description' => $seo['description'],
                            'publisher' => [
                                '@id' => $seo['canonical'].'#organization',
                            ],
                        ],
                    ],
                ];
            @endphp
            <script type="application/ld+json">@json($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
        @endif
    </head>
    <body class="font-sans antialiased" style="background-color: #f5f5f5;">
        @inertia
    </body>
</html>
