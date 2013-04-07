<?php

use Mockery as m;

class NotificationBagTest extends PHPUnit_Framework_TestCase
{
    private $bag;

    public function tearDown()
    {
        m::close();
    }

    protected function setUp()
    {
        $session = m::mock('Illuminate\Session\Store');
        $config = m::mock('Illuminate\Config\Repository');

        $session->shouldReceive('get')
            ->once()
            ->andReturn('[{"type":"error","message":"test error","format":":message!","alias":null,"position":null},{"type":"warning","message":"test warning","format":":message...","alias":null,"position":null}]');

        $config->shouldReceive('get')->with('notification::default_format')->andReturn('<div class="alert alert-:type">:message</div>');
        $config->shouldReceive('get')->with('notification::default_formats')->andReturn(array('__' => array()));

        $this->bag = new \Krucas\Notification\NotificationsBag('test',$session, $config);
    }

    public function testConstructor()
    {
        $this->assertInstanceOf('Krucas\Notification\NotificationsBag', $this->bag);

        return $this->bag;
    }

    /**
     * @depends testConstructor
     */
    public function testIsSetDefaultFormatFromConfig(\Krucas\Notification\NotificationsBag $bag)
    {
        $this->assertEquals('<div class="alert alert-:type">:message</div>', $bag->getFormat());

        return $bag;
    }

    /**
     * @depends testIsSetDefaultFormatFromConfig
     */
    public function testMessagesIsLoadedFromFlash(\Krucas\Notification\NotificationsBag $bag)
    {
        $this->assertCount(2, $bag);
        $this->assertCount(2, $bag->all());
        $this->assertInstanceOf('Krucas\Notification\Collection', $bag->all());
        $this->assertInstanceOf('Krucas\Notification\Message', $bag->all()->first());
        $this->assertEquals('test error', $bag->all()->first()->getMessage());
        $this->assertEquals('error', $bag->all()->first()->getType());
        $this->assertEquals(':message!', $bag->all()->first()->getFormat());

        return $bag;
    }

    /**
     * @depends testMessagesIsLoadedFromFlash
     */
    public function testAddFlashableSuccessMessageWithCustomFormat(\Krucas\Notification\NotificationsBag $bag)
    {
        $bag->getSessionStore()
            ->shouldReceive('flash')
            ->once()
            ->with(
                'notifications_test',
                '[{"message":"all ok","format":"custom: :message","type":"success","flashable":true,"alias":null,"position":null}]'
            );

        $bag->success('all ok', 'custom: :message');

        $this->assertCount(3, $bag);
        $this->assertCount(3, $bag->all());
        $this->assertInstanceOf('Krucas\Notification\Collection', $bag->all());
        $this->assertInstanceOf('Krucas\Notification\Message', $bag->get('success')->first());
        $this->assertEquals('all ok', $bag->get('success')->first()->getMessage());
        $this->assertEquals('success', $bag->get('success')->first()->getType());
        $this->assertEquals('custom: :message', $bag->get('success')->first()->getFormat());
        $this->assertTrue($bag->get('success')->first()->isFlashable());

        return $bag;
    }

    /**
     * @depends testAddFlashableSuccessMessageWithCustomFormat
     */
    public function testAddFlashableWarningMessage(\Krucas\Notification\NotificationsBag $bag)
    {
        $bag->getSessionStore()->shouldReceive('flash')->once();

        $bag->warning('second message');

        $this->assertCount(3, $bag);
        $this->assertCount(4, $bag->all());
        $this->assertInstanceOf('Krucas\Notification\Collection', $bag->all());
        $this->assertInstanceOf('Krucas\Notification\Message', $bag->get('warning')->first());
        $this->assertEquals('test warning', $bag->get('warning')->first()->getMessage());
        $this->assertEquals('warning', $bag->get('warning')->first()->getType());
        $this->assertEquals(':message...', $bag->get('warning')->first()->getFormat());
        $this->assertFalse($bag->get('warning')->first()->isFlashable());

        return $bag;
    }

