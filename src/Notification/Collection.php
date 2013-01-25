<?php namespace Notification;

use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\RenderableInterface;
use Countable;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use Notification\Message;
use Session;

class Collection implements ArrayAccess, ArrayableInterface, Countable, IteratorAggregate, JsonableInterface, RenderableInterface
{
    /**
     * Messages added to collection.
     *
     * @var array
     */
    protected $messages = array();

    /**
     * Create a new Message collection.
     *
     * @param array $messages
     */
    public function __construct(array $messages = array())
    {
        $this->messages = $messages;
    }

    /**
     * Add message to collection.
     *
     * @param Message $message
     * @return Collection
     */
    public function add(Message $message)
    {
        $this->messages[] = $message;

        return $this;
    }

    /**
     * Adds message to collection only if it is unique.
     *
     * @param Message $message
     * @return Collection
     */
    public function addUnique(Message $message)
    {
        if(!$this->contains($message))
        {
            return $this->add($message);
        }

        return $this;
    }

    /**
     * Determines if given message is already in collection.
     *
     * @param Message $message
     * @return bool
     */
    public function contains(Message $message)
    {
        return in_array($message, $this->messages);
    }

    /**
     * Get the first item of the collection.
     *
     * @return Message
     */
    public function first()
    {
        return $this->count() > 0 ? $this->offsetGet(0) : new Message();
    }

    /**
     * Get all of the items in collection.
     *
     * @return array
     */
    public function all()
    {
        return $this->messages;
    }

    /**
     * Determine if collection is empty or not.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->messages);
    }

    /**
     * Get the evaluated contents of the object.
     *
     * @return string
     */
    public function render()
    {
        $output = '';

        foreach($this->messages as $message)
        {
            $output .= $message->render();
        }

        return $output;
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
     * Determine if an item exists at an offset.
     *
     * @param mixed $offset
     * @return bool|void
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->messages);
    }

    /**
     * Get an item at a given offset.
     *
     * @param mixed $offset
     * @return mixed|void
     */
    public function offsetGet($offset)
    {
        return $this->messages[$offset];
    }

    /**
     * Set the item at a given offset.
     *
     * @param mixed $offset
     * @param mixed $value
     * @return Collection|void
     */
    public function offsetSet($offset, $value)
    {
        $this->messages[$offset] = $value;

        return $this;
    }

    /**
     * Unset the item at a given offset.
     *
     * @param mixed $offset
     * @return Collection|void
     */
    public function offsetUnset($offset)
    {
        unset($this->messages[$offset]);

        return $this;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_map(function(Message $message)
        {
            return $message->toArray();
        }, $this->messages);
    }

    /**
     * Count the number of messages in collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->messages);
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator|\Traversable
     */
    public function getIterator()
    {
        return new ArrayIterator($this->messages);
    }

    /**
     * Convert the collection to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }
}