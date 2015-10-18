<?php

use Mockery as m;

class SubscriberTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

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

        $notificationsBag->shouldReceive('getName')->once()->andReturn('test');
        $subscriber->getSession()->shouldReceive('push')->once()->with('notifications.test', $message);

        $this->assertTrue($subscriber->onFlash($notification, $notificationsBag, $message));
    }

    protected function getSubscriber()
    {
        $subscriber = new \Krucas\Notification\Subscriber($this->getSessionStore(), 'notifications');
        return $subscriber;
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
