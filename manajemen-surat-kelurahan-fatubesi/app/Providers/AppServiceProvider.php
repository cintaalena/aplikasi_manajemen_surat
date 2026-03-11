<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Inject SSL stream options (cafile) ke Symfony SocketStream.
        // Laravel 12 (Symfony Mailer) tidak meneruskan config 'stream' ke SocketStream,
        // sehingga perlu di-patch manual melalui custom transport creator.
        $this->app->resolving('mail.manager', function ($manager) {
            $manager->extend('smtp', function (array $config) {
                $scheme = $config['scheme'] ?? null;
                if (! $scheme) {
                    $scheme = ($config['port'] == 465) ? 'smtps' : 'smtp';
                }

                $factory = new EsmtpTransportFactory();
                $transport = $factory->create(new Dsn(
                    $scheme,
                    $config['host'],
                    $config['username'] ?? null,
                    $config['password'] ?? null,
                    $config['port'] ?? null,
                    $config
                ));

                // Set SSL stream context options (cafile, verify_peer, dll.)
                if (isset($config['stream']['ssl']) && $transport instanceof EsmtpTransport) {
                    $stream = $transport->getStream();
                    if ($stream instanceof SocketStream) {
                        $stream->setStreamOptions(['ssl' => $config['stream']['ssl']]);
                    }
                }

                // Set timeout
                if (isset($config['timeout'])) {
                    $stream = $transport->getStream();
                    if ($stream instanceof SocketStream) {
                        $stream->setTimeout($config['timeout']);
                    }
                }

                return $transport;
            });
        });
    }
}
