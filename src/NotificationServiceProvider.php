<?php namespace Notification;

use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app['notification'] = $this->app->share(function($app)
        {
            return new Notification($app);
        });
    }

    public function boot()
    {

    }

}