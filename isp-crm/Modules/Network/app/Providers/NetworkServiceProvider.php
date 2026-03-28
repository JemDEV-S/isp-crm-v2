<?php

namespace Modules\Network\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Network\Entities\Device;
use Modules\Network\Entities\IpPool;
use Modules\Network\Entities\NapBox;
use Modules\Network\Entities\Node;
use Modules\Network\Policies\DevicePolicy;
use Modules\Network\Policies\IpPoolPolicy;
use Modules\Network\Policies\NapBoxPolicy;
use Modules\Network\Policies\NodePolicy;
use Modules\Network\Services\IpService;
use Modules\Network\Services\NapService;
use Modules\Network\Services\NetworkProvisioningService;
use Modules\Network\Services\OltService;
use Modules\Network\Services\ProvisioningService;
use Modules\Network\Services\RouterOsService;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class NetworkServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Network';

    protected string $nameLower = 'network';

    /**
     * Policy mappings for the module.
     */
    protected array $policies = [
        Node::class => NodePolicy::class,
        Device::class => DevicePolicy::class,
        IpPool::class => IpPoolPolicy::class,
        NapBox::class => NapBoxPolicy::class,
    ];

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerPolicies();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
        $this->registerServices();
    }

    /**
     * Register module services.
     */
    protected function registerServices(): void
    {
        $this->app->singleton(IpService::class);
        $this->app->singleton(NapService::class);
        $this->app->singleton(RouterOsService::class);
        $this->app->singleton(OltService::class);
        $this->app->singleton(NetworkProvisioningService::class, function ($app) {
            return new NetworkProvisioningService(
                $app->make(IpService::class),
                $app->make(NapService::class),
                $app->make(RouterOsService::class),
                $app->make(OltService::class),
            );
        });
        $this->app->singleton(ProvisioningService::class, function ($app) {
            return new ProvisioningService(
                $app->make(NetworkProvisioningService::class),
                $app->make(IpService::class),
            );
        });
    }

    /**
     * Register policies.
     */
    protected function registerPolicies(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        // $this->commands([]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $relativeConfigPath = config('modules.paths.generator.config.path');
        $configPath = module_path($this->name, $relativeConfigPath);

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $relativePath = str_replace($configPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $configKey = $this->nameLower . '.' . str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $relativePath);
                    $key = ($relativePath === 'config.php') ? $this->nameLower : $configKey;

                    $this->publishes([$file->getPathname() => config_path($relativePath)], 'config');
                    $this->mergeConfigFrom($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        $componentNamespace = $this->module_namespace($this->name, $this->app_path(config('modules.paths.generator.component-class.path')));
        Blade::componentNamespace($componentNamespace, $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
