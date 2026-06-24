<?php

namespace App\Providers;

use App\Listeners\UpdateLoginTimestamp;
use App\Models\CashierSubscription;
use App\Models\CashierSubscriptionItem;
use App\Models\Interview;
use App\Models\JobPosting;
use App\Models\Seeker;
use App\Models\SeekerResume;
use App\Observers\InterviewObserver;
use App\Observers\JobObserver;
use App\Observers\SeekerObserver;
use App\Observers\SeekerResumeObserver;
use App\Services\JobMatchingService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(JobMatchingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Cashier::useSubscriptionModel(CashierSubscription::class);
        Cashier::useSubscriptionItemModel(CashierSubscriptionItem::class);

        // Force URL generation to work in subdirectory
        if (App::environment('production')) {
            URL::forceRootUrl('https://ogsmanpower.com/backend');
        }

        // Prevent destructive commands in Production
        if (App::isProduction()) {
            DB::prohibitDestructiveCommands();
        }
        
        // Register model observers for translation invalidation
        Interview::observe(InterviewObserver::class);
        Seeker::observe(SeekerObserver::class);
        SeekerResume::observe(SeekerResumeObserver::class);
        JobPosting::observe(JobObserver::class);
        
        // Register login event listener
        Event::listen(Login::class, UpdateLoginTimestamp::class);
    }
}
