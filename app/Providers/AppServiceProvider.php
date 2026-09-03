<?php

namespace App\Providers;

use App\Listeners\RecordAttendanceOnLogin;
use App\Listeners\RecordAttendanceOnLogout;
use App\Models\User;
use App\Services\QueueService;
use App\Services\ServiceQueueAvailabilityService;
use App\Services\ThermalPrinterService;
use App\Services\MppBrandingService;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Auth\Events\Login as LaravelLogin;
use Illuminate\Auth\Events\Logout as LaravelLogout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ThermalPrinterService::class, function ($app) {
            return new ThermalPrinterService;
        });

        $this->app->singleton(QueueService::class, function ($app) {
            return new QueueService(
                $app->make(ServiceQueueAvailabilityService::class),
            );
        });

        $this->app->singleton(MppBrandingService::class, function ($app) {
            return new MppBrandingService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('access-admin-area', fn (User $user): bool => $user->isAdmin());
        Gate::define(
            'operate-counter',
            fn (User $user): bool => $user->isAdmin() || $user->isOperator(),
        );

        View::composer('*', function ($view): void {
            $view->with('mppBranding', app(MppBrandingService::class)->current());
        });

        FilamentAsset::register([
            Js::make('thermal-printer', asset('js/thermal-printer.js')),
            Js::make('call-queue', asset('js/call-queue.js')),
        ]);

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): string => auth()->user()?->isOperator()
                ? view('filament.partials.operator-session-guard')->render()
                : '',
        );

        // Register event listener for attendance - Laravel native Login event
        Event::listen(
            LaravelLogin::class,
            RecordAttendanceOnLogin::class
        );

        // Register event listener for attendance checkout on logout
        Event::listen(
            LaravelLogout::class,
            RecordAttendanceOnLogout::class
        );
    }
}
