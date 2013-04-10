<?php

use Mockery as m;

class NotificationTest extends PHPUnit_Framework_TestCase
{
    private $n;

    public function tearDown()
    {
        m::close();
    }

    protected function setUp()
    {
        $session = m::mock('Illuminate\Session\Store');
        $config = m::mock('Illuminate\Config\Repository');

        $config->shouldReceive('get')->with('notification::default_format')->andReturn('<div class="alert alert-:type">:message</div>');
        $config->shouldReceive('get')->with('notification::default_formats')->andReturn(array('__' => array()));
        $config->shouldReceive('get')->with('notification::session_prefix')->andReturn('notifications_');
        $config->shouldReceive('get')->with('notification::default_container')->andReturn('default');

        $session->shouldReceive('get')->andReturn(false);

        $this->n = new \Krucas\Notification\Notification($config, $session);
    }

    public function testConstructor()
    {
        $this->assertInstanceOf('Krucas\Notification\Notification', $this->n);
    }

    public function testContainerMethodIfReturnsNotificationBag()
    {
        $this->assertInstanceOf('Krucas\Notification\NotificationsBag', $this->n->container('test'));
    }

    public function testIfAddingSuccessMessageReturnsNotificationsBag()
    {
        $this->n->getConfigRepository()->shouldReceive('get')->with('notification::default_container')->andReturn('test');
        $this->n->getSessionStore()->shouldReceive('flash')->once();

        $this->assertInstanceOf('Krucas\Notification\NotificationsBag', $this->n->success('test'));
    }

    public function testIfAddingWarningMessageReturnsNotificationsBag()
    {
        $this->n->getConfigRepository()->shouldReceive('get')->with('notification::default_container')->andReturn('test');
        $this->n->getSessionStore()->shouldReceive('flash')->once();

        $this->assertInstanceOf('Krucas\Notification\NotificationsBag', $this->n->warning('test'));
    }

    public function testIfAddingErrorMessageReturnsNotificationsBag()
    {
        $this->n->getConfigRepository()->shouldReceive('get')->with('notification::default_container')->andReturn('test');
        $this->n->getSessionStore()->shouldReceive('flash')->once();

        $this->assertInstanceOf('Krucas\Notification\NotificationsBag', $this->n->error('test'));
    }

    public function testIfAddingInfoMessageReturnsNotificationsBag()
    {
        $this->n->getConfigRepository()->shouldReceive('get')->with('notification::default_container')->andReturn('test');
        $this->n->getSessionStore()->shouldReceive('flash')->once();

        $this->assertInstanceOf('Krucas\Notification\NotificationsBag', $this->n->info('test'));
    }

    public function testAddingAMessageToDifferentContainers()
    {
        $this->n->getConfigRepository()->shouldReceive('get')->with('notification::default_container')->andReturn('test');
        $this->n->getSessionStore()->shouldReceive('flash');

        $this->assertInstanceOf('Krucas\Notification\NotificationsBag', $this->n->info('test'));
        $this->assertCount(1, $this->n->container());

        $this->assertInstanceOf('Krucas\Notification\NotificationsBag', $this->n->container('other')->info('test'));
        $this->assertCount(1, $this->n->container('other'));
        $this->assertCount(1, $this->n->container());
    }

    public function testIfAddingInstantSuccessMessageReturnsNotificationsBag()
    {
        $this->n->getConfigRepository()->shouldReceive('get')->with('notification::default_container')->andReturn('test');

        $this->assertInstanceOf('Krucas\Notification\NotificationsBag', $this->n->successInstant('test'));
    }

    public function testIfAddingInstantWarningMessageReturnsNotificationsBag()
    {
        $this->n->getConfigRepository()->shouldReceive('get')->with('notification::default_container')->andReturn('test');

        $this->assertInstanceOf('Krucas\Notification\NotificationsBag', $this->n->warningInstant('test'));
    }

