<?php

namespace Tests\Feature\Seo;

use Tests\TestCase;

class PublicSeoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://sendora.codezela.com']);
    }

    public function test_robots_txt_is_stateless_and_blocks_private_routes(): void
    {
        $this->get(route('seo.robots'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertHeader('Cache-Control', 'max-age=3600, public, s-maxage=86400')
            ->assertHeaderMissing('Set-Cookie')
            ->assertSeeText('Disallow: /dashboard')
            ->assertSeeText('Disallow: /campaigns')
            ->assertSeeText('Disallow: /login')
            ->assertSeeText('Sitemap: https://sendora.codezela.com/sitemap.xml');
    }

    public function test_sitemap_contains_only_absolute_public_pages(): void
    {
        $this->get(route('seo.sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertHeader('Cache-Control', 'max-age=3600, public, s-maxage=86400')
            ->assertHeaderMissing('Set-Cookie')
            ->assertSee('<loc>https://sendora.codezela.com/</loc>', false)
            ->assertSee('<loc>https://sendora.codezela.com/privacy</loc>', false)
            ->assertSee('<loc>https://sendora.codezela.com/terms</loc>', false)
            ->assertDontSee('/dashboard')
            ->assertDontSee('/campaigns')
            ->assertDontSee('/contacts');
    }

    public function test_public_pages_expose_complete_metadata_while_auth_is_not_indexable(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<title inertia>SMS Campaign Operations Platform - Sendora</title>', false)
            ->assertSee('<meta name="description" content="Sendora brings contact records, imports, audience targeting, templates, queued SMS campaigns, message results, and audit logs into one operational workspace.">', false)
            ->assertSee('<meta name="robots" content="index,follow">', false)
            ->assertSee('<link rel="canonical" href="https://sendora.codezela.com/">', false)
            ->assertSee('<meta property="og:type" content="website">', false)
            ->assertSee('<meta property="og:image" content="https://sendora.codezela.com/images/og/sendora.png">', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('"@type":"WebApplication"', false);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex,nofollow">', false);
    }

    public function test_social_preview_asset_is_available_at_the_metadata_url(): void
    {
        $path = public_path('images/og/sendora.png');

        $this->assertFileExists($path);
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", (string) file_get_contents($path));
    }
}
