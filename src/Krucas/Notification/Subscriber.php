<?php namespace Krucas\Notification;

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
        $this->validateEventData($data);

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

    /**
     * Validates that the correct event data has been passed to self::onFlash()
     *
     * Data array should have 3 elements with sequential keys: Notification, NotificationsBag and Message
     *
     * @param  array  $data
     * @throws InvalidArgumentException  If the event data is invalid.
     */
    private function validateEventData(array $data)
    {
        if ( ! array_key_exists(0, $data) || ! array_key_exists(1, $data) || ! array_key_exists(2, $data)) {
            throw new \InvalidArgumentException(sprintf(
                '%s expects 3 elements in data array, %s given.',
                sprintf('%s::onFlash', __CLASS__),
                count($data)
            ));
        }

        if ( ! $data[0] instanceof Notification || ! $data[1] instanceof NotificationsBag || ! $data[2] instanceof Message) {
            $expected = [Notification::class, NotificationsBag::class, Message::class];

            $actual = array_map(function ($element) {
                return is_object($element) ? get_class($element) : '{' . gettype($element) . '}';
            }, $data);

            throw new \InvalidArgumentException(sprintf(
                '%s expects a data array containing [%s], actually given [%s]',
                sprintf('%s::onFlash', __CLASS__),
                implode(', ', $expected),
                implode(', ', $actual)
            ));
        }
    }
}
