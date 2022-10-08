<?php

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class NotificationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testIsConstructed()
    {
        $notification = $this->getNotification();
        $this->assertEquals('default', $notification->getDefaultContainerName());
        $this->assertCount(0, $notification->getContainers());
    }

    public function testGetDefaultContainerName()
    {
        $notification = $this->getNotification();
        $this->assertEquals('default', $notification->getDefaultContainerName());
    }

    public function testSetContainerTypes()
    {
        $notification = $this->getNotification();
        $this->assertEquals([], $notification->getContainerTypes('default'));

        $notification->setContainerTypes('default', ['success', 'info']);
        $this->assertEquals(['success', 'info'], $notification->getContainerTypes('default'));
    }

    public function testGetTypesContainerForContainer()
    {
        $notification = $this->getNotification();
        $this->assertEquals([], $notification->getContainerTypes('default'));
        $this->assertEquals([], $notification->getContainerTypes('test'));

        $notification->setContainerTypes('default', ['success', 'info']);
        $this->assertEquals(['success', 'info'], $notification->getContainerTypes('default'));
        $this->assertEquals([], $notification->getContainerTypes('test'));
    }

    public function testSetContainerFormat()
    {
        $notification = $this->getNotification();
        $this->assertNull($notification->getContainerFormat('default'));

        $notification->setContainerFormat('default', ':message');
        $this->assertEquals(':message', $notification->getContainerFormat('default'));
    }

    public function testGetContainerFormatForContainer()
    {
        $notification = $this->getNotification();
        $this->assertNull($notification->getContainerFormat('default'));
        $this->assertNull($notification->getContainerFormat('test'));

        $notification->setContainerFormat('default', ':message');
        $this->assertEquals(':message', $notification->getContainerFormat('default'));
        $this->assertNull($notification->getContainerFormat('test'));
    }

    public function testSetContainerContainerFormats()
    {
        $notification = $this->getNotification();
        $this->assertEquals([], $notification->getContainerFormats('default'));

        $notification->setContainerFormats('default', ['info' => ':message']);
        $this->assertEquals(['info' => ':message'], $notification->getContainerFormats('default'));
    }

    public function testGetContainerFormatsForContainer()
    {
        $notification = $this->getNotification();
        $this->assertEquals([], $notification->getContainerFormats('default'));
        $this->assertEquals([], $notification->getContainerFormats('test'));

        $notification->setContainerFormats('default', ['info' => ':message']);
        $this->assertEquals(['info' => ':message'], $notification->getContainerFormats('default'));
        $this->assertEquals([], $notification->getContainerFormats('test'));
    }

    public function testAddContainer()
    {
        $notification = $this->getNotification();
        $this->assertCount(0, $notification->getContainers());

        $notification->addContainer('test');
        $this->assertCount(1, $notification->getContainers());
    }

    public function testAddExistingContainer()
    {
        $notification = $this->getNotification();
        $this->assertCount(0, $notification->getContainers());

        $notification->addContainer('test');
        $this->assertCount(1, $notification->getContainers());

        $notification->addContainer('test');
        $this->assertCount(1, $notification->getContainers());
    }

    public function testSetNotificationInstanceOnNewContainer()
    {
        $notification = $this->getNotification();
        $this->assertCount(0, $notification->getContainers());

        $notification->addContainer('test');
        $this->assertCount(1, $notification->getContainers());
        $this->assertEquals($notification, $notification->container('test')->getNotification());
    }

    public function testNotificationBagInstanceOnDefaultContainer()
    {
        $notification = $this->getNotification();
        $this->assertInstanceOf('Krucas\Notification\NotificationsBag', $notification->container());
    }

    public function testNotificationBagInstanceOnDefaultContainerUsingMagicMethod()
    {
        $notification = $this->getNotification();
        $this->assertEquals('default', $notification->getName());
    }

    public function testNotificationBagInstanceOnAddedContainer()
    {
        $notification = $this->getNotification();
        $notification->addContainer('test');
        $this->assertInstanceOf('Krucas\Notification\NotificationsBag', $notification->container('test'));
    }

    public function testNotificationBagInstanceOnNonExistingContainer()
    {
        $notification = $this->getNotification();
        $this->assertInstanceOf('Krucas\Notification\NotificationsBag', $notification->container('test'));
    }

    public function testNotificationBagInstanceOnNonExistingContainerWithResolvedParams()
    {
        $notification = $this->getNotification();
        $notification->setContainerTypes('test', ['success']);
        $notification->setContainerFormat('test', ':message');
        $notification->setContainerFormats('test', ['success' => ':message - OK']);
        $container = $notification->container('test');
        $this->assertInstanceOf('Krucas\Notification\NotificationsBag', $container);
        $this->assertEquals(['success'], $container->getTypes());
        $this->assertEquals(':message', $container->getDefaultFormat());
        $this->assertEquals(':message - OK', $container->getFormat('success'));
    }

    public function testCallbackOnNotificationBag()
    {
        $notification = $this->getNotification();
        $this->assertEquals([], $notification->container('default')->getTypes());
        $notification->container('default', function ($container) {
            $container->addType('info');
        });
        $this->assertEquals(['info'], $notification->container('default')->getTypes());
    }

    public function testCreateNewEmptyMessageInstance()
    {
        $notification = $this->getNotification();
        $message = $notification->message();
        $this->assertInstanceOf('Krucas\Notification\Message', $message);
        $this->assertNull($message->getMessage());
    }

    public function testCreateNewMessageInstanceWithMessage()
    {
        $notification = $this->getNotification();
        $message = $notification->message('test');
        $this->assertInstanceOf('Krucas\Notification\Message', $message);
        $this->assertEquals('test', $message->getMessage());
    }

    public function testSetEventDispatcher()
    {
        $notification = $this->getNotification();
        $this->assertNull($notification->getEventDispatcher());
        $notification->setEventDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));
        $this->assertEquals($events, $notification->getEventDispatcher());
        $notification->unsetEventDispatcher();
        $this->assertNull($notification->getEventDispatcher());
    }

    public function testAddFlashMessageProcess()
    {
        $notification = $this->getNotification();
        $notification->setContainerTypes('default', ['info']);
        $notification->setEventDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));

        $message = $this->getMessage();
        $message->shouldReceive('setType')->with('info')->andReturn($message);
        $message->shouldReceive('isFlash')->andReturn(true);
        $message->shouldReceive('getPosition')->andReturn(null);
        $message->shouldReceive('getFormat')->andReturn(':message');

        $events->shouldReceive('dispatch')->once()->with('notification.flash: default', [$notification, $notification->container(), $message]);
        $notification->container()->info($message);
    }

    public function testAddInstantMessageProcess()
    {
        $notification = $this->getNotification();
        $notification->setContainerTypes('default', ['info']);
        $notification->setEventDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));

        $message = $this->getMessage();
        $message->shouldReceive('setType')->with('info')->andReturn($message);
        $message->shouldReceive('isFlash')->andReturn(false);
        $message->shouldReceive('getPosition')->andReturn(null);
        $message->shouldReceive('getFormat')->andReturn(':message');

        $events->shouldReceive('dispatch')->once()->with('notification.added: default', [$notification, $notification->container(), $message]);
        $notification->container()->infoInstant($message);
    }

    public function testCreateNewContainerWithDefaults()
    {
        $notification = new \Krucas\Notification\Notification(
            'default',
            ['info', 'warning', 'success', 'error'],
            [],
            ':type :message',
            [],
            [],
            []
        );

        $container = $notification->container('test');

        $this->assertEquals('test', $container->getName());
        $this->assertEquals(':type :message', $container->getDefaultFormat());
        $this->assertEquals(['info', 'warning', 'success', 'error'], $container->getTypes());
    }

    public function testCreateNewContainerFromDefined()
    {
        $notification = new \Krucas\Notification\Notification(
            'default',
            ['info', 'warning', 'success', 'error'],
            [
                'test' => ['info'],
            ],
            ':type :message',
            [
                'test' => ':message',
            ],
            [],
            [
                'test' => [
                    'info' => 'info :message',
                ],
            ]
        );

        $container = $notification->container('test');

        $this->assertEquals('test', $container->getName());
        $this->assertEquals(':message', $container->getDefaultFormat());
        $this->assertEquals(['info'], $container->getTypes());
        $this->assertEquals('info :message', $container->getFormat('info'));
    }

    protected function getNotification()
    {
        return new \Krucas\Notification\Notification('default', [], [], null, [], [], []);
    }

    protected function getMessage()
    {
        $message = m::mock('Krucas\Notification\Message');

        return $message;
    }
}
