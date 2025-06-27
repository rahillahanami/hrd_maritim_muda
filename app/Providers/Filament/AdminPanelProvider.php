<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Login;
use App\Filament\Widgets\Calendarwidget;
use App\Filament\Widgets\MyAttendanceStatus;
use App\Filament\Widgets\MyDivisionWorkPlansOverview;
use App\Filament\Widgets\StatsDashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Navigation\NavigationGroup;
use Filament\Widgets\Widget;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->brandName('Maritim Muda Nusantara')
            ->favicon(asset('images/logo-maritim.png')) 
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // ActiveEmployeesOverview::class,
                // PendingResignationsOverview::class,
                // PendingLeavesOverview::class,
                // StatsDashboard::class, // Removed because the class does not exist
                // Calendarwidget::class, // Pastikan ini ada
                StatsDashboard::class,
                MyAttendanceStatus::class,
                MyDivisionWorkPlansOverview::class,
                Calendarwidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])

            ->plugin(
                FilamentFullCalendarPlugin::make()
                    ->schedulerLicenseKey('')
                    ->selectable(true)
                    ->editable()
                    ->timezone(config('app.timezone'))
                    ->locale(config('app.locale'))
                    ->plugins(['dayGrid', 'timeGrid'])
                    ->config([])
            )

            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->navigationGroups([
                NavigationGroup::make('Organisasi') // Cukup nama grup sebagai argumen
                    ->label('Organisasi'), // Hanya label, tanpa icon dan sort

                NavigationGroup::make('Presensi')
                    ->label('Presensi'),

                NavigationGroup::make('Manajemen Kinerja')
                    ->label('Manajemen Kinerja'),

                NavigationGroup::make('Manajemen Sumber Daya Manusia')
                    ->label('Manajemen Sumber Daya Manusia'),

                NavigationGroup::make('Sistem Pengambilan Keputusan')
                    ->label('Sistem Pengambilan Keputusan'),

                NavigationGroup::make('Pelindung')
                    ->label('Pelindung'),
            ])
        ;
    }
}
