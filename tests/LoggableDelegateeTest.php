<?php

namespace ZloeSabo\SimpleDelegator;

use Psr\Log\LoggerInterface;

/**
 * @author Evgeny Soynov<saboteur@saboteur.me>
 */
class LoggableDelegateeTest extends \PHPUnit_Framework_TestCase
{
    /** @var LoggableDelegatee */
    private $subject;
    /** @var LoggerInterface */
    private $logger;
    /** @var object */
    private $caller;

    protected function setUp()
    {
        $this->logger = $this->getMockBuilder('Psr\Log\LoggerInterface')->getMock();
        $this->caller = $this->getMockBuilder('ZloeSabo\SimpleDelegator\CallerWithMethodsAndFunctions')->setMethods(null)->getMock();
        $this->subject = new LoggableDelegatee($this->caller, $this->logger);
    }

    /**
     * @test
     */
    public function implementsDelegateeInterface()
    {
        $this->assertInstanceOf('ZloeSabo\SimpleDelegator\DelegateeInterface', $this->subject);
    }

    /**
     * @test
     */
    public function canExecuteInstanceMethodsOfAnyVisibilityOnCaller()
    {
        $expectedPublic = mt_rand(1, 999);
        $expectedProtected = mt_rand(1, 999);
        $expectedPrivate = mt_rand(1, 999);
        $this->caller->setProperties($expectedPublic, $expectedProtected, $expectedPrivate);

        $public = $this->subject->call('getPublicProperty', []);
        $protected = $this->subject->call('getProtectedProperty', []);
        $private = $this->subject->call('getPrivateProperty', []);

        $this->assertEquals($expectedPublic, $public);
        $this->assertEquals($expectedProtected, $protected);
        $this->assertEquals($expectedPrivate, $private);
    }

    /**
     * @test
     */
    public function canExecuteStaticMethodsOfAnyVisibilityOnCaller()
    {
        $expectedPublic = mt_rand(1, 999);
        $expectedProtected = mt_rand(1, 999);
        $expectedPrivate = mt_rand(1, 999);

        $public = $this->subject->call('publicStatic', [$expectedPublic]);
        $protected = $this->subject->call('protectedStatic', [$expectedProtected]);
        $private = $this->subject->call('privateStatic', [$expectedPrivate]);

        $this->assertEquals($expectedPublic, $public);
        $this->assertEquals($expectedProtected, $protected);
        $this->assertEquals($expectedPrivate, $private);
    }

    /**
     * @test
     */
    public function canAccessPropertiesOfAnyVisibilityOnCaller()
    {
        $expectedPublic = mt_rand(1, 999);
        $expectedProtected = mt_rand(1, 999);
        $expectedPrivate = mt_rand(1, 999);
        $this->caller->setProperties($expectedPublic, $expectedProtected, $expectedPrivate);

        $public = $this->subject->get('publicProperty');
        $protected = $this->subject->get('protectedProperty');
        $private = $this->subject->get('privateProperty');

        $this->assertEquals($expectedPublic, $public);
        $this->assertEquals($expectedProtected, $protected);
        $this->assertEquals($expectedPrivate, $private);
    }

    //Delegates to public functions
    //Delegates to protected/private functions
    //Delegates to public static functions
    //Delegates to protected/private functions
    //Delegates to public properties
    //Delegates to protected/private properties
    //Logs calls to delegate
}

class CallerWithMethodsAndFunctions
{
    public $publicProperty;
    protected $protectedProperty;
    private $privateProperty;

    public function getPublicProperty()
    {
        return $this->publicProperty;
    }

    protected function getProtectedProperty()
    {
        return $this->protectedProperty;
    }

    private function getPrivateProperty()
    {
        return $this->privateProperty;
    }

    public static function publicStatic($args)
    {
        return $args;
    }

    protected static function protectedStatic($args)
    {
        return $args;
    }

    private static function privateStatic($args)
    {
        return $args;
    }

    public function setProperties($public, $protected, $private)
    {
        $this->publicProperty = $public;
        $this->protectedProperty = $protected;
        $this->privateProperty = $private;
    }
}