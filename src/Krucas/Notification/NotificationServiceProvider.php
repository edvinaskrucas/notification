<?php namespace Krucas\Notification;

use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('edvinaskrucas/notification');
        $this->app['events']->fire('notification.booted', $this->app['notification']);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['config']->package('edvinaskrucas/notification', __DIR__.'/../config');

        $this->app['notification'] = $this->app->share(function ($app) {
            $config = $app['config'];

            $notification = new Notification(
                $config->get('notification::default_container'),
                $config->get('notification::default_types'),
                $config->get('notification::default_format'),
                $config->get('notification::default_formats')
            );

            $notification->setEventDispatcher($app['events']);

            return $notification;
        });

        $this->app->bind('Krucas\Notification\Subscriber', function ($app) {
            return new Subscriber($app['session'], $app['config']);
        });

        $this->app['events']->subscribe('Krucas\Notification\Subscriber');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array();
    }
}
