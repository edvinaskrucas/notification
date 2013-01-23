<?php namespace Notification;

use Illuminate\Support\MessageBag;
use Illuminate\Support\Contracts\MessageProviderInterface;

class Notification implements MessageProviderInterface
{
    protected $app;

    protected $notifications;

    public function __construct($app)
    {
        $this->app = $app;
        $this->notifications = new MessageBag;
    }

    public function getNotifications()
    {
        return $this->notifications;
    }

    public function getMessageBag()
    {
        return $this->getNotifications();
    }
}