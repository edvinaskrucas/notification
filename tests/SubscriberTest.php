<?php

use Mockery as m;

require_once 'Mocks/SubscriberMock.php';

class SubscriberTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testIsConstructed()
    {
        $subscriber = $this->getSubscriber();
        $this->assertInstanceOf('Illuminate\Session\SessionManager', $subscriber->getSession());
        $this->assertInstanceOf('Illuminate\Config\Repository', $subscriber->getConfig());
    }

    public function testSubscribe()
    {
        $subscriber = $this->getSubscriber();
        $events = m::mock('Illuminate\Events\Dispatcher');
        $events->shouldReceive('listen')->once()->with('notification.flash: *', 'Krucas\Notification\Subscriber@onFlash');
        $events->shouldReceive('listen')->once()->with('notification.booted', 'Krucas\Notification\Subscriber@onBoot');
        $this->assertNull($subscriber->subscribe($events));
    }

    public function testFlashContainerNames()
    {
        $subscriber = $this->getSubscriber();
        $subscriber->getSession()->shouldReceive('flash')->once()->with('notifications_containers', array('test'));
        $subscriber->getConfig()->shouldReceive('get')->once()->with('notification::session_prefix')->andReturn('notifications_');

        $notification = $this->getNotification();
        $notificationsBag = $this->getNotificationsBag();

        $notification->shouldReceive('getContainers')->once()->andReturn(array($notificationsBag));
        $notificationsBag->shouldReceive('getName')->once()->andReturn('test');

        $this->assertNull($subscriber->flashContainerNames($notification));
    }

    public function testGenerateMessageKey()
    {
        $subscriber = $this->getSubscriber();
        $message = $this->getMessage();
        $message->shouldReceive('toJson')->andReturn('test');
        $this->assertNotNull($subscriber->generateMessageKey($message));
    }

    public function testGenerateDifferentKeysForDifferentMessages()
    {
        $subscriber = $this->getSubscriber();
        $message1 = $this->getMessage();
        $message2 = $this->getMessage();
        $message1->shouldReceive('toJson')->andReturn('test1');
        $message2->shouldReceive('toJson')->andReturn('test2');
        $this->assertNotSame($subscriber->generateMessageKey($message1), $subscriber->generateMessageKey($message2));
    }

    public function testGenerateDifferentKeysForSameMessages()
    {
        $subscriber = $this->getSubscriber();
        $message = $this->getMessage();
        $message->shouldReceive('toJson')->andReturn('test');
        $this->assertNotSame($subscriber->generateMessageKey($message), $subscriber->generateMessageKey($message));
    }

    public function testOnFlash()
    {
        $session = $this->getSessionManager();
        $config = $this->getConfigRepository();
        $subscriber = m::mock('SubscriberMock[flashContainerNames,generateMessageKey,getSession,getConfig]');

        $subscriber->shouldReceive('getSession')->andReturn($session);
        $subscriber->shouldReceive('getConfig')->andReturn($config);

        $notification = $this->getNotification();
        $notificationsBag = $this->getNotificationsBag();
        $message = $this->getMessage();

        $subscriber->shouldReceive('flashContainerNames')->once()->with($notification);
        $config->shouldReceive('get')->once()->with('notification::session_prefix')->andReturn('notifications_');
        $notificationsBag->shouldReceive('getName')->once()->andReturn('test');
        $subscriber->shouldReceive('generateMessageKey')->once()->with($message)->andReturn('test_key');
        $message->shouldReceive('toJson')->once()->andReturn('test_message');
        $session->shouldReceive('flash')->once()->with('notifications_test_test_key', 'test_message');

        $this->assertTrue($subscriber->onFlash($notification, $notificationsBag, $message));
    }

    public function testOnBoot()
    {
        $subscriber = $this->getSubscriber();
        $subscriber->getConfig()->shouldReceive('get')->once()->with('notification::session_prefix')->andReturn('notifications_');
        $subscriber->getSession()->shouldReceive('get')->once()->with('notifications_containers', array())->andReturn(array('test'));
        $flasedMessages = array(
            'notifications_test_1' => '{"message":"test message","format":":type: :message","type":"info","flashable":false,"alias":null,"position":null}',
            'notifications_test_2' => '{"message":"test message","format":":type: :message","type":"error","flashable":false,"alias":null,"position":null}',
        );
        $subscriber->getSession()->shouldReceive('all')->once()->andReturn($flasedMessages);

        $notificationsBag = $this->getNotificationsBag();
        $notificationsBag->shouldReceive('add')->once()->with('info', m::type('Krucas\Notification\Message'), false);
        $notificationsBag->shouldReceive('add')->once()->with('error', m::type('Krucas\Notification\Message'), false);

        $notification = $this->getNotification();
        $notification->shouldReceive('container')->twice()->with('test')->andReturn($notificationsBag);

        $this->assertTrue($subscriber->onBoot($notification));
    }

    protected function getSubscriber()
    {
        $subscriber = new SubscriberMock($this->getSessionManager(), $this->getConfigRepository());
        return $subscriber;
    }

    protected function getSessionManager()
    {
        return m::mock('Illuminate\Session\SessionManager');
    }

    protected function getConfigRepository()
    {
        return m::mock('Illuminate\Config\Repository');
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