    /**
     * @depends testAddFlashableWarningMessage
     */
    public function testAddFlashableInfoMessage(\Krucas\Notification\NotificationsBag $bag)
    {
        $bag->getSessionStore()->shouldReceive('flash')->once();

        $bag->info('info m');

        $this->assertCount(4, $bag);
        $this->assertCount(5, $bag->all());
        $this->assertInstanceOf('Krucas\Notification\Collection', $bag->all());
        $this->assertInstanceOf('Krucas\Notification\Message', $bag->get('info')->first());
        $this->assertEquals('info m', $bag->get('info')->first()->getMessage());
        $this->assertEquals('info', $bag->get('info')->first()->getType());
        $this->assertEquals('<div class="alert alert-:type">:message</div>', $bag->get('info')->first()->getFormat());
        $this->assertTrue($bag->get('info')->first()->isFlashable());

        return $bag;
    }

    /**
     * @depends testAddFlashableInfoMessage
     */
    public function testAddFlashableErrorMessage(\Krucas\Notification\NotificationsBag $bag)
    {
        $bag->getSessionStore()->shouldReceive('flash')->once();

        $bag->error('e m');

        $this->assertCount(4, $bag);
        $this->assertCount(6, $bag->all());
        $this->assertInstanceOf('Krucas\Notification\Collection', $bag->all());
        $this->assertInstanceOf('Krucas\Notification\Message', $bag->get('error')->first());
        $this->assertEquals('test error', $bag->get('error')->first()->getMessage());
        $this->assertEquals('error', $bag->get('error')->first()->getType());
        $this->assertEquals(':message!', $bag->get('error')->first()->getFormat());
        $this->assertFalse($bag->get('error')->first()->isFlashable());

        return $bag;
    }

    /**
     * @depends testAddFlashableErrorMessage
     */
    public function testAddInstantSuccessMessage(\Krucas\Notification\NotificationsBag $bag)
    {
        $bag->successInstant('s m');

        $this->assertCount(4, $bag);
        $this->assertCount(7, $bag->all());
        $this->assertInstanceOf('Krucas\Notification\Collection', $bag->all());
        $this->assertInstanceOf('Krucas\Notification\Message', $bag->get('success')[1]);
        $this->assertEquals('s m', $bag->get('success')[1]->getMessage());
        $this->assertEquals('success', $bag->get('success')[1]->getType());
        $this->assertEquals('<div class="alert alert-:type">:message</div>', $bag->get('success')[1]->getFormat());
        $this->assertFalse($bag->get('success')[1]->isFlashable());

        return $bag;
    }

    /**
     * @depends testAddInstantSuccessMessage
     */
    public function testAddInstantInfoMessage(\Krucas\Notification\NotificationsBag $bag)
    {
        $bag->infoInstant('i m');

        $this->assertCount(4, $bag);
        $this->assertCount(8, $bag->all());
        $this->assertInstanceOf('Krucas\Notification\Collection', $bag->all());
        $this->assertInstanceOf('Krucas\Notification\Message', $bag->get('info')[1]);
        $this->assertEquals('i m', $bag->get('info')[1]->getMessage());
        $this->assertEquals('info', $bag->get('info')[1]->getType());
        $this->assertEquals('<div class="alert alert-:type">:message</div>', $bag->get('info')[1]->getFormat());
        $this->assertFalse($bag->get('info')[1]->isFlashable());

        return $bag;
    }

    /**
     * @depends testAddInstantInfoMessage
     */
    public function testAddInstantWarningMessage(\Krucas\Notification\NotificationsBag $bag)
    {
        $bag->warningInstant('w m');

        $this->assertCount(4, $bag);
        $this->assertCount(9, $bag->all());
        $this->assertInstanceOf('Krucas\Notification\Collection', $bag->all());
        $this->assertInstanceOf('Krucas\Notification\Message', $bag->get('warning')[2]);
        $this->assertEquals('w m', $bag->get('warning')[2]->getMessage());
        $this->assertEquals('warning', $bag->get('warning')[2]->getType());
        $this->assertEquals('<div class="alert alert-:type">:message</div>', $bag->get('warning')[2]->getFormat());
        $this->assertFalse($bag->get('warning')[2]->isFlashable());

        return $bag;
    }

