<?php

namespace App\Providers;

use App\Models\Contact;
use App\Models\Import;
use App\Models\ListModel;
use App\Models\SmsCampaign;
use App\Models\SmsTemplate;
use App\Models\Tag;
use App\Models\User;
use App\Policies\ContactPolicy;
use App\Policies\ImportPolicy;
use App\Policies\ListPolicy;
use App\Policies\SmsCampaignPolicy;
use App\Policies\SmsTemplatePolicy;
use App\Policies\TagPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Register policies
        Gate::policy(Contact::class, ContactPolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
        Gate::policy(ListModel::class, ListPolicy::class);
        Gate::policy(SmsTemplate::class, SmsTemplatePolicy::class);
        Gate::policy(SmsCampaign::class, SmsCampaignPolicy::class);
        Gate::policy(Import::class, ImportPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }
}
