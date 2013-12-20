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
     * Default container name.
     *
     * @var string
     */
    protected $defaultContainer;

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
     * @param string $defaultContainer
     */
    public function __construct(Repository $configRepository, SessionStore $sessionStore, $defaultContainer = 'default')
    {
        $this->configRepository = $configRepository;
        $this->sessionStore = $sessionStore;
        $this->defaultContainer = $defaultContainer;
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
        $container = is_null($container) ? $this->defaultContainer : $container;

        if(!isset($this->containers[$container]))
        {
            $this->containers[$container] = new NotificationsBag($container, $this->sessionStore, $this->configRepository, array('success', 'info', 'error', 'warning'), '<div class="alert alert-:type">:message</div>', array());
        }

        if(is_callable($callback))
        {
            $callback($this->containers[$container]);
        }

        return $this->containers[$container];
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

    /**
     * Calls NotificationBag function for a default container.
     *
     * @param $name
     * @param $arguments
     * @return \Krucas\Notification\NotificationBag|null
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array(array($this->container(null), $name), $arguments);
    }

}