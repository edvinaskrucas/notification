<?php

use Mockery as m;

class NotificationTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

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

    public function testSetTypes()
    {
        $notification = $this->getNotification();
        $this->assertEquals(array(), $notification->getTypes('default'));

        $notification->setTypes('default', array('success', 'info'));
        $this->assertEquals(array('success', 'info'), $notification->getTypes('default'));
    }

    public function testGetTypeForContainer()
    {
        $notification = $this->getNotification();
        $this->assertEquals(array(), $notification->getTypes('default'));
        $this->assertEquals(array(), $notification->getTypes('test'));

        $notification->setTypes('default', array('success', 'info'));
        $this->assertEquals(array('success', 'info'), $notification->getTypes('default'));
        $this->assertEquals(array(), $notification->getTypes('test'));
    }

    public function testSetFormat()
    {
        $notification = $this->getNotification();
        $this->assertNull($notification->getFormat('default'));

        $notification->setFormat('default', ':message');
        $this->assertEquals(':message', $notification->getFormat('default'));
    }

    public function testGetFormatForContainer()
    {
        $notification = $this->getNotification();
        $this->assertNull($notification->getFormat('default'));
        $this->assertNull($notification->getFormat('test'));

        $notification->setFormat('default', ':message');
        $this->assertEquals(':message', $notification->getFormat('default'));
        $this->assertNull($notification->getFormat('test'));
    }

    public function testSetFormats()
    {
        $notification = $this->getNotification();
        $this->assertEquals(array(), $notification->getFormats('default'));

        $notification->setFormats('default', array('info' => ':message'));
        $this->assertEquals(array('info' => ':message'), $notification->getFormats('default'));
    }

    public function testGetFormatsForContainer()
    {
        $notification = $this->getNotification();
        $this->assertEquals(array(), $notification->getFormats('default'));
        $this->assertEquals(array(), $notification->getFormats('test'));

        $notification->setFormats('default', array('info' => ':message'));
        $this->assertEquals(array('info' => ':message'), $notification->getFormats('default'));
        $this->assertEquals(array(), $notification->getFormats('test'));
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
        $notification->setTypes('test', array('success'));
        $notification->setFormat('test', ':message');
        $notification->setFormats('test', array('success' => ':message - OK'));
        $container = $notification->container('test');
        $this->assertInstanceOf('Krucas\Notification\NotificationsBag', $container);
        $this->assertEquals(array('success'), $container->getTypes());
        $this->assertEquals(':message', $container->getDefaultFormat());
        $this->assertEquals(':message - OK', $container->getFormat('success'));
    }

    public function testCallbackOnNotificationBag()
    {
        $notification = $this->getNotification();
        $this->assertEquals(array(), $notification->container('default')->getTypes());
        $notification->container('default', function ($container) {
            $container->addType('info');
        });
        $this->assertEquals(array('info'), $notification->container('default')->getTypes());
    }

    protected function getNotification()
    {
        return new \Krucas\Notification\Notification('default');
    }
}
