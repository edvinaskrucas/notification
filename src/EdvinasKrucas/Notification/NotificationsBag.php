<?php namespace Krucas\Notification;

use Countable;
use Illuminate\Config\Repository;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Session\Store as SessionStore;
use Krucas\Notification\Message;
use Krucas\Notification\Collection;

class NotificationsBag implements ArrayableInterface, JsonableInterface, Countable
{
    /**
     * Config repository.
     *
     * @var \Illuminate\Config\Repository
     */
    protected $configRepository;

    /**
     * NotificationBag container name.
     *
     * @var string
     */
    protected $container;

    /**
     * Session store instance.
     *
     * @var \Illuminate\Session\Store
     */
    protected $sessionStore;

    /**
     * Messages collections by type.
     *
     * @var array
     */
    protected $collections = array();

    /**
     * Default global format for messages.
     *
     * @var null
     */
    protected $format = null;

    /**
     * Default message formats for individual types.
     *
     * @var array
     */
    protected $formats = array();

    /**
     * Creates new NotificationBag object.
     *
     * @param $container
     * @param SessionStore $sessionStore
     * @param Repository $configRepository
     */
    public function __construct($container, SessionStore $sessionStore, Repository $configRepository)
    {
        $this->container = $container;
        $this->configRepository = $configRepository;
        $this->sessionStore = $sessionStore;

        $this->loadFormats();

        $this->load();
    }

    /**
     * Adds new notification message to one of collections.
     * If message is array, adds multiple messages.
     * Message can be string, array (array can contain string for message, or array of message and format).
     * Flashes flashable messages.
     *
     * @param $type
     * @param string|array $message
     * @param bool $flashable
     * @param null $format
     * @return NotificationsBag
     */
    public function add($type, $message, $flashable = true, $format = null)
    {
        if(is_array($message))
        {
            foreach($message as $m)
            {
                if(is_array($m) && count($m) == 2)
                {
                    $this->get($type)->addUnique(new Message($type, $m[0], $flashable, $this->checkFormat($m[1], $type)));
                }
                else
                {
                    $this->get($type)->addUnique(new Message($type, $m, $flashable, $this->checkFormat($format, $type)));
                }
            }
        }
        else
        {
            $this->get($type)->addUnique(new Message($type, $message, $flashable, $this->checkFormat($format, $type)));
        }

        $this->flash();

        return $this;
    }

    /**
     * Shortcut to add success message.
     *
     * @param $message
     * @param null $format
     * @return NotificationsBag
     */
    public function success($message, $format = null)
    {
        return $this->add('success', $message, true, $format);
    }

    /**
     * Adds instant success message. It will be shown in same request.
     *
     * @param $message
     * @param null $format
     * @return NotificationsBag
     */
    public function successInstant($message, $format = null)
    {
        return $this->add('success', $message, false, $format);
    }

    /**
     * Shortcut to add error message.
     *
     * @param $message
     * @param null $format
     * @return NotificationsBag
     */
    public function error($message, $format = null)
    {
        return $this->add('error', $message, true, $format);
    }

    /**
     * Adds instant error message. It will be shown in same request.
     *
     * @param $message
     * @param null $format
     * @return NotificationsBag
     */
    public function errorInstant($message, $format = null)
    {
        return $this->add('error', $message, false, $format);
    }

    /**
     * Shortcut to add info message.
     *
     * @param $message
     * @param null $format
     * @return NotificationsBag
     */
    public function info($message, $format = null)
    {
        return $this->add('info', $message, true, $format);
    }

    /**
     * Adds instant info message. It will be shown in same request.
     *
     * @param $message
     * @param null $format
     * @return NotificationsBag
     */
    public function infoInstant($message, $format = null)
    {
        return $this->add('info', $message, false, $format);
    }

    /**
     * Shortcut to add warning message.
     *
     * @param $message
     * @param null $format
     * @return NotificationsBag
     */
    public function warning($message, $format = null)
    {
        return $this->add('warning', $message, true, $format);
    }

    /**
     * Adds instant warning message. It will be shown in same request.
     *
     * @param $message
     * @param null $format
     * @return NotificationsBag
     */
    public function warningInstant($message, $format = null)
    {
        return $this->add('warning', $message, false, $format);
    }

