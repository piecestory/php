<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Support\InitialsAvatar;
use App\Http\Middleware\SetLocale;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Staff panel at /admin, in Arabic. Only users holding the `access_admin` permission get in
 * (User::canAccessPanel); each section is further limited by its policy / permission.
 */
class AdminPanelProvider extends PanelProvider
{
    /** OKLCH shades of the brand bronze (hue 66). Generated palettes oversaturate it towards orange. */
    private const array BRONZE = [
        50 => 'oklch(0.975 0.012 66)',
        100 => 'oklch(0.945 0.025 66)',
        200 => 'oklch(0.885 0.045 66)',
        300 => 'oklch(0.805 0.065 66)',
        400 => 'oklch(0.715 0.080 66)',
        500 => 'oklch(0.635 0.090 66)',
        600 => 'oklch(0.564 0.092 66)',
        700 => 'oklch(0.485 0.082 66)',
        800 => 'oklch(0.405 0.068 66)',
        900 => 'oklch(0.340 0.054 66)',
        950 => 'oklch(0.250 0.040 66)',
    ];

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->brandName('قطعة وقصة')
            ->brandLogo(fn () => asset('images/brand/horizontal-ar.svg'))
            ->brandLogoHeight('2.75rem')
            ->defaultAvatarProvider(InitialsAvatar::class)
            ->favicon(asset('favicon.svg'))
            // The storefront bundles Livewire's CSP build itself (automatic injection is off), so the
            // panel adds Livewire's standard assets on its own pages only.
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn (): string => Blade::render('@livewireStyles'))
            ->renderHook(PanelsRenderHook::SCRIPTS_AFTER, fn (): string => Blade::render('@livewireScripts'))
            ->colors([
                // Brand bronze (#9a6a36 at 600) with its warm hue kept across shades; greys follow the stone palette.
                'primary' => self::BRONZE,
                'gray' => Color::Stone,
                'danger' => Color::hex('#9b3b2e'),
                'success' => Color::hex('#3f6b4e'),
            ])
            ->font('IBM Plex Sans Arabic', provider: LocalFontProvider::class)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->darkMode(false)
            ->maxContentWidth(Width::Full)
            ->sidebarCollapsibleOnDesktop()
            ->unsavedChangesAlerts()
            ->navigationGroups([
                __('admin.groups.sales'),
                __('admin.groups.catalog'),
                __('admin.groups.customers'),
                __('admin.groups.content'),
                __('admin.groups.settings'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SetLocale::class.':ar',
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
