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

    public function testIndexOf()
    {
        $collection = new \Krucas\Notification\Collection();

        $m1 = new \Krucas\Notification\Message('error', 'm');
        $m2 = new \Krucas\Notification\Message('info', 'm');
        $m3 = new \Krucas\Notification\Message('error', 'm2');
        $m4 = new \Krucas\Notification\Message('error', 'm');

        $collection->addUnique($m1)->addUnique($m2)->addUnique($m3);

        $this->assertEquals(0, $collection->indexOf($m4));
    }

    public function testSetAtPosition()
    {
        $collection = new \Krucas\Notification\Collection();

        $collection
            ->addUnique(new \Krucas\Notification\Message('info', 'i'))
            ->addUnique(new \Krucas\Notification\Message('info', 'i2'))
            ->addUnique(new \Krucas\Notification\Message('info', 'i3'))
            ->setAtPosition(2, new \Krucas\Notification\Message('info', 'info'));

        $this->assertEquals('info', $collection->getAtPosition(2)->getMessage());
        $this->assertEquals('i3', $collection->getAtPosition(3)->getMessage());
    }

    public function testSetAtPosition2()
    {
        $collection = new \Krucas\Notification\Collection();

        $collection
            ->setAtPosition(2, new \Krucas\Notification\Message('info', 'info'))
            ->addUnique(new \Krucas\Notification\Message('info', 'i'));

        $this->assertEquals('info', $collection->getAtPosition(2)->getMessage());
        $this->assertEquals('i', $collection->getAtPosition(3)->getMessage());
    }
}