    /**
     * @depends testAddInstantWarningMessage
     */
    public function testAddInstantErrorMessage(\Krucas\Notification\NotificationsBag $bag)
    {
        $bag->errorInstant('e m');

        $this->assertCount(4, $bag);
        $this->assertCount(10, $bag->all());
        $this->assertInstanceOf('Krucas\Notification\Collection', $bag->all());
        $this->assertInstanceOf('Krucas\Notification\Message', $bag->get('error')[2]);
        $this->assertEquals('e m', $bag->get('error')[2]->getMessage());
        $this->assertEquals('error', $bag->get('error')[2]->getType());
        $this->assertEquals('<div class="alert alert-:type">:message</div>', $bag->get('error')[2]->getFormat());
        $this->assertFalse($bag->get('error')[2]->isFlashable());

        return $bag;
    }

    /**
     * @depends testAddInstantErrorMessage
     */
    public function testHowManyContainersCreated(\Krucas\Notification\NotificationsBag $bag)
    {
        $this->assertCount(4, $bag);

        return $bag;
    }

    /**
     * @depends testHowManyContainersCreated
     */
    public function testHowManyMessagesAdded(\Krucas\Notification\NotificationsBag $bag)
    {
        $this->assertCount(10, $bag->all());

        return $bag;
    }

    /**
     * @depends testHowManyMessagesAdded
     */
    public function testGetErrorMessageContainer(\Krucas\Notification\NotificationsBag $bag)
    {
        $this->assertCount(3, $bag->get('error'));
        $this->assertInstanceOf('Krucas\Notification\Collection', $bag->get('error'));

        return $bag;
    }

    /**
     * @depends testGetErrorMessageContainer
     */
    public function testGetFirstMessageFromContainer(\Krucas\Notification\NotificationsBag $bag)
    {
        $this->assertInstanceOf('Krucas\Notification\Message', $bag->get('error')->first());
        $this->assertEquals('test error', $bag->get('error')->first()->getMessage());
        $this->assertEquals('error', $bag->get('error')->first()->getType());
        $this->assertEquals(':message!', $bag->get('error')->first()->getFormat());

        return $bag;
    }

    /**
     * @depends testGetFirstMessageFromContainer
     */
    public function testOverrideMessageFormat(\Krucas\Notification\NotificationsBag $bag)
    {
        $this->assertEquals('<div class="alert alert-:type">:message</div>', $bag->getFormat());
        $bag->setFormat(':message');
        $this->assertEquals(':message', $bag->getFormat());
        $bag->setFormat(':message!', 'error');
        $this->assertEquals(':message!', $bag->getFormat('error'));
        $this->assertEquals(':message', $bag->getFormat());

        return $bag;
    }

