<?php namespace Krucas\Notification;

use Countable;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\JsonableInterface;

class NotificationsBag implements ArrayableInterface, JsonableInterface, Countable
{
    /**
     * NotificationBag container name.
     *
     * @var string
     */
    protected $container = null;

    /**
     * Available message types in container.
     *
     * @var array
     */
    protected $types = array();

    /**
     * Array of matcher for extracting types.
     *
     * @var array
     */
    protected $matcher = array(
        'add'       => '{type}',
        'instant'   => '{type}Instant',
        'clear'     => 'clear{uType}',
        'show'      => 'show{uType}',
    );

    /**
     * Default format for all message types.
     *
     * @var string
     */
    protected $defaultFormat = null;

    /**
     * Default formats for types.
     *
     * @var array
     */
    protected $formats = array();

    /**
     * Collection to store all instant notification messages.
     *
     * @var \Krucas\Notification\Collection|null
     */
    protected $notifications;

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
     * The event dispatcher instance.
     *
     * @var \Illuminate\Events\Dispatcher
     */
    protected static $dispatcher;

    /**
     * Creates new NotificationBag object.
     *
     * @param $container
     * @param array $types
     * @param null $defaultFormat
     * @param array $formats
     */
    public function __construct($container, $types = array(), $defaultFormat = null, $formats = array())
    {
        $this->container = $container;
        $this->addType($types);
        $this->setDefaultFormat($defaultFormat);
        $this->setFormats($formats);
        $this->notifications = new Collection();
    }

    /**
     * Returns assigned container name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->container;
    }

    /**
     * Add new available type of message to bag.
     *
     * @param $type
     * @return \Krucas\Notification\NotificationsBag
     */
    public function addType($type)
    {
        if (is_array($type)) {
            foreach ($type as $t) {
                $this->addType($t);
            }
        } else {
            if (!$this->typeIsAvailable($type)) {
                $this->types[] = $type;
            }
        }

        return $this;
    }

    /**
     * Return available types of messages in container.
     *
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Determines if type is available in container.
     *
     * @param $type
     * @return bool
     */
    public function typeIsAvailable($type)
    {
        return in_array($type, array_values($this->types)) ? true : false;
    }

    /**
     * Resets types values.
     *
     * @return \Krucas\Notification\NotificationsBag
     */
    public function clearTypes()
    {
        $this->types = array();

        return $this;
    }

    /**
     * Extract type from a given string.
     *
     * @param $name
     * @return bool|array
     */
    protected function extractType($name)
    {
        if (count($this->types) <= 0) {
            return false;
        }

        foreach ($this->types as $type) {
            foreach ($this->matcher as $function => $pattern) {
                if (str_replace(array('{type}', '{uType}'), array($type, ucfirst($type)), $pattern) === $name) {
                    return array($type, $function);
                }
            }
        }

        return false;
    }

    /**
     * Set default format for all message types.
     *
     * @param $format
     * @return \Krucas\Notification\NotificationsBag
     */
    public function setDefaultFormat($format)
    {
        $this->defaultFormat = $format;

        return $this;
    }

    /**
     * Return default format.
     *
     * @return string
     */
    public function getDefaultFormat()
    {
        return $this->defaultFormat;
    }

    /**
     * Set formats for a given types.
     *
     * @param $formats
     * @return \Krucas\Notification\NotificationsBag
     */
    public function setFormats($formats)
    {
        foreach ($formats as $type => $format) {
            $this->setFormat($type, $format);
        }

        return $this;
    }

    /**
     * Set format for a given type.
     *
     * @param $type
     * @param $format
     * @return \Krucas\Notification\NotificationsBag
     */
    public function setFormat($type, $format)
    {
        if ($this->typeIsAvailable($type)) {
            $this->formats[$type] = $format;
        }

        return $this;
    }

    /**
     * Return format for a given type.
     *
     * @param $type
     * @return bool|string
     */
    public function getFormat($type)
    {
        if (!$this->typeIsAvailable($type)) {
            return false;
        }

        if (isset($this->formats[$type])) {
            return $this->formats[$type];
        }

        if (!is_null($this->getDefaultFormat())) {
            return $this->getDefaultFormat();
        }

        return false;
    }

    /**
     * Clear format for a given type.
     *
     * @param $type
     * @return \Krucas\Notification\NotificationsBag
     */
    public function clearFormat($type)
    {
        unset($this->formats[$type]);

        return $this;
    }

