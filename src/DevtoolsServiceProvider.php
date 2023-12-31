<?php

namespace Uxmal\Devtools;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Uxmal\Devtools\Command\Aws\CreateInfrastructureCommand;
use Uxmal\Devtools\Command\Aws\ECRDockerLoginCommand;
use Uxmal\Devtools\Command\Aws\ECRListRepositoriesCommand;
use Uxmal\Devtools\Command\Aws\ECRPushToRepositoryCommand;
use Uxmal\Devtools\Command\AWS\Route53CreateDomainsCommand;
use Uxmal\Devtools\Command\BuildInfrastructureConfigCommand;
use Uxmal\Devtools\Command\Docker\BuildBaseImagesCommand;
use Uxmal\Devtools\Command\Docker\ComposeBuildCommand;
use Uxmal\Devtools\Command\Docker\ComposeUpCommand;
use Uxmal\Devtools\Command\InstallCommand;
use Uxmal\Devtools\Command\TestCommand;
use Uxmal\Devtools\Command\Uxmaltech\AddBackOfficeUICommand;

class DevtoolsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->configurePublishing();
    }

    /**
     * Register the console commands for the package.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Docker
                BuildBaseImagesCommand::class,
                ComposeBuildCommand::class,
                InstallCommand::class,
                ComposeUpCommand::class,
                // Uxmaltech
                AddBackOfficeUICommand::class,
                BuildInfrastructureConfigCommand::class,
                TestCommand::class,
                // AWS
                ECRPushToRepositoryCommand::class,
                ECRListRepositoriesCommand::class,
                ECRDockerLoginCommand::class,
                Route53CreateDomainsCommand::class,
                CreateInfrastructureCommand::class,
            ]);
        }
    }

    /**
     * Configure publishing for the package.
     */
    protected function configurePublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../docker-images' => $this->app->basePath('docker-images'),
            ], ['aws-cicd-docker']);
        }

        $this->publishes([
            __DIR__.'/config/aws-cicd.php' => config_path('aws-cicd.php'),
        ], ['aws-cicd-config']);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            // Docker
            BuildBaseImagesCommand::class,
            ComposeBuildCommand::class,
            InstallCommand::class,
            ComposeUpCommand::class,
            // Uxmaltech
            AddBackOfficeUICommand::class,
            BuildInfrastructureConfigCommand::class,
            TestCommand::class,
            // AWS
            ECRPushToRepositoryCommand::class,
            ECRListRepositoriesCommand::class,
            ECRDockerLoginCommand::class,
            CreateInfrastructureCommand::class,
        ];
    }
}