    /**
     * @depends testOverrideMessageFormat
     */
    public function testToArray(\Krucas\Notification\NotificationsBag $bag)
    {
        $this->assertEquals(array(
            'collections'   => array(
                'error'     => array(
                    array(
                        'message'   => 'test error',
                        'type'      => 'error',
                        'format'    => ':message!',
                        'flashable' => false,
                        'alias'     => null,
                        'position'  => null
                    ),
                    array(
                        'message'   => 'e m',
                        'type'      => 'error',
                        'format'    => '<div class="alert alert-:type">:message</div>',
                        'flashable' => true,
                        'alias'     => null,
                        'position'  => null
                    ),
                    array(
                        'message'   => 'e m',
                        'type'      => 'error',
                        'format'    => '<div class="alert alert-:type">:message</div>',
                        'flashable' => false,
                        'alias'     => null,
                        'position'  => null
                    )
                ),
                'success'   => array(
                    array(
                        'message'   => 'all ok',
                        'type'      => 'success',
                        'format'    => 'custom: :message',
                        'flashable' => true,
                        'alias'     => null,
                        'position'  => null
                    ),
                    array(
                        'message'   => 's m',
                        'type'      => 'success',
                        'format'    => '<div class="alert alert-:type">:message</div>',
                        'flashable' => false,
                        'alias'     => null,
                        'position'  => null
                    )
                ),
                'warning'   => array(
                    array(
                        'message'   => 'test warning',
                        'type'      => 'warning',
                        'format'    => ':message...',
                        'flashable' => false,
                        'alias'     => null,
                        'position'  => null
                    ),
                    array(
                        'message'   => 'second message',
                        'type'      => 'warning',
                        'format'    => '<div class="alert alert-:type">:message</div>',
                        'flashable' => true,
                        'alias'     => null,
                        'position'  => null
                    ),
                    array(
                        'message'   => 'w m',
                        'type'      => 'warning',
                        'format'    => '<div class="alert alert-:type">:message</div>',
                        'flashable' => false,
                        'alias'     => null,
                        'position'  => null
                    )
                ),
                'info'      => array(
                    array(
                        'message'   => 'info m',
                        'type'      => 'info',
                        'format'    => '<div class="alert alert-:type">:message</div>',
                        'flashable' => true,
                        'alias'     => null,
                        'position'  => null
                    ),
                    array(
                        'message'   => 'i m',
                        'type'      => 'info',
                        'format'    => '<div class="alert alert-:type">:message</div>',
                        'flashable' => false,
                        'alias'     => null,
                        'position'  => null
                    )
                )
            ),
            'container'     => 'test',
            'format'        => ':message'
        ), $bag->toArray());


        return $bag;
    }

    /**
     * @depends testToArray
     */
    public function testToJson(\Krucas\Notification\NotificationsBag $bag)
    {
        $this->assertContains('"container":"test"', $bag->toJson());

        return $bag;
    }

    /**
     * @depends testToJson
     */
    public function testShowWarningContainer(\Krucas\Notification\NotificationsBag $bag)
    {
        $this->assertContains('<div class="alert alert-warning">w m</div>', $bag->show('warning'));

        return $bag;
    }

    /**
     * @depends testShowWarningContainer
     */
    public function testShowAllContainers(\Krucas\Notification\NotificationsBag $bag)
    {
        $this->assertContains('<div class="alert alert-warning">w m</div>', $bag->show());

        return $bag;
    }

    /**
     * @depends testShowAllContainers
     */
    public function testShowAllContainersWithACustomFormat(\Krucas\Notification\NotificationsBag $bag)
    {
        $this->assertContains('w m', $bag->show(null, ':message'));

        return $bag;
    }

    /**
     * @depends testShowAllContainersWithACustomFormat
     */
    public function testToString(\Krucas\Notification\NotificationsBag $bag)
    {
        $this->assertContains('info m', (string) $bag);

        return $bag;
    }

    public function testAddingMessageArray()
    {
        $this->bag->infoInstant(array(
            'first',
            'second'
        ));

        $this->assertCount(2, $this->bag->get('info'));
        $this->assertInstanceOf('Krucas\Notification\Message', $this->bag->get('info')->first());
        $this->assertEquals('first', $this->bag->get('info')->first()->getMessage());
        $this->assertEquals('info', $this->bag->get('info')->first()->getType());
        $this->assertEquals('<div class="alert alert-:type">:message</div>', $this->bag->get('info')->first()->getFormat());
        $this->assertFalse($this->bag->get('info')->first()->isFlashable());
    }

    public function testAddingMessageArrayWithCustomFormat()
    {
        $this->bag->infoInstant(array(
            array('first', ':message'),
            'second'
        ));

        $this->assertCount(2, $this->bag->get('info'));
        $this->assertInstanceOf('Krucas\Notification\Message', $this->bag->get('info')->first());
        $this->assertEquals('first', $this->bag->get('info')->first()->getMessage());
        $this->assertEquals('info', $this->bag->get('info')->first()->getType());
        $this->assertEquals(':message', $this->bag->get('info')->first()->getFormat());
        $this->assertFalse($this->bag->get('info')->first()->isFlashable());

        $this->assertInstanceOf('Krucas\Notification\Message', $this->bag->get('info')[1]);
        $this->assertEquals('second', $this->bag->get('info')[1]->getMessage());
        $this->assertEquals('info', $this->bag->get('info')[1]->getType());
        $this->assertEquals('<div class="alert alert-:type">:message</div>', $this->bag->get('info')[1]->getFormat());
        $this->assertFalse($this->bag->get('info')[1]->isFlashable());
    }

