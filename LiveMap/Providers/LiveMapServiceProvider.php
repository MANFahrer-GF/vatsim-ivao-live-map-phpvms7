<?php

namespace Modules\LiveMap\Providers;

use App\Services\ModuleService;
use Illuminate\Support\ServiceProvider;
use Route;

class LiveMapServiceProvider extends ServiceProvider
{
    protected ModuleService $moduleSvc;

    public function boot()
    {
        $this->moduleSvc = app(ModuleService::class);

        $this->registerRoutes();
        $this->registerViews();
        $this->registerLinks();
    }

    public function register()
    {
        //
    }

    protected function registerLinks(): void
    {
        $this->moduleSvc->addAdminLink('Live Map', '/admin/livemap', 'pe-7s-map-marker');
    }

    protected function registerRoutes(): void
    {
        Route::group([
            'as'         => 'livemap.',
            'prefix'     => 'livemap',
            'middleware' => ['web'],
            'namespace'  => 'Modules\LiveMap\Http\Controllers',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../Http/Routes/web.php');
        });

        Route::group([
            'as'         => 'livemap.',
            'prefix'     => 'admin/livemap',
            'middleware' => ['web', 'role:admin'],
            'namespace'  => 'Modules\LiveMap\Http\Controllers\Admin',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../Http/Routes/admin.php');
        });
    }

    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/livemap');
        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([$sourcePath => $viewPath], 'views');

        $paths = array_map(function ($path) {
            return $path.'/modules/livemap';
        }, \Config::get('view.paths'));

        $paths[] = $sourcePath;
        // Nur EXISTIERENDE Verzeichnisse registrieren. Die Theme-Override-
        // Pfade (view.paths + /modules/livemap) existieren i.d.R. nicht.
        // Live-Rendern überspringt fehlende Dirs lazy (Fallback $sourcePath),
        // aber `php artisan view:cache`/`optimize` scannt EAGER jeden Pfad per
        // Symfony-Finder → DirectoryNotFoundException. $sourcePath existiert
        // immer → der Namespace behält stets einen gültigen Pfad.
        $this->loadViewsFrom(array_filter($paths, 'is_dir'), 'livemap');
    }

    public function provides(): array
    {
        return [];
    }
}
