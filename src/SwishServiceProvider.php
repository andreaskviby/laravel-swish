<?php

namespace AndreasKviby\LaravelSwish;

use AndreasKviby\LaravelSwish\Support\Certificate;
use Illuminate\Support\ServiceProvider;

/**
 * Laravel Service Provider för Swish-paket
 */
class SwishServiceProvider extends ServiceProvider
{
    /**
     * Registrera paketets tjänster
     *
     * @return void
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/swish.php',
            'swish'
        );

        // Registrera SwishClient som singleton
        $this->app->singleton(SwishClient::class, function ($app) {
            $config = $app['config']['swish'];

            // Skapa certifikat-instans
            $certificate = new Certificate(
                $config['certificates']['client_cert'],
                $config['certificates']['client_cert_password'],
                $config['certificates']['root_cert']
            );

            // Välj endpoint baserat på miljö
            $endpoint = $config['endpoints'][$config['environment']] 
                ?? throw new \RuntimeException("Ogiltig Swish-miljö: {$config['environment']}. Tillåtna värden: production, test");

            // Skapa och returnera SwishClient
            return new SwishClient(
                $certificate,
                $endpoint,
                $config['verify_ssl'] ?? true,
                $config['timeout'] ?? 30,
                $config['qr_code_url'] ?? null
            );
        });

        // Registrera alias
        $this->app->alias(SwishClient::class, 'swish');
    }

    /**
     * Bootstrap paketets tjänster
     *
     * @return void
     */
    public function boot(): void
    {
        // Publicera konfigurationsfil
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/swish.php' => config_path('swish.php'),
            ], 'swish-config');
        }
    }

    /**
     * Hämta tjänster som paketet tillhandahåller
     *
     * @return array
     */
    public function provides(): array
    {
        return [SwishClient::class, 'swish'];
    }
}