    public function testSetCustomFormatAndDisplayAMessage()
    {
        $this->bag->setFormat('no format');

        $this->bag->infoInstant(array(
            array('first', ':message'),
            'second'
        ));

        $this->assertEquals('no format', $this->bag->getFormat());

        $this->assertCount(2, $this->bag->get('info'));
        $this->assertInstanceOf('Krucas\Notification\Message', $this->bag->get('info')->first());
        $this->assertEquals('first', $this->bag->get('info')->first()->getMessage());
        $this->assertEquals('info', $this->bag->get('info')->first()->getType());
        $this->assertEquals(':message', $this->bag->get('info')->first()->getFormat());
        $this->assertFalse($this->bag->get('info')->first()->isFlashable());

        $this->assertInstanceOf('Krucas\Notification\Message', $this->bag->get('info')[1]);
        $this->assertEquals('second', $this->bag->get('info')[1]->getMessage());
        $this->assertEquals('info', $this->bag->get('info')[1]->getType());
        $this->assertEquals('no format', $this->bag->get('info')[1]->getFormat());
        $this->assertFalse($this->bag->get('info')[1]->isFlashable());

        $this->assertEquals('test error!test warning...firstno format', $this->bag->show());
    }

    public function testClearSuccessMessages()
    {
        $this->bag->successInstant('test');
        $this->bag->successInstant('test2');

        $this->assertCount(2, $this->bag->get('success'));

        $this->bag->clearSuccess();

        $this->assertCount(0, $this->bag->get('success'));
    }

    public function testClearErrorMessages()
    {
        $this->bag->errorInstant('test');
        $this->bag->errorInstant('test2');

        $this->assertCount(3, $this->bag->get('error'));

        $this->bag->clearError();

        $this->assertCount(0, $this->bag->get('error'));
    }

    public function testClearInfoMessages()
    {
        $this->bag->infoInstant('test');
        $this->bag->infoInstant('test2');

        $this->assertCount(2, $this->bag->get('info'));

        $this->bag->clearInfo();

        $this->assertCount(0, $this->bag->get('info'));
    }

    public function testClearWarningMessages()
    {
        $this->bag->warningInstant('test');
        $this->bag->warningInstant('test2');

        $this->assertCount(3, $this->bag->get('warning'));

        $this->bag->clearWarning();

        $this->assertCount(0, $this->bag->get('warning'));
    }

    public function testClearAllMessages()
    {
        $this->bag->warningInstant('test');
        $this->bag->errorInstant('test');
        $this->bag->successInstant('test');
        $this->bag->infoInstant('test');

        $this->assertCount(6, $this->bag->all());

        $this->bag->clearAll();

        $this->assertCount(0, $this->bag->all());
    }

    public function testClearMethodsWhenBagIsEmpty()
    {
        $this->bag->clear();
        $this->assertCount(0, $this->bag->all());

        $this->bag->clear();
        $this->assertCount(0, $this->bag->all());

        $this->bag->clear('success');
        $this->assertCount(0, $this->bag->get('success'));
    }

    public function testSetMessageAtPosition()
    {
        $this->bag->clear();

        $this->bag->infoInstant('info')->atPosition(5);
        $this->assertCount(1, $this->bag->all());
        $this->assertEquals('info', $this->bag->get('info')->getAtPosition(5)->getMessage());
    }

    public function testSetTwoMessagesAtSamePosition()
    {
        $this->bag->clear();

        $this->bag->infoInstant('info')->atPosition(5);
        $this->bag->infoInstant('info2')->atPosition(5);
        $this->assertCount(2, $this->bag->all());
        $this->assertEquals('info2', $this->bag->get('info')->getAtPosition(5)->getMessage());
        $this->assertEquals('info', $this->bag->get('info')->getAtPosition(6)->getMessage());
    }

