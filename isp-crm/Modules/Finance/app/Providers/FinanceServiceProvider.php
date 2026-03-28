<?php

namespace Modules\Finance\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Finance\Console\GenerateInvoicesCommand;
use Modules\Finance\Console\ProcessDunningCommand;
use Modules\Finance\Jobs\ProcessExpiredPromisesJob;
use Modules\Finance\Jobs\RefreshAgingJob;
use Modules\Finance\Services\AgingService;
use Modules\Finance\Services\BillingCalculator;
use Modules\Finance\Services\CollectionCaseService;
use Modules\Finance\Services\DunningService;
use Modules\Finance\Services\InvoiceDisputeService;
use Modules\Finance\Services\InvoiceService;
use Modules\Finance\Services\PaymentAllocationService;
use Modules\Finance\Services\PaymentService;
use Modules\Finance\Services\PaymentWebhookService;
use Modules\Finance\Services\PromiseToPayService;
use Modules\Finance\Services\ReconnectionService;
use Modules\Finance\Services\RecurringBillingService;
use Modules\Finance\Services\WalletService;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FinanceServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Finance';

    protected string $nameLower = 'finance';

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
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
        $this->app->singleton(WalletService::class);
        $this->app->singleton(InvoiceService::class);
        $this->app->singleton(BillingCalculator::class);
        $this->app->singleton(RecurringBillingService::class);
        $this->app->singleton(DunningService::class);
        $this->app->singleton(AgingService::class);
        $this->app->singleton(PromiseToPayService::class);
        $this->app->singleton(InvoiceDisputeService::class);
        $this->app->singleton(CollectionCaseService::class);
        $this->app->singleton(PaymentService::class);
        $this->app->singleton(PaymentAllocationService::class);
        $this->app->singleton(ReconnectionService::class);
        $this->app->singleton(PaymentWebhookService::class);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            GenerateInvoicesCommand::class,
            ProcessDunningCommand::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('finance:generate-invoices --sync')
                ->dailyAt('00:01')
                ->withoutOverlapping()
                ->onOneServer()
                ->appendOutputTo(storage_path('logs/billing.log'));

            $schedule->job(new RefreshAgingJob)
                ->dailyAt('06:00')
                ->withoutOverlapping();

            $schedule->job(new ProcessExpiredPromisesJob)
                ->dailyAt('07:00')
                ->withoutOverlapping();

            $schedule->command('finance:process-dunning --sync')
                ->dailyAt('08:00')
                ->withoutOverlapping()
                ->onOneServer()
                ->appendOutputTo(storage_path('logs/dunning.log'));
        });
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
