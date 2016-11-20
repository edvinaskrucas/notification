<?php namespace Krucas\Notification;

use Countable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Closure;

class NotificationsBag implements Arrayable, Jsonable, Countable
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
     * Sequence of how messages should be rendered by its type.
     *
     * @var array
     */
    protected $groupForRender = array();

    /**
     * Notification library instance.
     *
     * @var \Krucas\Notification\Notification
     */
    protected $notification;

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
        if (func_num_args() > 1) {
            foreach (func_get_args() as $t) {
                $this->addType($t);
            }
        } else {
            if (is_array($type)) {
                foreach ($type as $t) {
                    $this->addType($t);
                }
            } else {
                if (!$this->typeIsAvailable($type)) {
                    $this->types[] = $type;
                }
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
     * @param string|\Krucas\Notification\Message|\Closure $message
     * @param bool $flash
     * @param null $format
     * @return \Krucas\Notification\NotificationsBag
     */
    public function add($type, $message, $flash = true, $format = null)
    {
        if (!$this->typeIsAvailable($type)) {
            return $this;
        }

        if ($message instanceof \Krucas\Notification\Message) {
            $m = $message;
            $this->addInstance($m, $type, $flash, $format);
        } elseif ($message instanceof Closure) {
            $m = new Message($type, null, $flash, $format);
            call_user_func_array($message, [$m]);
            $this->addInstance($m, $type, $flash, $format);
        } else {
            $m = new Message($type, $message, $flash, $this->checkFormat($format, $type));
        }

        if (!$m->isFlash()) {
            $this->notifications->add($m);
            $this->fireEvent('added', $m);
        } else {
            $this->fireEvent('flash', $m);
        }

        return $this;
    }

    /**
     * Add message by instance.
     *
     * @param \Krucas\Notification\Message $message
     * @param string $type
     * @param bool $flash
     * @param null $format
     */
    protected function addInstance(Message $message, $type, $flash = true, $format = null)
    {
        $message->setType($type);
        if ($message->isFlash() != $flash) {
            $message->setFlash($flash);
        }
        if (is_null($message->getFormat())) {
            $message->setFormat($this->getFormat($type));
        }
        if (!is_null($format)) {
            $message->setFormat($this->checkFormat($format, $type));
        }
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
                $collection->add($message);
            }
        }

        return $collection;
    }

    /**
     * Clears message for a given type.
     *
     * @param null $type
     * @return \Krucas\Notification\NotificationsBag
     */
    public function clear($type = null)
    {
        if (is_null($type)) {
            $this->notifications = new Collection();
        } else {
            $notifications = new Collection();

            foreach ($this->notifications as $message) {
                if ($message->getType() != $type) {
                    $notifications->add($message);
                }
            }

            $this->notifications = $notifications;
        }

        return $this;
    }

    /**
     * Clears all messages.
     * Alias for clear(null).
     *
     * @return \Krucas\Notification\NotificationsBag
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
     * Returns generated output of non flash messages.
     *
     * @param null $type
     * @param null $format
     * @return string
     */
    public function show($type = null, $format = null)
    {
        $messages = $this->getMessagesForRender($type);

        $this->groupForRender = array();

        $output = '';

        foreach ($messages as $message) {
            if (!$message->isFlash()) {
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
            if (count($this->groupForRender) > 0) {
                $messages = array();

                foreach ($this->groupForRender as $typeToRender) {
                    $messages = array_merge($messages, $this->get($typeToRender)->all());
                }

                return new Collection($messages);
            }

            return $this->all();
        }
        return $this->get($type);
    }

    /**
     * Return array with groups list for rendering.
     *
     * @return array
     */
    public function getGroupingForRender()
    {
        return $this->groupForRender;
    }

    /**
     * Set order to render types.
     * Call this method: group('success', 'info', ...)
     *
     * @return \Krucas\Notification\NotificationsBag
     */
    public function group()
    {
        if (func_num_args() > 0) {
            $types = func_get_args();
            $this->groupForRender = array();
            foreach ($types as $type) {
                $this->addToGrouping($type);
            }
        }

        return $this;
    }

    /**
     * Adds type for rendering.
     *
     * @param $type
     * @return \Krucas\Notification\NotificationsBag
     */
    public function addToGrouping($type)
    {
        if (!$this->typeIsAvailable($type)) {
            return $this;
        }

        if (!in_array($type, $this->groupForRender)) {
            $this->groupForRender[] = $type;
        }

        return $this;
    }

    /**
     * Removes type from rendering.
     *
     * @param $type
     * @return \Krucas\Notification\NotificationsBag
     */
    public function removeFromGrouping($type)
    {
        foreach ($this->groupForRender as $key => $typeToRender) {
            if ($type == $typeToRender) {
                unset($this->groupForRender[$key]);
            }
        }

        $this->groupForRender = array_values($this->groupForRender);

        return $this;
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
     * Convert the Bag to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->notifications;
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
     * Check if a message is set for given type.
     *
     * @param $type
     * @return bool
     */
    public function has($type = null)
    {
        if ($this->count() <= 0) {
            return false;
        }

        if (is_null($type)) {
            return true;
        }

        if (!$this->typeIsAvailable($type)) {
            return false;
        }

        foreach ($this->notifications as $key => $message) {
            if ($message->getType() == $type) {
                return true;
            }
        }

        return false;
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
        if (!isset($this->notification)) {
            return true;
        }

        return $this->getNotification()->fire($event, $this, $message);
    }

    /**
     * Set notification instance.
     *
     * @param \Krucas\Notification\Notification $notification
     * @return void
     */
    public function setNotification(Notification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Get notification instance.
     *
     * @return \Krucas\Notification\Notification
     */
    public function getNotification()
    {
        return $this->notification;
    }

    /**
     * Unset notification instance.
     *
     * @return void
     */
    public function unsetNotification()
    {
        $this->notification = null;
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
