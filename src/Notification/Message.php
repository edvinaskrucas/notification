<?php namespace Notification;

use Illuminate\Support\Contracts\RenderableInterface;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\ArrayableInterface;

class Message implements RenderableInterface, JsonableInterface, ArrayableInterface
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
     * If flashable, then it will be displayed on next request.
     * If no, it will be displayed in same request.
     *
     * @var bool
     */
    protected $flashable = true;

    /**
     * Construct default message object.
     *
     * @param null $type
     * @param null $message
     * @param bool $flashable
     * @param null $format
     */
    public function __construct($type = null, $message = null, $flashable = true, $format = null)
    {
        $this->setType($type);
        $this->setMessage($message);
        $this->setFlashable($flashable);
        $this->setFormat($format);
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
     * @return Message
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
    public function isFlashable()
    {
        return $this->flashable;
    }

    /**
     * Sets flashable value, and returns message object.
     *
     * @param $flashable
     * @return Message
     */
    public function setFlashable($flashable)
    {
        $this->flashable = $flashable;

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
     * @return Message
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
     * @return Message
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the evaluated contents of the object.
     *
     * @return string
     */
    public function render()
    {
        return is_null($this->getMessage()) ? '' : str_replace(array(':message', ':type'), array($this->getMessage(), $this->getType()), $this->getFormat());
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
        return $this->toJson();
    }


}