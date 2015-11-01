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
    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $logger;
    /** @var object */
    private $caller;

    protected function setUp()
    {
        $this->logger = $this->getMockBuilder('Psr\Log\LoggerInterface')->getMock();
        $this->caller = new CallerWithMethodsAndFunctions();
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

        $this->logger->expects($this->exactly(3))->method('debug');

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

        $this->logger->expects($this->exactly(3))->method('debug');

        $public = $this->subject->call('publicStatic', [$expectedPublic]);
        $protected = $this->subject->call('protectedStatic', [$expectedProtected]);
        $private = $this->subject->call('privateStatic', [$expectedPrivate]);

        $this->assertEquals($expectedPublic, $public);
        $this->assertEquals($expectedProtected, $protected);
        $this->assertEquals($expectedPrivate, $private);
    }

    /**
     * @test
     * @expectedException ZloeSabo\SimpleDelegator\NoMethodException
     */
    public function throwsExceptionWhenTriesToExecuteNonExistingMethod()
    {
        $this->logger->expects($this->never())->method($this->anything());
        $this->subject->call('someNonExistingMethod', []);
    }

    /**
     * @test
     */
    public function canAccessExisingPropertiesOfAnyVisibilityOnCaller()
    {
        $expectedPublic = mt_rand(1, 999);
        $expectedProtected = mt_rand(1, 999);
        $expectedPrivate = mt_rand(1, 999);
        $this->caller->setProperties($expectedPublic, $expectedProtected, $expectedPrivate);

        $this->logger->expects($this->exactly(3))->method('debug');

        $public = $this->subject->get('publicProperty');
        $protected = $this->subject->get('protectedProperty');
        $private = $this->subject->get('privateProperty');

        $this->assertEquals($expectedPublic, $public);
        $this->assertEquals($expectedProtected, $protected);
        $this->assertEquals($expectedPrivate, $private);
    }

    /**
     * @test
     * @expectedException ZloeSabo\SimpleDelegator\NoPropertyException
     */
    public function throwsExceptionWhenTriesToAccessNonExistingPropertyOnCaller()
    {
        $this->logger->expects($this->never())->method($this->anything());
        $this->subject->get('nonexistingProperty');
    }

    /**
     * @test
     */
    public function canSetExistingPropertyOnCaller()
    {
        $expectedPublic = mt_rand(1, 999);
        $expectedProtected = mt_rand(1, 999);
        $expectedPrivate = mt_rand(1, 999);

        $this->logger->expects($this->exactly(3))->method('debug');

        $this->subject->set('publicProperty', $expectedPublic);
        $this->subject->set('protectedProperty', $expectedProtected);
        $this->subject->set('privateProperty', $expectedPrivate);

        list($public, $protected, $private) = $this->caller->getProperties();

        $this->assertEquals($expectedPublic, $public);
        $this->assertEquals($expectedProtected, $protected);
        $this->assertEquals($expectedPrivate, $private);
    }

    /**
     * @test
     * @expectedException ZloeSabo\SimpleDelegator\NoPropertyException
     */
    public function throwsExceptionWhenTriesToSetNonExistingPropertyOnCaller()
    {
        $this->logger->expects($this->never())->method($this->anything());
        $this->subject->set('nonExistingProperty', mt_rand(1, 999));
    }


    /**
     * @test
     */
    public function canCheckIfPropertyExistsOnCaller()
    {
        $this->caller->setProperties(true, true, true);

        $this->logger->expects($this->exactly(4))->method('debug');

        $publicIsSet = $this->subject->propertyIsSet('publicProperty');
        $protectedIsSet = $this->subject->propertyIsSet('protectedProperty');
        $privateIsSet = $this->subject->propertyIsSet('privateProperty');
        $nonExistingIsSet = $this->subject->propertyIsSet('nonExistingProperty');

        $this->assertEquals(true, $publicIsSet);
        $this->assertEquals(true, $protectedIsSet);
        $this->assertEquals(true, $privateIsSet);
        $this->assertEquals(false, $nonExistingIsSet);
    }

    /**
     * @test
     */
    public function canUnsetExistingPropertyOnCaller()
    {
        isset($this->caller->publicProperty);

        $this->logger->expects($this->exactly(3))->method('debug');

        $this->subject->unsetProperty('publicProperty');
        $this->subject->unsetProperty('protectedProperty');
        $this->subject->unsetProperty('privateProperty');

        list($publicExists, $protectedExists, $privateExists) = $this->caller->getPropertiesExistence();

        $this->assertFalse($publicExists);
        $this->assertFalse($protectedExists);
        $this->assertFalse($privateExists);
    }

    /**
     * @test
     * @expectedException ZloeSabo\SimpleDelegator\NoPropertyException
     */
    public function throwsExceptionWhenTriesToUnsetNonExistingPropertyOnCaller()
    {
        $this->logger->expects($this->never())->method($this->anything());
        $this->subject->unsetProperty('nonExistingProperty');
    }
}

/**
 * @internal
 */
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

    public function getProperties()
    {
        return [$this->publicProperty, $this->protectedProperty, $this->privateProperty];
    }

    public function getPropertiesExistence()
    {
        return [isset($this->publicProperty), isset($this->protectedProperty), isset($this->privateProperty)];
    }
}