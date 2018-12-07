<?php namespace Krucas\Notification;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class Message implements Renderable, Jsonable, Arrayable
{
    /**
     * Notification message.
     *
     * @var string|null
     */
    protected $message = null;

    /**
     * Notification message format.
     * Replacements:
     * :message - notification message.
     * :type - type of message (error, success, warning, info).
     *
     * @var string|null
     */
    protected $format = null;

    /**
     * Notification type (error, success, warning, info).
     *
     * @var string|null
     */
    protected $type = null;

    /**
     * Is notification flashable?
     * If flash, then it will be displayed on next request.
     * If no, it will be displayed in same request.
     *
     * @var bool
     */
    protected $flash = true;

    /**
     * Message position.
     *
     * @var int|null
     */
    protected $position = null;

    /**
     * Construct default message object.
     *
     * @param null $type
     * @param null $message
     * @param bool $flash
     * @param null $format
     * @param null $position
     */
    public function __construct($type = null, $message = null, $flash = true, $format = null, $position = null)
    {
        $this->setType($type);
        $this->setMessage($message);
        $this->setFlash($flash);
        $this->setFormat($format);
        $this->setPosition($position);
    }

    /**
     * Returns message value.
     *
     * @return null|string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Sets message value, and returns message object.
     *
     * @param $message
     * @return \Krucas\Notification\Message
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Returns if message is flashable.
     *
     * @return bool
     */
    public function isFlash()
    {
        return $this->flash;
    }

    /**
     * Sets flash value, and returns message object.
     *
     * @param $flash
     * @return \Krucas\Notification\Message
     */
    public function setFlash($flash)
    {
        $this->flash = $flash;

        return $this;
    }

    /**
     * Returns message format.
     *
     * @return null|string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Sets message format, and returns message object.
     *
     * @param $format
     * @return \Krucas\Notification\Message
     */
    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Returns message type.
     *
     * @return null|string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets message type, and returns message object.
     *
     * @param $type
     * @return \Krucas\Notification\Message
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Returns message position.
     *
     * @return int|null
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Sets message position.
     *
     * @param $position
     * @return \Krucas\Notification\Message
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Set message.
     * Shortcut for `setMessage()`
     *
     * @param $message
     * @return \Krucas\Notification\Message
     */
    public function message($message)
    {
        $this->setMessage($message);

        return $this;
    }

    /**
     * Set format.
     * Shortcut for `setFormat()`
     *
     * @param $format
     * @return \Krucas\Notification\Message
     */
    public function format($format)
    {
        $this->setFormat($format);

        return $this;
    }

    /**
     * Set message to be instant.
     * Shortcut for `setFlash()`
     *
     * @return \Krucas\Notification\Message
     */
    public function instant()
    {
        $this->setFlash(false);

        return $this;
    }

    /**
     * Set message to be flashable.
     * Shortcut for `setFlash()`
     *
     * @return \Krucas\Notification\Message
     */
    public function flash()
    {
        $this->setFlash(true);

        return $this;
    }

    /**
     * Set message position.
     * Shortcut for `setPosition()`
     *
     * @param $position
     * @return \Krucas\Notification\Message
     */
    public function position($position)
    {
        $this->setPosition($position);

        return $this;
    }

    /**
     * Get the evaluated contents of the object.
     *
     * @return string
     */
    public function render()
    {
        if (is_null($this->getMessage())) {
            return '';
        }
        return str_replace([':message', ':type'], [$this->getMessage(), $this->getType()], $this->getFormat());
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return get_object_vars($this);
    }

    /**
     * Evaluates object as string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }
}
