<?php

use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    public function testCollectionConstructor()
    {
        $collection = $this->getCollection();

        $this->assertInstanceOf('Krucas\Notification\Collection', $collection);
        $this->assertCount(0, $collection);
    }

    public function testAddingMessagesToCollection()
    {
        $collection = $this->getCollection();
        $this->assertCount(0, $collection);

        $collection->add(new \Krucas\Notification\Message());
        $this->assertCount(1, $collection);

        $collection->add(new \Krucas\Notification\Message());
        $this->assertCount(2, $collection);
    }

    public function testContainsMethod()
    {
        $collection = $this->getCollection();

        $collection->add(new \Krucas\Notification\Message());

        $this->assertCount(1, $collection);
        $this->assertTrue($collection->contains(new \Krucas\Notification\Message()));
        $this->assertFalse($collection->contains(new \Krucas\Notification\Message('error')));
    }

    public function testCollectionRender()
    {
        $collection = $this->getCollection();

        $collection->add(new \Krucas\Notification\Message('error', 'error message', false, ':type: :message'));
        $collection->add(new \Krucas\Notification\Message('warning', 'w', false, ':message'));

        $this->assertCount(2, $collection);
        $this->assertEquals('error: error messagew', $collection->render());
    }

    public function testCollectionToString()
    {
        $collection = $this->getCollection();

        $collection->add(new \Krucas\Notification\Message('error', 'error message', false, ':type: :message'));
        $collection->add(new \Krucas\Notification\Message('warning', 'w', false, ':message'));

        $this->assertCount(2, $collection);
        $this->assertEquals('error: error messagew', (string)$collection);
    }

    public function testSetAtPosition()
    {
        $collection = $this->getCollection();

        $message1 = new \Krucas\Notification\Message();
        $message1->setPosition(2);

        $message2 = new \Krucas\Notification\Message();
        $message2->setPosition(1);

        $collection
            ->add($message1)
            ->add($message2);

        $this->assertEquals($message2, $collection[0]);
        $this->assertEquals($message1, $collection[1]);
    }

    protected function getCollection()
    {
        return new \Krucas\Notification\Collection();
    }
}
