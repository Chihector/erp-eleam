<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

use Filament\View\PanelsRenderHook;
use Illuminate\Support\HtmlString;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->brandName('ERP ELEAM')
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString('
                    <style>
                        /* Botón principal de Entrar / Iniciar Sesión */
                        button[type="submit"],
                        .fi-btn.fi-color-primary,
                        .fi-ac-btn-action[type="submit"] {
                            background-color: #2563eb !important;
                            background-image: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
                            color: #ffffff !important;
                            font-weight: 700 !important;
                            font-size: 1.05rem !important;
                            min-height: 48px !important;
                            border-radius: 0.625rem !important;
                            box-shadow: 0 4px 14px 0 rgba(37, 99, 235, 0.4) !important;
                            transition: all 0.2s ease-in-out !important;
                            border: none !important;
                            letter-spacing: 0.02em !important;
                            display: inline-flex !important;
                            align-items: center !important;
                            justify-content: center !important;
                            width: 100% !important;
                        }

                        button[type="submit"]:hover,
                        .fi-btn.fi-color-primary:hover,
                        .fi-ac-btn-action[type="submit"]:hover {
                            background-image: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%) !important;
                            box-shadow: 0 6px 20px 0 rgba(37, 99, 235, 0.55) !important;
                            transform: translateY(-1px) !important;
                        }

                        button[type="submit"]:active,
                        .fi-btn.fi-color-primary:active {
                            transform: translateY(0px) !important;
                        }

                        button[type="submit"] span,
                        .fi-btn.fi-color-primary span {
                            color: #ffffff !important;
                            font-weight: 700 !important;
                        }

                        /* Botón de ojito (Mostrar / Ocultar Contraseña) */
                        [x-ref="showPasswordAction"],
                        [x-ref="hidePasswordAction"] {
                            display: inline-flex !important;
                            align-items: center !important;
                            justify-content: center !important;
                            background-color: #e0e7ff !important;
                            border: 1.5px solid #818cf8 !important;
                            border-radius: 0.5rem !important;
                            padding: 0.4rem 0.55rem !important;
                            margin-right: 0.25rem !important;
                            color: #1d4ed8 !important;
                            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08) !important;
                            cursor: pointer !important;
                            opacity: 1 !important;
                            visibility: visible !important;
                        }

                        .dark [x-ref="showPasswordAction"],
                        .dark [x-ref="hidePasswordAction"] {
                            background-color: #1e293b !important;
                            border: 1.5px solid #3b82f6 !important;
                            color: #60a5fa !important;
                        }

                        [x-ref="showPasswordAction"]:hover,
                        [x-ref="hidePasswordAction"]:hover {
                            background-color: #c7d2fe !important;
                            color: #1e40af !important;
                        }

                        .dark [x-ref="showPasswordAction"]:hover,
                        .dark [x-ref="hidePasswordAction"]:hover {
                            background-color: #334155 !important;
                            color: #93c5fd !important;
                        }

                        [x-ref="showPasswordAction"] svg,
                        [x-ref="hidePasswordAction"] svg {
                            width: 1.35rem !important;
                            height: 1.35rem !important;
                            stroke-width: 2 !important;
                            color: currentColor !important;
                        }

                        /* FullCalendar: Optimización de turnos en móvil */
                        .fc .fc-daygrid-event {
                            white-space: normal !important;
                            align-items: flex-start !important;
                            padding: 2px 3px !important;
                            margin-top: 1px !important;
                            margin-bottom: 2px !important;
                            border-radius: 4px !important;
                        }

                        .fc .fc-daygrid-event .fc-event-main {
                            white-space: normal !important;
                            display: block !important;
                            width: 100% !important;
                            line-height: 1.15 !important;
                        }

                        .fc .fc-daygrid-event .fc-event-title {
                            white-space: normal !important;
                            font-size: 0.72rem !important;
                            font-weight: 600 !important;
                            word-break: break-word !important;
                            display: block !important;
                        }

                        @media (max-width: 640px) {
                            .fc .fc-toolbar {
                                flex-direction: column !important;
                                gap: 0.4rem !important;
                            }
                            .fc .fc-toolbar-title {
                                font-size: 1.05rem !important;
                            }
                            .fc .fc-daygrid-event .fc-event-title {
                                font-size: 0.68rem !important;
                                line-height: 1.1 !important;
                            }
                            .fc .fc-col-header-cell-cushion {
                                font-size: 0.72rem !important;
                                padding: 2px !important;
                            }
                        }
                    </style>
                ')
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
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
            ->plugin(\Saade\FilamentFullCalendar\FilamentFullCalendarPlugin::make());
    }
}
