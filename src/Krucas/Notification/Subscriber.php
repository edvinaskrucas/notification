<?php namespace Krucas\Notification;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Session\Store;

class Subscriber
{
    /**
     * Session instance for flashing messages.
     *
     * @var \Illuminate\Session\Store
     */
    protected $session;

    /**
     * Session key.
     *
     * @var string
     */
    protected $key;

    /**
     * Create new subscriber.
     *
     * @param \Illuminate\Session\Store $session
     * @param string $key
     */
    public function __construct(Store $session, $key)
    {
        $this->session = $session;
        $this->key = $key;
    }

    /**
     * Get session instance.
     *
     * @return \Illuminate\Session\Store
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Get session key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Execute this event to flash messages.
     *
     * @param string $eventName
     * @param array  $data       Event payload. Should be an array containing 3 elements:
     *                           [ Notification, NotificationsBag, Message ]
     * @return bool
     */
    public function onFlash($eventName, array $data)
    {
        // Data array should have 3 elements with sequential keys: Notification, NotificationsBag and Message
        if ( ! array_key_exists(0, $data) || ! array_key_exists(1, $data) || ! array_key_exists(2, $data)) {
            throw new \InvalidArgumentException(sprintf('%s expects 3 elements in data array, %s given.', __METHOD__, count($data)));
        }
        if ( ! $data[0] instanceof Notification || ! $data[1] instanceof NotificationsBag || ! $data[2] instanceof Message) {
            throw new \InvalidArgumentException(sprintf('%s expects a data array containing [%s], actually given [%s]', __METHOD__, implode(', ', [Notification::class, NotificationsBag::class, Message::class]), implode(', ', array_map(function ($element) {
                return is_object($element) ? get_class($element) : '{' . gettype($element) . '}';
            }, $data))));
        }

        list($notification, $notificationBag, $message) = $data;

        $key = implode('.', [$this->key, $notificationBag->getName()]);

        $this->session->push($key, $message);

        return true;
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param \Illuminate\Contracts\Events\Dispatcher $events
     * @return array
     */
    public function subscribe($events)
    {
        $events->listen('notification.flash: *', 'Krucas\Notification\Subscriber@onFlash');
    }
}
