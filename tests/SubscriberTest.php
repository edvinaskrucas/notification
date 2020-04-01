<?php

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class SubscriberTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    

    public function testIsConstructed()
    {
        $subscriber = $this->getSubscriber();
        $this->assertInstanceOf('Illuminate\Session\Store', $subscriber->getSession());
        $this->assertEquals('notifications', $subscriber->getKey());
    }

    public function testSubscribe()
    {
        $subscriber = $this->getSubscriber();
        $events = m::mock('Illuminate\Contracts\Events\Dispatcher');
        $events->shouldReceive('listen')->once()->with('notification.flash: *', 'Krucas\Notification\Subscriber@onFlash');
        $this->assertNull($subscriber->subscribe($events));
    }

    public function testOnFlash()
    {
        $subscriber = $this->getSubscriber();

        $notification = $this->getNotification();
        $notificationsBag = $this->getNotificationsBag();
        $message = $this->getMessage();

        $notificationsBag->shouldReceive('getName')->once()->andReturn('my_test_bag');
        $subscriber->getSession()->shouldReceive('push')->once()->with('notifications.my_test_bag', $message);

        // As of Laravel 5.4 wildcard event subscribers now receive the event
        // name as their first argument and the array of event data as their
        // second argument
        $this->assertTrue($subscriber->onFlash('notification.flash: default', [$notification, $notificationsBag, $message]));
    }

    public function testOnFlashValidation()
    {
        $subscriber = $this->getSubscriber();

        try {
            $subscriber->onFlash('notification.flash: default', [new \stdClass]);
            $this->fail('Failed to throw expected InvalidArgumentException when passing incorrect number of event data array elements to Krucas\Notification\Subscriber::onFlash');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Krucas\Notification\Subscriber::onFlash expects 3 elements in data array, 1 given.', $e->getMessage());
        }

        try {
            $subscriber->onFlash('notification.flash: default', [new \stdClass, new \stdClass, new \stdClass]);
            $this->fail('Failed to throw expected InvalidArgumentException when passing incorrect type of event data array elements to Krucas\Notification\Subscriber::onFlash');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Krucas\Notification\Subscriber::onFlash expects a data array containing [Krucas\Notification\Notification, Krucas\Notification\NotificationsBag, Krucas\Notification\Message], actually given [stdClass, stdClass, stdClass]', $e->getMessage());
        }
    }

    protected function getSubscriber()
    {
        return new \Krucas\Notification\Subscriber($this->getSessionStore(), 'notifications');
    }

    protected function getSessionStore()
    {
        return m::mock('Illuminate\Session\Store');
    }

    protected function getNotification()
    {
        return m::mock('Krucas\Notification\Notification');
    }

    protected function getNotificationsBag()
    {
        return m::mock('Krucas\Notification\NotificationsBag');
    }

    protected function getMessage()
    {
        return m::mock('Krucas\Notification\Message');
    }
}
