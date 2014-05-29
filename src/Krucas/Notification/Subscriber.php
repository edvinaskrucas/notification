<?php namespace Krucas\Notification;

use Illuminate\Config\Repository;
use Illuminate\Session\SessionManager;

class Subscriber
{
    /**
     * Count of flashed messages.
     *
     * @var int
     */
    protected static $flashCount = 0;

    /**
     * Session instance for flashing messages.
     *
     * @var \Illuminate\Session\SessionManager
     */
    protected $session;

    /**
     * Config repository.
     *
     * @var \Illuminate\Config\Repository
     */
    protected $config;

    /**
     * Create new subscriber.
     *
     * @param \Illuminate\Session\SessionManager $session
     * @param \Illuminate\Config\Repository $config
     */
    public function __construct(SessionManager $session, Repository $config)
    {
        $this->session = $session;
        $this->config = $config;
    }

    /**
     * Get session instance.
     *
     * @return \Illuminate\Session\SessionManager
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Get config repository instance.
     *
     * @return \Illuminate\Config\Repository
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Execute this event when notification package is booted.
     * Load flash messages and add them as instant.
     *
     * @param Notification $notification
     * @return bool
     */
    public function onBoot(Notification $notification)
    {
        $sessionPrefix = $this->getConfig()->get('notification::session_prefix');

        $containerNames = $this->getSession()->get($sessionPrefix.'containers', array());

        $sessionVariables = $this->getSession()->all();

        foreach ($containerNames as $containerName) {
            foreach ($sessionVariables as $sessionKey => $value) {
                if (strpos($sessionKey, $sessionPrefix.$containerName) === 0 && is_string($value)) {
                    $jsonMessage = json_decode($value);
                    $notification->container($containerName)->add(
                        $jsonMessage->type,
                        new Message(
                            $jsonMessage->type,
                            $jsonMessage->message,
                            false,
                            $jsonMessage->format,
                            $jsonMessage->alias,
                            $jsonMessage->position
                        ),
                        false
                    );
                }
            }
        }

        return true;
    }

    /**
     * Execute this event to flash messages.
     *
     * @param Notification $notification
     * @param NotificationsBag $notificationBag
     * @param Message $message
     * @return bool
     */
    public function onFlash(Notification $notification, NotificationsBag $notificationBag, Message $message)
    {
        $this->flashContainerNames($notification);

        $sessionKey  = $this->getConfig()->get('notification::session_prefix');
        $sessionKey .= $notificationBag->getName();
        $sessionKey .= '_'.$this->generateMessageKey($message);

        $this->getSession()->flash($sessionKey, $message->toJson());

        return true;
    }

    /**
     * Flash used container names.
     *
     * @param Notification $notification
     * @return void
     */
    protected function flashContainerNames(Notification $notification)
    {
        $names = array();

        foreach ($notification->getContainers() as $container) {
            $names[] = $container->getName();
        }

        $this->getSession()->flash($this->getConfig()->get('notification::session_prefix').'containers', $names);
    }

    /**
     * Generate session suffix for given message.
     *
     * @param Message $message
     * @return string
     */
    protected function generateMessageKey(Message $message)
    {
        static::$flashCount++;

        return static::$flashCount;
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param \Illuminate\Events\Dispatcher $events
     * @return array
     */
    public function subscribe($events)
    {
        $events->listen('notification.flash: *', 'Krucas\Notification\Subscriber@onFlash');
        $events->listen('notification.booted', 'Krucas\Notification\Subscriber@onBoot');
    }
}
