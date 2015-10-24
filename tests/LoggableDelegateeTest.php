<?php

namespace ZloeSabo\SimpleDelegator;

/**
 * @author Evgeny Soynov<saboteur@saboteur.me>
 */
class LoggableDelegateeTest extends \PHPUnit_Framework_TestCase
{
    /** @var SimpleDelegator|\PHPUnit_Framework_MockObject_MockObject */
    private $subject;

    protected function setUp()
    {
        $this->subject = $this->getMockBuilder('ZloeSabo\SimpleDelegator\SimpleDelegator')->getMockForTrait();
    }

    //Delegates to public functions
    //Delegates to protected/private functions
    //Delegates to public static functions
    //Delegates to protected/private functions
    //Delegates to public properties
    //Delegates to protected/private properties
    //Logs calls to delegate
}