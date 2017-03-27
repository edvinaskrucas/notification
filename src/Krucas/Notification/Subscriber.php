<?php namespace Krucas\Notification;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Session\Store;
use Krucas\Notification\Event\FlashEvent;

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
     * @param Notification $notification
     * @param NotificationsBag $notificationBag
     * @param Message $message
     * @return bool
     */
    public function onFlash(string $event,  $flashEvent)
    {
        $key = implode('.', [$this->key, $flashEvent[0]->getNotificationBag()->getName()]);

        $this->session->push($key, $flashEvent[0]->getMessage());

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
