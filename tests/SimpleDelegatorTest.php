<?php

namespace ZloeSabo\SimpleDelegatorTest;

use ZloeSabo\SimpleDelegator\DelegateeInterface;
use ZloeSabo\SimpleDelegator\SimpleDelegator;

/**
 * @TODO always check calls from inside and outside
 * @TODO always check calls from static and instance methods
 * @TODO do we need to test cases when property exists on delegator?
 * @author Evgeny Soynov<saboteur@saboteur.me>
 */
class SimpleDelegatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var SimpleDelegator|\PHPUnit_Framework_MockObject_MockObject */
    private $subject;
    /** @var DelegateeInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $delegatee;

    protected function setUp()
    {
        $this->delegatee = $this->getMock('ZloeSabo\SimpleDelegator\DelegateeInterface');
        $this->subject = $this->getMockBuilder('ZloeSabo\SimpleDelegator\SimpleDelegator')->setMethods(['getDelegatee'])->getMockForTrait();
        $this->subject->method('getDelegatee')->willReturn($this->delegatee);
    }

    /**
     * @test
     */
    public function calculatesValidCaller()
    {
        $caller = $this->subject->getCaller();
        $this->assertEquals($this, $caller);

        $caller = $this->subject->getStaticCaller();
        $this->assertEquals($this, $caller);
    }

    /**
     * @test
     */
    public function forwardsNonExistentInstanceMethodsToDelegateeContext()
    {
        $methodName = sprintf('method%s', mt_rand(1, 999));
        $arguments = range(1, 10);
        shuffle($arguments);
        $expectedResult = 'abcde';
        $this->delegatee->expects($this->at(0))->method('call')->with($methodName, [$arguments])->willReturn($expectedResult);

        $result = $this->subject->$methodName($arguments);

        $this->assertEquals($expectedResult, $result);

        $this->subject = $this
            ->getMockBuilder('ZloeSabo\SimpleDelegatorTest\ExecuteEverythingOnCallerBehalfTarget')
            ->setMethods(['getDelegatee'])
            ->getMock()
        ;
        $this->subject->method('getDelegatee')->willReturn($this->delegatee);
        $this->delegatee->expects($this->at(0))->method('call')->with('firstFunction', []);
        $this->delegatee->expects($this->at(1))->method('call')->with('secondFunction', []);

        $this->subject->run();
    }

    /**
     * @test
     */
    public function executesExistingInstanceMethodsOnOwnBehalf()
    {
        $this->subject = $this->getMockBuilder('ZloeSabo\SimpleDelegatorTest\SampleSubject')->setMethods(['getDelegatee'])->getMock();
        $this->subject->method('getDelegatee')->willReturn($this->delegatee);

        $this->delegatee->expects($this->never())->method('existingPublicFunction');
        $this->delegatee->expects($this->never())->method('existingPrivateFunction');

        $args = ['ab', 'c'];
        $result = $this->subject->existingPublicFunction($args);
        $this->assertEquals($args, $result);
    }

    /**
     * @test
     */
    public function executesNonExistentStaticMethodsInCallerContext()
    {
        $this->subject = $this->getMockBuilder('ZloeSabo\SimpleDelegatorTest\StaticTestSubject')->setMethods(['getDelegatee'])->getMock();
        $this->subject->method('getDelegatee')->willReturn($this->delegatee);

        $expected = sprintf('result-%s', mt_rand(1, 999));
        StaticTestDelegatee::$result = $expected;
        $result = StaticTestDelegatee::callDelegator($this->subject);
        $this->assertEquals($expected, $result);

        $tested = new StaticTestDelegatee();
        $result = $tested->callDelegatorFromInstanceMethod($this->subject);
        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function canExecuteStaticMethodOfAnyVisibilityOnCallerClass()
    {
        $this->subject = $this->getMockBuilder('ZloeSabo\SimpleDelegatorTest\StaticTestSubject')->setMethods(['getDelegatee'])->getMock();

        $public = mt_rand(1, 999);
        $protected = mt_rand(1, 999);
        $private = mt_rand(1, 999);
        StaticTestDelegatee::setVisibilityTestValues($public, $protected, $private);

        $result = StaticTestDelegatee::testStaticAccessibliltyFromStaticMethod($this->subject);
        $this->assertEquals([$public, $protected, $private], $result);
    }

    /**
     * @test
     * @expectedException ZloeSabo\SimpleDelegator\NoMethodException
     */
    public function throwsExceptionWhenMethodNotFoundOnCaller()
    {
        StaticTestSubject::notExistingMethod();
    }

    /**
     * @test
     * @link https://bugs.php.net/bug.php?id=62330
     */
    public function staticCallsFromInstanceMethodShouldBeForwardedToDelegatee()
    {
        $this->subject = $this->getMockBuilder('ZloeSabo\SimpleDelegatorTest\StaticTestSubject')->setMethods(['getDelegatee'])->getMock();
        $this->subject->method('getDelegatee')->willReturn($this->delegatee);

        $public = mt_rand(1, 999);
        $protected = mt_rand(1, 999);
        $private = mt_rand(1, 999);
        StaticTestDelegatee::setVisibilityTestValues($public, $protected, $private);
        $this->delegatee->expects($this->at(0))->method('call')->with('somePublicFunction', [])->willReturn($public);
        $this->delegatee->expects($this->at(1))->method('call')->with('someProtectedFunction', [])->willReturn($protected);
        $this->delegatee->expects($this->at(2))->method('call')->with('somePrivateFunction', [])->willReturn($private);

        $result = StaticTestDelegatee::testStaticAccessibliltyFromInstanceMethod($this->subject);

        $this->assertEquals([$public, $protected, $private], $result);
    }

    /**
     * @test
     */
    public function searchesNonExistingPropertiesOnDelegatee()
    {
        $expected = mt_rand(1, 999);
        $propertyName = 'someProperty';
        $this->delegatee->expects($this->once())->method('get')->with($propertyName)->willReturn($expected);

        $result = $this->subject->$propertyName;

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function forwardsSetToDelegateeWhenNoPropertyExists()
    {
        $expected = mt_rand(1, 999);
        $propertyName = 'someProperty';
        $this->delegatee->expects($this->once())->method('set')->with($propertyName, $expected);

        $this->subject->$propertyName = $expected;
    }

    /**
     * @test
     */
    public function forwardsIssetCheckToDelegateeWhenNoPropertyExists()
    {
        $expected = mt_rand(1, 10) > 5;
        $propertyName = 'someProperty';
        $this->delegatee->expects($this->once())->method('propertyIsSet')->with($propertyName)->willReturn($expected);

        $result = isset($this->subject->$propertyName);

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function forwardsUnsetToDelegateeWhenNoPropertyExists()
    {
        $propertyName = 'someProperty';
        $this->delegatee->expects($this->once())->method('unsetProperty')->with($propertyName);

        unset($this->subject->$propertyName);
    }


    /**
     * This test ensures that caller will be saved to delegatee on first call("entry") and will be used afterwards.
     * So there will be no difference in delegating from inside delegate or from outside call
     * @test
     */
    public function createsDelegateeOnFirstCall()
    {
        $this->subject = $this
            ->getMockBuilder('ZloeSabo\SimpleDelegatorTest\ExecuteEverythingOnCallerBehalfTarget')
            ->setMethods(['getCaller'])
            ->getMock()
        ;

        $this->subject->expects($this->once())->method('getCaller')->willReturn($this->delegatee);

        $this->subject->run(); //Delegatee should be created after first function call
    }
}

/**
 * @internal
 */
class SampleSubject
{
    use SimpleDelegator;

    private function existingPrivateFunction() {}
    public function existingPublicFunction($args)
    {
        $this->existingPrivateFunction();
        //TODO check call from the inside

        return $args;
    }
}

/**
 * @internal
 */
class StaticTestDelegatee
{
    public static $result;

    public static $publicFunctionValue;
    protected static $protectedFunctionValue;
    private static $privateFunctionValue;


    public static function callDelegator($delegator)
    {
        return $delegator::run();
    }

    public static function getResult()
    {
        return self::$result;
    }

    public function callDelegatorFromInstanceMethod($delegator)
    {
        return $delegator::run();
    }

    public static function existingStaticFunction()
    {
        throw new \Exception('That shouldn\'t be called');
    }

    public static function setVisibilityTestValues($public, $protected, $private)
    {
        self::$publicFunctionValue = $public;
        self::$protectedFunctionValue  = $protected;
        self::$privateFunctionValue = $private;
    }

    /**
     * Access static methods of this class from static method of delegator
     * @param $delegator
     * @return mixed
     */
    public static function testStaticAccessibliltyFromStaticMethod($delegator)
    {
        return $delegator::testAccessToMethodsWithAnyVisibilityFromStaticMethod();
    }

    /**
     * Access static methods of this class from instance method of delegator
     * @param $delegator
     * @return mixed
     */
    public static function testStaticAccessibliltyFromInstanceMethod($delegator)
    {
        return $delegator->testAccessToMethodsWithAnyVisibilityFromInstanceMethod();
    }

    public static function somePublicFunction()
    {
        return self::$publicFunctionValue;
    }

    protected static function someProtectedFunction()
    {
        return self::$protectedFunctionValue;
    }

    protected static function somePrivateFunction()
    {
        return self::$privateFunctionValue;
    }
}

/**
 * @internal
 */
class StaticTestSubject
{
    use SimpleDelegator;

    public static function run()
    {
        self::existingStaticFunction();
        return self::getResult();
    }

    public static function existingStaticFunction()
    {

    }

    public static function testAccessToMethodsWithAnyVisibilityFromStaticMethod()
    {
        $publicResult = self::somePublicFunction();
        $protectedResult = self::someProtectedFunction();
        $privateResult = self::somePrivateFunction();

        return [$publicResult, $protectedResult, $privateResult];
    }

    //TODO calls __call instead of __callStatic because of https://bugs.php.net/bug.php?id=62330
    //So need to forward call to delegatee
    public function testAccessToMethodsWithAnyVisibilityFromInstanceMethod()
    {
        $publicResult = self::somePublicFunction();
        $protectedResult = self::someProtectedFunction();
        $privateResult = self::somePrivateFunction();

        return [$publicResult, $protectedResult, $privateResult];
    }
}

/**
 * @internal
 */
class ExecuteEverythingOnCallerBehalfTarget
{
    use SimpleDelegator;

    public function run()
    {
        $this->firstFunction();
        $this->secondFunction();
    }
}