    public function testIfAddingInstantErrorMessageReturnsNotificationsBag()
    {
        $this->n->getConfigRepository()->shouldReceive('get')->with('notification::default_container')->andReturn('test');

        $this->assertInstanceOf('Krucas\Notification\NotificationsBag', $this->n->errorInstant('test'));
    }

    public function testIfAddingInstantInfoMessageReturnsNotificationsBag()
    {
        $this->n->getConfigRepository()->shouldReceive('get')->with('notification::default_container')->andReturn('test');

        $this->assertInstanceOf('Krucas\Notification\NotificationsBag', $this->n->infoInstant('test'));
    }

    public function testShowSuccessMethod()
    {
        $this->n->getConfigRepository()->shouldReceive('get')->with('notification::default_container')->andReturn('test');
        $this->n->getSessionStore()->shouldReceive('flash');

        $this->n->successInstant('ok', ':message');
        $this->n->successInstant('ok');
        $this->n->success('ok flashed');

        $this->assertEquals('ok<div class="alert alert-success">ok</div>', $this->n->showSuccess());
    }

    public function testShowInfoMethod()
    {
        $this->n->getConfigRepository()->shouldReceive('get')->with('notification::default_container')->andReturn('test');

        $this->n->infoInstant('info');

        $this->assertEquals('<div class="alert alert-info">info</div>', $this->n->showInfo());
    }

    public function testShowErrorMethod()
    {
        $this->n->getConfigRepository()->shouldReceive('get')->with('notification::default_container')->andReturn('test');

        $this->n->errorInstant('error');

        $this->assertEquals('<div class="alert alert-error">error</div>', $this->n->showError());
    }

    public function testShowWarningMethod()
    {
        $this->n->getConfigRepository()->shouldReceive('get')->with('notification::default_container')->andReturn('test');

        $this->n->warningInstant('warning');

        $this->assertEquals('<div class="alert alert-warning">warning</div>', $this->n->showWarning());
    }

    public function testShowAllWithACustomFormat()
    {
        $this->n->getConfigRepository()->shouldReceive('get')->with('notification::default_container')->andReturn('test');
        $this->n->getSessionStore()->shouldReceive('flash');

        $this->n->warningInstant('warning');
        $this->n->errorInstant('error');
        $this->n->infoInstant('info');
        $this->n->successInstant('success');
        $this->n->success('success flash');

        $this->assertEquals('warning error info success ', $this->n->showAll(':message '));
    }

    public function testAddToMoreThanOneContainerAndShowOneOfThem()
    {
        $this->n->getConfigRepository()->shouldReceive('get')->with('notification::default_container')->andReturn('test');
        $this->n->getSessionStore()->shouldReceive('flash');

        $this->n->warningInstant('warning');
        $this->n->errorInstant('error');
        $this->n->infoInstant('info');
        $this->n->successInstant('success');
        $this->n->success('success flash');

        $this->n->container('a')->warningInstant('warning');

        $this->n->container('b')->warning('warning flash');

        $this->assertEquals('<div class="alert alert-warning">warning</div>', $this->n->container('a')->showAll());
        $this->assertEquals('', $this->n->container('b')->showAll());
    }

    public function testAddInstantMessageAndInstantlyShowIt()
    {
        $this->n->getConfigRepository()->shouldReceive('get')->with('notification::default_container')->andReturn('test');

        $this->assertEquals('<div class="alert alert-info">instant</div>', (string) $this->n->container('instant')->infoInstant('instant'));
    }

    public function testAddingMessagesInAClosure()
    {
        $this->n->container('a', function($bag)
        {
            $bag->infoInstant('info');
            $bag->errorInstant('error');
        });

        $this->assertCount(2, $this->n->container('a'));
    }

