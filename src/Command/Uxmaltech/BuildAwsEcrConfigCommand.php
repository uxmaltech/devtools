<?php

namespace Uxmal\Devtools\Command\Uxmaltech;

use Illuminate\Console\Command;
use Random\RandomException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Uxmal\Devtools\Traits\GeneralUtils;

class BuildAwsEcrConfigCommand extends Command
{
    use GeneralUtils;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'devtools:build-aws-ecr-config';

    public function __construct()
    {
        parent::__construct();
        $this->configureSilentOption();
    }

    public function handle(): void
    {
        try {
            if ($this->checkEnvironment() === false) {
                exit(1);
            }
        } catch (ProcessFailedException $exception) {
            $this->warn('An error occurred: ' . $exception->getMessage());
        } catch (RandomException $e) {
            $this->warn('An error occurred: ' . $e->getMessage());
        }
        system('clear');
        $this->buildAwsEcrConfig();
    }

    private function buildAwsEcrConfig(): void
    {
        $this->info('Creando (los) archivo de configuración (AWS-ECR)...');

        $this->newLine();

        $app_name = config('uxmaltech.name', config('APP_NAME', 'laravel'));
        $app_mode = config('uxmaltech.mode', config('APP_MODE', 'nginx-phpfpm-2'));

        $ecr_proxy = $this->ask('AWS ECR Proxy', '{AWS_ACCOUNT_ID}.dkr.ecr.{AWS_REGION}.amazonaws.com');
        switch ($app_mode) {
            case 'nginx-phpfpm-2':
                $ecr_php_fpm_push = $this->confirm('Enable pushing base-images php-fpm');
                $ecr_php_fpm_base_image = $this->ask('Tag de la imagen base de php-fpm', 'php:8.2.13-fpm-alpine3.19');
                $ecr_php_fpm_image = $this->ask('Tag de la imagen de php-fpm', $app_name . '-php-fpm');

                $ecr_nginx_push = $this->confirm('Enable pushing base-images nginx');
                $ecr_nginx_base_image = $this->ask('Tag de la imagen base de nginx', 'nginx:1.24-alpine:3.19');
                $ecr_nginx_image = $this->ask('Tag de la imagen de nginx', $app_name . '-nginx');

                $variablesTable = [
                    ['ecr.proxy', $ecr_proxy],
                    ['ecr.php-fpm.base-push', $ecr_php_fpm_push ? 'yes' : 'no'],
                    ['ecr.php-fpm.base-image', $ecr_php_fpm_base_image],
                    ['ecr.php-fpm.image', $ecr_php_fpm_image],
                    ['ecr.nginx.base-push', $ecr_nginx_push ? 'yes' : 'no'],
                    ['ecr.nginx.base-image', $ecr_nginx_base_image],
                    ['ecr.nginx.image', $ecr_nginx_image],
                ];

                $search = [
                    '@ecr.proxy@',
                    '@ecr.php-fpm.base-push@',
                    '@ecr.php-fpm.base-image@',
                    '@ecr.php-fpm.image@',
                    '@ecr.nginx.base-push@',
                    '@ecr.nginx.base-image@',
                    '@ecr.nginx.image@',
                ];

                $replace = [
                    $ecr_proxy,
                    $ecr_php_fpm_push ? 'true' : 'false',
                    $ecr_php_fpm_base_image,
                    $ecr_php_fpm_image,
                    $ecr_nginx_push ? 'true' : 'false',
                    $ecr_nginx_base_image,
                    $ecr_nginx_image,
                ];
                break;
            case 'apache-php':
                $ecr_apache_php_push = $this->confirm('Enable pushing base-images apache-php');
                $ecr_apache_php_base_image = $this->ask('Tag de la imagen base de apache-php', 'php:8.2.13-apache');
                $ecr_apache_php_image = $this->ask('Tag de la imagen de apache-php', $app_name . '-apache');

                $variablesTable = [
                    ['ecr.proxy', $ecr_proxy],
                    ['ecr.apache-php.base-push', $ecr_apache_php_push ? 'yes' : 'no'],
                    ['ecr.apache-php.base-image', $ecr_apache_php_base_image],
                    ['ecr.apache-php.image', $ecr_apache_php_image],
                ];

                $search = [
                    '@ecr.proxy@',
                    '@ecr.apache-php.base-push@',
                    '@ecr.apache-php.base-image@',
                    '@ecr.apache-php.image@',
                ];

                $replace = [
                    $ecr_proxy,
                    $ecr_apache_php_push ? 'true' : 'false',
                    $ecr_apache_php_base_image,
                    $ecr_apache_php_image,
                ];
                break;
            case 'artisan-php':
                $ecr_artisan_php_push = $this->confirm('Enable pushing base-images artisan-php');
                $ecr_artisan_php_base_image = $this->ask('Tag de la imagen base de artisan-php', 'php:8.2.13');
                $ecr_artisan_php_image = $this->ask('Tag de la imagen de artisan-php', $app_name . '-apache');

                $variablesTable = [
                    ['ecr.proxy', $ecr_proxy],
                    ['ecr.artisan-php.base-push', $ecr_artisan_php_push ? 'yes' : 'no'],
                    ['ecr.artisan-php.base-image', $ecr_artisan_php_base_image],
                    ['ecr.artisan-php.image', $ecr_artisan_php_image],
                ];

                $search = [
                    '@ecr.proxy@',
                    '@ecr.artisan-php.base-push@',
                    '@ecr.artisan-php.base-image@',
                    '@ecr.artisan-php.image@',
                ];

                $replace = [
                    $ecr_proxy,
                    $ecr_artisan_php_push ? 'true' : 'false',
                    $ecr_artisan_php_base_image,
                    $ecr_artisan_php_image,
                ];
                break;
        }

        $headers = ['Variable', 'Valor'];
        $this->table($headers, $variablesTable);
        if (!$this->confirm('¿Es correcto?')) {
            $this->error('Abortando...');
            exit(1);
        }

        $config = file_get_contents(__DIR__ . "/Stubs/tmpl-aws-ecr-{$app_mode}.php");
        $config = str_replace($search, $replace, $config);

        if (file_exists(config_path('aws-ecr.php'))) {
            copy(config_path('aws-ecr.php'), config_path('aws-ecr.php.' . date('YmdHis') . '.bak'));
            unlink(config_path('aws-ecr.php'));
        }

        file_put_contents(config_path('aws-ecr.php'), $config);

        $this->info('Archivo de configuración creado... config/aws-ecr.php');

        system(base_path('./vendor/bin/pint') . ' ' . config_path('aws-ecr.php'));
    }
}
