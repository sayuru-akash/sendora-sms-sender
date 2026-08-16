<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class PublicPageController extends Controller
{
    public function home(): Response
    {
        return Inertia::render('Welcome', [
            'seo' => SeoController::publicPageSeo(
                '/',
                'SMS Campaign Operations Platform',
                'Sendora brings contact records, imports, audience targeting, templates, queued SMS campaigns, message results, and audit logs into one operational workspace.'
            ),
        ]);
    }

    public function privacy(): Response
    {
        return Inertia::render('Legal/Privacy', [
            'seo' => SeoController::publicPageSeo(
                '/privacy',
                'Privacy Policy',
                'How Sendora handles contact records, imports, SMS campaign activity, provider responses, user accounts, and operational logs.'
            ),
        ]);
    }

    public function terms(): Response
    {
        return Inertia::render('Legal/Terms', [
            'seo' => SeoController::publicPageSeo(
                '/terms',
                'Terms of Use',
                'Terms for using Sendora, an SMS campaign, contact management, import, reporting, and sending operations platform.'
            ),
        ]);
    }
}
