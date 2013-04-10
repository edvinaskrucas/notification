<?php namespace Krucas\Notification;

use Krucas\Notification\NotificationsBag;
use Closure;
use Illuminate\Config\Repository;
use Illuminate\Session\Store as SessionStore;

class Notification
{
    /**
     * Config repository.
     *
     * @var \Illuminate\Config\Repository
     */
    protected $configRepository;

    /**
     * Session store instance.
     *
     * @var \Illuminate\Session\Store
     */
    protected $sessionStore;

    /**
     * List of instantiated containers.
     *
     * @var array
     */
    protected $containers = array();

    /**
     * Creates new instance.
     *
     * @param \Illuminate\Config\Repository $configRepository
     * @param \Illuminate\Session\Store $sessionStore
     */
    public function __construct(Repository $configRepository, SessionStore $sessionStore)
    {
        $this->configRepository = $configRepository;
        $this->sessionStore = $sessionStore;
    }

    /**
     * Adds success message to default container.
     *
     * @param $message
     * @param null $format
     * @return \Krucas\Notification\NotificationBag
     */
    public function success($message, $format = null)
    {
        return $this->addMessage(null, 'success', $message, true, $format);
    }

    /**
     * Adds instant success message. It will be shown in same request.
     *
     * @param $message
     * @param null $format
     * @return \Krucas\Notification\NotificationBag
     */
    public function successInstant($message, $format = null)
    {
        return $this->addMessage(null, 'success', $message, false, $format);
    }

    /**
     * Clears success messages in a given container.
     *
     * @return \Krucas\Notification\NotificationBag
     */
    public function clearSuccess()
    {
        return $this->container(null)->clearSuccess();
    }

    /**
     * Adds error message to default container.
     *
     * @param $message
     * @param null $format
     * @return \Krucas\Notification\NotificationBag
     */
    public function error($message, $format = null)
    {
        return $this->addMessage(null, 'error', $message, true, $format);
    }

    /**
     * Adds instant error message. It will be shown in same request.
     *
     * @param $message
     * @param null $format
     * @return \Krucas\Notification\NotificationBag
     */
    public function errorInstant($message, $format = null)
    {
        return $this->addMessage(null, 'error', $message, false, $format);
    }

    /**
     * Clears error messages in a given container.
     *
     * @return \Krucas\Notification\NotificationBag
     */
    public function clearError()
    {
        return $this->container(null)->clearError();
    }

    /**
     * Adds warning message to default container.
     *
     * @param $message
     * @param null $format
     * @return \Krucas\Notification\NotificationBag
     */
    public function warning($message, $format = null)
    {
        return $this->addMessage(null, 'warning', $message, true, $format);
    }

    /**
     * Adds instant warning message. It will be shown in same request.
     *
     * @param $message
     * @param null $format
     * @return \Krucas\Notification\NotificationBag
     */
    public function warningInstant($message, $format = null)
    {
        return $this->addMessage(null, 'warning', $message, false, $format);
    }

    /**
     * Clears warning messages in a given container.
     *
     * @return \Krucas\Notification\NotificationBag
     */
    public function clearWarning()
    {
        return $this->container(null)->clearWarning();
    }

    /**
     * Adds info message to default container.
     *
     * @param $message
     * @param null $format
     * @return \Krucas\Notification\NotificationBag
     */
    public function info($message, $format = null)
    {
        return $this->addMessage(null, 'info', $message, true, $format);
    }

    /**
     * Adds instant info message. It will be shown in same request.
     *
     * @param $message
     * @param null $format
     * @return \Krucas\Notification\NotificationBag
     */
    public function infoInstant($message, $format = null)
    {
        return $this->addMessage(null, 'info', $message, false, $format);
    }

    /**
     * Clears info messages in a given container.
     *
     * @return \Krucas\Notification\NotificationBag
     */
    public function clearInfo()
    {
        return $this->container(null)->clearInfo();
    }

    /**
     * Clears all messages in a given container.
     *
     * @return \Krucas\Notification\NotificationBag
     */
    public function clearAll()
    {
        return $this->container(null)->clearAll();
    }

    /**
     * Returns container.
     *
     * @param $container
     * @return mixed
     */
    public function get($container)
    {
        return $this->container($container);
    }

    /**
     * Returns message at given position in a default container.
     *
     * @param $position
     * @return \Krucas\Notification\Message
     */
    public function getAtPosition($position)
    {
        return $this->container(null)->getAtPosition($position);
    }

    /**
     * Adds message to container.
     *
     * @param $container
     * @param $type
     * @param $message
     * @param bool $flash
     * @param null $format
     */
    protected function addMessage($container, $type, $message, $flash = true, $format = null)
    {
        return $this->container($container)->add($type, $message, $flash, $format);
    }

    /**
     * Returns container instance.
     *
     * @param null $container
     * @param callable $callback
     * @return mixed
     */
    public function container($container = null, Closure $callback = null)
    {
        $container = is_null($container) ? $this->configRepository->get('notification::default_container') : $container;

        if(!isset($this->containers[$container]))
        {
            $this->containers[$container] = new NotificationsBag($container, $this->sessionStore, $this->configRepository);
        }

        if(is_callable($callback))
        {
            $callback($this->containers[$container]);
        }

        return $this->containers[$container];
    }

    /**
     * Renders each message by given type (or all) in container.
     *
     * @param null $type
     * @param null $container
     * @param null $format
     * @return mixed
     */
    protected function show($type = null, $container = null, $format = null)
    {
        return $this->container($container)->show($type, $format);
    }

    /**
     * Renders error messages in given container.
     *
     * @param null $container
     * @param null $format
     * @return mixed
     */
    public function showError($container = null, $format = null)
    {
        return $this->show('error', $container, $format);
    }

    /**
     * Renders success messages in given container.
     *
     * @param null $container
     * @param null $format
     * @return mixed
     */
    public function showSuccess($container = null, $format = null)
    {
        return $this->show('success', $container, $format);
    }

    /**
     * Renders info messages in given container.
     *
     * @param null $container
     * @param null $format
     * @return mixed
     */
    public function showInfo($container = null, $format = null)
    {
        return $this->show('info', $container, $format);
    }

    /**
     * Renders warning messages in given container.
     *
     * @param null $container
     * @param null $format
     * @return mixed
     */
    public function showWarning($container = null, $format = null)
    {
        return $this->show('warning', $container, $format);
    }

    /**
     * Renders all messages in given container.
     *
     * @param null $container
     * @param null $format
     * @return mixed
     */
    public function showAll($container = null, $format = null)
    {
        return $this->show(null, $container, $format);
    }

    /**
     * Returns config repository instance.
     *
     * @return \Illuminate\Config\Repository
     */
    public function getConfigRepository()
    {
        return $this->configRepository;
    }

    /**
     * Returns session store instance.
     *
     * @return \Illuminate\Session\Store
     */
    public function getSessionStore()
    {
        return $this->sessionStore;
    }

}