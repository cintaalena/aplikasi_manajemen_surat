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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

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

                if (isset($config['stream']['ssl']) && $transport instanceof EsmtpTransport) {
                    $stream = $transport->getStream();
                    if ($stream instanceof SocketStream) {
                        $stream->setStreamOptions(['ssl' => $config['stream']['ssl']]);
                    }
                }

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