    /**
     * Clear all formats.
     *
     * @return \Krucas\Notification\NotificationsBag
     */
    public function clearFormats()
    {
        $this->formats = array();

        return $this;
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
     * Adds new notification message to one of collections.
     * If message is array, adds multiple messages.
     * Message can be string, array (array can contain string for message, or array of message and format).
     * Flashes flashable messages.
     *
     * @param $type
     * @@param string|array $message
     * @param bool $flashable
     * @param null $format
     * @return \Krucas\Notification\NotificationsBag
     */
    public function add($type, $message, $flashable = true, $format = null)
    {
        $this->lastMessage = null;
        $this->lastPosition = null;

        if (!$this->typeIsAvailable($type)) {
            return $this;
        }

        $m = new Message($type, $message, $flashable, $this->checkFormat($format, $type));
        if (!$flashable) {
            $this->lastMessage = $m;
            $this->notifications->addUnique($this->lastMessage);
            //$this->fireEvent('added', $this->lastMessage);
        } else {
            $this->fireEvent('flash', $m);
        }

        return $this;
    }

    /**
     * Returns all messages for given type.
     *
     * @param $type
     * @return \Krucas\Notification\Collection
     */
    public function get($type)
    {
        $collection = new Collection();

        foreach ($this->notifications as $key => $message) {
            if ($message->getType() == $type) {
                if (!is_null($message->getPosition())) {
                    $collection->setAtPosition($key, $message);
                } else {
                    $collection->addUnique($message);
                }
            }
        }

        return $collection;
    }

    /**
     * Clears message for a given type.
     *
     * @param null $type
     * @return \Krucas\Notification\NotificationBag
     */
    public function clear($type = null)
    {
        if (is_null($type)) {
            $this->notifications = new Collection();
        } else {
            foreach ($this->notifications as $key => $message) {
                if ($message->getType() == $type) {
                    $this->notifications->offsetUnset($key);
                }
            }
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
     * Returns all messages in bag.
     *
     * @return \Krucas\Notification\Collection
     */
    public function all()
    {
        return $this->notifications;
    }

    /**
     * Returns first message object for given type.
     *
     * @return \Krucas\Notification\Message
     */
    public function first()
    {
        return $this->notifications->first();
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
        $messages = $this->getMessagesForRender($type);

        $output = '';

        foreach ($messages as $message) {
            if (!$message->isFlashable()) {
                if (!is_null($format)) {
                    $message->setFormat($format);
                }

                $output .= $message->render();
            }
        }

        return $output;
    }

    /**
     * Renders all messages.
     *
     * @param null $format
     * @return string
     */
    public function showAll($format = null)
    {
        return $this->show(null, $format);
    }

    /**
     * Resolves which messages should be returned for rendering.
     *
     * @param null $type
     * @return \Krucas\Notification\Collection
     */
    protected function getMessagesForRender($type = null)
    {
        if (is_null($type)) {
            return $this->all();
        } else {
            return $this->get($type);
        }
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
            'format'            => $this->getDefaultFormat(),
            'types'             => $this->getTypes(),
            'notifications'     => $this->notifications->toArray()
        );

        return $arr;
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Count the number of colections.
     *
     * @return int
     */
    public function count()
    {
        return count($this->notifications);
    }

    /**
     * Fire event for a given message.
     *
     * @param $event
     * @param $message
     * @return boolean
     */
    protected function fireEvent($event, $message)
    {
        if (!isset(static::$dispatcher)) {
            return true;
        }

        $event = "notification.{$event}: ".$this->getName();

        return static::$dispatcher->fire($event, array($this, $message));
    }

    /**
     * Get the event dispatcher instance.
     *
     * @return \Illuminate\Events\Dispatcher
     */
    public static function getEventDispatcher()
    {
        return static::$dispatcher;
    }

    /**
     * Set the event dispatcher instance.
     *
     * @param  \Illuminate\Events\Dispatcher  $dispatcher
     * @return void
     */
    public static function setEventDispatcher(Dispatcher $dispatcher)
    {
        static::$dispatcher = $dispatcher;
    }

    /**
     * Unset the event dispatcher for models.
     *
     * @return void
     */
    public static function unsetEventDispatcher()
    {
        static::$dispatcher = null;
    }

    /**
     * Execute short version of function calls.
     *
     * @param $name
     * @param $arguments
     * @return \Krucas\Notification\NotificationsBag|string
     */
    public function __call($name, $arguments)
    {
        if (($extracted = $this->extractType($name)) !== false) {
            switch($extracted[1]) {
                case 'add':
                    return $this->add(
                        $extracted[0],
                        isset($arguments[0]) ? $arguments[0] : null,
                        true,
                        isset($arguments[1]) ? $arguments[1] : null
                    );
                    break;

                case 'instant':
                    return $this->add(
                        $extracted[0],
                        isset($arguments[0]) ? $arguments[0] : null,
                        false,
                        isset($arguments[1]) ? $arguments[1] : null
                    );
                    break;

                case 'clear':
                    return $this->clear($extracted[0]);
                    break;

                case 'show':
                    return $this->show($extracted[0], isset($arguments[0]) ? $arguments[0] : null);
                    break;
            }
        }
    }
}
