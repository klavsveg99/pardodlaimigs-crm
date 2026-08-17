<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Auth\Login;
use App\Filament\Admin\Widgets\CalendarViewings;
use App\Filament\Admin\Widgets\CrmStatsOverview;
use App\Filament\Admin\Widgets\TodayViewings;
use App\Filament\Admin\Widgets\UpcomingTasks;
use App\Http\Middleware\TrustCrmHost;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->brandName('Pārdod Laimīgs · CRM')
            ->brandLogo(asset('images/favicon-180x180.jpg'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('images/favicon-32x32.jpg'))
            ->colors([
                'primary' => '#285854',
                'gray' => '#414042',
                'success' => '#0f7d60',
                'warning' => '#966830',
                'danger' => '#cf2e2e',
                'info' => '#236D63',
            ])
            ->renderHook('panels::body.start', fn () => view('filament.brand-fonts'))
            ->renderHook('panels::head.end', fn () => view('filament.calendar-assets'))
            ->renderHook('panels::styles.after', fn () => view('filament.page-animations'))
            ->renderHook('panels::styles.after', fn () => view('filament.filament-custom-inline'))
            ->renderHook('panels::scripts.after', fn () => view('filament.badge-poll'))
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->widgets([
                CrmStatsOverview::class,
                UpcomingTasks::class,
                TodayViewings::class,
                CalendarViewings::class,
            ])
            ->middleware([
                TrustCrmHost::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->login(Login::class)
            ->navigationGroups([
                'CRM',
                'Darbplūsma',
                'Sistēma',
            ]);
    }
}
