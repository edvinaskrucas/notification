<?php

use Mockery as m;

class CollectionTest extends PHPUnit_Framework_TestCase
{
    public function testCollectionConstructor()
    {
        $collection = new \Krucas\Notification\Collection();

        $this->assertInstanceOf('Krucas\Notification\Collection', $collection);
        $this->assertCount(0, $collection);
    }

    public function testAddingMessagesToCollection()
    {
        $collection = new \Krucas\Notification\Collection();

        $collection->add(new \Krucas\Notification\Message());
        $collection->add(new \Krucas\Notification\Message());

        $this->assertCount(2, $collection);
    }

    public function testContainsMethod()
    {
        $collection = new \Krucas\Notification\Collection();

        $collection->add(new \Krucas\Notification\Message());

        $this->assertTrue($collection->contains(new \Krucas\Notification\Message()));
        $this->assertFalse($collection->contains(new \Krucas\Notification\Message('error')));
    }

    public function testAddUniqueMessages()
    {
        $collection = new \Krucas\Notification\Collection();

        $collection->addUnique(new \Krucas\Notification\Message());
        $collection->addUnique(new \Krucas\Notification\Message());

        $this->assertCount(1, $collection);
    }

    public function testCollectionRender()
    {
        $collection = new \Krucas\Notification\Collection();

        $collection->add(new \Krucas\Notification\Message('error', 'error message', false, ':type: :message'));
        $collection->add(new \Krucas\Notification\Message('warning', 'w', false, ':message'));

        $this->assertEquals('error: error messagew', $collection->render());
    }

    public function testCollectionToString()
    {
        $collection = new \Krucas\Notification\Collection();

        $collection->add(new \Krucas\Notification\Message('error', 'error message', false, ':type: :message'));
        $collection->add(new \Krucas\Notification\Message('warning', 'w', false, ':message'));

        $this->assertEquals('error: error messagew', (string)$collection);
    }
}