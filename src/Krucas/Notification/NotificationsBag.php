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
     * Collection to store all notification messages.
     *
     * @var \Krucas\Notification\Collection|null
     */
    protected $notifications = null;

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
     * Sequence of how messages should be rendered by its type.
     *
     * @var array
     */
    protected $groupForRender = array();

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
        $this->notifications = new Collection();

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

            $this->notifications->addUnique($this->lastMessage);

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
            $lastMessageIndex = $this->notifications->indexOf($this->lastMessage);

            $this->lastMessage->setAlias($alias);

            foreach($this->notifications as $key => $message)
            {
                if($message->getAlias() == $alias)
                {
                    $index = $this->notifications->indexOf($message);

                    if($index !== false)
                    {
                        $this->notifications->offsetUnset($index);
                        $this->notifications->offsetUnset($lastMessageIndex);
                        $this->notifications->setAtPosition(is_null($this->lastPosition) ? $index : $this->lastPosition, $this->lastMessage);
                    }
                }
            }

            if($this->lastMessage->isFlashable())
            {
                $this->flash();
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
            $lastMessageIndex = $this->notifications->indexOf($this->lastMessage);

            $this->lastMessage->setPosition($position);

            $this->notifications->offsetUnset($lastMessageIndex);
            $this->notifications->setAtPosition($position, $this->lastMessage);

            if($this->lastMessage->isFlashable())
            {
                $this->flash();
            }
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
            $this->notifications = new Collection();
        }
        else
        {
            foreach($this->notifications as $key => $message)
            {
                if($message->getType() == $type)
                {
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
     * Renders success messages.
     *
     * @param null $format
     * @return string
     */
    public function showSuccess($format = null)
    {
        return $this->show('success', $format);
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
     * Renders error messages.
     *
     * @param null $format
     * @return string
     */
    public function showError($format = null)
    {
        return $this->show('error', $format);
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
     * Renders info messages.
     *
     * @param null $format
     * @return string
     */
    public function showInfo($format = null)
    {
        return $this->show('info', $format);
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
     * Renders warning messages.
     *
     * @param null $format
     * @return string
     */
    public function showWarning($format = null)
    {
        return $this->show('warning', $format);
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
     * Returns all messages for given type.
     *
     * @param $type
     * @return \Krucas\Notification\Collection
     */
    public function get($type)
    {
        $collection = new Collection();

        foreach($this->notifications as $key => $message)
        {
            if($message->getType() == $type)
            {
                if(!is_null($message->getPosition()))
                {
                    $collection->setAtPosition($key, $message);
                }
                else
                {
                    $collection->addUnique($message);
                }
            }
        }

        return $collection;
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
        $flashed = $this->sessionStore->get($this->configRepository->get('notification::session_prefix').$this->container);

        if($flashed)
        {
            $messages = json_decode($flashed);

            if(is_array($messages))
            {
                foreach($messages as $key => $message)
                {
                    $this->add($message->type, $message->message, false, $message->format);

                    if(isset($message->alias) && !is_null($message->alias))
                    {
                        $this->alias($message->alias);
                    }

                    if(isset($message->position) && !is_null($message->position))
                    {
                        $this->atPosition($message->position);
                    }
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
        $this->sessionStore->flash($this->configRepository->get('notification::session_prefix').$this->container, $this->getFlashable()->toJson());
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
        if(func_num_args() > 0)
        {
            $this->groupForRender = func_get_args();
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
        if(!in_array($type, $this->groupForRender))
        {
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
        foreach($this->groupForRender as $key => $typeToRender)
        {
            if($type == $typeToRender)
            {
                unset($this->groupForRender[$key]);
            }
        }

        $this->groupForRender = array_values($this->groupForRender);

        return $this;
    }

    /**
     * Resolves which messages should be returned for rendering.
     *
     * @param null $type
     * @return \Krucas\Notification\Collection
     */
    protected function getMessagesForRender($type = null)
    {
        if(is_null($type))
        {
            if(count($this->groupForRender) > 0)
            {
                $messages = array();

                foreach($this->groupForRender as $typeToRender)
                {
                    $messages = array_merge($messages, $this->get($typeToRender)->all());
                }

                return new Collection($messages);
            }
            else
            {
                return $this->all();
            }
        }
        else
        {
            return $this->get($type);
        }
    }

    /**
     * Returns generated output of non flashable messages.
     *
     * @param null $type
     * @param null $format
     * @return string
     */
    protected function show($type = null, $format = null)
    {
        $messages = $this->getMessagesForRender($type);

        $this->groupForRender = array();

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
     * Returns messages at given position.
     * Shortcut to all()->getAtPosition().
     *
     * @param $position
     * @return \Krucas\Notification\Message
     */
    public function getAtPosition($position)
    {
        return $this->all()->getAtPosition($position);
    }

    /**
     * Returns message with a given alias or null if not found.
     *
     * @param $alias
     * @return \Krucas\Notification\Message|null
     */
    public function getAliased($alias)
    {
        return $this->all()->getAliased($alias);
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
            'notifications'     => $this->notifications->toArray()
        );

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
        return count($this->notifications);
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
        return (string) $this->notifications;
    }
}