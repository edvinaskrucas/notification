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
     * Instance of lastly added message.
     *
     * @var \Krucas\Notification\Message|null
     */
    protected $lastMessage = null;

    /**
     * Lastly added message position (when used atPosition()).
     *
     * @var int|null
     */
    protected $lastPosition = null;

    /**
     * Creates new NotificationBag object.
     *
     * @param $container
     * @param \Illuminate\Session\Store $sessionStore
     * @param \Illuminate\Config\Repository $configRepository
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
     * @return \Krucas\Notification\NotificationsBag
     */
    public function add($type, $message, $flashable = true, $format = null)
    {
        $this->lastMessage = null;
        $this->lastPosition = null;

        if(is_array($message))
        {
            $this->addArray($type, $message, $flashable, $format);
        }
        else
        {
            $this->lastMessage = new Message($type, $message, $flashable, $this->checkFormat($format, $type));

            $this->get($type)->addUnique($this->lastMessage);

            if($flashable)
            {
                $this->flash();
            }
        }

        return $this;
    }

    /**
     * Adds messages from an array.
     *
     * @param $type
     * @param array $messages
     * @param bool $flashable
     * @param null $defaultFormat
     *
     * @return void
     */
    protected function addArray($type, array $messages = array(), $flashable = true, $defaultFormat = null)
    {
        foreach($messages as $message)
        {
            $text = $format = $alias = $position = null;

            if(!is_null($defaultFormat))
            {
                $format = $defaultFormat;
            }

            if(is_array($message) && isset($message['message']))
            {
                $text = $message['message'];

                if(isset($message['alias']))
                {
                    $alias = $message['alias'];
                }

                if(isset($message['format']))
                {
                    $format = $message['format'];
                }

                if(isset($message['position']))
                {
                    $position = $message['position'];
                }
            }
            elseif(is_array($message) && count($message) == 2)
            {
                $text = $message[0];
                $format = $message[1];
            }
            else
            {
                $text = $message;
            }

            $this->add($type, $text, $flashable, $format);

            if(!is_null($alias))
            {
                $this->alias($alias);
            }

            if(!is_null($position))
            {
                $this->atPosition($position);
            }
        }
    }

    /**
     * Sets alias for lastly added message.
     * If message with alias exists, it overrides it.
     *
     * @param $alias
     * @return \Krucas\Notification\NotificationBag
     */
    public function alias($alias)
    {
        if($this->lastMessage instanceof Message)
        {
            $lastMessageIndex = $this->get($this->lastMessage->getType())->indexOf($this->lastMessage);

            $this->lastMessage->setAlias($alias);

            foreach($this->get($this->lastMessage->getType()) as $key => $message)
            {
                if($message->getAlias() == $alias)
                {
                    $index = $this->get($message->getType())->indexOf($message);

                    if($index !== false)
                    {
                        $this->get($message->getType())->offsetUnset($index);
                        $this->get($this->lastMessage->getType())->offsetUnset($lastMessageIndex);
                        $this->get($this->lastMessage->getType())->setAtPosition(is_null($this->lastPosition) ? $index : $this->lastPosition, $this->lastMessage);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Sets last message at given position.
     *
     * @param $position
     * @return \Krucas\Notification\NotificationBag
     */
    public function atPosition($position)
    {
        $this->lastPosition = $position;

        if($this->lastMessage instanceof Message)
        {
            $this->get($this->lastMessage->getType())->offsetUnset($this->get($this->lastMessage->getType())->indexOf($this->lastMessage));
            $this->get($this->lastMessage->getType())->setAtPosition($position, $this->lastMessage);
        }

        return $this;
    }

    /**
     * Clears message for a given type.
     *
     * @param null $type
     * @return \Krucas\Notification\NotificationBag
     */
    public function clear($type = null)
    {
        if(is_null($type))
        {
            $this->collections = array();
        }
        else
        {
            unset($this->collections[$type]);
        }

        return $this;
    }

    /**
     * Clears all messages.
     * Alias for clear(null).
     *
     * @return \Krucas\Notification\NotificationBag
     */
    public function clearAll()
    {
        return $this->clear(null);
    }

    /**
     * Shortcut to add success message.
     *
     * @param $message
     * @param null $format
     * @return \Krucas\Notification\NotificationsBag
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
     * @return \Krucas\Notification\NotificationsBag
     */
    public function successInstant($message, $format = null)
    {
        return $this->add('success', $message, false, $format);
    }

    /**
     * Clears success messages.
     *
     * @return \Krucas\Notification\NotificationsBag
     */
    public function clearSuccess()
    {
        return $this->clear('success');
    }

    /**
     * Shortcut to add error message.
     *
     * @param $message
     * @param null $format
     * @return \Krucas\Notification\NotificationsBag
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
     * @return \Krucas\Notification\NotificationsBag
     */
    public function errorInstant($message, $format = null)
    {
        return $this->add('error', $message, false, $format);
    }

    /**
     * Clears error messages.
     *
     * @return \Krucas\Notification\NotificationsBag
     */
    public function clearError()
    {
        return $this->clear('error');
    }

    /**
     * Shortcut to add info message.
     *
     * @param $message
     * @param null $format
     * @return \Krucas\Notification\NotificationsBag
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
     * @return \Krucas\Notification\NotificationsBag
     */
    public function infoInstant($message, $format = null)
    {
        return $this->add('info', $message, false, $format);
    }

    /**
     * Clears info messages.
     *
     * @return \Krucas\Notification\NotificationsBag
     */
    public function clearInfo()
    {
        return $this->clear('info');
    }

    /**
     * Shortcut to add warning message.
     *
     * @param $message
     * @param null $format
     * @return \Krucas\Notification\NotificationsBag
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
     * @return \Krucas\Notification\NotificationsBag
     */
    public function warningInstant($message, $format = null)
    {
        return $this->add('warning', $message, false, $format);
    }

    /**
     * Clears warning messages.
     *
     * @return \Krucas\Notification\NotificationsBag
     */
    public function clearWarning()
    {
        return $this->clear('warning');
    }

    /**
     * Returns first message object for given type.
     *
     * @param $type
     * @return \Krucas\Notification\Message
     */
    public function first($type)
    {
        return $this->get($type)->first();
    }

    /**
     * Returns all messages for given type.
     *
     * @param $type
     * @return \Krucas\Notification\Collection
     */
    public function get($type)
    {
        return array_key_exists($type, $this->collections) ? $this->collections[$type] : $this->collections[$type] = new Collection();
    }

    /**
     * Returns all messages in bag.
     *
     * @return \Krucas\Notification\Collection
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
     *
     * @return void
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
     * @return \Krucas\Notification\NotificationsBag
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
     *
     * @return void
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
     *
     * @return void
     */
    protected function flash()
    {
        $this->sessionStore->flash('notifications_'.$this->container, $this->getFlashable()->toJson());
    }

    /**
     * Returns all flashable messages.
     *
     * @return \Krucas\Notification\Collection
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
     * @return \Illuminate\Session\Store
     */
    public function getSessionStore()
    {
        return $this->sessionStore;
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