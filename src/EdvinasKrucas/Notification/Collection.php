<?php namespace Krucas\Notification;

use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\RenderableInterface;
use Illuminate\Support\Collection as BaseCollection;
use Krucas\Notification\Message;
use Session;

class Collection extends BaseCollection implements RenderableInterface
{
    /**
     * Add message to collection.
     *
     * @param Message $message
     * @return Collection
     */
    public function add(Message $message)
    {
        $this->items[] = $message;

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
        return in_array($message, $this->items);
    }

    /**
     * Get the evaluated contents of the object.
     *
     * @return string
     */
    public function render()
    {
        $output = '';

        foreach($this->items as $message)
        {
            $output .= $message->render();
        }

        return $output;
    }

    /**
     * Convert the collection to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }
}