    /**
     * Returns first message object for given type.
     *
     * @param $type
     * @return Message
     */
    public function first($type)
    {
        return $this->get($type)->first();
    }

    /**
     * Returns all messages for given type.
     *
     * @param $type
     * @return Collection
     */
    public function get($type)
    {
        return array_key_exists($type, $this->collections) ? $this->collections[$type] : $this->collections[$type] = new Collection();
    }

    /**
     * Returns all messages in bag.
     *
     * @return Collection
     */
    public function all()
    {
        $all = array();

        foreach($this->collections as $collection)
        {
            $all = array_merge($all, $collection->all());
        }

        return new Collection($all);
    }

    /**
     * Loads default formats for messages.
     */
    protected function loadFormats()
    {
        $this->setFormat($this->configRepository->get('notification::default_format'));

        $config = $this->configRepository->get('notification::default_formats');

        $formats = isset($config[$this->container]) ?
            $config[$this->container] :
            $config['__'];

        foreach($formats as $type => $format)
        {
            $this->setFormat($format, $type);
        }
    }

    /**
     * Sets global or individual message format.
     *
     * @param $format
     * @param null $type
     * @return NotificationsBag
     */
    public function setFormat($format, $type = null)
    {
        if(!is_null($type))
        {
            $this->formats[$type] = $format;
        }
        else
        {
            $this->format = $format;
        }

        return $this;
    }

    /**
     * Returns message format.
     *
     * @param null $type
     * @return null
     */
    public function getFormat($type = null)
    {
        return !is_null($type) && isset($this->formats[$type]) ? $this->formats[$type] : $this->format;
    }

    /**
     * Returns valid format.
     *
     * @param $format
     * @param null $type
     * @return null
     */
    protected function checkFormat($format, $type = null)
    {
        return !is_null($format) ? $format : $this->getFormat($type);
    }

    /**
     * Loads flashed messages.
     */
    protected function load()
    {
        $flashed = $this->sessionStore->get('notifications_'.$this->container);

        if($flashed)
        {
            $messages = json_decode($flashed);

            if(is_array($messages))
            {
                foreach($messages as $key => $message)
                {
                    $this->get($message->type)->addUnique(new Message($message->type, $message->message, false, $message->format));
                }
            }
        }
    }

    /**
     * Flashes all flashable messages.
     */
    protected function flash()
    {
        $this->sessionStore->flash('notifications_'.$this->container, $this->getFlashable()->toJson());
    }

    /**
     * Returns all flashable messages.
     *
     * @return Collection
     */
    protected function getFlashable()
    {
        $collection = new Collection();

        foreach($this->all() as $message)
        {
            if($message->isFlashable())
            {
                $collection->addUnique($message);
            }
        }

        return $collection;
    }

    /**
     * Returns generated output of non flashable messages.
     *
     * @param null $type
     * @param null $format
     * @return string
     */
    public function show($type = null, $format = null)
    {
        $messages = is_null($type) ? $this->all() : $this->get($type);

        $output = '';

        foreach($messages as $message)
        {
            if(!$message->isFlashable())
            {
                if(!is_null($format)) $message->setFormat($format);

                $output .= $message->render();
            }
        }

        return $output;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        $arr = array
        (
            'container'         => $this->container,
            'format'            => $this->format,
            'collections'       => array()
        );

        foreach($this->collections as $type => $collection)
        {
            $arr['collections'][$type] = $collection->toArray();
        }

        return $arr;
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray());
    }

    /**
     * Count the number of colections.
     *
     * @return int
     */
    public function count()
    {
        return count($this->collections);
    }

    /**
     * Returns session store instance.
     *
     * @return SessionStore
     */
    public function getSessionStore()
    {
        return $this->sessionStore;
    }

    /**
     * Returns config repository instance.
     *
     * @return Repository
     */
    public function getConfigRepository()
    {
        return $this->configRepository;
    }

    /**
     * Convert the Bag to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        $html = '';

        foreach($this->collections as $collection)
        {
            $html .= $collection;
        }

        return $html;
    }


}