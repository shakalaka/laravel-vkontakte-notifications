<?php

namespace NotificationChannels\Vkontakte;

use GuzzleHttp\Client;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use NotificationChannels\Vkontakte\Vkontakte;

/**
 * Class VkontakteServiceProvider.
 */
class VkontakteServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->app->bind(Vkontakte::class, static function () {
            return new Vkontakte(
                config('services.vkontakte-api.secret'),
                app(Client::class),
                config('services.vkontakte-api.base_uri')
            );
        });

        Notification::resolved(static function (ChannelManager $service) {
            $service->extend('vkontakte', static function ($app) {
                return $app->make(VkontakteChannel::class);
            });
        });
    }
}
