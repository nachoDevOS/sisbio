<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\EquiposStats;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
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
use Illuminate\Contracts\View\View;
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
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName(config('app.name'))
            // Ícono + APP_NAME juntos (vista propia en lugar de solo la imagen).
            ->brandLogo(fn (): View => view('filament.logo'))
            ->brandLogoHeight('2.5rem')
            // La misma imagen como ícono de la pestaña del navegador.
            ->favicon(asset('image/icon.png'))
            // Sin buscador global en el topbar.
            ->globalSearch(false)
            // Paleta institucional estilo SISCOR (AdminLTE skin verde).
            ->colors([
                'primary' => Color::hex('#00a65a'),
                'gray' => Color::Slate,
            ])
            // Permite ocultar/colapsar el menú lateral en escritorio (botón junto al logo).
            ->sidebarCollapsibleOnDesktop()
            // Sidebar más angosto que el default (20rem) para ganar espacio de contenido.
            ->sidebarWidth('14.5rem')
            // Contenido a ancho completo: la tabla aprovecha toda la pantalla.
            ->maxContentWidth(Width::Full)
            // Tras crear un registro en cualquier recurso, vuelve al listado.
            ->resourceCreatePageRedirect('index')
            // Errores inesperados como notificación toast (con APP_DEBUG=false)
            // en lugar del modal de error de Livewire.
            ->registerErrorNotification(
                title: 'Ocurrió un error',
                body: 'La operación no se pudo completar. Inténtalo nuevamente.',
            )
            ->registerErrorNotification(
                title: 'Registro no encontrado',
                body: 'El registro que buscas ya no existe.',
                statusCode: 404,
            )
            // Inyecta el tema visual (color del sidebar y realce de las cajas).
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => view('filament.theme')->render(),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                EquiposStats::class,
            ])
            ->middleware([
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
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
