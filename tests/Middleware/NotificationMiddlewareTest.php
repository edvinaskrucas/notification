<?php

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class NotificationMiddlewareTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    

    public function testOnBoot()
    {
        $messages = [new \Krucas\Notification\Message('error'), new \Krucas\Notification\Message('info')];

        $notificationsBag = $this->getNotificationsBag();
        $notificationsBag->shouldReceive('add')->once()->with('error', $messages[0], false);
        $notificationsBag->shouldReceive('add')->once()->with('info', $messages[1], false);

        $session = $this->getSessionStore();
        $notification = $this->getNotification();
        $notification->shouldReceive('container')->twice()->with('test')->andReturn($notificationsBag);
        $prefix = 'notifications';

        $middleware = new \Krucas\Notification\Middleware\NotificationMiddleware($session, $notification, $prefix);
        $session->shouldReceive('get')->once()->with('notifications', array())->andReturn(array('test' => $messages));
        $session->shouldReceive('forget')->once()->with('notifications');

        $middleware->handle(m::mock('Illuminate\Http\Request'), function() {});
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
}