    public function testAddMessageAndThenSetOtherMessageToPosition()
    {
        $this->bag->clear();

        $this->bag->infoInstant('info');
        $this->bag->infoInstant('info2')->atPosition(5);
        $this->assertCount(2, $this->bag->all());
        $this->assertEquals('info2', $this->bag->get('info')->getAtPosition(5)->getMessage());
        $this->assertEquals('info', $this->bag->get('info')->getAtPosition(0)->getMessage());
    }

    public function testSetMessageAtPositionAndThenAdd()
    {
        $this->bag->clear();

        $this->bag->infoInstant('info2')->atPosition(5);
        $this->bag->infoInstant('info');
        $this->assertCount(2, $this->bag->all());
        $this->assertEquals('info2', $this->bag->get('info')->getAtPosition(5)->getMessage());
        $this->assertEquals('info', $this->bag->get('info')->getAtPosition(0)->getMessage());
    }


    public function testSettingAliasAfterMessageWasAdded()
    {
        $this->bag->clear();

        $this->bag->infoInstant('info')->alias('f');
        $this->assertEquals('f', $this->bag->get('info')->first()->getAlias());

        $this->bag->infoInstant('info2');
        $this->assertEquals('f', $this->bag->get('info')->first()->getAlias());
        $this->assertEquals('info2', $this->bag->get('info')->getAtPosition(1)->getMessage());
    }

    public function testSettingSameAliasForTwoMessages()
    {
        $this->bag->clear();

        $this->bag->infoInstant('info1');
        $this->bag->infoInstant('info')->alias('f');
        $this->assertCount(2, $this->bag->get('info'));
        $this->assertEquals('f', $this->bag->get('info')->getAtPosition(1)->getAlias());

        $this->bag->infoInstant('info2')->alias('f');
        $this->assertCount(2, $this->bag->get('info'));
        $this->assertEquals('info1', $this->bag->get('info')->getAtPosition(0)->getMessage());
        $this->assertEquals('f', $this->bag->get('info')->getAtPosition(1)->getAlias());
        $this->assertEquals('info2', $this->bag->get('info')->getAtPosition(1)->getMessage());
    }

    public function testAddingAssocMessageArray()
    {
        $this->bag->clear();

        $this->bag->infoInstant(array(
            array('message' => 'm', 'format' => 'f', 'alias' => 'a'),
            'second',
            array('message' => 'm2', 'alias' => 'a2')
        ), 'default');

        $this->assertCount(3, $this->bag->get('info'));
        $this->assertInstanceOf('Krucas\Notification\Message', $this->bag->get('info')->first());
        $this->assertEquals('m', $this->bag->get('info')->first()->getMessage());
        $this->assertEquals('info', $this->bag->get('info')->first()->getType());
        $this->assertEquals('f', $this->bag->get('info')->first()->getFormat());
        $this->assertFalse($this->bag->get('info')->first()->isFlashable());
        $this->assertEquals('a', $this->bag->get('info')->first()->getAlias());

        $this->assertInstanceOf('Krucas\Notification\Message', $this->bag->get('info')[1]);
        $this->assertEquals('second', $this->bag->get('info')->getAtPosition(1)->getMessage());
        $this->assertEquals('info', $this->bag->get('info')->getAtPosition(1)->getType());
        $this->assertEquals('default', $this->bag->get('info')->getAtPosition(1)->getFormat());
        $this->assertFalse($this->bag->get('info')->getAtPosition(1)->isFlashable());
        $this->assertNull($this->bag->get('info')->getAtPosition(1)->getAlias());

        $this->assertInstanceOf('Krucas\Notification\Message', $this->bag->get('info')->getAtPosition(2));
        $this->assertEquals('m2', $this->bag->get('info')->getAtPosition(2)->getMessage());
        $this->assertEquals('info', $this->bag->get('info')->getAtPosition(2)->getType());
        $this->assertEquals('default', $this->bag->get('info')->getAtPosition(2)->getFormat());
        $this->assertFalse($this->bag->get('info')->getAtPosition(2)->isFlashable());
        $this->assertEquals('a2', $this->bag->get('info')->getAtPosition(2)->getAlias());
    }

