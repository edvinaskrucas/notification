<?php namespace Krucas\Notification;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Collection as BaseCollection;

class Collection extends BaseCollection implements Renderable
{
    /**
     * Queued messages.
     *
     * @var \SplPriorityQueue
     */
    protected $queue;

    /**
     * Create new collection of messages.
     *
     * @param array $items
     */
    public function __construct($items = [])
    {
        $this->queue = new \SplPriorityQueue();

        $items = is_array($items) ? $items : $this->getArrayableItems($items);

        foreach ($items as $item) {
            $this->add($item);
        }
    }

    /**
     * Add message to collection.
     *
     * @param Message $item
     * @return \Krucas\Notification\Collection
     */
    public function add($item)
    {
        if (is_array($item)) {
            $position = $item['position'];
        } else {
            $position = $item->getPosition();
        }

        $this->queue->insert($item, is_null($position) ? null : -$position);

        $this->copyQueue(clone $this->queue);

        return $this;
    }

    /**
     * Copy queue items.
     *
     * @param \SplPriorityQueue $queue
     * @return void
     */
    protected function copyQueue(\SplPriorityQueue $queue)
    {
        $this->items = [];

        foreach ($queue as $item) {
            $this->items[] = $item;
        }
    }

    /**
     * Get the evaluated contents of the object.
     *
     * @return string
     */
    public function render()
    {
        $output = '';

        foreach ($this->items as $message) {
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
