<?php

namespace Uxmal\Devtools\Command\Docker;

use Illuminate\Console\Command;
use JetBrains\PhpStorm\NoReturn;
use Random\RandomException;
use Uxmal\Devtools\Traits\GeneralUtils;

class BuildBaseImagesCommand extends Command
{
    use GeneralUtils;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'docker:build-base-images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build the base docker image for CI/CD.';

    public function __construct()
    {
        parent::__construct();
        $this->configureSilentOption();
    }

    /**
     * @throws RandomException
     */
    public function handle(): void
    {
        if ($this->checkEnvironment() === false) {
            exit(1);
        }
        system('clear');
        $this->buildDockerImage();
    }

    /**
     * Build the base docker image.
     */
    #[NoReturn]
    protected function buildDockerImage(): void
    {
        $this->line('Building the base docker image for ' . config('uxmaltech.name') . ' ...');
        $this->newLine();

        $headers = ['Variable', 'Contenido'];

        $ecr_proxy = config('aws-ecr.proxy');
        // Mostrar la tabla
        $envTable = [
            ['Build Tag', $this->ecrImageTag],
            ['Ecr Proxy', $ecr_proxy],

        ];

        $prefix = config('uxmaltech.prefix', 'uxtch');

        $ecr_php_fpm = config('aws-ecr.php-fpm');
        if ($ecr_php_fpm) {
            $ecr_php_fpm_versioned_tag = $prefix . '-php-fpm' . ':' . $this->ecrImageTag;
            $ecr_php_fpm_repository = $ecr_proxy . '/' . $prefix . '-php-fpm';
            $ecr_php_fpm_base_image = $ecr_php_fpm['base-image'];
            $ecr_php_fpm_exposed_port = $ecr_php_fpm['exposed-port'];

            $envTable = array_merge($envTable, [
                ['Ecr PHP FPM base image', $ecr_php_fpm_base_image],
                ['Ecr PHP FPM repository', $ecr_php_fpm_repository],
                ['Ecr PHP FPM versioned tag', $ecr_php_fpm_versioned_tag],
                ['Ecr PHP FPM exposed ports', $ecr_php_fpm_exposed_port],
            ]);
        }

        $ecr_nginx = config('aws-ecr.nginx');
        if ($ecr_nginx) {
            $ecr_nginx_versioned_tag = $prefix . '-nginx' . ':' . $this->ecrImageTag;
            $ecr_nginx_repository = $ecr_proxy . '/' . $prefix . '-nginx';
            $ecr_nginx_base_image = $ecr_nginx['base-image'];
            $ecr_nginx_exposed_port = $ecr_nginx['exposed-port'];

            $envTable = array_merge($envTable, [
                ['Ecr Nginx base image', $ecr_nginx_base_image],
                ['Ecr Nginx repository', $ecr_nginx_repository],
                ['Ecr Nginx versioned tag', $ecr_nginx_versioned_tag],
                ['Ecr Nginx exposed port', $ecr_nginx_exposed_port],
            ]);
        }

        $ecr_apache_php = config('aws-ecr.apache-php');
        if ($ecr_apache_php) {
            $ecr_apache_php_versioned_tag = $prefix . '-apache' . ':' . $this->ecrImageTag;
            $ecr_apache_php_repository = $ecr_proxy . '/' . $prefix . '-apache';
            $ecr_apache_php_base_image = $ecr_apache_php['base-image'];
            $ecr_apache_php_exposed_port = $ecr_apache_php['exposed-port'];

            $envTable = array_merge($envTable, [
                ['Ecr APACHE base image', $ecr_apache_php_base_image],
                ['Ecr APACHE repository', $ecr_apache_php_repository],
                ['Ecr APACHE versioned tag', $ecr_apache_php_versioned_tag],
                ['Ecr APACHE exposed ports', $ecr_apache_php_exposed_port],
            ]);
        }

        $ecr_artisan_php = config('aws-ecr.artisan-php');
        if ($ecr_artisan_php) {
            $ecr_artisan_php_versioned_tag = $prefix . '-artisan' . ':' . $this->ecrImageTag;
            $ecr_artisan_php_repository = $ecr_proxy . '/' . $prefix . '-artisan';
            $ecr_artisan_php_base_image = $ecr_artisan_php['base-image'];
            $ecr_artisan_php_exposed_port = $ecr_artisan_php['exposed-port'];

            $envTable = array_merge($envTable, [
                ['Ecr ARTISAN base image', $ecr_artisan_php_base_image],
                ['Ecr ARTISAN repository', $ecr_artisan_php_repository],
                ['Ecr ARTISAN versioned tag', $ecr_artisan_php_versioned_tag],
                ['Ecr ARTISAN exposed ports', $ecr_artisan_php_exposed_port],
            ]);
        }

        $this->table($headers, $envTable);

        $this->newLine();
        if (!$this->confirm('Do you wish to continue?', false)) {
            $this->info('Bye!');
            exit(0);
        }

        if ($ecr_php_fpm) {
            $workDir = $this->laravel->basePath('docker-images/base-images/uxtch-php-fpm/');
            if (!is_dir($workDir)) {
                $this->error('The directory docker-images/base-images/uxtch-php-fpm does not exists.');
                exit(1);
            }
            $ecr_proxy_php_fpm_tag = $ecr_php_fpm_repository . ':' . $this->ecrImageTag;
            $latest_php_fpm_tag = $ecr_php_fpm_repository . ':latest';
            $this->runDockerCmd(['build', '.', '-t', $ecr_php_fpm_versioned_tag], $workDir);
            $this->runDockerCmd(['tag', $ecr_php_fpm_versioned_tag, $ecr_proxy_php_fpm_tag], $workDir);
            $this->runDockerCmd(['tag', $ecr_php_fpm_versioned_tag, $latest_php_fpm_tag], $workDir);
            $this->info("Docker image built $ecr_php_fpm_versioned_tag successfully.");
            $this->replaceConfigKey('php-fpm.latest-base-image', $ecr_php_fpm_versioned_tag, false, config_path('aws-ecr.php'));
        }

        if ($ecr_nginx) {
            $workDir = $this->laravel->basePath('docker-images/base-images/uxtch-nginx/');
            if (!is_dir($workDir)) {
                $this->error('The directory docker-images/base-images/uxtch-nginx does not exists.');
                exit(1);
            }
            $ecr_proxy_nginx_tag = $ecr_nginx_repository . ':' . $this->ecrImageTag;
            $latest_nginx_tag = $ecr_nginx_repository . ':latest';
            $this->runDockerCmd(['build', '.', '-t', $ecr_nginx_versioned_tag], $workDir);
            $this->runDockerCmd(['tag', $ecr_nginx_versioned_tag, $ecr_proxy_nginx_tag], $workDir);
            $this->runDockerCmd(['tag', $ecr_nginx_versioned_tag, $latest_nginx_tag], $workDir);
            $this->info("Docker image built $ecr_nginx_versioned_tag successfully.");
            $this->replaceConfigKey('nginx.latest-base-image', $ecr_nginx_versioned_tag, false, config_path('aws-ecr.php'));
        }

        if ($ecr_apache_php) {
            $workDir = $this->laravel->basePath('docker-images/base-images/uxtch-apache-php/');
            if (!is_dir($workDir)) {
                $this->error('The directory docker-images/base-images/uxtch-apache-php does not exists.');
                exit(1);
            }
            $ecr_proxy_apache_php_tag = $ecr_apache_php_repository . ':' . $this->ecrImageTag;
            $latest_apache_php_tag = $ecr_apache_php_repository . ':latest';
            $this->runDockerCmd(['build', '.', '-t', $ecr_apache_php_versioned_tag], $workDir);
            $this->runDockerCmd(['tag', $ecr_apache_php_versioned_tag, $ecr_proxy_apache_php_tag], $workDir);
            $this->runDockerCmd(['tag', $ecr_apache_php_versioned_tag, $latest_apache_php_tag], $workDir);
            $this->info("Docker image built $ecr_apache_php_versioned_tag successfully.");
            $this->replaceConfigKey('apache-php.latest-base-image', $ecr_apache_php_versioned_tag, false, config_path('aws-ecr.php'));
        }

        if ($ecr_artisan_php) {
            $workDir = $this->laravel->basePath('docker-images/base-images/uxtch-artisan-php/');
            if (!is_dir($workDir)) {
                $this->error('The directory docker-images/base-images/uxtch-artisan-php does not exists.');
                exit(1);
            }
            $ecr_proxy_artisan_php_tag = $ecr_artisan_php_repository . ':' . $this->ecrImageTag;
            $latest_artisan_php_tag = $ecr_artisan_php_repository . ':latest';
            $this->runDockerCmd(['build', '.', '-t', $ecr_artisan_php_versioned_tag], $workDir);
            $this->runDockerCmd(['tag', $ecr_artisan_php_versioned_tag, $ecr_proxy_artisan_php_tag], $workDir);
            $this->runDockerCmd(['tag', $ecr_artisan_php_versioned_tag, $latest_artisan_php_tag], $workDir);
            $this->info("Docker image built $ecr_artisan_php_versioned_tag successfully.");
            $this->replaceConfigKey('artisan-php.latest-base-image', $ecr_artisan_php_versioned_tag, false, config_path('aws-ecr.php'));
        }
        exit(0);
    }
}
