<?php namespace Krucas\Notification;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Krucas\Notification\Middleware\NotificationMiddleware;

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
     * @param \Illuminate\Contracts\Events\Dispatcher $dispatcher
     * @return void
     */
    public function boot(Dispatcher $dispatcher)
    {
        $this->publishes(array(
            __DIR__ . '/../../config/notification.php' => config_path('notification.php'),
        ), 'config');

        $dispatcher->subscribe('Krucas\Notification\Subscriber');

        $this->app->afterResolving('blade.compiler', function ($bladeCompiler) {
            $bladeCompiler->directive('notification', function ($container = null) {
                if (strcasecmp('()', $container) === 0) {
                    $container = null;
                }

                return "<?php echo app('notification')->container({$container})->show(); ?>";
            });
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/notification.php', 'notification');

        $this->app->singleton('notification', function ($app) {
            $config = $app['config'];

            $notification = new Notification(
                $config->get('notification.default_container'),
                $config->get('notification.default_types'),
                $config->get('notification.types'),
                $config->get('notification.default_format'),
                $config->get('notification.format'),
                $config->get('notification.default_formats'),
                $config->get('notification.formats')
            );

            $notification->setEventDispatcher($app['events']);

            return $notification;
        });

        $this->app->alias('notification', 'Krucas\Notification\Notification');

        $this->app->singleton('Krucas\Notification\Subscriber', function ($app) {
            return new Subscriber($app['session.store'], $app['config']['notification.session_key']);
        });

        $this->app->singleton('Krucas\Notification\Middleware\NotificationMiddleware', function ($app) {
            return new NotificationMiddleware(
                $app['session.store'],
                $app['notification'],
                $app['config']->get('notification.session_key')
            );
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array(
            'Krucas\Notification\Notification',
            'Krucas\Notification\Subscriber',
            'notification',
        );
    }
}