<?php namespace Krucas\Notification\Middleware;

use Closure;
use Illuminate\Session\Store;
use Krucas\Notification\Notification;

class NotificationMiddleware
{
    /**
     * @var \Illuminate\Session\Store
     */
    protected $session;

    /**
     * @var \Krucas\Notification\Notification
     */
    protected $notification;

    /**
     * @var string
     */
    protected $key;

    /**
     * @param \Illuminate\Session\Store $session
     * @param \Krucas\Notification\Notification $notification
     * @param string $key
     */
    public function __construct(Store $session, Notification $notification, $key)
    {
        $this->session = $session;
        $this->notification = $notification;
        $this->key = $key;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $containers = $this->session->get($this->key, []);

        if (count($containers) > 0) {
            foreach ($containers as $name => $messages) {
                /** @var \Krucas\Notification\Message $message */
                foreach ($messages as $message) {
                    $this->notification->container($name)->add($message->getType(), $message, false);
                }
            }
        }

        $this->session->forget($this->key);

        return $next($request);
    }
}
