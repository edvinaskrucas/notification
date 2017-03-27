<?php namespace Krucas\Notification\Event;


use Krucas\Notification\Message;
use Krucas\Notification\NotificationsBag;

class FlashEvent
{
    protected $message;

    protected $notificationBag;

    public function __construct(NotificationsBag $notificationBag, Message $message)
    {
        $this->notificationBag = $notificationBag;
        $this->message = $message;
    }

    public function getMessage(){
        return $this->message;
    }

    public  function getNotificationBag(){
        return $this->notificationBag;
    }

}