    public function testClearSuccessMessages()
    {
        $this->n->getConfigRepository()->shouldReceive('get')->with('notification::default_container')->andReturn('test');

        $this->n->successInstant('test');
        $this->assertCount(1, $this->n->container()->get('success'));

        $this->n->clearSuccess();
        $this->assertCount(0, $this->n->container()->get('success'));
    }

    public function testClearInfoMessages()
    {
        $this->n->getConfigRepository()->shouldReceive('get')->with('notification::default_container')->andReturn('test');

        $this->n->infoInstant('test');
        $this->assertCount(1, $this->n->container()->get('info'));

        $this->n->clearInfo();
        $this->assertCount(0, $this->n->container()->get('info'));
    }

    public function testClearWarningMessages()
    {
        $this->n->getConfigRepository()->shouldReceive('get')->with('notification::default_container')->andReturn('test');

        $this->n->warningInstant('test');
        $this->assertCount(1, $this->n->container()->get('warning'));

        $this->n->clearWarning();
        $this->assertCount(0, $this->n->container()->get('warning'));
    }

    public function testClearErrorMessages()
    {
        $this->n->getConfigRepository()->shouldReceive('get')->with('notification::default_container')->andReturn('test');

        $this->n->errorInstant('test');
        $this->assertCount(1, $this->n->container()->get('error'));

        $this->n->clearError();
        $this->assertCount(0, $this->n->container()->get('error'));
    }

    public function testClearAllMessages()
    {
        $this->n->getConfigRepository()->shouldReceive('get')->with('notification::default_container')->andReturn('test');

        $this->n->errorInstant('test');
        $this->n->infoInstant('test');
        $this->n->warningInstant('test');
        $this->n->successInstant('test');
        $this->assertCount(4, $this->n->container()->all());

        $this->n->clearAll();
        $this->assertCount(0, $this->n->container()->all());
    }

    public function testClearMessagesWhenNoMessagesSet()
    {
        $this->n->getConfigRepository()->shouldReceive('get')->with('notification::default_container')->andReturn('test');

        $this->assertCount(0, $this->n->container()->all());

        $this->n->clearAll();
        $this->assertCount(0, $this->n->container()->all());
    }

    public function testGetAtPositionForADefaultContainer()
    {
        $this->n->getConfigRepository()->shouldReceive('get')->with('notification::default_container')->andReturn('test');

        $this->n->clearAll();

        $this->n->infoInstant('info')->atPosition(5);
        $this->assertCount(1, $this->n->container());
        $this->assertEquals('info', $this->n->getAtPosition(5)->getMessage());
    }

    public function testGetAliasedFromADefaultContainer()
    {
        $this->n->getConfigRepository()->shouldReceive('get')->with('notification::default_container')->andReturn('test');

        $this->n->clearAll();

        $this->n->infoInstant('info')->alias('a');
        $this->assertCount(1, $this->n->container());
        $this->assertEquals('info', $this->n->getAliased('a')->getMessage());
    }

    public function testGroupingForRendering()
    {
        $this->n->getConfigRepository()->shouldReceive('get')->with('notification::default_container')->andReturn('test');

        $this->n->clearAll();

        $this->n->infoInstant('info');
        $this->n->infoInstant('info2');
        $this->n->errorInstant('error');
        $this->n->warningInstant('warning');
        $this->n->successInstant('success');

        $this->assertCount(5, $this->n->container());
        $this->assertEquals('infoinfo2errorwarningsuccess', $this->n->showAll(':message'));
        $this->assertEquals('successwarning', $this->n->group('success', 'warning')->showAll(':message'));
        $this->assertEquals('success', $this->n->group('success')->showAll(':message'));
        $this->assertEquals('infoinfo2errorwarningsuccess', $this->n->showAll(':message'));
        $this->assertEquals('infoinfo2errorwarningsuccess', $this->n->group()->showAll(':message'));
        $this->assertEquals('infoinfo2errorwarningsuccess', $this->n->group('info', 'error', 'warning', 'success')->showAll(':message'));
    }
}