    public function testAddingAssocMessageArrayWithSameAlias()
    {
        $this->bag->infoInstant(array(
            array('message' => 'm', 'format' => 'f', 'alias' => 'a'),
            'second',
            array('message' => 'm2', 'alias' => 'a')
        ), 'default');

        $this->assertCount(2, $this->bag->get('info'));
        $this->assertInstanceOf('Krucas\Notification\Message', $this->bag->get('info')->first());
        $this->assertEquals('m2', $this->bag->get('info')->first()->getMessage());
        $this->assertEquals('info', $this->bag->get('info')->first()->getType());
        $this->assertEquals('default', $this->bag->get('info')->first()->getFormat());
        $this->assertFalse($this->bag->get('info')->first()->isFlashable());
        $this->assertEquals('a', $this->bag->get('info')->first()->getAlias());

        $this->assertInstanceOf('Krucas\Notification\Message', $this->bag->get('info')->getAtPosition(1));
        $this->assertEquals('second', $this->bag->get('info')->getAtPosition(1)->getMessage());
        $this->assertEquals('info', $this->bag->get('info')->getAtPosition(1)->getType());
        $this->assertEquals('default', $this->bag->get('info')->getAtPosition(1)->getFormat());
        $this->assertFalse($this->bag->get('info')->getAtPosition(1)->isFlashable());
        $this->assertNull($this->bag->get('info')->getAtPosition(1)->getAlias());
    }

    public function testAddMessageWithAliasAndPosition()
    {
        $this->bag->clear();

        $this->bag->infoInstant('test')->alias('f')->atPosition(5);

        $this->assertCount(1, $this->bag->get('info'));
        $this->assertEquals('f', $this->bag->get('info')->getAtPosition(5)->getAlias());
    }

    public function testAddMessagesWithSameAliasAndDifferentPosition()
    {
        $this->bag->clear();

        $this->bag->infoInstant('test')->alias('f')->atPosition(5);
        $this->bag->infoInstant('test1')->alias('f')->atPosition(2);

        $this->assertCount(1, $this->bag->get('info'));
        $this->assertEquals('f', $this->bag->get('info')->getAtPosition(2)->getAlias());
        $this->assertEquals('test1', $this->bag->get('info')->getAtPosition(2)->getMessage());

        $this->bag->clear();

        $this->bag->infoInstant('test')->atPosition(5)->alias('f');
        $this->bag->infoInstant('test1')->atPosition(2)->alias('f');

        $this->assertCount(1, $this->bag->get('info'));
        $this->assertEquals('f', $this->bag->get('info')->getAtPosition(2)->getAlias());
        $this->assertEquals('test1', $this->bag->get('info')->getAtPosition(2)->getMessage());

        $this->bag->clear();

        $this->bag->infoInstant('test')->atPosition(10)->alias('f');
        $this->bag->infoInstant('test1')->atPosition(12)->alias('f');

        $this->assertCount(1, $this->bag->get('info'));
        $this->assertEquals('f', $this->bag->get('info')->getAtPosition(12)->getAlias());
        $this->assertEquals('test1', $this->bag->get('info')->getAtPosition(12)->getMessage());
    }

    public function testAddArrayOfMessagesWithPositions()
    {
        $this->bag->infoInstant(array(
            array('message' => 'm', 'position' => 2),
            'second',
            array('message' => 'm2', 'position' => 5)
        ));

        $this->assertCount(3, $this->bag->get('info'));
        $this->assertEquals('m', $this->bag->get('info')->getAtPosition(2)->getMessage());
        $this->assertEquals('second', $this->bag->get('info')->getAtPosition(0)->getMessage());
        $this->assertEquals('m2', $this->bag->get('info')->getAtPosition(5)->getMessage());
    }
}