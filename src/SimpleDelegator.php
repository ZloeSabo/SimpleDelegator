<?php

namespace ZloeSabo\SimpleDelegator;

use Psr\Log\LoggerAwareTrait;

/**
 * @todo Won't return static properties of caller as of php 5.6
 * @todo Won't switch to caller context if called via call_user_func*
 * @todo class constants
 * @author Evgeny Soynov<saboteur@saboteur.me>
 */
trait SimpleDelegator
{
    use LoggerAwareTrait;

    private $delegatee;

    public function getCaller()
    {
        return static::getStaticCaller();
    }

    /**
     * @TODO investigate class name changes when calling 1. object 2. class because that changes inside of closure
     * @return mixed
     */
    public static function getStaticCaller()
    {
        $stacktrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 5);
        $currentClass = __CLASS__; //Do not edit it. otherwise tests will fail
        $stacktrace = array_filter($stacktrace, function($stackEntry) use ($currentClass) {
            return isset($stackEntry['class']) && $currentClass !== $stackEntry['class'] && __CLASS__ !== $stackEntry['class'];
        });
        $callerInfo = array_shift($stacktrace);
        $caller = isset($callerInfo['object']) ? //If there is no class, then we have been called from static function
            $callerInfo['object'] : $callerInfo['class']
        ;


        return $caller;
    }

    public function getDelegatee()
    {
        if(!$this->delegatee) {
            $caller = $this->getCaller();
            //TODO caller can be string when this called from static function
            $this->delegatee = new LoggableDelegatee($caller, $this->logger);
        }

        return $this->delegatee;
    }

    public function setDelegate(DelegateeInterface $delegatee)
    {
        $this->delegatee = $delegatee;
    }

    /**
     * Notice: also invoked when calling static method from instance method
     * Explained in https://bugs.php.net/bug.php?id=62330
     * @param string $method
     * @param array $args
     */
    public function __call($method, array $args = [])
    {
        return $this->getDelegatee()->call($method, $args);
    }

    public static function __callStatic($method, $args)
    {
        $caller = self::getStaticCaller();

        $reflectedClass = new \ReflectionClass($caller);
        if($reflectedClass->hasMethod($method)) {
            $method = $reflectedClass->getMethod($method);
            $closure = $method->getClosure(null);

            return call_user_func_array($closure, $args);
        }

        throw new NoMethodException(sprintf('Method %s does not exist on class %s', $method, $reflectedClass->getName()));
    }

    public function __get($name)
    {
        return $this->getDelegatee()->get($name);
    }

    public function __set($name, $args)
    {
        return $this->getDelegatee()->set($name, $args);
    }

    public function __isset($name)
    {
        return $this->getDelegatee()->propertyIsSet($name);
    }

    public function __unset($name)
    {
        return $this->getDelegatee()->unsetProperty($name);
    }
}