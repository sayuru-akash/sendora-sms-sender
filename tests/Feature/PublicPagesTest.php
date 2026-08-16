<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    public function test_public_homepage_is_available_to_guests(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->where('seo.title', 'SMS Campaign Operations Platform')
                ->where('seo.robots', 'index,follow')
                ->where('seo.imageWidth', 1200)
                ->where('seo.imageHeight', 630));
    }

    public function test_public_legal_pages_are_available_to_guests(): void
    {
        $this->get(route('privacy'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Legal/Privacy')
                ->where('seo.title', 'Privacy Policy')
                ->where('seo.robots', 'index,follow'));

        $this->get(route('terms'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Legal/Terms')
                ->where('seo.title', 'Terms of Use')
                ->where('seo.robots', 'index,follow'));
    }

    public function test_workspace_and_registration_routes_are_not_public(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login', absolute: false));
        $this->get('/register')->assertNotFound();
    }

    public function test_missing_public_page_uses_the_branded_not_found_experience(): void
    {
        $this->get('/missing-public-page')
            ->assertNotFound()
            ->assertSee('<title inertia>Page Not Found - Sendora</title>', false)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Errors/NotFound'));
    }
}
