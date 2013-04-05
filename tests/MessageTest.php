<?php

use Mockery as m;

class MessageTest extends PHPUnit_Framework_TestCase
{
    public function testMessageConstructor()
    {
        $message = new \Krucas\Notification\Message('error', 'test message', false, ':type: :message', 'test');

        $this->assertInstanceOf('Krucas\Notification\Message', $message);
        $this->assertEquals('error', $message->getType());
        $this->assertEquals('test message', $message->getMessage());
        $this->assertEquals(':type: :message', $message->getFormat());
        $this->assertEquals('test', $message->getAlias());
        $this->assertFalse($message->isFlashable());
    }

    public function testMethodChaining()
    {
        $message = new \Krucas\Notification\Message();

        $this->assertInstanceOf('Krucas\Notification\Message', $message);
        $this->assertNull($message->getType());
        $this->assertNull($message->getMessage());
        $this->assertNull($message->getFormat());
        $this->assertTrue($message->isFlashable());
        $this->assertNull($message->getAlias());

        $message->setFlashable(false)
            ->setFormat('Test: :message')
            ->setType('warning')
            ->setMessage('test')
            ->setAlias('test');

        $this->assertEquals('warning', $message->getType());
        $this->assertEquals('test', $message->getMessage());
        $this->assertEquals('Test: :message', $message->getFormat());
        $this->assertFalse($message->isFlashable());
        $this->assertEquals('test', $message->getAlias());
    }

    public function testToStringMethod()
    {
        $message = new \Krucas\Notification\Message('error', 'test message', false, ':type: :message');

        $this->assertEquals('error: test message', (string)$message);
    }

    public function testMessageRendering()
    {
        $message = new \Krucas\Notification\Message('error', 'test message', false, ':type: :message');

        $this->assertEquals('error: test message', $message->render());
    }

    public function testMessageToArray()
    {
        $message = new \Krucas\Notification\Message('error', 'test message', false, ':type: :message');

        $this->assertEquals(array(
            'message' => 'test message',
            'format' => ':type: :message',
            'type' => 'error',
            'flashable' => false,
            'alias' => null
        ), $message->toArray());
    }

    public function testMessageToJson()
    {
        $message = new \Krucas\Notification\Message('error', 'test message', false, ':type: :message');

        $this->assertEquals('{"message":"test message","format":":type: :message","type":"error","flashable":false,"alias":null}', $message->toJson());
    }
}